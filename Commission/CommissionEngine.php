<?php
/**
 * CommissionEngine - Dynamic Commission & Referral Calculation Engine
 * 100% Compatible with PHP 5.6
 */

class CommissionEngine {

    /**
     * Get eligible referrers for a given user type
     */
    public static function getEligibleReferrers($userType, $currentUserID = 0) {
        $currentUserID = intval($currentUserID);
        $userType = strtolower(trim($userType));

        if ($userType === 'founder' || $userType === 'general' || $userType === 'normal') {
            return array();
        }

        $where = "UserIsActive = 1";
        if ($currentUserID > 0) {
            $where .= " AND UserID != " . $currentUserID;
        }

        if ($userType === 'director') {
            // Directors can ONLY be referred by a Founder
            $where .= " AND user_type = 'founder'";
        } elseif ($userType === 'salesperson') {
            // Salespersons can be referred by a Director or a Founder
            $where .= " AND user_type IN ('director', 'founder')";
        } else {
            return array();
        }

        $users = SQL_Select("User", $where, "NameFirst ASC");
        if (!is_array($users)) {
            $users = array();
        }
        return $users;
    }

    /**
     * Validate referral hierarchy and circular referral
     */
    public static function validateReferralChain($targetUserID, $userType, $referredByID) {
        $targetUserID = intval($targetUserID);
        $referredByID = intval($referredByID);
        $userType     = strtolower(trim($userType));

        // General / Normal users have no relation with commissions or referrals
        if ($userType === 'general' || $userType === 'normal') {
            if ($referredByID > 0) {
                return array('valid' => false, 'message' => 'General users have no relation with referrals or commissions.');
            }
            return array('valid' => true, 'message' => '');
        }

        // Founder cannot have a referrer
        if ($userType === 'founder') {
            if ($referredByID > 0) {
                return array('valid' => false, 'message' => 'Founders cannot have a referrer.');
            }
            return array('valid' => true, 'message' => '');
        }

        // Prevent self referral
        if ($targetUserID > 0 && $targetUserID === $referredByID) {
            return array('valid' => false, 'message' => 'A user cannot refer themselves.');
        }

        // If no referrer specified for director/salesperson, check policy
        if ($referredByID === 0) {
            // Salesperson or Director without direct referrer can be permitted under direct company/founder fallback
            return array('valid' => true, 'message' => '');
        }

        // Check referrer existence and type
        $referrer = SQL_Select("User", "UserID = " . $referredByID, "", true);
        if (empty($referrer) || empty($referrer["UserID"])) {
            return array('valid' => false, 'message' => 'Selected referrer user does not exist.');
        }

        $refType = strtolower(trim($referrer["user_type"]));

        if ($userType === 'director') {
            if ($refType !== 'founder') {
                return array('valid' => false, 'message' => 'A Director can only be referred by a Founder.');
            }
        } elseif ($userType === 'salesperson') {
            if ($refType !== 'director' && $refType !== 'founder') {
                return array('valid' => false, 'message' => 'A Salesperson can only be referred by a Director or Founder.');
            }
        }

        // Circular reference detection (traverse upwards)
        if ($targetUserID > 0) {
            $currentCheckID = $referredByID;
            $visited = array($targetUserID => true);
            $maxDepth = 20;

            while ($currentCheckID > 0 && $maxDepth > 0) {
                if (isset($visited[$currentCheckID])) {
                    return array('valid' => false, 'message' => 'Circular referral loop detected! This referral chain is invalid.');
                }
                $visited[$currentCheckID] = true;
                $ancestor = SQL_Select("User", "UserID = " . $currentCheckID, "", true);
                if (!empty($ancestor) && !empty($ancestor["referred_by"])) {
                    $currentCheckID = intval($ancestor["referred_by"]);
                } else {
                    $currentCheckID = 0;
                }
                $maxDepth--;
            }
        }

        return array('valid' => true, 'message' => '');
    }

    /**
     * Resolve full referral chain for a user
     */
    public static function resolveReferralChain($userID) {
        $userID = intval($userID);
        $chain = array(
            'salesperson' => null,
            'director'    => null,
            'founder'     => null,
            'founders'    => array()
        );

        if ($userID <= 0) {
            return $chain;
        }

        $user = SQL_Select("User", "UserID = " . $userID, "", true);
        if (empty($user)) {
            return $chain;
        }

        $userType = strtolower(trim($user["user_type"]));

        if ($userType === 'salesperson') {
            $chain['salesperson'] = $user;
            $refID = intval($user['referred_by']);
            if ($refID > 0) {
                $refUser = SQL_Select("User", "UserID = " . $refID, "", true);
                if (!empty($refUser)) {
                    $refType = strtolower(trim($refUser['user_type']));
                    if ($refType === 'director') {
                        $chain['director'] = $refUser;
                        // Check Director's referrer
                        $dirRefID = intval($refUser['referred_by']);
                        if ($dirRefID > 0) {
                            $dirRefUser = SQL_Select("User", "UserID = " . $dirRefID, "", true);
                            if (!empty($dirRefUser) && strtolower(trim($dirRefUser['user_type'])) === 'founder') {
                                $chain['founder'] = $dirRefUser;
                            }
                        }
                    } elseif ($refType === 'founder') {
                        $chain['founder'] = $refUser;
                    }
                }
            }
        } elseif ($userType === 'director') {
            $chain['director'] = $user;
            $refID = intval($user['referred_by']);
            if ($refID > 0) {
                $refUser = SQL_Select("User", "UserID = " . $refID, "", true);
                if (!empty($refUser) && strtolower(trim($refUser['user_type'])) === 'founder') {
                    $chain['founder'] = $refUser;
                }
            }
        } elseif ($userType === 'founder') {
            $chain['founder'] = $user;
        }

        // Also fetch all active Founders for pool distribution
        $allFounders = SQL_Select("User", "user_type = 'founder' AND UserIsActive = 1", "UserID ASC");
        if (is_array($allFounders)) {
            $chain['founders'] = $allFounders;
        }

        return $chain;
    }

    /**
     * Retrieve active or specified commission rule
     */
    public static function getRule($version = '') {
        $where = "CommissionRuleIsActive = 1";
        if (!empty($version)) {
            $vSafe = mysql_real_escape_string($version);
            $rule = SQL_Select("commission_rules", "RuleVersion = '{$vSafe}'", "", true);
            if (!empty($rule)) {
                return $rule;
            }
        }

        $rule = SQL_Select("commission_rules", "CommissionRuleIsActive = 1 AND EffectiveFrom <= CURDATE()", "EffectiveFrom DESC, CommissionRuleID DESC", true);
        if (!empty($rule)) {
            return $rule;
        }

        // Fallback default structure
        return array(
            'CommissionRuleID' => 0,
            'RuleVersion' => 'v1.0',
            'TotalCommissionPercent' => 15.00,
            'SalespersonMaxCommissionPercent' => 10.00,
            'FounderBaseCommissionPercent' => 5.00,
            'DirectorDirectSaleCommissionPercent' => 13.00,
            'DirectorReferralCommissionPercent' => 3.00,
            'FounderReferralCommissionPercent' => 2.00,
            'MonthlySalespersonTargetDefault' => 10,
            'TargetFormula' => 'MIN(Actual/Target, 1)',
            'FounderDistributionMethod' => 'equal',
            'RoundingPrecision' => 2
        );
    }

