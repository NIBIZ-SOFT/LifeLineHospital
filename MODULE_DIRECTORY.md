# LifeLine Hospital ERP — Module Directory Map

An exhaustive reference manual detailing every functional module directory in the repository, explaining its purpose, core tables, key files, and architectural flows.

---

## 📂 Core Business Modules

### 1. `Customer/` — Customer & Lead Management
- **Purpose**: Manages patient and real estate buyer profiles, KYC information, nominees, and auto-generated customer file numbers (`LLH-YYYY/ID`).
- **Tables**: `tblcustomer`, `tblcrm`
- **Key Files**:
  - `Manage.php`: Datagrid list with search by name, phone, NID, customer number. Enforces `SecurityScope::getCustomerWhereCondition()`.
  - `Insertupdate.php`: Multi-column customer creation form with locked auto-generated Customer No display.
  - `Insertupdateaction.php`: Auto-generates `LLH-YYYY/ID` on insert; binds `CreatedByUserID`.
  - `import.php`: Excel/CSV bulk lead and customer import handler.

---

### 2. `Sales/` — Property, Shop & Share Sales
- **Purpose**: Handles apartment bookings, commercial shops, land shares, and hospital shares. Links products, customers, and sellers.
- **Tables**: `tblsales`, `tblproducts`, `tblcustomer`, `tbluser`
- **Key Files**:
  - `Manage.php`: Sales contracts datagrid. Enforces `SecurityScope::getSalesWhereCondition()`.
  - `Insertupdate.php`: Contract pricing, discount, installment plan selection, seller dropdown categorized by Salesperson, Director, and Founder.
  - `Insertupdateaction.php`: Records sale, preserves referral chain snapshot (`SalespersonUserID`, `DirectorUserID`, `FounderUserID`), and triggers commission eligibility.
  - `AjaxRequest.php`: Dynamic product size, unit price, and total computation endpoints.

---

### 3. `CrVoucher/` — Credit Vouchers & Money Receipts
- **Purpose**: Payment collection entry against sales installments, booking money, or miscellaneous receipts.
- **Tables**: `tblcrvoucher`, `tblactualsalsepayment`, `tblsales`
- **Key Files**:
  - `Manage.php`: List of credit vouchers with quick print links.
  - `MoneyReceipt.php`: Official printable 210mm x 148mm customer receipt with auto-scaling layout, dynamic QR code, digital authorized signature, and direct WhatsApp PDF sharing.
  - `Insertupdateaction.php`: Saves voucher, updates customer party ledger, and records actual sales payments.
  - `signature.png`: High-resolution transparent authorized signature asset.

---

### 4. `Commission/` — Multi-Tier Commission Suite
- **Purpose**: Financial commission engine, target scaling, payout disbursement, and audit reports.
- **Tables**: `tblcommission_transactions`, `tblcommission_rules`, `tbluser`
- **Key Files**:
  - `CommissionEngine.php`: Master calculation engine, referral tree validator, and auto-migration runner.
  - `Manage.php`: Transaction ledger. Restricted to personal records for salespersons/directors without global scope.
  - `Dashboard.php`: High-level metrics, founder pool status, monthly target leaderboard.
  - `UserDetails.php`: Agent performance card, earned rate, sales history, balance summary.
  - `Pay.php` & `PayAction.php`: Commission payout disbursement interface and bank accounting integration.
  - `Reports.php`: Filterable date-range reports for agent earnings and payouts.
  - `Migration.php`: Database schema synchronization.

---

### 5. `SchedulePayment/` — Payment Schedules & Customer Statements
- **Purpose**: Manages installment schedules, overdue payment alerts, and full customer financial statements.
- **Tables**: `tblschedulepayment`, `tblactualsalsepayment`, `tblsales`
- **Key Files**:
  - `Manage.php`: Schedule list and collection reconciliation.
  - `Print.php`: Comprehensive Customer Statement & EMI Sheet with flat/property breakdown, payments collected, due balances, and file number (`LLH-YYYY/CustomerID`).
  - `MoneyReceipt.php`: Schedule installment receipt view.
  - `Delaypaymnetletter.php` & `Reminderletter.php`: Customer notice generator.

