<?php
/**
 * SecurityScope.php - Central Data Isolation & Role-Based Scope Manager
 * Compatible with PHP 5.6+ and MySQL 5.6+
 *
 * Enforces strict data isolation for Salesperson, Director, and Admin/Global scopes
 * across Customer, Sales, and Commission modules.
 */

class SecurityScope {
    private static $initialized = false;
    private static $currentUser = null;

    /**
     * Auto-ensure database columns exist in tbluser and tblcustomer
     */
    public static function ensureTables() {
        if (self::$initialized) {
            return;
        }
        self::$initialized = true;

        // Auto-run schema migrations
        self::runSql("ALTER TABLE tbluser ADD COLUMN OptionCustomerScope TINYINT(1) NOT NULL DEFAULT 0 AFTER OptionCustomerDelete");
        self::runSql("ALTER TABLE tbluser ADD COLUMN OptionSalesScope TINYINT(1) NOT NULL DEFAULT 0 AFTER OptionSalesDelete");
        self::runSql("ALTER TABLE tbluser ADD COLUMN OptionCommissionScope TINYINT(1) NOT NULL DEFAULT 0 AFTER OptionCommissionReports");
        self::runSql("ALTER TABLE tblcustomer ADD COLUMN CreatedByUserID INT(11) NOT NULL DEFAULT 0 AFTER CustomerID");
        self::runSql("CREATE INDEX idx_tblcustomer_createdby ON tblcustomer(CreatedByUserID)");

        // Ensure Admin user has full commission permissions by default
        self::runSql("UPDATE tbluser SET OptionCommission = 1, OptionCommissionView = 1, OptionCommissionRules = 1, OptionCommissionPay = 1, OptionCommissionReports = 1, OptionCommissionScope = 1 WHERE UserID = 1 OR LOWER(user_type) = 'admin'");
    }

    /**
     * Universal SQL execution helper (supports mysql_*, mysqli_*, and globals)
     */
    public static function runSql($sql) {
        if (function_exists('mysql_query')) {
            return @mysql_query($sql);
        }
        global $conn, $link, $db, $mysqli;
        $connection = null;
        if (!empty($conn) && is_object($conn)) $connection = $conn;
        elseif (!empty($link) && is_object($link)) $connection = $link;
        elseif (!empty($db) && is_object($db)) $connection = $db;
        elseif (!empty($mysqli) && is_object($mysqli)) $connection = $mysqli;

        if ($connection && function_exists('mysqli_query')) {
            return @mysqli_query($connection, $sql);
        }
        return false;
    }

    /**
     * Get currently logged-in user row
     */
    public static function getCurrentUser() {
        if (self::$currentUser !== null) {
            return self::$currentUser;
        }
        $uid = isset($_SESSION['UserID']) ? intval($_SESSION['UserID']) : 0;
        if ($uid <= 0) {
            self::$currentUser = array();
            return self::$currentUser;
        }

        // Fetch fresh from tbluser to ensure scope settings take effect immediately
        if (function_exists('SQL_Select')) {
            $userRows = SQL_Select("User", "UserID = " . $uid, "", true);
            if (is_array($userRows) && !empty($userRows)) {
                self::$currentUser = $userRows;
                return self::$currentUser;
            }
        }
        self::$currentUser = isset($_SESSION) ? $_SESSION : array();
        return self::$currentUser;
    }

    /**
     * Check if user is SuperAdmin
     */
    public static function isAdmin() {
        $uid = isset($_SESSION['UserID']) ? intval($_SESSION['UserID']) : 0;
        if ($uid == 1) return true;
        $user = self::getCurrentUser();
        $uType = isset($user['user_type']) ? strtolower(trim($user['user_type'])) : '';
        return ($uType === 'admin');
    }

    /**
     * Get user type (salesperson, director, founder, admin, general)
     */
    public static function getUserType() {
        if (self::isAdmin()) return 'admin';
        $user = self::getCurrentUser();
        return isset($user['user_type']) ? strtolower(trim($user['user_type'])) : 'general';
    }

    /**
     * Get all salesperson UserIDs under a Director
     */
    public static function getTeamUserIDs($directorUserID) {
        $directorUserID = intval($directorUserID);
        if ($directorUserID <= 0) return array();

        $ids = array();
        if (function_exists('SQL_Select')) {
            $rows = SQL_Select("User", "referred_by = " . $directorUserID . " AND UserIsActive = 1", "", false);
            if (is_array($rows)) {
                foreach ($rows as $r) {
                    if (isset($r['UserID'])) {
                        $ids[] = intval($r['UserID']);
                    }
                }
                return $ids;
            }
        }

        $q = self::runSql("SELECT UserID FROM tbluser WHERE referred_by = " . $directorUserID . " AND UserIsActive = 1");
        if ($q) {
            if (function_exists('mysql_fetch_assoc')) {
                while ($r = @mysql_fetch_assoc($q)) {
                    $ids[] = intval($r['UserID']);
                }
            } elseif (function_exists('mysqli_fetch_assoc')) {
                while ($r = @mysqli_fetch_assoc($q)) {
                    $ids[] = intval($r['UserID']);
                }
            }
        }
        return $ids;
    }