    /**
     * Calculate monthly salesperson target achievement
     */
    public static function getTargetAchievement($salespersonUserID, $dateStr, $rule) {
        $salespersonUserID = intval($salespersonUserID);
        if ($salespersonUserID <= 0) {
            return array('target' => 10, 'actual' => 0, 'achievement' => 0.0);
        }

        $user = SQL_Select("User", "UserID = " . $salespersonUserID, "", true);
        // Target resolution:
        // If user has MonthlySalesTarget defined (including 0 for 'no target'):
        if (isset($user['MonthlySalesTarget']) && $user['MonthlySalesTarget'] !== "") {
            $target = intval($user['MonthlySalesTarget']);
        } elseif (isset($rule['MonthlySalespersonTargetDefault'])) {
            $target = intval($rule['MonthlySalespersonTargetDefault']);
        } else {
            $target = 10;
        }

        if ($target < 0) {
            $target = 0;
        }

        // Count unique sales made by this salesperson
        $time = !empty($dateStr) ? strtotime($dateStr) : time();
        $monthStart = date('Y-m-01', $time);
        $monthEnd   = date('Y-m-t', $time);

        $actual = 0;
        if (function_exists('mysql_query')) {
            // Count sales matching either SalespersonUserID or SellerID
            // Without requiring SalesIsActive = 1 (as SalesIsActive is often null/not populated by legacy forms)
            $sqlMonth = "
                SELECT COUNT(DISTINCT SalesID) as TotalSales 
                FROM tblsales 
                WHERE (SalespersonUserID = {$salespersonUserID} OR SellerID = {$salespersonUserID})
                  AND (
                    (SalesDate >= '{$monthStart}' AND SalesDate <= '{$monthEnd} 23:59:59')
                    OR (DateInserted >= '{$monthStart} 00:00:00' AND DateInserted <= '{$monthEnd} 23:59:59')
                  )
            ";
            $countQuery = @mysql_query($sqlMonth);
            if ($countQuery && $row = @mysql_fetch_assoc($countQuery)) {
                $actual = intval($row['TotalSales']);
            }

            // Fallback: If 0 in date range, count all sales for this salesperson
            if ($actual <= 0) {
                $sqlAll = "
                    SELECT COUNT(DISTINCT SalesID) as TotalSales 
                    FROM tblsales 
                    WHERE (SalespersonUserID = {$salespersonUserID} OR SellerID = {$salespersonUserID})
                ";
                $countAll = @mysql_query($sqlAll);
                if ($countAll && $rowA = @mysql_fetch_assoc($countAll)) {
                    $actual = intval($rowA['TotalSales']);
                }
            }
        }

        // If target == 0, that means NO TARGET is set (no target constraint)
        // In that case, salesperson automatically achieves 100% (1.0) achievement rate.
        if ($target == 0) {
            $achievement = 1.0;
        } else {
            $achievement = min(1.0, (float)$actual / (float)$target);
            if ($achievement < 0) {
                $achievement = 0.0;
            }
        }

        return array(
            'target'      => $target,
            'actual'      => $actual,
            'achievement' => $achievement,
            'no_target'   => ($target == 0)
        );
    }