---

### 6. `DrVoucher/` — Debit Vouchers & Expenditure
- **Purpose**: Office and hospital operational expenditure, supplier payments, and advance settlement.
- **Tables**: `tbldrvoucher`, `tblexpensehead`, `tblbankcash`
- **Key Files**:
  - `Manage.php`: Debit voucher audit list.
  - `Voucher.php`: Printable voucher slip.
  - `Insertupdateaction.php`: Deducts funds from cash/bank ledgers and posts to expense heads.

---

### 7. `Transaction/` — Financial Accounting & Reporting
- **Purpose**: Core accounting engine: Trial Balance, Balance Sheet, Profit & Loss, Daily Receipts & Payments, and General Ledgers.
- **Tables**: `tbltransaction`, `tblbankcash`, `tblexpensehead`
- **Key Files**:
  - `BalanceSheet.php`: Assets vs Liabilities balance sheet.
  - `ProfitLoss.php`: Income and expenditure profit/loss account.
  - `DailyRP.php`: Daily receipt and payment report.
  - `CustomerPartyLedger.php`: Complete customer financial ledger.
  - `TB.php`: Trial balance computation.

---

### 8. `Users/` — Staff Accounts & Access Management
- **Purpose**: User creation, password assignment, role selection (Founder, Director, Salesperson, General), and granular permission editing.
- **Tables**: `tbluser`
- **Key Files**:
  - `Manage.php`: List of staff accounts, enroll numbers, and roles.
  - `Insertupdate.php`: User form with referral dropdown, target input, module permission checkboxes, and Customer/Sales/Commission scope radio selectors (`Own` vs `Global`).
  - `Insertupdateaction.php`: Enforces uniqueness on Enroll Number (`UserName`), validates referral tree, and persists scopes.

---

### 9. `Custom/` — Core Enterprise Security
- **Purpose**: Global data scope gatekeeper, security policies, and query interceptors.
- **Key Files**:
  - `SecurityScope.php`: Implements `getCustomerWhereCondition()`, `getSalesWhereCondition()`, and `getCommissionWhereCondition()`.

---

## 🏢 Operations & Support Modules

| Folder | Function |
| :--- | :--- |
| `Attendance/` | Staff daily attendance logging, biometric device integration, and reports. |
| `Salary/` | Payroll processing, monthly salary calculation, deductions, and pay slips. |
| `Leave/` | Employee leave applications, approval workflows, and leave quotas. |
| `BOQ/` | Bill of Quantities estimation, project construction costs, materials, and categories. |
| `Products/` | Inventory of apartments, commercial units, and hotel/land shares. |
| `Category/` | Construction projects and property developments. |
| `ProjectLocation/` | Project geographic locations, districts, and zones. |
| `BankCash/` | Bank accounts, branch details, and cash-in-hand accounts. |
| `ChequeManager/` | Cheque tracking (cleared, bounced, in-transit). |
| `CRM/` | Lead pipeline, inquiry logs, follow-up scheduling, and conversion status. |
| `ContractorBill/` | Construction contractor work orders, measurement sheets, and running bills. |
| `Purchase/` | Hospital and construction material procurement, requisitions, and orders. |
| `Stock/` | Warehouse inventory, materials receipt, issuance, and stock adjustments. |
| `Settings/` | Company profile, hospital address, hotlines, logos, and global configurations. |
| `SMS/` & `SendSMS/` | SMS gateway integration for customer payment alerts and OTPs. |
| `System/` | Authentication controllers, session handlers, profile updates, and error handlers. |
| `theme/` | Active layout templates (`default/template/header.php`, CSS, JS, assets). |

---
*Maintained by NIBIZ SOFT Technical Team.*
