-- SQLite Database Schema for Accounting System

-- 1. Accounts
CREATE TABLE IF NOT EXISTS accounts (
  acct_id INTEGER PRIMARY KEY AUTOINCREMENT,
  acct_code VARCHAR(50) NOT NULL UNIQUE,
  acct_name VARCHAR(150) NOT NULL,
  acct_type VARCHAR(20) NOT NULL CHECK(acct_type IN ('Asset','Liability','Equity','Income','Expense')),
  acct_class VARCHAR(50) DEFAULT NULL,
  acct_alias VARCHAR(100) DEFAULT NULL,
  acct_table_name VARCHAR(100) DEFAULT NULL,
  normal_balance VARCHAR(10) NOT NULL DEFAULT 'Debit' CHECK(normal_balance IN ('Debit','Credit')),
  is_active INTEGER DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL
);

-- 2. Fiscal periods
CREATE TABLE IF NOT EXISTS fiscal_periods (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name VARCHAR(50) NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  is_open INTEGER DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 3. Journal entries (master)
CREATE TABLE IF NOT EXISTS journal_entries (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  entry_no VARCHAR(50) NULL,
  entry_date DATE NOT NULL,
  period_id INTEGER NULL,
  description TEXT,
  reference_no VARCHAR(80) DEFAULT NULL,
  entry_type VARCHAR(30) DEFAULT 'Standard' CHECK(entry_type IN ('Standard','OpeningBalance','Adjustment','Reclassification')),
  status VARCHAR(20) DEFAULT 'Draft' CHECK(status IN ('Draft','Pending','Posted','Cancelled')),
  created_by VARCHAR(100) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  posted_by VARCHAR(100) DEFAULT NULL,
  posted_at DATETIME NULL,
  approved_by VARCHAR(100) DEFAULT NULL,
  approved_at DATETIME NULL,
  audit_info TEXT DEFAULT NULL,
  FOREIGN KEY (period_id) REFERENCES fiscal_periods(id)
);

-- 4. Journal lines (debit/credit detail)
CREATE TABLE IF NOT EXISTS journal_lines (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  journal_entry_id INTEGER NOT NULL,
  line_no INTEGER NOT NULL,
  acct_id INTEGER NOT NULL,
  debit DECIMAL(18,2) DEFAULT 0,
  credit DECIMAL(18,2) DEFAULT 0,
  narrative VARCHAR(255) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id) ON DELETE CASCADE,
  FOREIGN KEY (acct_id) REFERENCES accounts(acct_id)
);

-- 5. Opening balances
CREATE TABLE IF NOT EXISTS opening_balances (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  acct_id INTEGER NOT NULL,
  period_id INTEGER NOT NULL,
  amount DECIMAL(18,2) NOT NULL,
  side VARCHAR(10) NOT NULL CHECK(side IN ('Debit','Credit')),
  created_by VARCHAR(100) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (acct_id) REFERENCES accounts(acct_id),
  FOREIGN KEY (period_id) REFERENCES fiscal_periods(id)
);

-- 6. Audit log
CREATE TABLE IF NOT EXISTS audit_log (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  entity VARCHAR(100),
  entity_id VARCHAR(100),
  action VARCHAR(50),
  payload TEXT,
  user_name VARCHAR(100),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 7. Legacy table for existing transactions (account_general_transaction_new)
CREATE TABLE IF NOT EXISTS account_general_transaction_new (
  remit_id INTEGER PRIMARY KEY AUTOINCREMENT,
  date_of_payment DATE NOT NULL,
  transaction_desc TEXT,
  receipt_no VARCHAR(100),
  shop_no VARCHAR(100),
  remitting_customer VARCHAR(200),
  debit_account INTEGER,
  credit_account INTEGER,
  amount_paid DECIMAL(18,2) DEFAULT 0,
  approval_status VARCHAR(20) DEFAULT 'Pending' CHECK(approval_status IN ('Pending','Approved','Rejected')),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (debit_account) REFERENCES accounts(acct_id),
  FOREIGN KEY (credit_account) REFERENCES accounts(acct_id)
);

-- Insert sample fiscal period
INSERT INTO fiscal_periods (name, start_date, end_date, is_open) 
VALUES ('FY2025-Jan', '2025-01-01', '2025-01-31', 1);

-- Insert sample Chart of Accounts
INSERT INTO accounts (acct_code, acct_name, acct_type, acct_class, acct_alias, normal_balance) VALUES
('1000', 'Assets', 'Asset', 'Current Assets', 'Assets', 'Debit'),
('1010', 'Cash at Bank', 'Asset', 'Current Assets', 'Cash', 'Debit'),
('1020', 'Accounts Receivable', 'Asset', 'Current Assets', 'A/R', 'Debit'),
('1030', 'Inventory', 'Asset', 'Current Assets', 'Inventory', 'Debit'),
('1100', 'Fixed Assets', 'Asset', 'Non-Current Assets', 'Fixed Assets', 'Debit'),
('1110', 'Equipment', 'Asset', 'Non-Current Assets', 'Equipment', 'Debit'),

('2000', 'Liabilities', 'Liability', 'Current Liabilities', 'Liabilities', 'Credit'),
('2010', 'Accounts Payable', 'Liability', 'Current Liabilities', 'A/P', 'Credit'),
('2020', 'Short-term Loans', 'Liability', 'Current Liabilities', 'Loans', 'Credit'),

('3000', 'Equity', 'Equity', 'Shareholders Equity', 'Equity', 'Credit'),
('3010', 'Share Capital', 'Equity', 'Shareholders Equity', 'Capital', 'Credit'),
('3020', 'Retained Earnings', 'Equity', 'Shareholders Equity', 'Retained', 'Credit'),

('4000', 'Revenue', 'Income', 'Operating Revenue', 'Revenue', 'Credit'),
('4010', 'Sales Revenue', 'Income', 'Operating Revenue', 'Sales', 'Credit'),
('4020', 'Service Revenue', 'Income', 'Operating Revenue', 'Services', 'Credit'),

('5000', 'Expenses', 'Expense', 'Operating Expenses', 'Expenses', 'Debit'),
('5010', 'Cost of Goods Sold', 'Expense', 'Operating Expenses', 'COGS', 'Debit'),
('5020', 'Salaries Expense', 'Expense', 'Operating Expenses', 'Salaries', 'Debit'),
('5030', 'Rent Expense', 'Expense', 'Operating Expenses', 'Rent', 'Debit'),
('5040', 'Utilities Expense', 'Expense', 'Operating Expenses', 'Utilities', 'Debit');

-- Insert sample transactions
INSERT INTO account_general_transaction_new (date_of_payment, transaction_desc, receipt_no, debit_account, credit_account, amount_paid, approval_status)
VALUES
('2025-01-02', 'Initial capital investment', 'REC001', 1010, 3010, 50000.00, 'Approved'),
('2025-01-05', 'Purchase of equipment', 'REC002', 1110, 1010, 15000.00, 'Approved'),
('2025-01-10', 'Sales revenue - Invoice #101', 'INV101', 1010, 4010, 5000.00, 'Approved'),
('2025-01-15', 'Rent payment for January', 'REC003', 5030, 1010, 2000.00, 'Approved'),
('2025-01-20', 'Utilities payment', 'REC004', 5040, 1010, 500.00, 'Approved'),
('2025-01-25', 'Service revenue - Invoice #102', 'INV102', 1010, 4020, 3000.00, 'Approved'),
('2025-01-28', 'Salary payment', 'REC005', 5020, 1010, 4000.00, 'Approved');