    /**
     * Compute distribution splits for an eligible collection
     */
    public static function calculateSplits($sale, $collectionAmount, $collectionDate) {
        $collectionAmount = round(floatval($collectionAmount), 2);
        if ($collectionAmount <= 0) {
            return array();
        }

        $ruleVersion = !empty($sale['CommissionRuleVersion']) ? $sale['CommissionRuleVersion'] : '';
        $rule = self::getRule($ruleVersion);

        $totalRate = floatval($rule['TotalCommissionPercent']); // e.g. 15.00
        $precision = isset($rule['RoundingPrecision']) ? intval($rule['RoundingPrecision']) : 2;

        $sellerUserID = !empty($sale['SalespersonUserID']) ? intval($sale['SalespersonUserID']) : intval($sale['SellerID']);
        $sellerUser = ($sellerUserID > 0) ? SQL_Select("User", "UserID = " . $sellerUserID, "", true) : null;
        $sellerType = !empty($sellerUser['user_type']) ? strtolower(trim($sellerUser['user_type'])) : '';

        $isFounderDirect = (!empty($sale['IsFounderDirectSale']) && intval($sale['IsFounderDirectSale']) == 1) || ($sellerType === 'founder');
        $isDirectorDirect = (!empty($sale['IsDirectorDirectSale']) && intval($sale['IsDirectorDirectSale']) == 1) || ($sellerType === 'director');
        $splits = array();

        // All active founders for pool distributions
        $allFounders = SQL_Select("User", "user_type = 'founder' AND (UserIsActive = 1 OR UserIsActive IS NULL)", "UserID ASC");
        if (!is_array($allFounders)) {
            $allFounders = array();
        }

        // ====================================================================
        // CATEGORY 1: ফাউন্ডার নিজে বিক্রি করলে (Founder Direct Sale)
        // সংশ্লিষ্ট ফাউন্ডার সম্পূর্ণ কমিশন পাবেন ১৫%
        // ====================================================================
        if ($isFounderDirect) {
            $sellingFounderID = $sellerUserID;
            if ($sellingFounderID <= 0 && !empty($sale['FounderUserID'])) {
                $sellingFounderID = intval($sale['FounderUserID']);
            }
            if ($sellingFounderID <= 0 && !empty($sale['SellerID'])) {
                $sellingFounderID = intval($sale['SellerID']);
            }

            $founderAmount = round(($collectionAmount * $totalRate) / 100.0, $precision);
            $splits[] = array(
                'user_id'     => $sellingFounderID,
                'user_type'   => 'founder',
                'rate'        => $totalRate,
                'amount'      => $founderAmount,
                'description' => "ফাউন্ডার নিজে বিক্রি: সংশ্লিষ্ট ফাউন্ডার সম্পূর্ণ কমিশন ({$totalRate}%)"
            );
            return $splits;
        }

        // ====================================================================
        // CATEGORY 3: ডিরেক্টর নিজে বিক্রি করলে (Director Direct Sale)
        // সংশ্লিষ্ট ডিরেক্টর পাবেন ১৩% এবং ফাউন্ডারগণ সমহারে পাবেন ২%
        // ====================================================================
        if ($isDirectorDirect) {
            $directorRate = floatval($rule['DirectorDirectSaleCommissionPercent']); // 13.00
            $founderPoolRate = round($totalRate - $directorRate, $precision); // 2.00

            $directorUserID = intval($sale['DirectorUserID']);
            if ($directorUserID <= 0 && $sellerUserID > 0) {
                $directorUserID = $sellerUserID;
            }

            if ($directorUserID > 0) {
                $dirAmount = round(($collectionAmount * $directorRate) / 100.0, $precision);
                $splits[] = array(
                    'user_id'     => $directorUserID,
                    'user_type'   => 'director',
                    'rate'        => $directorRate,
                    'amount'      => $dirAmount,
                    'description' => "ডিরেক্টর নিজে বিক্রি: সংশ্লিষ্ট ডিরেক্টর কমিশন ({$directorRate}%)"
                );
            }

            // ফাউন্ডারগণ সমহারে পাবেন ২%
            self::distributeFounderPool($splits, $allFounders, $collectionAmount, $founderPoolRate, $rule, $precision);
            return $splits;
        }

        // ====================================================================
        // CATEGORIES 2, 4, 5, 6: সেলার / সেলসপার্সন সংক্রান্ত বিক্রয়
        // ====================================================================
        $salespersonID = $sellerUserID;
        $targetInfo = self::getTargetAchievement($salespersonID, $collectionDate, $rule, $sale);
        $achievement = $targetInfo['achievement'];

        // Fallback: If query was 0, but this collection is for a valid sale made by salesperson
        if ($achievement <= 0 && $salespersonID > 0) {
            if ($targetInfo['target'] > 0) {
                $achievement = min(1.0, 1.0 / (float)$targetInfo['target']);
            } else {
                $achievement = 1.0;
            }
        }

        $maxSpRate = floatval($rule['SalespersonMaxCommissionPercent']); // 10.00
        $spEarnedRate = round($maxSpRate * $achievement, $precision);
        $unusedSpRate = round($maxSpRate - $spEarnedRate, $precision);

        if ($salespersonID > 0 && $spEarnedRate > 0) {
            $spAmount = round(($collectionAmount * $spEarnedRate) / 100.0, $precision);
            $splits[] = array(
                'user_id'     => $salespersonID,
                'user_type'   => 'salesperson',
                'rate'        => $spEarnedRate,
                'amount'      => $spAmount,
                'description' => "সেলার কমিশন ({$spEarnedRate}% অর্জিত: " . round($achievement * 100, 1) . "%)"
            );
        }

        // Determine referral hierarchy
        $directorID = intval($sale['DirectorUserID']);
        $founderID = intval($sale['FounderUserID']);

        if (($directorID <= 0 || $founderID <= 0) && $salespersonID > 0) {
            $spUser = SQL_Select("User", "UserID = " . $salespersonID, "", true);
            if (!empty($spUser['referred_by'])) {
                $refUser = SQL_Select("User", "UserID = " . intval($spUser['referred_by']), "", true);
                if (!empty($refUser)) {
                    $rType = strtolower(trim($refUser['user_type']));
                    if ($rType === 'director') {
                        $directorID = intval($refUser['UserID']);
                        if (!empty($refUser['referred_by'])) {
                            $founderID = intval($refUser['referred_by']);
                        }
                    } elseif ($rType === 'founder') {
                        $founderID = intval($refUser['UserID']);
                    }
                }
            }
        }

        // ====================================================================
        // CATEGORY 4: ডিরেক্টরের অধীনে সেলার বিক্রি করলে
        // সেলার পাবেন ১০% (বা অর্জিত), সংশ্লিষ্ট ডিরেক্টর পাবেন ৩%, ফাউন্ডারগণ সমহারে পাবেন ২% (+ অব্যবহৃত অংশ)
        // ====================================================================
        if ($directorID > 0) {
            $dirRefRate = floatval($rule['DirectorReferralCommissionPercent']); // 3.00
            $dirAmount = round(($collectionAmount * $dirRefRate) / 100.0, $precision);
            $splits[] = array(
                'user_id'     => $directorID,
                'user_type'   => 'director',
                'rate'        => $dirRefRate,
                'amount'      => $dirAmount,
                'description' => "সংশ্লিষ্ট ডিরেক্টর রেফারেল কমিশন ({$dirRefRate}%)"
            );

            // ফাউন্ডারগণ সমহারে পাবেন ২% + সেলারের অব্যবহৃত অংশ
            $founderBaseRate = floatval($rule['FounderReferralCommissionPercent']); // 2.00
            $founderTotalRate = round($founderBaseRate + $unusedSpRate, $precision);
            self::distributeFounderPool($splits, $allFounders, $collectionAmount, $founderTotalRate, $rule, $precision);

        // ====================================================================
        // CATEGORY 2: ফাউন্ডারের অধীনস্থ সেলার বিক্রি করলে
        // সেলার পাবেন ১০% (বা অর্জিত) এবং সংশ্লিষ্ট ফাউন্ডার পাবেন ৫% (+ অব্যবহৃত অংশ)
        // ====================================================================
        } elseif ($founderID > 0) {
            $fndBaseRate = floatval($rule['FounderBaseCommissionPercent']); // 5.00
            $fndTotalRate = round($fndBaseRate + $unusedSpRate, $precision);
            $fndAmount = round(($collectionAmount * $fndTotalRate) / 100.0, $precision);

            $splits[] = array(
                'user_id'     => $founderID,
                'user_type'   => 'founder',
                'rate'        => $fndTotalRate,
                'amount'      => $fndAmount,
                'description' => "ফাউন্ডারের অধীনস্থ সেলার: সংশ্লিষ্ট ফাউন্ডার কমিশন ({$fndTotalRate}%)"
            );

        // ====================================================================
        // CATEGORIES 5 & 6: অফিস সেলস সংক্রান্ত বিক্রয় (কোনো ডিরেক্টর বা ফাউন্ডার রেফারার নেই)
        // সেলার পাবেন ১০% (বা অর্জিত) এবং ফাউন্ডারগণ সমহারে পাবেন ৫% (+ অবশিষ্ট অংশ)
        // ====================================================================
        } else {
            $officeFndBaseRate = floatval($rule['FounderBaseCommissionPercent']); // 5.00
            $officeFndTotalRate = round($officeFndBaseRate + $unusedSpRate, $precision);

            self::distributeFounderPool($splits, $allFounders, $collectionAmount, $officeFndTotalRate, $rule, $precision);
        }

        return $splits;

        return $splits;
    }

    /**
     * Distribute Founder Pool rate equally among all active Founders
     * Rule: No single founder receives direct referral commission.
     * All founder pool funds (including 2% pool and any unused salesperson commission) are split equally among all active Founders.
     */
    private static function distributeFounderPool(&$splits, $allFounders, $collectionAmount, $poolRate, $rule, $precision = 2) {
        if ($poolRate <= 0) {
            return;
        }

        $poolAmount = round(($collectionAmount * $poolRate) / 100.0, $precision);
        if ($poolAmount <= 0) {
            return;
        }

        if (empty($allFounders)) {
            $allFounders = SQL_Select("User", "user_type = 'founder' AND (UserIsActive = 1 OR UserIsActive IS NULL)", "UserID ASC");
            if (!is_array($allFounders)) {
                $allFounders = array();
            }
        }

        if (empty($allFounders)) {
            return;
        }

        $founderCount = count($allFounders);
        $method = isset($rule['FounderDistributionMethod']) ? strtolower(trim($rule['FounderDistributionMethod'])) : 'equal';

        if ($method === 'percentage') {
            // Distribute based on FounderSharePercent if explicitly set in settings
            foreach ($allFounders as $founder) {
                $sharePct = floatval($founder['FounderSharePercent']);
                if ($sharePct > 0) {
                    $fRate = round(($poolRate * $sharePct) / 100.0, $precision);
                    $fAmount = round(($collectionAmount * $fRate) / 100.0, $precision);
                } else {
                    $fRate = round($poolRate / $founderCount, $precision);
                    $fAmount = round($poolAmount / $founderCount, $precision);
                }

                $splits[] = array(
                    'user_id'     => intval($founder['UserID']),
                    'user_type'   => 'founder',
                    'rate'        => $fRate,
                    'amount'      => $fAmount,
                    'description' => "Founder Pool share ({$fRate}%) - distributed among founders"
                );
            }
        } else {
            // Default & Standard: Equal distribution across all active founders
            $perFounderRate = round($poolRate / $founderCount, $precision);
            $perFounderAmountBase = floor(($poolAmount / $founderCount) * 100) / 100;
            $remainder = round($poolAmount - ($perFounderAmountBase * $founderCount), 2);

            foreach ($allFounders as $founder) {
                $thisAmount = $perFounderAmountBase;
                if ($remainder > 0.009) {
                    $thisAmount = round($thisAmount + 0.01, 2);
                    $remainder = round($remainder - 0.01, 2);
                }

                $splits[] = array(
                    'user_id'     => intval($founder['UserID']),
                    'user_type'   => 'founder',
                    'rate'        => $perFounderRate,
                    'amount'      => $thisAmount,
                    'description' => "Founder Pool equal share ({$perFounderRate}%) across {$founderCount} active founders"
                );
            }
        }
    }