    /**
     * Secure WHERE clause for Customer list
     *
     * Rules:
     * - Admin or OptionCustomerScope == 1: All customers
     * - Salesperson: Only their customers (created by them OR sold by them)
     * - Director: Their customers + team salespersons' customers
     */
    public static function getCustomerWhereCondition($tableAlias = '') {
        self::ensureTables();
        if (self::isAdmin()) {
            return '1 = 1';
        }

        $user = self::getCurrentUser();
        $uid = isset($_SESSION['UserID']) ? intval($_SESSION['UserID']) : 0;
        if ($uid <= 0) return '1 = 0';

        // Check explicit global scope in settings
        $isGlobal = isset($user['OptionCustomerScope']) ? intval($user['OptionCustomerScope']) : 0;
        if ($isGlobal == 1) {
            return '1 = 1';
        }

        $uType = self::getUserType();
        $prefix = $tableAlias !== '' ? rtrim($tableAlias, '.') . '.' : '';

        if ($uType === 'director') {
            $teamIDs = self::getTeamUserIDs($uid);
            $allIDs = array_merge(array($uid), $teamIDs);
            $idList = implode(',', $allIDs);

            $salerNames = array();
            foreach ($allIDs as $id) {
                $salerNames[] = "'user_" . intval($id) . "'";
            }

            $cond = "({$prefix}CustomerID IN (
                SELECT CustomerID FROM tblsales 
                WHERE DirectorUserID IN ({$idList}) 
                   OR SalespersonUserID IN ({$idList}) 
                   OR SellerID IN ({$idList})
                   OR SalerNameID IN (" . implode(',', $salerNames) . ")
            ) OR ({$prefix}CreatedByUserID IN ({$idList}) AND {$prefix}CreatedByUserID > 0))";
            return $cond;
        }

        // Salesperson or any other user with Own-only scope
        $cond = "({$prefix}CustomerID IN (
            SELECT CustomerID FROM tblsales 
            WHERE SalespersonUserID = {$uid} 
               OR SellerID = {$uid} 
               OR SalerNameID = 'user_{$uid}'
        ) OR ({$prefix}CreatedByUserID = {$uid} AND {$prefix}CreatedByUserID > 0))";
        return $cond;
    }

    /**
     * Secure WHERE clause for Sales list
     *
     * Rules:
     * - Admin or OptionSalesScope == 1: All sales
     * - Salesperson: Only sales where they are the seller
     * - Director: Direct sales of director + sales of team salespersons
     */
    public static function getSalesWhereCondition($tableAlias = '') {
        self::ensureTables();
        if (self::isAdmin()) {
            return '1 = 1';
        }

        $user = self::getCurrentUser();
        $uid = isset($_SESSION['UserID']) ? intval($_SESSION['UserID']) : 0;
        if ($uid <= 0) return '1 = 0';

        $isGlobal = isset($user['OptionSalesScope']) ? intval($user['OptionSalesScope']) : 0;
        if ($isGlobal == 1) {
            return '1 = 1';
        }

        $uType = self::getUserType();
        $prefix = $tableAlias !== '' ? rtrim($tableAlias, '.') . '.' : '';

        if ($uType === 'director') {
            $teamIDs = self::getTeamUserIDs($uid);
            $allIDs = array_merge(array($uid), $teamIDs);
            $idList = implode(',', $allIDs);

            $salerNames = array();
            foreach ($allIDs as $id) {
                $salerNames[] = "'user_" . intval($id) . "'";
            }

            $cond = "({$prefix}DirectorUserID IN ({$idList}) 
                     OR {$prefix}SalespersonUserID IN ({$idList}) 
                     OR {$prefix}SellerID IN ({$idList})
                     OR {$prefix}SalerNameID IN (" . implode(',', $salerNames) . "))";
            return $cond;
        }

        // Salesperson or other Own-only user
        $cond = "({$prefix}SalespersonUserID = {$uid} 
                 OR {$prefix}SellerID = {$uid} 
                 OR {$prefix}SalerNameID = 'user_{$uid}')";
        return $cond;
    }

    /**
     * Secure WHERE clause for Commission ledger / reports
     *
     * Rules:
     * - Admin or OptionCommissionScope == 1: All commissions
     * - Salesperson / Director / Other: Strictly their own commission (UserID = loggedInUserID)
     */
    public static function getCommissionWhereCondition($tableAlias = '') {
        self::ensureTables();
        if (self::isAdmin()) {
            return '1 = 1';
        }

        $user = self::getCurrentUser();
        $uid = isset($_SESSION['UserID']) ? intval($_SESSION['UserID']) : 0;
        if ($uid <= 0) return '1 = 0';

        $isGlobal = isset($user['OptionCommissionScope']) ? intval($user['OptionCommissionScope']) : 0;
        if ($isGlobal == 1) {
            return '1 = 1';
        }

        $prefix = $tableAlias !== '' ? rtrim($tableAlias, '.') . '.' : '';
        return "{$prefix}UserID = {$uid}";
    }

    /**
     * Guard against accessing another user's commission details via URL manipulation
     */
    public static function enforcePersonalCommissionOnly($requestedUserID) {
        $requestedUserID = intval($requestedUserID);
        if (self::isAdmin()) return true;

        $user = self::getCurrentUser();
        $isGlobal = isset($user['OptionCommissionScope']) ? intval($user['OptionCommissionScope']) : 0;
        if ($isGlobal == 1) return true;

        $uid = isset($_SESSION['UserID']) ? intval($_SESSION['UserID']) : 0;
        if ($requestedUserID > 0 && $requestedUserID !== $uid) {
            return false;
        }
        return true;
    }
}
