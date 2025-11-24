# Accounting System - Replit Project

## Overview
This is a PHP-based accounting system that provides professional financial management tools including General Ledger, Trial Balance, IFRS Financial Statements, Period Closing functionality, and a three-level transaction posting approval workflow. The system is configured to run in the Replit environment using SQLite as the database backend.

## Project Structure
```
├── admin/              # UI layout and navigation
├── controllers/        # MVC Controllers (Ledger, TrialBalance, Financials, Closing, Posting)
├── models/             # Data models (Ledger, TrialBalance, Financial, Closing, Posting)
├── views/              # View templates organized by feature
├── posting/            # Transaction posting workflow module
│   ├── views/          # Staff review, FC approval, Audit approval, result pages
│   └── models/         # Posting approval logic
├── helpers/            # Helper classes (DB singleton)
├── includes/           # Legacy database connection
├── data/               # SQLite database storage (not in git)
├── pages/              # Legacy standalone pages
└── index.php           # Main entry point
```

## Key Features
1. **General Ledger** - Complete transaction listing with filtering and sorting
2. **Trial Balance** - Summary of all account balances with debit/credit verification
3. **IFRS Financial Statements** - 4-page professional financial reporting module
4. **Period Closing & Opening** - Automated fiscal period management with opening balances
5. **Transaction Posting Workflow** - Three-level approval system:
   - Level 1: Accounting Staff Review (verify transaction details)
   - Level 2: FC Head Approval (authorize posting, check budgets)
   - Level 3: Audit Approval (compliance check, post to ledger)

## Technology Stack
- **Language**: PHP 8.2
- **Database**: SQLite (converted from MySQL for Replit compatibility)
- **Frontend**: Tailwind CSS (via CDN)
- **Server**: PHP Built-in Server
- **Port**: 5000

## Database
The application uses SQLite at `/home/runner/workspace/data/accounting.db`.

### Schema includes:
- `accounts` - Chart of accounts with classifications
- `account_general_transaction_new` - All transactions with approval status
- `fiscal_periods` - Fiscal period management
- `journal_entries` - Journal entry headers
- `journal_lines` - Journal entry debit/credit details
- `opening_balances` - Period opening balances
- `audit_log` - Comprehensive audit trail for all transactions

### Sample Data
Pre-populated database includes:
- 20 accounts across Asset, Liability, Equity, Income, and Expense categories
- 7 sample transactions at various approval stages

## Transaction Posting Workflow
Transactions flow through a controlled approval process:

1. **Pending** → Accounting Staff reviews transaction details, verifies account codes and amounts
2. **Staff-Approved** → FC Head reviews for budget compliance and authorization
3. **FC-Approved** → Audit Department conducts compliance check and posts to ledger
4. **Approved** → Transaction is finalized in General Ledger, all actions logged to audit trail

All actions are tracked in the audit_log for compliance and traceability.

## Setup & Installation
The project is pre-configured and ready to run. The database initializes automatically with sample data.

To reset the database:
```bash
php setup.php
```

## Running the Application
The application automatically starts on port 5000:
```bash
php -S 0.0.0.0:5000
```

Access via the web interface with navigation for:
- General Ledger
- Trial Balance
- IFRS Financials
- Closing & Opening
- Transaction Posting

## Recent Changes (November 21, 2025)

### Transaction Posting Workflow (NEW)
- Created `/posting` folder with complete three-level approval system
- Added `PostingController.php` for role-based routing (staff, fc_head, audit)
- Created `PostingModel.php` for approval logic and audit trail logging
- Built role-specific views: staff_review.php, fc_approval.php, audit_approval.php
- Integrated with main navigation in sidebar

### IFRS Financial Statements Module
- Created views/ifrs/ directory with 4 professional financial pages
- Implemented Chart of Accounts summary with account hierarchy
- Added Ledger View for detailed account transactions
- Built Trial Balance report with balance verification
- Created Financial Statements placeholder for future enhancements
- Fixed column name mappings: acct_class, transaction_desc, remit_id

### General Closing & Opening
- Created complete Period Closing UI in views/closing/
- Implemented closing model with opening balance generation
- Added result confirmation page

### Database & Models
- Converted database from MySQL to SQLite for Replit compatibility
- Updated all models to use DB singleton from helpers/DB.php
- Fixed SQL syntax for SQLite (LIMIT/OFFSET, JOIN parenthesization, GROUP BY)
- Ensured proper column name usage across all queries

## Deployment
The application is configured for deployment on Replit. Use the deployment configuration to set up production deployment when ready.

## Future Enhancements
Based on the system architecture, potential improvements include:
1. Role-based user authentication and session management
2. Enhanced journal entry tracking with more metadata
3. Advanced opening balances management and period analysis
4. Detailed account hierarchy and cost center allocation
5. Custom financial report generation
6. Multi-currency support
7. Improved account classification system

## Known Limitations
- Using Tailwind CSS via CDN (not recommended for production)
- Some legacy pages exist alongside the MVC structure
- Database path is hardcoded (can be made configurable)
- Session management is basic (for demo purposes)

## Support
For issues about this Replit setup, refer to the code comments or consult the GitHub repository from which this was imported.

Last Updated: November 21, 2025