    /**
     * Process an eligible customer collection and generate commission transactions
     */
    public static function processCollection($collectionVoucherNo, $saleID, $collectionAmount, $collectionDate, $userIDInserted = 0) {
        $collectionVoucherNo = intval($collectionVoucherNo);
        $saleID = intval($saleID);
        $collectionAmount = floatval($collectionAmount);

        if ($collectionVoucherNo <= 0 || $saleID <= 0 || $collectionAmount <= 0) {
            return array('success' => false, 'message' => 'Invalid parameters for commission processing.');
        }

        // Fetch sale
        $sale = SQL_Select("Sales", "SalesID = " . $saleID, "", true);
        if (empty($sale) || empty($sale['SalesID'])) {
            return array('success' => false, 'message' => 'Sale record not found.');
        }

        $ruleVersion = !empty($sale['CommissionRuleVersion']) ? $sale['CommissionRuleVersion'] : 'v1.0';
        $rule = self::getRule($ruleVersion);
        $ruleID = isset($rule['CommissionRuleID']) ? intval($rule['CommissionRuleID']) : 0;

        // Calculate splits
        $splits = self::calculateSplits($sale, $collectionAmount, $collectionDate);
        if (empty($splits)) {
            return array('success' => false, 'message' => 'No commission splits generated.');
        }

        $generatedRecords = 0;
        $totalCommissionEarned = 0.0;

        foreach ($splits as $split) {
            $existing = SQL_Select("commission_transactions", "CollectionID = {$collectionVoucherNo} AND UserID = {$split['user_id']} AND TransactionType = 'commission_earned' AND CommissionTransactionIsActive = 1");
            if (!empty($existing) && count($existing) > 0) {
                continue; // This user already received commission for this collection
            }
            $ref = 'COM-' . $collectionVoucherNo . '-' . $split['user_id'] . '-' . time() . '-' . rand(100, 999);
            $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
            $txData = array(
                'commission_transactionsUUID'  => $uuid,
                'CommissionTransactionUUID'    => $uuid,
                'TransactionReference'         => $ref,
                'UserID'                       => $split['user_id'],
                'UserType'                     => $split['user_type'],
                'SalesID'                      => $saleID,
                'CollectionID'                 => $collectionVoucherNo,
                'VoucherID'                    => $collectionVoucherNo,
                'CommissionRuleID'             => $ruleID,
                'TransactionType'              => 'commission_earned',
                'Direction'                    => 'credit',
                'CommissionRate'               => $split['rate'],
                'BaseAmount'                   => $collectionAmount,
                'Amount'                       => $split['amount'],
                'Description'                  => $split['description'] . " against Sale #{$saleID}, Collection #{$collectionVoucherNo}",
                'RelatedTransactionID'         => 0,
                'TransactionDate'              => !empty($collectionDate) ? $collectionDate : date('Y-m-d'),
                'CommissionTransactionIsActive' => 1,
                'CommissionTransactionIsDisplay'=> 0,
                'UserIDInserted'               => $userIDInserted > 0 ? $userIDInserted : 1,
                'DateInserted'                 => date('Y-m-d H:i:s')
            );

            $ins = @SQL_InsertUpdate("commission_transactions", $txData);
            if (!$ins && function_exists('mysql_query')) {
                @mysql_query("INSERT INTO `tblcommission_transactions` (
                    `commission_transactionsUUID`, `CommissionTransactionUUID`, `TransactionReference`,
                    `UserID`, `UserType`, `SalesID`, `CollectionID`, `VoucherID`, `CommissionRuleID`,
                    `TransactionType`, `Direction`, `CommissionRate`, `BaseAmount`, `Amount`,
                    `Description`, `RelatedTransactionID`, `TransactionDate`,
                    `CommissionTransactionIsActive`, `CommissionTransactionIsDisplay`,
                    `UserIDInserted`, `DateInserted`
                ) VALUES (
                    '{$uuid}', '{$uuid}', '{$ref}',
                    " . intval($split['user_id']) . ", '" . mysql_real_escape_string($split['user_type']) . "',
                    " . intval($saleID) . ", " . intval($collectionVoucherNo) . ", " . intval($collectionVoucherNo) . ",
                    " . intval($ruleID) . ", 'commission_earned', 'credit',
                    " . floatval($split['rate']) . ", " . floatval($collectionAmount) . ", " . floatval($split['amount']) . ",
                    '" . mysql_real_escape_string($txData['Description']) . "', 0, '" . mysql_real_escape_string($txData['TransactionDate']) . "',
                    1, 0, " . intval($txData['UserIDInserted']) . ", NOW()
                )");
            }
            $generatedRecords++;
            $totalCommissionEarned += $split['amount'];
        }

        // Update Sale Eligibility Status
        self::updateSaleStatusAfterCollection($saleID);

        return array(
            'success' => true,
            'records' => $generatedRecords,
            'total_amount' => $totalCommissionEarned
        );
    }

