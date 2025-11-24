-- 1. Accounts (extend your existing accounts table or use this instead)
CREATE TABLE IF NOT EXISTS accounts (
  acct_id INT AUTO_INCREMENT PRIMARY KEY,
  acct_code VARCHAR(50) NOT NULL,         -- human readable code, e.g. 1010
  acct_name VARCHAR(150) NOT NULL,        -- e.g. Cash at Bank
  acct_type ENUM('Asset','Liability','Equity','Income','Expense') NOT NULL,
  acct_class VARCHAR(50) DEFAULT NULL,    -- optional classification
  acct_alias VARCHAR(100) DEFAULT NULL,
  acct_table_name VARCHAR(100) DEFAULT NULL, -- existing legacy mapping
  normal_balance ENUM('Debit','Credit') NOT NULL DEFAULT 'Debit',
  is_active TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY (acct_code)
);

-- 2. Fiscal periods
CREATE TABLE IF NOT EXISTS fiscal_periods (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL,   -- e.g. 'FY2025-Jan'
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  is_open TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 3. Journal entries (master)
CREATE TABLE IF NOT EXISTS journal_entries (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  entry_no VARCHAR(50) NULL,        -- optional human ref
  entry_date DATE NOT NULL,
  period_id INT NULL,
  description TEXT,
  reference_no VARCHAR(80) DEFAULT NULL,
  entry_type ENUM('Standard','OpeningBalance','Adjustment','Reclassification') DEFAULT 'Standard',
  status ENUM('Draft','Pending','Posted','Cancelled') DEFAULT 'Draft',
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
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  journal_entry_id BIGINT NOT NULL,
  line_no INT NOT NULL,
  acct_id INT NOT NULL,
  debit DECIMAL(18,2) DEFAULT 0,
  credit DECIMAL(18,2) DEFAULT 0,
  narrative VARCHAR(255) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id) ON DELETE CASCADE,
  FOREIGN KEY (acct_id) REFERENCES accounts(acct_id)
);

-- 5. Opening balances (optionally stored separately; we will also create opening journal entries)
CREATE TABLE IF NOT EXISTS opening_balances (
  id INT AUTO_INCREMENT PRIMARY KEY,
  acct_id INT NOT NULL,
  period_id INT NOT NULL,
  amount DECIMAL(18,2) NOT NULL,  -- positive amount
  side ENUM('Debit','Credit') NOT NULL,
  created_by VARCHAR(100) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ob_acct FOREIGN KEY (acct_id) REFERENCES accounts(acct_id),
  CONSTRAINT fk_ob_period FOREIGN KEY (period_id) REFERENCES fiscal_periods(id)
);

-- 6. Audit log (general purpose)
CREATE TABLE IF NOT EXISTS audit_log (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  entity VARCHAR(100),
  entity_id VARCHAR(100),
  action VARCHAR(50),
  payload TEXT,
  user_name VARCHAR(100),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
