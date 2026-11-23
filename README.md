# LifeLine Hospital ERP System (RealBiz ERP V9.1)

Enterprise Resource Planning (ERP) platform designed for **LifeLine Hospital & Real Estate Operations**. Built on a modular Fusebox architecture with PHP 5.6+ backward compatibility, MySQL 5.6+, and a responsive Bootstrap 5 theme.

---

## 📌 Table of Contents
1. [Overview](#-overview)
2. [Technology Stack](#-technology-stack)
3. [System Architecture](#-system-architecture)
4. [User Roles & Security Scopes](#-user-roles--security-scopes)
5. [Commission Engine](#-commission-engine)
6. [Core Modules Map](#-core-modules-map)
7. [Installation & Setup](#-installation--setup)
8. [Documentation Links](#-documentation-links)

---

## 🏥 Overview
LifeLine Hospital ERP combines hospital management, real estate property sales, share ownership, financial accounting, payment schedules, contractor billing, HRM, and dynamic multi-tier commission & referral hierarchy.

### Key Capabilities
- **Customer & Lead Management**: Customer profiles, KYC (NID, nominee), auto-generated customer file numbers (`LLH-YYYY/ID`).
- **Property & Share Sales**: Apartment, shop, land share, and hotel share booking, installments, and payment schedules.
- **Voucher & Accounting Ledger**: Credit vouchers (`CrVoucher`), Debit vouchers (`DrVoucher`), Journal vouchers (`JournalVoucher`), double-entry balance sheets, and daily receipts/payments.
- **Automated Money Receipts**: Print-ready 148mm x 210mm receipts with QR code login, bank info, dynamic file numbers, and digital authorized signature integration.
- **Multi-Tier Dynamic Commission**: Salesperson achievement targets, Director team referral commissions, Founder pool sharing, and an immutable transaction ledger.
- **Strict Role-Based Data Isolation**: Granular controls allowing salespersons and directors to view only their own records, with global toggle controls for administrators.

---

## 🛠 Technology Stack
- **Server Language**: PHP 5.6+ (compatible with 7.x and 8.x; strictly avoids non-backwards-compatible syntax like `??` or typed properties)
- **Database**: MySQL 5.6+ / MariaDB 10.x (InnoDB engine with UTF-8 character encoding)
- **Frontend & UI**: HTML5, Vanilla JavaScript, jQuery, Bootstrap 5.3, FontAwesome 6, Inter typography
- **Document & PDF Generation**: HTML2Canvas, JSPDF, HTML2PDF, QRCodeJS
- **Deployment**: Git, GitHub Actions, FTP/SFTP automated synchronization

---

## 🏛 System Architecture
The platform utilizes a **Modular Fusebox Request Lifecycle**:
```
Request -> index.php?Theme={theme}&Base={Module}&Script={Action}
            │
            ├── System Authentication & Session Check (SessionSetUser)
            ├── Permission Gatekeeper (GetPermission)
            ├── Scriptvariables.php (Entity and DB definitions)
            ├── Action Script (Manage, Insertupdate, Insertupdateaction, etc.)
            │    └── Data Layer: SQL_Select, SQL_InsertUpdate, SQL_Delete
            └── Theme Wrapper (BeforeMainContent + MainContent + AfterMainContent)
```

For comprehensive details on the lifecycle, controllers, and database abstraction, refer to [ARCHITECTURE.md](ARCHITECTURE.md).

---

## 🔐 User Roles & Security Scopes
The platform enforces strict multi-tenant isolation at the query level:
- **SuperAdmin (`UserID = 1` or `admin`)**: Complete system access, unconstrained global views, user creation, rule configuration.
- **Founder (`user_type = 'founder'`)**: High-level reporting, automated 2% Founder Pool distribution, referral head.
- **Director (`user_type = 'director'`)**:
  - Customer List: Only customers belonging to this Director and their referred Salesperson team.
  - Sales List: Direct sales + sales executed by their referral tree.
  - Commission: Personal commissions earned from direct sales (13%) and referral sales (3%).
- **Salesperson / Sales Executive (`user_type = 'salesperson'`)**:
  - Customer List: Only customers created by or sold to by this salesperson.
  - Sales List: Only their own sales transactions.
  - Commission: Strictly personal earnings (`UserID = loggedInUserID`), target achievement ratios.
- **Admin Scope Controls**: In **Settings > User Management**, Admins can toggle any user between **Own / Team Scope (`0`)** and **Global Scope (`1`)** at will.

Full matrix and implementation details are documented in [PERMISSIONS_AND_ROLES.md](PERMISSIONS_AND_ROLES.md).

---

## 💰 Commission Engine
LifeLine Hospital incorporates an automated, audit-ready financial commission engine:
- **Salesperson Commission**: Up to 10% (scaled by monthly sales target formula `MIN(Actual / Target, 1.0)`).
- **Director Direct Sale**: 13% for sales conducted directly by a Director.
- **Director Referral**: 3% override commission on sales made by referred Salespersons.
- **Founder Pool**: 2% divided equally or proportionally among all active Founders.
- **Transaction Ledger**: `tblcommission_transactions` stores credit, debit (payout), and reversal records with UUID references.

Full mathematical breakdown and lifecycle are available in [COMMISSION_SYSTEM.md](COMMISSION_SYSTEM.md).

---

## 📂 Core Modules Map

| Directory | Module Function | Primary Tables |
| :--- | :--- | :--- |
| `Customer/` | Patient & Buyer customer management | `tblcustomer`, `tblcrm` |
| `Sales/` | Booking, apartment/shop sales, contracts | `tblsales`, `tblproducts` |
| `CrVoucher/` | Credit voucher entry, Money Receipt generation | `tblcrvoucher`, `tblactualsalsepayment` |
| `DrVoucher/` | Debit vouchers, operational expenses | `tbldrvoucher`, `tblexpensehead` |
| `Commission/` | Multi-tier commission calculation & payout suite | `tblcommission_transactions`, `tblcommission_rules` |
| `SchedulePayment/`| EMI schedule, customer statement printing | `tblschedulepayment`, `tblactualsalsepayment` |
| `Custom/` | Enterprise Security Scope & Data Isolation logic | `SecurityScope.php` |
| `System/` | Authentication, login, signup, user profiles | `tbluser`, `tblsettings` |
| `Users/` | Role permissions, referral tree, scopes | `tbluser` |
| `Transaction/` | Accounting balance sheet, profit & loss, cash flow| `tbltransaction`, `tblbankcash` |

For an exhaustive directory reference, see [MODULE_DIRECTORY.md](MODULE_DIRECTORY.md).

---

## 🚀 Installation & Setup

1. **Clone the Repository**:
   ```bash
   git clone https://github.com/NIBIZ-SOFT/LifeLineHospital.git
   cd LifeLineHospital
   ```

2. **Web Server Configuration**:
   - Point your Apache/Nginx web server document root to the project folder or `/public_html/`.
   - Ensure `upload/` directory is writable (`chmod 775 upload`).
   - PHP version required: 5.6.x to 8.x.

3. **Database Setup**:
   - Create a MySQL database (e.g. `lifeline_erp`).
   - Import the database migration scripts:
     ```bash
     mysql -u db_user -p lifeline_erp < database_migration.sql
     ```
   - Automated self-healing: Key tables and isolation columns (`OptionCustomerScope`, `OptionSalesScope`, `OptionCommissionScope`, `CreatedByUserID`) are automatically checked and provisioned by `SecurityScope::ensureTables()`.

4. **Configuration**:
   - Update database credentials in your server environment or database connector file.
   - Configure company profile, hotline, logos, and branches in **Settings**.

---

## 📚 Documentation Links
- [System Architecture Guide (ARCHITECTURE.md)](ARCHITECTURE.md)
- [Permissions, Roles & Security Scopes (PERMISSIONS_AND_ROLES.md)](PERMISSIONS_AND_ROLES.md)
- [Commission Engine Specification (COMMISSION_SYSTEM.md)](COMMISSION_SYSTEM.md)
- [Module Directory Reference (MODULE_DIRECTORY.md)](MODULE_DIRECTORY.md)
- [Database Schema Reference (DATABASE_SCHEMA.md)](DATABASE_SCHEMA.md)

---
*Maintained by NIBIZ SOFT Technical Team for LifeLine Hospital.*