    /**
     * Reverse all commissions generated by a collection (e.g. Credit Voucher cancelled/reversed)
     */
    public static function reverseCollection($collectionVoucherNo, $userIDInserted = 0, $reason = '') {
        $collectionVoucherNo = intval($collectionVoucherNo);
        if ($collectionVoucherNo <= 0) {
            return array('success' => false, 'message' => 'Invalid collection voucher number.');
        }

        $existing = SQL_Select("commission_transactions", "CollectionID = {$collectionVoucherNo} AND TransactionType = 'commission_earned' AND CommissionTransactionIsActive = 1");
        if (empty($existing)) {
            return array('success' => true, 'reversed' => 0, 'message' => 'No active commission transactions found for this collection.');
        }

        $reversedCount = 0;
        foreach ($existing as $orig) {
            $origID = intval($orig['CommissionTransactionID']);
            // Check if already reversed
            $revCheck = SQL_Select("commission_transactions", "RelatedTransactionID = {$origID} AND TransactionType = 'commission_reversal'");
            if (!empty($revCheck)) {
                continue;
            }

            $revRef = 'REV-' . $collectionVoucherNo . '-' . $orig['UserID'] . '-' . time() . '-' . rand(100, 999);
            $revUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
            $revData = array(
                'commission_transactionsUUID'  => $revUuid,
                'CommissionTransactionUUID'    => $revUuid,
                'TransactionReference'         => $revRef,
                'UserID'                       => $orig['UserID'],
                'UserType'                     => $orig['UserType'],
                'SalesID'                      => $orig['SalesID'],
                'CollectionID'                 => $collectionVoucherNo,
                'VoucherID'                    => $orig['VoucherID'],
                'CommissionRuleID'             => $orig['CommissionRuleID'],
                'TransactionType'              => 'commission_reversal',
                'Direction'                    => 'debit',
                'CommissionRate'               => $orig['CommissionRate'],
                'BaseAmount'                   => $orig['BaseAmount'],
                'Amount'                       => $orig['Amount'],
                'Description'                  => "Reversal of {$orig['TransactionReference']}: " . (!empty($reason) ? $reason : 'Credit voucher invalidated/cancelled'),
                'RelatedTransactionID'         => $origID,
                'TransactionDate'              => date('Y-m-d'),
                'CommissionTransactionIsActive' => 1,
                'CommissionTransactionIsDisplay'=> 0,
                'UserIDInserted'               => $userIDInserted > 0 ? $userIDInserted : 1,
                'DateInserted'                 => date('Y-m-d H:i:s')
            );

            $insRev = @SQL_InsertUpdate("commission_transactions", $revData);
            if (!$insRev && function_exists('mysql_query')) {
                @mysql_query("INSERT INTO `tblcommission_transactions` (
                    `commission_transactionsUUID`, `CommissionTransactionUUID`, `TransactionReference`,
                    `UserID`, `UserType`, `SalesID`, `CollectionID`, `VoucherID`, `CommissionRuleID`,
                    `TransactionType`, `Direction`, `CommissionRate`, `BaseAmount`, `Amount`,
                    `Description`, `RelatedTransactionID`, `TransactionDate`,
                    `CommissionTransactionIsActive`, `CommissionTransactionIsDisplay`,
                    `UserIDInserted`, `DateInserted`
                ) VALUES (
                    '{$revUuid}', '{$revUuid}', '{$revRef}',
                    " . intval($orig['UserID']) . ", '" . mysql_real_escape_string($orig['UserType']) . "',
                    " . intval($orig['SalesID']) . ", " . intval($collectionVoucherNo) . ", " . intval($orig['VoucherID']) . ",
                    " . intval($orig['CommissionRuleID']) . ", 'commission_reversal', 'debit',
                    " . floatval($orig['CommissionRate']) . ", " . floatval($orig['BaseAmount']) . ", " . floatval($orig['Amount']) . ",
                    '" . mysql_real_escape_string($revData['Description']) . "', " . intval($origID) . ", '" . mysql_real_escape_string($revData['TransactionDate']) . "',
                    1, 0, " . intval($revData['UserIDInserted']) . ", NOW()
                )");
            }
            $reversedCount++;
        }

        if (!empty($existing[0]['SalesID'])) {
            self::updateSaleStatusAfterCollection(intval($existing[0]['SalesID']));
        }

        return array('success' => true, 'reversed' => $reversedCount);
    }

    /**
     * Update Sale commission eligibility status based on collections
     */
    public static function updateSaleStatusAfterCollection($saleID) {
        $saleID = intval($saleID);
        if ($saleID <= 0) return;

        $sale = SQL_Select("Sales", "SalesID = " . $saleID, "", true);
        if (empty($sale)) return;

        // Total received from actual sales payment
        $colQuery = mysql_query("SELECT SUM(ActualReceiveAmount) as TotalCollected FROM tblactualsalsepayment WHERE SalesID = {$saleID} AND ActualSalsePaymentIsActive = 1");
        $totalCollected = 0.0;
        if ($colQuery && $colRow = mysql_fetch_assoc($colQuery)) {
            $totalCollected = floatval($colRow['TotalCollected']);
        }

        // Check product price
        $product = SQL_Select("Products", "ProductsID = " . intval($sale['ProductID']), "", true);
        $flatPrice = !empty($product['NetSalesPrice']) ? floatval($product['NetSalesPrice']) : (!empty($product['FlatPrice']) ? floatval($product['FlatPrice']) : 0.0);
        $discount = floatval($sale['Discount']);
        $totalPayable = max(0, ($flatPrice * max(1, intval($sale['Quantity']))) - $discount);

        $status = 'pending_collection';
        if ($totalCollected <= 0) {
            $status = 'pending_collection';
        } elseif ($totalPayable > 0 && $totalCollected >= $totalPayable) {
            $status = 'fully_collected';
        } else {
            $status = 'partially_collected';
        }

        // Check if commission transactions exist
        $commCount = mysql_query("SELECT COUNT(*) as Cnt FROM tblcommission_transactions WHERE SalesID = {$saleID} AND TransactionType = 'commission_earned'");
        if ($commCount && $cRow = mysql_fetch_assoc($commCount)) {
            if (intval($cRow['Cnt']) > 0 && $status !== 'pending_collection') {
                $status = ($status === 'fully_collected') ? 'fully_collected' : 'commission_generated';
            }
        }

        mysql_query("UPDATE tblsales SET CommissionEligibilityStatus = '{$status}' WHERE SalesID = {$saleID}");
    }

    /**
     * Calculate ledger commission balance for a user
     */
    public static function getUserBalance($userID) {
        $userID = intval($userID);
        if ($userID <= 0) {
            return array('earned' => 0.0, 'paid' => 0.0, 'reversed' => 0.0, 'adjustments' => 0.0, 'balance' => 0.0);
        }

        $query = mysql_query("
            SELECT 
                SUM(CASE WHEN TransactionType = 'commission_earned' AND Direction = 'credit' THEN Amount ELSE 0 END) as TotalEarned,
                SUM(CASE WHEN TransactionType = 'commission_paid' AND Direction = 'debit' THEN Amount ELSE 0 END) as TotalPaid,
                SUM(CASE WHEN TransactionType = 'commission_reversal' AND Direction = 'debit' THEN Amount ELSE 0 END) as TotalReversed,
                SUM(CASE WHEN TransactionType = 'commission_adjustment' THEN (CASE WHEN Direction = 'credit' THEN Amount ELSE -Amount END) ELSE 0 END) as TotalAdjustments
            FROM tblcommission_transactions
            WHERE UserID = {$userID} AND CommissionTransactionIsActive = 1
        ");

        $earned = 0.0;
        $paid = 0.0;
        $reversed = 0.0;
        $adjustments = 0.0;

        if ($query && $row = mysql_fetch_assoc($query)) {
            $earned = floatval($row['TotalEarned']);
            $paid = floatval($row['TotalPaid']);
            $reversed = floatval($row['TotalReversed']);
            $adjustments = floatval($row['TotalAdjustments']);
        }

        $balance = ($earned + $adjustments) - $paid - $reversed;

        $pending = 0.0;
        $pendingQuery = mysql_query("
            SELECT SUM(Amount) as TotalPending
            FROM tblcommission_transactions
            WHERE UserID = {$userID} AND TransactionType = 'commission_paid' AND CommissionTransactionIsActive = 0
        ");
        if ($pendingQuery && $pRow = mysql_fetch_assoc($pendingQuery)) {
            $pending = floatval($pRow['TotalPending']);
        }

        return array(
            'earned'         => $earned,
            'paid'           => $paid,
            'reversed'       => $reversed,
            'adjustments'    => $adjustments,
            'pending_payout' => $pending,
            'balance'        => $balance
        );
    }

    /**
     * Batch calculate balances for all users to prevent N+1 queries
     */
    public static function getAllUserBalances() {
        $balances = array();
        $query = mysql_query("
            SELECT 
                UserID,
                SUM(CASE WHEN TransactionType = 'commission_earned' AND Direction = 'credit' THEN Amount ELSE 0 END) as TotalEarned,
                SUM(CASE WHEN TransactionType = 'commission_paid' AND Direction = 'debit' THEN Amount ELSE 0 END) as TotalPaid,
                SUM(CASE WHEN TransactionType = 'commission_reversal' AND Direction = 'debit' THEN Amount ELSE 0 END) as TotalReversed,
                SUM(CASE WHEN TransactionType = 'commission_adjustment' THEN (CASE WHEN Direction = 'credit' THEN Amount ELSE -Amount END) ELSE 0 END) as TotalAdjustments
            FROM tblcommission_transactions
            WHERE CommissionTransactionIsActive = 1
            GROUP BY UserID
        ");

        if ($query) {
            while ($row = mysql_fetch_assoc($query)) {
                $uid = intval($row['UserID']);
                $earned = floatval($row['TotalEarned']);
                $paid = floatval($row['TotalPaid']);
                $reversed = floatval($row['TotalReversed']);
                $adj = floatval($row['TotalAdjustments']);
                $balance = ($earned + $adj) - $paid - $reversed;

                $balances[$uid] = array(
                    'earned'         => $earned,
                    'paid'           => $paid,
                    'reversed'       => $reversed,
                    'adjustments'    => $adj,
                    'pending_payout' => 0.0,
                    'balance'        => $balance
                );
            }
        }

        // Fetch pending payouts
        $pQuery = mysql_query("
            SELECT UserID, SUM(Amount) as TotalPending
            FROM tblcommission_transactions
            WHERE TransactionType = 'commission_paid' AND CommissionTransactionIsActive = 0
            GROUP BY UserID
        ");
        if ($pQuery) {
            while ($pRow = mysql_fetch_assoc($pQuery)) {
                $pUid = intval($pRow['UserID']);
                $pAmt = floatval($pRow['TotalPending']);
                if (isset($balances[$pUid])) {
                    $balances[$pUid]['pending_payout'] = $pAmt;
                } else {
                    $balances[$pUid] = array(
                        'earned'         => 0.0,
                        'paid'           => 0.0,
                        'reversed'       => 0.0,
                        'adjustments'    => 0.0,
                        'pending_payout' => $pAmt,
                        'balance'        => 0.0
                    );
                }
            }
        }

        return $balances;
    }

    /**
     * Safe Column Addition (Idempotent)
     */
    public static function safeAddColumn($table, $column, $definition) {
        if (!function_exists('mysql_query') || !function_exists('mysql_num_rows')) return false;
        $check = @mysql_query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        if ($check && mysql_num_rows($check) > 0) {
            return true;
        }
        return @mysql_query("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
    }

    /**
     * Ensure all necessary commission tables, columns, and default rules exist in the database
     */
    public static function ensureTablesExist() {
        static $alreadyChecked = false;
        if ($alreadyChecked) return;
        $alreadyChecked = true;

        if (!function_exists('mysql_query') || !function_exists('mysql_num_rows')) return;

        // Check if there is an active database link
        $testConn = @mysql_query("SELECT 1");
        if ($testConn === false || is_null($testConn)) {
            return;
        }

        // 1. Ensure required tables exist
        $sqlRules = "CREATE TABLE IF NOT EXISTS `tblcommission_rules` (
          `CommissionRuleID` INT(11) NOT NULL AUTO_INCREMENT,
          `CommissionRuleUUID` VARCHAR(36) NOT NULL DEFAULT '',
          `commission_rulesUUID` VARCHAR(36) NOT NULL DEFAULT '',
          `commission_rulesID` INT(11) NOT NULL DEFAULT 0,
          `RuleVersion` VARCHAR(50) NOT NULL DEFAULT 'v1.0',
          `EffectiveFrom` DATE NOT NULL,
          `EffectiveTo` DATE NULL DEFAULT NULL,
          `TotalCommissionPercent` DECIMAL(5,2) NOT NULL DEFAULT 15.00,
          `SalespersonMaxCommissionPercent` DECIMAL(5,2) NOT NULL DEFAULT 10.00,
          `FounderBaseCommissionPercent` DECIMAL(5,2) NOT NULL DEFAULT 5.00,
          `DirectorDirectSaleCommissionPercent` DECIMAL(5,2) NOT NULL DEFAULT 13.00,
          `DirectorReferralCommissionPercent` DECIMAL(5,2) NOT NULL DEFAULT 3.00,
          `FounderReferralCommissionPercent` DECIMAL(5,2) NOT NULL DEFAULT 2.00,
          `MonthlySalespersonTargetDefault` INT(11) NOT NULL DEFAULT 10,
          `TargetFormula` VARCHAR(100) NOT NULL DEFAULT 'MIN(Actual/Target, 1)',
          `FounderDistributionMethod` VARCHAR(30) NOT NULL DEFAULT 'equal',
          `RoundingPrecision` INT(11) NOT NULL DEFAULT 2,
          `CommissionRuleIsActive` TINYINT(1) NOT NULL DEFAULT 1,
          `CommissionRuleIsDisplay` TINYINT(1) NOT NULL DEFAULT 0,
          `Notes` TEXT NULL,
          `UserIDInserted` INT(11) NOT NULL DEFAULT 0,
          `UserIDUpdated` INT(11) NOT NULL DEFAULT 0,
          `UserIDLocked` INT(11) NOT NULL DEFAULT 0,
          `DateInserted` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
          `DateUpdated` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
          `DateLocked` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
          PRIMARY KEY (`CommissionRuleID`),
          UNIQUE KEY `uk_comm_rule_version` (`RuleVersion`),
          KEY `idx_comm_rule_active` (`CommissionRuleIsActive`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        @mysql_query($sqlRules);

        $sqlTx = "CREATE TABLE IF NOT EXISTS `tblcommission_transactions` (
          `CommissionTransactionID` INT(11) NOT NULL AUTO_INCREMENT,
          `CommissionTransactionUUID` VARCHAR(36) NOT NULL DEFAULT '',
          `commission_transactionsUUID` VARCHAR(36) NOT NULL DEFAULT '',
          `commission_transactionsID` INT(11) NOT NULL DEFAULT 0,
          `TransactionReference` VARCHAR(100) NOT NULL,
          `UserID` INT(11) NOT NULL,
          `UserType` VARCHAR(30) NOT NULL,
          `SalesID` INT(11) NOT NULL DEFAULT 0,
          `CollectionID` INT(11) NOT NULL DEFAULT 0,
          `VoucherID` INT(11) NOT NULL DEFAULT 0,
          `CommissionRuleID` INT(11) NOT NULL DEFAULT 0,
          `TransactionType` VARCHAR(50) NOT NULL,
          `Direction` ENUM('credit', 'debit') NOT NULL,
          `CommissionRate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
          `BaseAmount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
          `Amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
          `Description` TEXT NULL,
          `RelatedTransactionID` INT(11) NOT NULL DEFAULT 0,
          `TransactionDate` DATE NOT NULL,
          `CommissionTransactionIsActive` TINYINT(1) NOT NULL DEFAULT 1,
          `CommissionTransactionIsDisplay` TINYINT(1) NOT NULL DEFAULT 0,
          `UserIDInserted` INT(11) NOT NULL DEFAULT 0,
          `UserIDUpdated` INT(11) NOT NULL DEFAULT 0,
          `UserIDLocked` INT(11) NOT NULL DEFAULT 0,
          `DateInserted` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
          `DateUpdated` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
          `DateLocked` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
          PRIMARY KEY (`CommissionTransactionID`),
          UNIQUE KEY `uk_comm_tx_ref` (`TransactionReference`),
          KEY `idx_comm_tx_user` (`UserID`),
          KEY `idx_comm_tx_sale` (`SalesID`),
          KEY `idx_comm_tx_collection` (`CollectionID`),
          KEY `idx_comm_tx_type` (`TransactionType`),
          KEY `idx_comm_tx_direction` (`Direction`),
          KEY `idx_comm_tx_date` (`TransactionDate`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        @mysql_query($sqlTx);

        // 2. Always ensure required columns exist across all tables
        self::safeAddColumn("tblcommission_transactions", "commission_transactionsUUID", "VARCHAR(36) NOT NULL DEFAULT ''");
        self::safeAddColumn("tblcommission_transactions", "CommissionTransactionUUID", "VARCHAR(36) NOT NULL DEFAULT ''");
        self::safeAddColumn("tblcommission_transactions", "commission_transactionsID", "INT(11) NOT NULL DEFAULT 0");
        self::safeAddColumn("tblcommission_transactions", "Image", "VARCHAR(255) NOT NULL DEFAULT ''");

        self::safeAddColumn("tblcommission_rules", "commission_rulesUUID", "VARCHAR(36) NOT NULL DEFAULT ''");
        self::safeAddColumn("tblcommission_rules", "CommissionRuleUUID", "VARCHAR(36) NOT NULL DEFAULT ''");
        self::safeAddColumn("tblcommission_rules", "commission_rulesID", "INT(11) NOT NULL DEFAULT 0");

        self::safeAddColumn("tbluser", "user_type", "VARCHAR(30) NOT NULL DEFAULT 'general'");
        self::safeAddColumn("tbluser", "referred_by", "INT(11) NOT NULL DEFAULT 0");
        self::safeAddColumn("tbluser", "FounderSharePercent", "DECIMAL(5,2) NOT NULL DEFAULT 0.00");
        self::safeAddColumn("tbluser", "MonthlySalesTarget", "INT(11) NOT NULL DEFAULT 10");
        self::safeAddColumn("tbluser", "OptionCommission", "TINYINT(2) NOT NULL DEFAULT 0");
        self::safeAddColumn("tbluser", "OptionCommissionView", "TINYINT(2) NOT NULL DEFAULT 0");
        self::safeAddColumn("tbluser", "OptionCommissionRules", "TINYINT(2) NOT NULL DEFAULT 0");
        self::safeAddColumn("tbluser", "OptionCommissionPay", "TINYINT(2) NOT NULL DEFAULT 0");
        self::safeAddColumn("tbluser", "OptionCommissionReports", "TINYINT(2) NOT NULL DEFAULT 0");

        self::safeAddColumn("tblsales", "SalespersonUserID", "INT(11) NOT NULL DEFAULT 0");
        self::safeAddColumn("tblsales", "DirectorUserID", "INT(11) NOT NULL DEFAULT 0");
        self::safeAddColumn("tblsales", "FounderUserID", "INT(11) NOT NULL DEFAULT 0");
        self::safeAddColumn("tblsales", "IsFounderDirectSale", "TINYINT(1) NOT NULL DEFAULT 0");
        self::safeAddColumn("tblsales", "IsDirectorDirectSale", "TINYINT(1) NOT NULL DEFAULT 0");
        self::safeAddColumn("tblsales", "CommissionRuleVersion", "VARCHAR(50) NOT NULL DEFAULT 'v1.0'");
        self::safeAddColumn("tblsales", "CommissionEligibilityStatus", "VARCHAR(50) NOT NULL DEFAULT 'pending_collection'");

        self::safeAddColumn("tblproducts", "MonthlyTargetDeduction", "DECIMAL(10,2) NOT NULL DEFAULT 1.00");

        // DrVoucher and Transaction beneficiary tracking
        self::safeAddColumn("tbldrvoucher", "UserID", "INT(11) NOT NULL DEFAULT 0");
        self::safeAddColumn("tbldrvoucher", "UserType", "VARCHAR(30) NOT NULL DEFAULT ''");
        self::safeAddColumn("tbltransaction", "UserID", "INT(11) NOT NULL DEFAULT 0");
        self::safeAddColumn("tbltransaction", "UserType", "VARCHAR(30) NOT NULL DEFAULT ''");
        self::safeAddColumn("tblcommission_transactions", "PayoutStatus", "VARCHAR(30) NOT NULL DEFAULT 'confirmed'");

        // 3. Ensure default rule v1.0 exists
        $checkDef = @mysql_query("SELECT CommissionRuleID FROM tblcommission_rules WHERE RuleVersion = 'v1.0'");
        if ($checkDef && mysql_num_rows($checkDef) == 0) {
            $insertDef = "INSERT INTO tblcommission_rules (
                CommissionRuleUUID, commission_rulesUUID, RuleVersion, EffectiveFrom,
                TotalCommissionPercent, SalespersonMaxCommissionPercent, FounderBaseCommissionPercent,
                DirectorDirectSaleCommissionPercent, DirectorReferralCommissionPercent, FounderReferralCommissionPercent,
                MonthlySalespersonTargetDefault, TargetFormula, FounderDistributionMethod, RoundingPrecision,
                CommissionRuleIsActive, CommissionRuleIsDisplay, Notes, DateInserted
            ) VALUES (
                UUID(), UUID(), 'v1.0', '2026-01-01',
                15.00, 10.00, 5.00,
                13.00, 3.00, 2.00,
                10, 'MIN(Actual/Target, 1)', 'equal', 2,
                1, 0, 'Default System Commission Rule v1.0', NOW()
            )";
            @mysql_query($insertDef);
        }
    }

    /**
     * Scan and process any eligible Credit Voucher collections that have not generated commission
     */
    public static function syncPendingCollections() {
        if (!function_exists('mysql_query')) return 0;

        // Auto-link legacy sales where SalespersonUserID = 0 but SellerID > 0
        @mysql_query("
            UPDATE tblsales 
            SET SalespersonUserID = SellerID 
            WHERE (SalespersonUserID = 0 OR SalespersonUserID IS NULL) AND SellerID > 0
        ");

        // Auto-link Director and Founder chains if missing
        @mysql_query("
            UPDATE tblsales s
            JOIN tbluser sp ON s.SalespersonUserID = sp.UserID
            JOIN tbluser dir ON sp.referred_by = dir.UserID AND dir.user_type = 'director'
            SET s.DirectorUserID = dir.UserID
            WHERE (s.DirectorUserID = 0 OR s.DirectorUserID IS NULL) AND sp.user_type = 'salesperson'
        ");

        @mysql_query("
            UPDATE tblsales s
            JOIN tbluser dir ON s.DirectorUserID = dir.UserID
            JOIN tbluser f ON dir.referred_by = f.UserID AND f.user_type = 'founder'
            SET s.FounderUserID = f.UserID
            WHERE (s.FounderUserID = 0 OR s.FounderUserID IS NULL)
        ");

        // Find posted credit vouchers linked to sales that have missing commission records
        $sql = "
            SELECT c.CrVoucherID, c.SalesID, c.Amount, c.Date, c.UserIDInserted
            FROM tblcrvoucher c
            WHERE (c.CrVoucherIsDisplay = 0 OR c.CrVoucherIsDisplay IS NULL)
              AND c.SalesID > 0
              AND c.Amount > 0
            ORDER BY c.CrVoucherID ASC
        ";
        $res = @mysql_query($sql);
        $count = 0;
        if ($res) {
            while ($row = @mysql_fetch_assoc($res)) {
                $vNo = intval($row['CrVoucherID']);
                $sId = intval($row['SalesID']);
                $amt = floatval($row['Amount']);
                $date = !empty($row['Date']) ? $row['Date'] : date('Y-m-d');
                $uId = intval($row['UserIDInserted']);

                if ($vNo > 0 && $sId > 0 && $amt > 0) {
                    $proc = self::processCollection($vNo, $sId, $amt, $date, $uId);
                    if (!empty($proc['success']) && !empty($proc['records'])) {
                        $count++;
                    }
                }
            }
        }
        return $count;
    }

    /**
     * Check if a user can be safely deleted without violating financial integrity or referral chains.
     * Prevents orphan commission ledgers, vouchers, and broken hierarchy.
     */
    public static function canDeleteUser($userID) {
        $userID = intval($userID);
        if ($userID <= 0) {
            return array('allowed' => false, 'message' => 'অবৈধ ব্যবহারকারী আইডি (Invalid User ID)।');
        }

        // 1. Check commission transactions history
        $txCount = 0;
        $qTx = @mysql_query("SELECT COUNT(*) as total FROM tblcommission_transactions WHERE UserID = {$userID}");
        if ($qTx && ($row = @mysql_fetch_assoc($qTx))) {
            $txCount = intval($row['total']);
        }
        if ($txCount > 0) {
            return array(
                'allowed' => false,
                'message' => "নিরাপত্তা ও অডিট সতর্কতা: এই ব্যবহারকারীর নামে {$txCount}টি কমিশন লেনদেন (Ledger Transactions) বিদ্যমান। ফাইন্যান্সিয়াল ও লিগ্যাল অডিট অখণ্ডতা রক্ষার্থে ব্যবহারকারীকে মুছে ফেলা যাবে না। আপনি চাইলে ব্যবহারকারীকে 'Inactive' (নিষ্ক্রিয়) করতে পারেন।"
            );
        }

        // 2. Check if user is associated with any sales
        $salesCount = 0;
        $qSales = @mysql_query("SELECT COUNT(*) as total FROM tblsales WHERE SalespersonUserID = {$userID} OR DirectorUserID = {$userID} OR FounderUserID = {$userID} OR SellerID = {$userID}");
        if ($qSales && ($row = @mysql_fetch_assoc($qSales))) {
            $salesCount = intval($row['total']);
        }
        if ($salesCount > 0) {
            return array(
                'allowed' => false,
                'message' => "নিরাপত্তা সতর্কতা: এই ব্যবহারকারী {$salesCount}টি বিক্রয় (Sales Records)-এর সাথে বিক্রেতা বা রেফারার হিসেবে যুক্ত। ডেটাবেজ ইন্টিগ্রিটি রক্ষার্থে ব্যবহারকারীকে মুছে ফেলা সম্ভব নয়। অনুগ্রহ করে প্রোফাইল থেকে Inactive করুন।"
            );
        }

        // 3. Check if other active users were referred by this user
        $subCount = 0;
        $qSub = @mysql_query("SELECT COUNT(*) as total FROM tbluser WHERE referred_by = {$userID}");
        if ($qSub && ($row = @mysql_fetch_assoc($qSub))) {
            $subCount = intval($row['total']);
        }
        if ($subCount > 0) {
            return array(
                'allowed' => false,
                'message' => "হায়ারার্কি সতর্কতা: অন্য {$subCount} জন ব্যবহারকারী এই ব্যক্তির রেফারেন্সে রয়েছেন। চেইন ভেঙে যাওয়া রোধে আগে অধীনস্থদের রেফারার পরিবর্তন করুন অথবা এই ব্যবহারকারীকে Inactive করুন।"
            );
        }

        return array('allowed' => true, 'message' => 'ব্যবহারকারী নিরাপদে মুছে ফেলা সম্ভব।');
    }

    /**
     * Safely reset and clean all test commission data.
     * Truncates tblcommission_transactions and resets sales commission statuses.
     */
    public static function resetAllCommissionData() {
        if (!function_exists('mysql_query')) return false;

        // Truncate commission transactions
        @mysql_query("TRUNCATE TABLE tblcommission_transactions");

        // Reset Sales commission status back to pending_collection
        @mysql_query("UPDATE tblsales SET CommissionEligibilityStatus = 'pending_collection'");

        return true;
    }

    /**
     * Generate the next available unique Enroll Number (UserName) for tbluser.
     * Guarantees uniqueness against all existing active and inactive users.
     */
    public static function generateNextEnrollNumber($userType = 'salesperson') {
        $allUsers = SQL_Select("User", "UserName != '' AND UserName IS NOT NULL", "UserID ASC");
        if (!is_array($allUsers)) {
            $allUsers = array();
        }

        $taken = array();
        $maxNumeric = 1000;
        $prefixMax = array();
        $lastPrefix = "";

        foreach ($allUsers as $u) {
            $raw = trim((string)$u["UserName"]);
            if ($raw === '') continue;
            $taken[strtolower($raw)] = true;

            // Pattern 1: Pure numeric, e.g. 1001, 1002, 1003...
            if (preg_match('/^(\d+)$/', $raw, $m)) {
                $numVal = intval($m[1]);
                if ($numVal > $maxNumeric && $numVal < 10000000) {
                    $maxNumeric = $numVal;
                }
            }
            // Pattern 2: Prefix + number, e.g. LL-1001, SL-1001, EMP-101
            elseif (preg_match('/^([A-Za-z0-9_-]*?[A-Za-z_-]+)(\d+)$/', $raw, $m)) {
                $pref = strtoupper($m[1]);
                $numVal = intval($m[2]);
                if (!isset($prefixMax[$pref]) || $numVal > $prefixMax[$pref]) {
                    $prefixMax[$pref] = $numVal;
                }
                $lastPrefix = $pref;
            }
        }

        // If a prefix is used by existing users, continue with that prefix
        if (!empty($prefixMax)) {
            $pref = !empty($lastPrefix) ? $lastPrefix : key($prefixMax);
            $nextNum = $prefixMax[$pref] + 1;
            $candidate = $pref . $nextNum;
            while (isset($taken[strtolower($candidate)])) {
                $nextNum++;
                $candidate = $pref . $nextNum;
            }
            return $candidate;
        }

        // Default to clean numeric sequence: 1001, 1002, 1003...
        $nextNum = $maxNumeric + 1;
        $candidate = (string)$nextNum;
        while (isset($taken[strtolower($candidate)])) {
            $nextNum++;
            $candidate = (string)$nextNum;
        }

        return $candidate;
    }
}

// Automatically ensure tables exist if running within ERP web database context
if (function_exists('mysql_query')) {
    CommissionEngine::ensureTablesExist();
}
