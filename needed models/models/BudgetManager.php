<?php
//require_once 'Database.php';
require_once 'PHPExcel/Classes/PHPExcel.php';
class BudgetManager {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Check user access permissions
     */
    public function checkAccess($level, $permission) {
        $this->db->query("
            SELECT {$permission} as has_permission
            FROM budget_access_control 
            WHERE level = :level
        ");
        $this->db->bind(':level', $level);
        $result = $this->db->single();
        
        return $result && $result['has_permission'] === 'Yes';
    }
    // public function checkAccess($user_id, $permission) {
    //     $this->db->query("
    //         SELECT {$permission} as has_permission
    //         FROM budget_access_control 
    //         WHERE user_id = :user_id
    //     ");
    //     $this->db->bind(':user_id', $user_id);
    //     $result = $this->db->single();
        
    //     return $result && $result['has_permission'] === 'Yes';
    // }
    
    /**
     * Get all active income lines for budget setup
     */
    public function getActiveIncomeLines() {
        $this->db->query("
            SELECT acct_id, acct_desc 
            FROM accounts 
            WHERE active = 'Yes' AND income_line = 'Yes'
            ORDER BY acct_desc ASC
        ");
        return $this->db->resultSet();
    }
    
    /**
     * Get budget lines for a specific year
     */
    public function getBudgetLines($year) {
        $this->db->query("
            SELECT bl.*, s.full_name as created_by_name, su.full_name as updated_by_name
            FROM budget_lines bl
            LEFT JOIN staffs s ON bl.created_by = s.user_id
            LEFT JOIN staffs su ON bl.updated_by = su.user_id
            WHERE bl.budget_year = :year
            ORDER BY bl.acct_desc ASC
        ");
        $this->db->bind(':year', $year);
        return $this->db->resultSet();
    }
    
    /**
     * Get specific budget line
     */
    public function getBudgetLine($id) {
        $this->db->query("
            SELECT * FROM budget_lines 
            WHERE id = :id
        ");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }
    
    /**
     * Create or update budget line
     */
    // public function saveBudgetLine($data) {
    //     $this->db->beginTransaction();
        
    //     try {
    //         // Calculate annual budget
    //         $annual_budget = $data['january_budget'] + $data['february_budget'] + $data['march_budget'] + 
    //                        $data['april_budget'] + $data['may_budget'] + $data['june_budget'] + 
    //                        $data['july_budget'] + $data['august_budget'] + $data['september_budget'] + 
    //                        $data['october_budget'] + $data['november_budget'] + $data['december_budget'];
            
    //         if (isset($data['id']) && $data['id']) {
    //             // Update existing budget
    //             $this->db->query("
    //                 UPDATE budget_lines SET
    //                     acct_id = :acct_id,
    //                     acct_desc = :acct_desc,
    //                     budget_year = :budget_year,
    //                     january_budget = :january_budget,
    //                     february_budget = :february_budget,
    //                     march_budget = :march_budget,
    //                     april_budget = :april_budget,
    //                     may_budget = :may_budget,
    //                     june_budget = :june_budget,
    //                     july_budget = :july_budget,
    //                     august_budget = :august_budget,
    //                     september_budget = :september_budget,
    //                     october_budget = :october_budget,
    //                     november_budget = :november_budget,
    //                     december_budget = :december_budget,
    //                     annual_budget = :annual_budget,
    //                     status = :status,
    //                     updated_by = :updated_by
    //                 WHERE id = :id
    //             ");
    //             $this->db->bind(':id', $data['id']);
    //             $this->db->bind(':updated_by', $data['user_id']);
    //         } else {
    //             // Create new budget
    //             $this->db->query("
    //                 INSERT INTO budget_lines (
    //                     acct_id, acct_desc, budget_year,
    //                     january_budget, february_budget, march_budget, april_budget,
    //                     may_budget, june_budget, july_budget, august_budget,
    //                     september_budget, october_budget, november_budget, december_budget,
    //                     annual_budget, status, created_by
    //                     status, created_by
    //                 ) VALUES (
    //                     :acct_id, :acct_desc, :budget_year,
    //                     :january_budget, :february_budget, :march_budget, :april_budget,
    //                     :may_budget, :june_budget, :july_budget, :august_budget,
    //                     :september_budget, :october_budget, :november_budget, :december_budget,
    //                     :annual_budget, :status, :created_by
    //                     :status, :created_by
    //                 )
    //             ");
    //             $this->db->bind(':created_by', $data['user_id']);
    //         }
            
    //         // Bind common parameters
    //         $this->db->bind(':acct_id', $data['acct_id']);
    //         $this->db->bind(':acct_desc', $data['acct_desc']);
    //         $this->db->bind(':budget_year', $data['budget_year']);
    //         $this->db->bind(':january_budget', $data['january_budget']);
    //         $this->db->bind(':february_budget', $data['february_budget']);
    //         $this->db->bind(':march_budget', $data['march_budget']);
    //         $this->db->bind(':april_budget', $data['april_budget']);
    //         $this->db->bind(':may_budget', $data['may_budget']);
    //         $this->db->bind(':june_budget', $data['june_budget']);
    //         $this->db->bind(':july_budget', $data['july_budget']);
    //         $this->db->bind(':august_budget', $data['august_budget']);
    //         $this->db->bind(':september_budget', $data['september_budget']);
    //         $this->db->bind(':october_budget', $data['october_budget']);
    //         $this->db->bind(':november_budget', $data['november_budget']);
    //         $this->db->bind(':december_budget', $data['december_budget']);
    //         $this->db->bind(':annual_budget', $annual_budget);
    //         $this->db->bind(':status', $data['status']);
            
    //         $this->db->execute();
    //         $this->db->endTransaction();
            
    //         return ['success' => true, 'message' => 'Budget saved successfully!'];
            
    //     } catch (Exception $e) {
    //         $this->db->cancelTransaction();
    //         return ['success' => false, 'message' => 'Error saving budget: ' . $e->getMessage()];
    //     }
    // }
    public function saveBudgetLine($data, $useTransaction = true) {
        if ($useTransaction) {
            $this->db->beginTransaction();
        }

        try {
            // Calculate annual budget
            $annual_budget =
                $data['january_budget'] + $data['february_budget'] + $data['march_budget'] +
                $data['april_budget'] + $data['may_budget'] + $data['june_budget'] +
                $data['july_budget'] + $data['august_budget'] + $data['september_budget'] +
                $data['october_budget'] + $data['november_budget'] + $data['december_budget'];

            if (!empty($data['id'])) {
                // Update existing budget
                $this->db->query("
                    UPDATE budget_lines SET
                        acct_id = :acct_id,
                        acct_desc = :acct_desc,
                        budget_year = :budget_year,
                        january_budget = :january_budget,
                        february_budget = :february_budget,
                        march_budget = :march_budget,
                        april_budget = :april_budget,
                        may_budget = :may_budget,
                        june_budget = :june_budget,
                        july_budget = :july_budget,
                        august_budget = :august_budget,
                        september_budget = :september_budget,
                        october_budget = :october_budget,
                        november_budget = :november_budget,
                        december_budget = :december_budget,
                        annual_budget = :annual_budget,
                        status = :status,
                        updated_by = :updated_by
                    WHERE id = :id
                ");
                $this->db->bind(':id', $data['id']);
                $this->db->bind(':updated_by', $data['user_id']);
            } else {
                // Create new budget
                $this->db->query("
                    INSERT INTO budget_lines (
                        acct_id, acct_desc, budget_year,
                        january_budget, february_budget, march_budget, april_budget,
                        may_budget, june_budget, july_budget, august_budget,
                        september_budget, october_budget, november_budget, december_budget,
                        annual_budget, status, created_by
                    ) VALUES (
                        :acct_id, :acct_desc, :budget_year,
                        :january_budget, :february_budget, :march_budget, :april_budget,
                        :may_budget, :june_budget, :july_budget, :august_budget,
                        :september_budget, :october_budget, :november_budget, :december_budget,
                        :annual_budget, :status, :created_by
                    )
                ");
                $this->db->bind(':created_by', $data['user_id']);
            }

            // Bind common parameters
            $this->db->bind(':acct_id', $data['acct_id']);
            $this->db->bind(':acct_desc', $data['acct_desc']);
            $this->db->bind(':budget_year', $data['budget_year']);
            $this->db->bind(':january_budget', $data['january_budget']);
            $this->db->bind(':february_budget', $data['february_budget']);
            $this->db->bind(':march_budget', $data['march_budget']);
            $this->db->bind(':april_budget', $data['april_budget']);
            $this->db->bind(':may_budget', $data['may_budget']);
            $this->db->bind(':june_budget', $data['june_budget']);
            $this->db->bind(':july_budget', $data['july_budget']);
            $this->db->bind(':august_budget', $data['august_budget']);
            $this->db->bind(':september_budget', $data['september_budget']);
            $this->db->bind(':october_budget', $data['october_budget']);
            $this->db->bind(':november_budget', $data['november_budget']);
            $this->db->bind(':december_budget', $data['december_budget']);
            $this->db->bind(':annual_budget', $annual_budget);
            $this->db->bind(':status', $data['status']);

            $this->db->execute();

            if ($useTransaction) {
                $this->db->endTransaction();
            }

            return ['success' => true, 'message' => 'Budget saved successfully!'];
        } catch (Exception $e) {
            if ($useTransaction) {
                $this->db->cancelTransaction();
            }
            return ['success' => false, 'message' => 'Error saving budget: ' . $e->getMessage()];
        }
    }

    
    /**
     * Delete budget line
     */
    public function deleteBudgetLine($id) {
        $this->db->query("DELETE FROM budget_lines WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
    
    /**
     * Calculate daily budget for a specific month
     */
    public function calculateDailyBudget($monthly_budget, $month, $year) {
        // Get number of working days (excluding Sundays)
        $working_days = $this->getWorkingDaysInMonth($month, $year);
        return $working_days > 0 ? $monthly_budget / $working_days : 0;
    }
    
    /**
     * Process Excel upload for budget data
     */
    public function processExcelUpload($file, $user_id) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, ['xlsx', 'xls'])) {
            return ['success' => false, 'message' => 'Invalid file format. Please upload .xlsx or .xls files only.'];
        }
        
        $upload_path = $upload_dir . 'budget_' . time() . '.' . $file_extension;
        
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            return ['success' => false, 'message' => 'Failed to upload file.'];
        }
        
        try {
            // Simple CSV-like parsing for Excel content
            // Note: For production, you'd want to use a proper Excel library like PhpSpreadsheet
            $result = $this->parseExcelFile($upload_path, $user_id);
            
            // Clean up uploaded file
            unlink($upload_path);
            
            return $result;
            
        } catch (Exception $e) {
            // Clean up uploaded file on error
            if (file_exists($upload_path)) {
                unlink($upload_path);
            }
            return ['success' => false, 'message' => 'Error processing file: ' . $e->getMessage()];
        }
    }
    
    /**
     * Parse Excel file and extract budget data
     * This is a simplified parser, use PhpSpreadsheet library
     */
    // private function parseExcelFile($file_path, $user_id) {
    //     // For this demo, we'll simulate Excel parsing
    //     // In production, you would use PhpSpreadsheet or similar library
        
    //     $warnings = [];
    //     $errors = [];
    //     $success_count = 0;
        
    //     // Get current year from filename or default
    //     $year = date('Y');
    //     if (preg_match('/(\d{4})/', basename($file_path), $matches)) {
    //         $year = $matches[1];
    //     }
        
    //     // Get active income lines for validation
    //     $valid_accounts = [];
    //     $income_lines = $this->getActiveIncomeLines();
    //     foreach ($income_lines as $line) {
    //         $valid_accounts[$line['acct_id']] = $line['acct_desc'];
    //     }
        
    //     // Simulate parsing Excel data
    //     // In real implementation, you would read the Excel file here
    //     $sample_data = [
    //         ['carpark', 'Car Park Revenue', 500000, 520000, 550000, 530000, 540000, 560000, 580000, 570000, 550000, 540000, 530000, 520000],
    //         ['loading', 'Loading & Offloading Revenue', 300000, 310000, 320000, 315000, 325000, 330000, 340000, 335000, 325000, 320000, 315000, 310000],
    //         ['hawkers', 'Hawkers Revenue', 200000, 210000, 220000, 215000, 225000, 230000, 240000, 235000, 225000, 220000, 215000, 210000]
    //     ];
        
    //     $this->db->beginTransaction();
        
    //     try {
    //         foreach ($sample_data as $row_index => $row) {
    //             $acct_id = $row[0];
    //             $acct_desc = $row[1];
                
    //             // Validate account ID
    //             if (!isset($valid_accounts[$acct_id])) {
    //                 $warnings[] = "Row " . ($row_index + 1) . ": Invalid account ID '{$acct_id}' - skipped";
    //                 continue;
    //             }
                
    //             // Validate monthly amounts
    //             $monthly_budgets = array_slice($row, 2, 12);
    //             $valid_amounts = true;
                
    //             foreach ($monthly_budgets as $amount) {
    //                 if (!is_numeric($amount) || $amount < 0) {
    //                     $warnings[] = "Row " . ($row_index + 1) . ": Invalid amount '{$amount}' for {$acct_desc}";
    //                     $valid_amounts = false;
    //                     break;
    //                 }
    //             }
                
    //             if (!$valid_amounts) {
    //                 continue;
    //             }
                
    //             // Prepare budget data
    //             $budget_data = [
    //                 'acct_id' => $acct_id,
    //                 'acct_desc' => $acct_desc,
    //                 'budget_year' => $year,
    //                 'january_budget' => $monthly_budgets[0],
    //                 'february_budget' => $monthly_budgets[1],
    //                 'march_budget' => $monthly_budgets[2],
    //                 'april_budget' => $monthly_budgets[3],
    //                 'may_budget' => $monthly_budgets[4],
    //                 'june_budget' => $monthly_budgets[5],
    //                 'july_budget' => $monthly_budgets[6],
    //                 'august_budget' => $monthly_budgets[7],
    //                 'september_budget' => $monthly_budgets[8],
    //                 'october_budget' => $monthly_budgets[9],
    //                 'november_budget' => $monthly_budgets[10],
    //                 'december_budget' => $monthly_budgets[11],
    //                 'status' => 'Active',
    //                 'user_id' => $user_id
    //             ];
                
    //             $result = $this->saveBudgetLine($budget_data);
    //             if ($result['success']) {
    //                 $success_count++;
    //             } else {
    //                 $errors[] = "Row " . ($row_index + 1) . ": " . $result['message'];
    //             }
    //         }
            
    //         $this->db->endTransaction();
            
    //         $message = "Excel upload completed! {$success_count} budget lines processed successfully.";
    //         if (!empty($warnings)) {
    //             $message .= " " . count($warnings) . " warnings generated.";
    //         }
            
    //         return [
    //             'success' => true,
    //             'message' => $message,
    //             'warnings' => $warnings,
    //             'errors' => $errors
    //         ];
            
    //     } catch (Exception $e) {
    //         $this->db->cancelTransaction();
    //         return [
    //             'success' => false,
    //             'message' => 'Error processing Excel file: ' . $e->getMessage(),
    //             'errors' => $errors
    //         ];
    //     }
    // }
    // private function parseExcelFile($file_path, $user_id) {
    //     $warnings = [];
    //     $errors = [];
    //     $success_count = 0;

    //     // Get current year from filename or default
    //     $year = date('Y');
    //     if (preg_match('/(\d{4})/', basename($file_path), $matches)) {
    //         $year = $matches[1];
    //     }

    //     // Get active income lines for validation
    //     $valid_accounts = [];
    //     $income_lines = $this->getActiveIncomeLines();
    //     foreach ($income_lines as $line) {
    //         $valid_accounts[$line['acct_id']] = $line['acct_desc'];
    //     }

    //     // 🔹 DEBUG: Print all valid accounts from DB
    //     echo "<pre>VALID DB ACCOUNTS:\n";
    //     foreach ($valid_accounts as $id => $desc) {
    //         echo "ID: {$id} => {$desc}\n";
    //     }
    //     echo "</pre>";

    //     // Simulate parsing Excel data
    //     $sample_data = [
    //         ['carpark', 'Car Park Revenue', 500000, 520000, 550000, 530000, 540000, 560000, 580000, 570000, 550000, 540000, 530000, 520000],
    //         ['loading', 'Loading & Offloading Revenue', 300000, 310000, 320000, 315000, 325000, 330000, 340000, 335000, 325000, 320000, 315000, 310000],
    //         ['hawkers', 'Hawkers Revenue', 200000, 210000, 220000, 215000, 225000, 230000, 240000, 235000, 225000, 220000, 215000, 210000]
    //     ];

    //     $this->db->beginTransaction();

    //     try {
    //         foreach ($sample_data as $row_index => $row) {
    //             $acct_id = $row[0];   // Excel first column
    //             $acct_desc = $row[1];

    //             // 🔹 DEBUG: Print Excel row acct_id
    //             echo "Row " . ($row_index + 1) . " Excel acct_id: '{$acct_id}'<br>";

    //             // Validate account ID
    //             if (!isset($valid_accounts[$acct_id])) {
    //                 $warnings[] = "Row " . ($row_index + 1) . ": Invalid account ID '{$acct_id}' - skipped";
    //                 continue;
    //             }

    //             // Validate monthly amounts
    //             $monthly_budgets = array_slice($row, 2, 12);
    //             $valid_amounts = true;

    //             foreach ($monthly_budgets as $amount) {
    //                 if (!is_numeric($amount) || $amount < 0) {
    //                     $warnings[] = "Row " . ($row_index + 1) . ": Invalid amount '{$amount}' for {$acct_desc}";
    //                     $valid_amounts = false;
    //                     break;
    //                 }
    //             }

    //             if (!$valid_amounts) {
    //                 continue;
    //             }

    //             // Prepare budget data
    //             $budget_data = [
    //                 'acct_id' => $acct_id,
    //                 'acct_desc' => $acct_desc,
    //                 'budget_year' => $year,
    //                 'january_budget' => $monthly_budgets[0],
    //                 'february_budget' => $monthly_budgets[1],
    //                 'march_budget' => $monthly_budgets[2],
    //                 'april_budget' => $monthly_budgets[3],
    //                 'may_budget' => $monthly_budgets[4],
    //                 'june_budget' => $monthly_budgets[5],
    //                 'july_budget' => $monthly_budgets[6],
    //                 'august_budget' => $monthly_budgets[7],
    //                 'september_budget' => $monthly_budgets[8],
    //                 'october_budget' => $monthly_budgets[9],
    //                 'november_budget' => $monthly_budgets[10],
    //                 'december_budget' => $monthly_budgets[11],
    //                 'status' => 'Active',
    //                 'user_id' => $user_id
    //             ];

    //             $result = $this->saveBudgetLine($budget_data);
    //             if ($result['success']) {
    //                 $success_count++;
    //             } else {
    //                 $errors[] = "Row " . ($row_index + 1) . ": " . $result['message'];
    //             }
    //         }

    //         $this->db->endTransaction();

    //         $message = "Excel upload completed! {$success_count} budget lines processed successfully.";
    //         if (!empty($warnings)) {
    //             $message .= " " . count($warnings) . " warnings generated.";
    //         }

    //         return [
    //             'success' => true,
    //             'message' => $message,
    //             'warnings' => $warnings,
    //             'errors' => $errors
    //         ];

    //     } catch (Exception $e) {
    //         $this->db->cancelTransaction();
    //         return [
    //             'success' => false,
    //             'message' => 'Error processing Excel file: ' . $e->getMessage(),
    //             'errors' => $errors
    //         ];
    //     }
    // }
    // private function parseExcelFile($file_path, $user_id) {
    //     $warnings = [];
    //     $errors = [];
    //     $success_count = 0;

    //     // Get current year from filename or default
    //     $year = date('Y');
    //     if (preg_match('/(\d{4})/', basename($file_path), $matches)) {
    //         $year = $matches[1];
    //     }

    //     // Get active income lines for validation
    //     $valid_accounts = [];
    //     $income_lines = $this->getActiveIncomeLines();
    //     foreach ($income_lines as $line) {
    //         $valid_accounts[$line['acct_id']] = $line['acct_desc'];
    //     }

    //     // NOTE: Replace this with real Excel parsing later
    //     $sample_data = [
    //         [10800, 'Car Park Ticket', 500000, 520000, 550000, 530000, 540000, 560000, 580000, 570000, 550000, 540000, 530000, 520000],
    //         [10850, 'Car Loading (Taxi)', 300000, 310000, 320000, 315000, 325000, 330000, 340000, 335000, 325000, 320000, 315000, 310000],
    //         [12750, 'Apple Loading - Offloading', 200000, 210000, 220000, 215000, 225000, 230000, 240000, 235000, 225000, 220000, 215000, 210000],
    //         [99999, 'Nonexistent Revenue', 100000, 105000, 110000, 115000, 120000, 125000, 130000, 135000, 140000, 145000, 150000, 155000] // invalid
    //     ];

    //     try {
    //         // Only start a transaction if not already in one
    //         if (!$this->db->inTransaction()) {
    //             $this->db->beginTransaction();
    //         }

    //         foreach ($sample_data as $row_index => $row) {
    //             $acct_id   = isset($row[0]) ? trim($row[0]) : '';
    //             $acct_desc = isset($row[1]) ? trim($row[1]) : '';

    //             // Debug log
    //             error_log("Checking Row " . ($row_index + 1) . ": ID={$acct_id}, DESC={$acct_desc}");

    //             // Validate against DB
    //             if (!isset($valid_accounts[$acct_id]) || $valid_accounts[$acct_id] !== $acct_desc) {
    //                 $warnings[] = "Row " . ($row_index + 1) . ": Invalid account ID/Desc '{$acct_id} - {$acct_desc}' - skipped";
    //                 continue;
    //             }

    //             // Validate monthly amounts
    //             $monthly_budgets = array_slice($row, 2, 12);
    //             $valid_amounts = true;

    //             foreach ($monthly_budgets as $amount) {
    //                 if (!is_numeric($amount) || $amount < 0) {
    //                     $warnings[] = "Row " . ($row_index + 1) . ": Invalid amount '{$amount}' for {$acct_desc}";
    //                     $valid_amounts = false;
    //                     break;
    //                 }
    //             }

    //             if (!$valid_amounts) {
    //                 continue;
    //             }

    //             // Prepare data
    //             $budget_data = [
    //                 'acct_id'          => $acct_id,
    //                 'acct_desc'        => $acct_desc,
    //                 'budget_year'      => $year,
    //                 'january_budget'   => $monthly_budgets[0],
    //                 'february_budget'  => $monthly_budgets[1],
    //                 'march_budget'     => $monthly_budgets[2],
    //                 'april_budget'     => $monthly_budgets[3],
    //                 'may_budget'       => $monthly_budgets[4],
    //                 'june_budget'      => $monthly_budgets[5],
    //                 'july_budget'      => $monthly_budgets[6],
    //                 'august_budget'    => $monthly_budgets[7],
    //                 'september_budget' => $monthly_budgets[8],
    //                 'october_budget'   => $monthly_budgets[9],
    //                 'november_budget'  => $monthly_budgets[10],
    //                 'december_budget'  => $monthly_budgets[11],
    //                 'status'           => 'Active',
    //                 'user_id'          => $user_id
    //             ];

    //             $result = $this->saveBudgetLine($budget_data);
    //             if ($result['success']) {
    //                 $success_count++;
    //             } else {
    //                 $errors[] = "Row " . ($row_index + 1) . ": " . $result['message'];
    //             }
    //         }

    //         // Commit only if we started the transaction
    //         if ($this->db->inTransaction()) {
    //             $this->db->commit();
    //         }

    //         $message = "Excel upload completed! {$success_count} budget lines processed successfully.";
    //         if (!empty($warnings)) {
    //             $message .= " " . count($warnings) . " warnings generated.";
    //         }

    //         return [
    //             'success'  => true,
    //             'message'  => $message,
    //             'warnings' => $warnings,
    //             'errors'   => $errors
    //         ];

    //     } catch (Exception $e) {
    //         if ($this->db->inTransaction()) {
    //             $this->db->rollBack();
    //         }
    //         return [
    //             'success' => false,
    //             'message' => 'Error processing Excel file: ' . $e->getMessage(),
    //             'errors'  => $errors
    //         ];
    //     }
    // }
    // private function parseExcelFile($file_path, $user_id) {
    //     $warnings = [];
    //     $errors = [];
    //     $success_count = 0;

    //     // Get current year from filename or default
    //     $year = date('Y');
    //     if (preg_match('/(\d{4})/', basename($file_path), $matches)) {
    //         $year = $matches[1];
    //     }

    //     // Get active income lines for validation
    //     $valid_accounts = [];
    //     $income_lines = $this->getActiveIncomeLines();
    //     foreach ($income_lines as $line) {
    //         $valid_accounts[$line['acct_id']] = $line['acct_desc'];
    //     }

    //     // NOTE: Replace this with real Excel parsing later
    //     $sample_data = [
    //         [10800, 'Car Park Ticket', 500000, 520000, 550000, 530000, 540000, 560000, 580000, 570000, 550000, 540000, 530000, 520000],
    //         [10850, 'Car Loading (Taxi)', 300000, 310000, 320000, 315000, 325000, 330000, 340000, 335000, 325000, 320000, 315000, 310000],
    //         [12750, 'Apple Loading - Offloading', 200000, 210000, 220000, 215000, 225000, 230000, 240000, 235000, 225000, 220000, 215000, 210000],
    //         [99999, 'Nonexistent Revenue', 100000, 105000, 110000, 115000, 120000, 125000, 130000, 135000, 140000, 145000, 150000, 155000] // invalid
    //     ];

    //     // Track whether this method started a transaction
    //     $startedTransaction = false;

    //     try {
    //         if (!$this->db->inTransaction()) {
    //             $this->db->beginTransaction();
    //             $startedTransaction = true;
    //         }

    //         foreach ($sample_data as $row_index => $row) {
    //             $acct_id   = isset($row[0]) ? trim($row[0]) : '';
    //             $acct_desc = isset($row[1]) ? trim($row[1]) : '';

    //             // Validate against DB
    //             if (!isset($valid_accounts[$acct_id]) || $valid_accounts[$acct_id] !== $acct_desc) {
    //                 $warnings[] = "Row " . ($row_index + 1) . ": Invalid account ID/Desc '{$acct_id} - {$acct_desc}' - skipped";
    //                 continue;
    //             }

    //             // Validate monthly amounts
    //             $monthly_budgets = array_slice($row, 2, 12);
    //             $valid_amounts = true;

    //             foreach ($monthly_budgets as $amount) {
    //                 if (!is_numeric($amount) || $amount < 0) {
    //                     $warnings[] = "Row " . ($row_index + 1) . ": Invalid amount '{$amount}' for {$acct_desc}";
    //                     $valid_amounts = false;
    //                     break;
    //                 }
    //             }

    //             if (!$valid_amounts) {
    //                 continue;
    //             }

    //             // Prepare data
    //             $budget_data = [
    //                 'acct_id'          => $acct_id,
    //                 'acct_desc'        => $acct_desc,
    //                 'budget_year'      => $year,
    //                 'january_budget'   => $monthly_budgets[0],
    //                 'february_budget'  => $monthly_budgets[1],
    //                 'march_budget'     => $monthly_budgets[2],
    //                 'april_budget'     => $monthly_budgets[3],
    //                 'may_budget'       => $monthly_budgets[4],
    //                 'june_budget'      => $monthly_budgets[5],
    //                 'july_budget'      => $monthly_budgets[6],
    //                 'august_budget'    => $monthly_budgets[7],
    //                 'september_budget' => $monthly_budgets[8],
    //                 'october_budget'   => $monthly_budgets[9],
    //                 'november_budget'  => $monthly_budgets[10],
    //                 'december_budget'  => $monthly_budgets[11],
    //                 'status'           => 'Active',
    //                 'user_id'          => $user_id
    //             ];

    //             $result = $this->saveBudgetLine($budget_data);
    //             if ($result['success']) {
    //                 $success_count++;
    //             } else {
    //                 $errors[] = "Row " . ($row_index + 1) . ": " . $result['message'];
    //             }
    //         }

    //         if ($startedTransaction) {
    //             $this->db->commit();
    //         }

    //         $message = "Excel upload completed! {$success_count} budget lines processed successfully.";
    //         if (!empty($warnings)) {
    //             $message .= " " . count($warnings) . " warnings generated.";
    //         }

    //         return [
    //             'success'  => true,
    //             'message'  => $message,
    //             'warnings' => $warnings,
    //             'errors'   => $errors
    //         ];

    //     } catch (Exception $e) {
    //         if ($startedTransaction && $this->db->inTransaction()) {
    //             $this->db->rollBack();
    //         }
    //         return [
    //             'success' => false,
    //             'message' => 'Error processing Excel file: ' . $e->getMessage(),
    //             'errors'  => $errors
    //         ];
    //     }
    // }
    // private function parseExcelFile($file_path, $user_id) {
    //     $warnings = [];
    //     $errors = [];
    //     $success_count = 0;

    //     // Get current year from filename or default
    //     $year = date('Y');
    //     if (preg_match('/(\d{4})/', basename($file_path), $matches)) {
    //         $year = $matches[1];
    //     }

    //     // Get active income lines for validation
    //     $valid_accounts = [];
    //     $income_lines = $this->getActiveIncomeLines();
    //     foreach ($income_lines as $line) {
    //         $valid_accounts[$line['acct_id']] = $line['acct_desc'];
    //     }

    //     // Track whether this method started a transaction
    //     $startedTransaction = false;

    //     try {
    //         if (!$this->db->inTransaction()) {
    //             $this->db->beginTransaction();
    //             $startedTransaction = true;
    //         }

    //         // 🔹 Load Excel file with PHPExcel
    //         $objPHPExcel = PHPExcel_IOFactory::load($file_path);
    //         $sheet = $objPHPExcel->getActiveSheet();
    //         $highestRow = $sheet->getHighestRow();

    //         // Loop rows starting from row 2 (assuming row 1 is headers)
    //         for ($rowIndex = 2; $rowIndex <= $highestRow; $rowIndex++) {
    //             $acct_id   = trim($sheet->getCellByColumnAndRow(0, $rowIndex)->getValue());
    //             $acct_desc = trim($sheet->getCellByColumnAndRow(1, $rowIndex)->getValue());

    //             // Validate against DB
    //             if (!isset($valid_accounts[$acct_id]) || $valid_accounts[$acct_id] !== $acct_desc) {
    //                 $warnings[] = "Row {$rowIndex}: Invalid account ID/Desc '{$acct_id} - {$acct_desc}' - skipped";
    //                 continue;
    //             }

    //             // Get next 12 columns for monthly budgets
    //             $monthly_budgets = [];
    //             for ($i = 0; $i < 12; $i++) {
    //                 $val = $sheet->getCellByColumnAndRow($i + 2, $rowIndex)->getCalculatedValue();
    //                 $monthly_budgets[] = is_numeric($val) ? (float)$val : null;
    //             }

    //             // Validate monthly amounts
    //             $valid_amounts = true;
    //             foreach ($monthly_budgets as $amount) {
    //                 if ($amount === null || $amount < 0) {
    //                     $warnings[] = "Row {$rowIndex}: Invalid amount '{$amount}' for {$acct_desc}";
    //                     $valid_amounts = false;
    //                     break;
    //                 }
    //             }
    //             if (!$valid_amounts) {
    //                 continue;
    //             }

    //             // Prepare data
    //             $budget_data = [
    //                 'acct_id'          => $acct_id,
    //                 'acct_desc'        => $acct_desc,
    //                 'budget_year'      => $year,
    //                 'january_budget'   => $monthly_budgets[0],
    //                 'february_budget'  => $monthly_budgets[1],
    //                 'march_budget'     => $monthly_budgets[2],
    //                 'april_budget'     => $monthly_budgets[3],
    //                 'may_budget'       => $monthly_budgets[4],
    //                 'june_budget'      => $monthly_budgets[5],
    //                 'july_budget'      => $monthly_budgets[6],
    //                 'august_budget'    => $monthly_budgets[7],
    //                 'september_budget' => $monthly_budgets[8],
    //                 'october_budget'   => $monthly_budgets[9],
    //                 'november_budget'  => $monthly_budgets[10],
    //                 'december_budget'  => $monthly_budgets[11],
    //                 'status'           => 'Active',
    //                 'user_id'          => $user_id
    //             ];

    //             $result = $this->saveBudgetLine($budget_data);
    //             if ($result['success']) {
    //                 $success_count++;
    //             } else {
    //                 $errors[] = "Row {$rowIndex}: " . $result['message'];
    //             }
    //         }

    //         if ($startedTransaction) {
    //             $this->db->commit();
    //         }

    //         $message = "Excel upload completed! {$success_count} budget lines processed successfully.";
    //         if (!empty($warnings)) {
    //             $message .= " " . count($warnings) . " warnings generated.";
    //         }

    //         return [
    //             'success'  => true,
    //             'message'  => $message,
    //             'warnings' => $warnings,
    //             'errors'   => $errors
    //         ];

    //     } catch (Exception $e) {
    //         if ($startedTransaction && $this->db->inTransaction()) {
    //             $this->db->rollBack();
    //         }
    //         return [
    //             'success' => false,
    //             'message' => 'Error processing Excel file: ' . $e->getMessage(),
    //             'errors'  => $errors
    //         ];
    //     }
    // }
    // private function parseExcelFile($file_path, $user_id) {
    //     $warnings = [];
    //     $errors = [];
    //     $success_count = 0;

    //     // Get current year from filename or default
    //     $year = date('Y');
    //     if (preg_match('/(\d{4})/', basename($file_path), $matches)) {
    //         $year = $matches[1];
    //     }

    //     // Get active income lines for validation
    //     $valid_accounts = [];
    //     $income_lines = $this->getActiveIncomeLines();
    //     foreach ($income_lines as $line) {
    //         $valid_accounts[$line['acct_id']] = $line['acct_desc'];
    //     }

    //     // ====== READ EXCEL FILE USING PHPExcel ======
    //     require_once '../PHPExcel/Classes/PHPExcel.php';

    //     try {
    //         $objPHPExcel = PHPExcel_IOFactory::load($file_path);
    //         $sheet = $objPHPExcel->getActiveSheet();
    //         $highestRow = $sheet->getHighestRow();
    //         $highestCol = $sheet->getHighestColumn();

    //         $excel_data = [];
    //         for ($row = 2; $row <= $highestRow; $row++) { // assuming row 1 is header
    //             $rowData = [];
    //             for ($col = 'A'; $col <= $highestCol; $col++) {
    //                 $rowData[] = trim($sheet->getCell($col . $row)->getValue());
    //             }
    //             if (!empty(array_filter($rowData))) { // skip empty rows
    //                 $excel_data[] = $rowData;
    //             }
    //         }

    //         $this->db->beginTransaction();

    //         foreach ($excel_data as $row_index => $row) {
    //             $acct_id   = isset($row[0]) ? trim($row[0]) : '';
    //             $acct_desc = isset($row[1]) ? trim($row[1]) : '';

    //             // Validate account ID
    //             if (!isset($valid_accounts[$acct_id])) {
    //                 $warnings[] = "Row " . ($row_index + 2) . ": Invalid account ID '{$acct_id}' - skipped";
    //                 continue;
    //             }

    //             // Validate monthly budgets
    //             $monthly_budgets = array_slice($row, 2, 12);
    //             $valid_amounts = true;
    //             foreach ($monthly_budgets as $amount) {
    //                 if (!is_numeric($amount) || $amount < 0) {
    //                     $warnings[] = "Row " . ($row_index + 2) . ": Invalid amount '{$amount}' for {$acct_desc}";
    //                     $valid_amounts = false;
    //                     break;
    //                 }
    //             }
    //             if (!$valid_amounts) {
    //                 continue;
    //             }

    //             // Prepare budget data with safe index checks (no ?? operator)
    //             $budget_data = [
    //                 'acct_id'          => $acct_id,
    //                 'acct_desc'        => $acct_desc,
    //                 'budget_year'      => $year,
    //                 'january_budget'   => isset($monthly_budgets[0]) ? $monthly_budgets[0] : 0,
    //                 'february_budget'  => isset($monthly_budgets[1]) ? $monthly_budgets[1] : 0,
    //                 'march_budget'     => isset($monthly_budgets[2]) ? $monthly_budgets[2] : 0,
    //                 'april_budget'     => isset($monthly_budgets[3]) ? $monthly_budgets[3] : 0,
    //                 'may_budget'       => isset($monthly_budgets[4]) ? $monthly_budgets[4] : 0,
    //                 'june_budget'      => isset($monthly_budgets[5]) ? $monthly_budgets[5] : 0,
    //                 'july_budget'      => isset($monthly_budgets[6]) ? $monthly_budgets[6] : 0,
    //                 'august_budget'    => isset($monthly_budgets[7]) ? $monthly_budgets[7] : 0,
    //                 'september_budget' => isset($monthly_budgets[8]) ? $monthly_budgets[8] : 0,
    //                 'october_budget'   => isset($monthly_budgets[9]) ? $monthly_budgets[9] : 0,
    //                 'november_budget'  => isset($monthly_budgets[10]) ? $monthly_budgets[10] : 0,
    //                 'december_budget'  => isset($monthly_budgets[11]) ? $monthly_budgets[11] : 0,
    //                 'status'           => 'Active',
    //                 'user_id'          => $user_id
    //             ];

    //             $result = $this->saveBudgetLine($budget_data);
    //             if ($result['success']) {
    //                 $success_count++;
    //             } else {
    //                 $errors[] = "Row " . ($row_index + 2) . ": " . $result['message'];
    //             }
    //         }

    //         $this->db->commit();

    //         $message = "Excel upload completed! {$success_count} budget lines processed successfully.";
    //         if (!empty($warnings)) {
    //             $message .= " " . count($warnings) . " warnings generated.";
    //         }

    //         return [
    //             'success'  => true,
    //             'message'  => $message,
    //             'warnings' => $warnings,
    //             'errors'   => $errors
    //         ];

    //     } catch (Exception $e) {
    //         $this->db->rollBack();
    //         return [
    //             'success' => false,
    //             'message' => 'Error processing Excel file: ' . $e->getMessage(),
    //             'errors'  => $errors
    //         ];
    //     }
    // }
    // private function parseExcelFile($file_path, $user_id) {
    //     $warnings = [];
    //     $errors = [];
    //     $success_count = 0;

    //     // Get current year from filename or default
    //     $year = date('Y');
    //     if (preg_match('/(\d{4})/', basename($file_path), $matches)) {
    //         $year = $matches[1];
    //     }

    //     // Get active income lines for validation
    //     $valid_accounts = [];
    //     $income_lines = $this->getActiveIncomeLines();
    //     foreach ($income_lines as $line) {
    //         $valid_accounts[$line['acct_id']] = $line['acct_desc'];
    //     }

    //     // ====== READ EXCEL FILE USING PHPExcel ======
    //     require_once '../PHPExcel/Classes/PHPExcel.php';

    //     try {
    //         $objPHPExcel = PHPExcel_IOFactory::load($file_path);
    //         $sheet = $objPHPExcel->getActiveSheet();
    //         $highestRow = $sheet->getHighestRow();
    //         $highestCol = $sheet->getHighestColumn();

    //         $excel_data = [];
    //         for ($row = 2; $row <= $highestRow; $row++) { // assuming row 1 is header
    //             $rowData = [];
    //             for ($col = 'A'; $col <= $highestCol; $col++) {
    //                 $rowData[] = trim($sheet->getCell($col . $row)->getValue());
    //             }
    //             if (!empty(array_filter($rowData))) { // skip empty rows
    //                 $excel_data[] = $rowData;
    //             }
    //         }

    //         // ✅ Safeguard: Roll back if a transaction is already open
    //         if ($this->db->inTransaction()) {
    //             $this->db->rollBack();
    //         }

    //         $this->db->beginTransaction();

    //         foreach ($excel_data as $row_index => $row) {
    //             $acct_id   = isset($row[0]) ? trim($row[0]) : '';
    //             $acct_desc = isset($row[1]) ? trim($row[1]) : '';

    //             // Validate account ID
    //             if (!isset($valid_accounts[$acct_id])) {
    //                 $warnings[] = "Row " . ($row_index + 2) . ": Invalid account ID '{$acct_id}' - skipped";
    //                 continue;
    //             }

    //             // Validate monthly budgets
    //             $monthly_budgets = array_slice($row, 2, 12);
    //             $valid_amounts = true;
    //             foreach ($monthly_budgets as $amount) {
    //                 if (!is_numeric($amount) || $amount < 0) {
    //                     $warnings[] = "Row " . ($row_index + 2) . ": Invalid amount '{$amount}' for {$acct_desc}";
    //                     $valid_amounts = false;
    //                     break;
    //                 }
    //             }
    //             if (!$valid_amounts) {
    //                 continue;
    //             }

    //             // Prepare budget data
    //             $budget_data = [
    //                 'acct_id'          => $acct_id,
    //                 'acct_desc'        => $acct_desc,
    //                 'budget_year'      => $year,
    //                 'january_budget'   => isset($monthly_budgets[0]) ? $monthly_budgets[0] : 0,
    //                 'february_budget'  => isset($monthly_budgets[1]) ? $monthly_budgets[1] : 0,
    //                 'march_budget'     => isset($monthly_budgets[2]) ? $monthly_budgets[2] : 0,
    //                 'april_budget'     => isset($monthly_budgets[3]) ? $monthly_budgets[3] : 0,
    //                 'may_budget'       => isset($monthly_budgets[4]) ? $monthly_budgets[4] : 0,
    //                 'june_budget'      => isset($monthly_budgets[5]) ? $monthly_budgets[5] : 0,
    //                 'july_budget'      => isset($monthly_budgets[6]) ? $monthly_budgets[6] : 0,
    //                 'august_budget'    => isset($monthly_budgets[7]) ? $monthly_budgets[7] : 0,
    //                 'september_budget' => isset($monthly_budgets[8]) ? $monthly_budgets[8] : 0,
    //                 'october_budget'   => isset($monthly_budgets[9]) ? $monthly_budgets[9] : 0,
    //                 'november_budget'  => isset($monthly_budgets[10]) ? $monthly_budgets[10] : 0,
    //                 'december_budget'  => isset($monthly_budgets[11]) ? $monthly_budgets[11] : 0,
    //                 'status'           => 'Active',
    //                 'user_id'          => $user_id
    //             ];

    //             $result = $this->saveBudgetLine($budget_data);
    //             if ($result['success']) {
    //                 $success_count++;
    //             } else {
    //                 $errors[] = "Row " . ($row_index + 2) . ": " . $result['message'];
    //             }
    //         }

    //         $this->db->commit();

    //         $message = "Excel upload completed! {$success_count} budget lines processed successfully.";
    //         if (!empty($warnings)) {
    //             $message .= " " . count($warnings) . " warnings generated.";
    //         }

    //         return [
    //             'success'  => true,
    //             'message'  => $message,
    //             'warnings' => $warnings,
    //             'errors'   => $errors
    //         ];

    //     } catch (Exception $e) {
    //         if ($this->db->inTransaction()) {
    //             $this->db->rollBack();
    //         }
    //         return [
    //             'success' => false,
    //             'message' => 'Error processing Excel file: ' . $e->getMessage(),
    //             'errors'  => $errors
    //         ];
    //     }
    // }
    private function parseExcelFile($file_path, $user_id) {
        $warnings = [];
        $errors = [];
        $success_count = 0;

        // Get current year from filename or default
        $year = date('Y');
        if (preg_match('/(\d{4})/', basename($file_path), $matches)) {
            $year = $matches[1];
        }

        // Get active income lines for validation
        $valid_accounts = [];
        $income_lines = $this->getActiveIncomeLines();
        foreach ($income_lines as $line) {
            $valid_accounts[$line['acct_id']] = $line['acct_desc'];
        }

        try {
            $objPHPExcel = PHPExcel_IOFactory::load($file_path);
            $sheet = $objPHPExcel->getActiveSheet();
            $highestRow = $sheet->getHighestRow();
            $highestCol = $sheet->getHighestColumn();

            $excel_data = [];
            for ($row = 2; $row <= $highestRow; $row++) { // assuming row 1 is header
                $rowData = [];
                for ($col = 'A'; $col <= $highestCol; $col++) {
                    $rowData[] = trim($sheet->getCell($col . $row)->getValue());
                }
                if (!empty(array_filter($rowData))) { // skip empty rows
                    $excel_data[] = $rowData;
                }
            }

            // Reset any open transaction
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            $this->db->beginTransaction();

            foreach ($excel_data as $row_index => $row) {
                $acct_id   = isset($row[0]) ? trim($row[0]) : '';
                $acct_desc = isset($row[1]) ? trim($row[1]) : '';

                // Validate account ID
                if (!isset($valid_accounts[$acct_id])) {
                    $warnings[] = "Row " . ($row_index + 2) . ": Invalid account ID '{$acct_id}' - skipped";
                    continue;
                }

                // Validate monthly budgets
                $monthly_budgets = array_slice($row, 2, 12);
                $valid_amounts = true;
                foreach ($monthly_budgets as $amount) {
                    if (!is_numeric($amount) || $amount < 0) {
                        $warnings[] = "Row " . ($row_index + 2) . ": Invalid amount '{$amount}' for {$acct_desc}";
                        $valid_amounts = false;
                        break;
                    }
                }
                if (!$valid_amounts) {
                    continue;
                }

                // Prepare budget data
                $budget_data = [
                    'acct_id'          => $acct_id,
                    'acct_desc'        => $acct_desc,
                    'budget_year'      => $year,
                    'january_budget'   => isset($monthly_budgets[0]) ? $monthly_budgets[0] : 0,
                    'february_budget'  => isset($monthly_budgets[1]) ? $monthly_budgets[1] : 0,
                    'march_budget'     => isset($monthly_budgets[2]) ? $monthly_budgets[2] : 0,
                    'april_budget'     => isset($monthly_budgets[3]) ? $monthly_budgets[3] : 0,
                    'may_budget'       => isset($monthly_budgets[4]) ? $monthly_budgets[4] : 0,
                    'june_budget'      => isset($monthly_budgets[5]) ? $monthly_budgets[5] : 0,
                    'july_budget'      => isset($monthly_budgets[6]) ? $monthly_budgets[6] : 0,
                    'august_budget'    => isset($monthly_budgets[7]) ? $monthly_budgets[7] : 0,
                    'september_budget' => isset($monthly_budgets[8]) ? $monthly_budgets[8] : 0,
                    'october_budget'   => isset($monthly_budgets[9]) ? $monthly_budgets[9] : 0,
                    'november_budget'  => isset($monthly_budgets[10]) ? $monthly_budgets[10] : 0,
                    'december_budget'  => isset($monthly_budgets[11]) ? $monthly_budgets[11] : 0,
                    'status'           => 'Active',
                    'user_id'          => $user_id
                ];

                // Save without opening a new transaction
                $result = $this->saveBudgetLine($budget_data, false);
                if ($result['success']) {
                    $success_count++;
                } else {
                    $errors[] = "Row " . ($row_index + 2) . ": " . $result['message'];
                }
            }

            $this->db->commit();

            $message = "Excel upload completed! {$success_count} budget lines processed successfully.";
            if (!empty($warnings)) {
                $message .= " " . count($warnings) . " warnings generated.";
            }

            return [
                'success'  => true,
                'message'  => $message,
                'warnings' => $warnings,
                'errors'   => $errors
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return [
                'success' => false,
                'message' => 'Error processing Excel file: ' . $e->getMessage(),
                'errors'  => $errors
            ];
        }
    }







    
    /**
     * Get working days in month (excluding Sundays)
     */
    private function getWorkingDaysInMonth($month, $year) {
        $total_days = date('t', mktime(0, 0, 0, $month, 1, $year));
        $working_days = 0;
        
        for ($day = 1; $day <= $total_days; $day++) {
            $day_of_week = date('w', mktime(0, 0, 0, $month, $day, $year));
            if ($day_of_week != 0) { // 0 = Sunday
                $working_days++;
            }
        }
        
        return $working_days;
    }
    
    /**
     * Get budget vs actual performance
     */
    // public function getBudgetPerformance($year, $month = null) {
    //     $month_condition = $month ? "AND bp.performance_month = :month" : "";
        
    //     $this->db->query("
    //         SELECT 
    //             bl.acct_id,
    //             bl.acct_desc,
    //             bp.performance_month,
    //             bp.budgeted_amount,
    //             bp.actual_amount,
    //             bp.variance_amount,
    //             bp.variance_percentage,
    //             bp.performance_status
    //         FROM budget_lines bl
    //         LEFT JOIN budget_performance bp ON bl.acct_id = bp.acct_id 
    //             AND bl.budget_year = bp.performance_year
    //         WHERE bl.budget_year = :year
    //         {$month_condition}
    //         ORDER BY bl.acct_desc ASC, bp.performance_month ASC
    //     ");
        
    //     $this->db->bind(':year', $year);
    //     if ($month) {
    //         $this->db->bind(':month', $month);
    //     }
        
    //     return $this->db->resultSet();
    // }
    
    /**
     * Update budget performance with actual data
     */
    // public function updateBudgetPerformance($year, $month) {
    //     $this->db->beginTransaction();
        
    //     try {
    //         // Get all budget lines for the year
    //         $budget_lines = $this->getBudgetLines($year);
            
    //         foreach ($budget_lines as $budget_line) {
    //             // Get monthly budget amount
    //             $month_field = strtolower(date('F', mktime(0, 0, 0, $month, 1))) . '_budget';
    //             $budgeted_amount = $budget_line[$month_field];
                
    //             // Get actual collections from transactions
    //             $this->db->query("
    //                 SELECT COALESCE(SUM(amount_paid), 0) as actual_amount
    //                 FROM account_general_transaction_new 
    //                 WHERE credit_account = :acct_id
    //                 AND MONTH(date_of_payment) = :month 
    //                 AND YEAR(date_of_payment) = :year
    //                 AND (approval_status = 'Approved' OR approval_status = '')
    //             ");
                
    //             $this->db->bind(':acct_id', $budget_line['acct_id']);
    //             $this->db->bind(':month', $month);
    //             $this->db->bind(':year', $year);
                
    //             $actual_result = $this->db->single();
    //             $actual_amount = $actual_result['actual_amount'];
                
    //             // Insert or update budget performance
    //             $this->db->query("
    //                 INSERT INTO budget_performance (
    //                     acct_id, performance_month, performance_year, 
    //                     budgeted_amount, actual_amount
    //                 ) VALUES (
    //                     :acct_id, :month, :year, :budgeted_amount, :actual_amount
    //                 ) ON DUPLICATE KEY UPDATE
    //                     budgeted_amount = VALUES(budgeted_amount),
    //                     actual_amount = VALUES(actual_amount)
    //             ");
                
    //             $this->db->bind(':acct_id', $budget_line['acct_id']);
    //             $this->db->bind(':month', $month);
    //             $this->db->bind(':year', $year);
    //             $this->db->bind(':budgeted_amount', $budgeted_amount);
    //             $this->db->bind(':actual_amount', $actual_amount);
                
    //             $this->db->execute();
    //         }
            
    //         $this->db->endTransaction();
    //         return ['success' => true, 'message' => 'Budget performance updated successfully!'];
            
    //     } catch (Exception $e) {
    //         $this->db->cancelTransaction();
    //         return ['success' => false, 'message' => 'Error updating budget performance: ' . $e->getMessage()];
    //     }
    // }
    public function updateBudgetPerformance($year, $month) {
        $this->db->beginTransaction();
        try {
            // Get all budget lines for the year
            $budget_lines = $this->getBudgetLines($year);

            // Preload actuals in a single query (performance boost)
            $this->db->query("
                SELECT credit_account, COALESCE(SUM(amount_paid), 0) as actual_amount
                FROM account_general_transaction_new
                WHERE MONTH(date_of_payment) = :month 
                AND YEAR(date_of_payment) = :year
                AND (approval_status = 'Approved' OR approval_status = '')
                GROUP BY credit_account
            ");
            $this->db->bind(':month', $month);
            $this->db->bind(':year', $year);
            $actuals = $this->db->resultSet();
            
            // Convert actuals to lookup array
            $actual_map = [];
            foreach ($actuals as $row) {
                $actual_map[$row['credit_account']] = $row['actual_amount'];
            }

            foreach ($budget_lines as $budget_line) {
                // Get monthly budget field
                $month_field = strtolower(date('F', mktime(0, 0, 0, $month, 1))) . '_budget';
                $budgeted_amount = isset($budget_line[$month_field]) ? $budget_line[$month_field] : 0;

                // Get actual amount from map
                $acct_id = $budget_line['acct_id'];
                $actual_amount = isset($actual_map[$acct_id]) ? $actual_map[$acct_id] : 0;

                // Insert or update budget performance
                $this->db->query("
                    INSERT INTO budget_performance (
                        acct_id, performance_month, performance_year, 
                        budgeted_amount, actual_amount
                    ) VALUES (
                        :acct_id, :month, :year, :budgeted_amount, :actual_amount
                    ) ON DUPLICATE KEY UPDATE
                        budgeted_amount = VALUES(budgeted_amount),
                        actual_amount = VALUES(actual_amount)
                ");
                $this->db->bind(':acct_id', $acct_id);
                $this->db->bind(':month', $month);
                $this->db->bind(':year', $year);
                $this->db->bind(':budgeted_amount', $budgeted_amount);
                $this->db->bind(':actual_amount', $actual_amount);

                $this->db->execute();
            }

            $this->db->endTransaction();
            return ['success' => true, 'message' => 'Budget performance updated successfully!'];
        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return ['success' => false, 'message' => 'Error updating budget performance: ' . $e->getMessage()];
        }
    }


    /**
     * Get budget vs actual performance
     */
    public function getBudgetPerformance($year, $month = null) {
        $month_condition = $month ? "AND bp.performance_month = :month" : "";
        
        $this->db->query("
            SELECT 
                bl.acct_id,
                bl.acct_desc,
                bp.performance_month,
                bp.budgeted_amount,
                bp.actual_amount,
                bp.variance_amount,
                bp.variance_percentage,
                bp.performance_status
            FROM budget_lines bl
            LEFT JOIN budget_performance bp ON bl.acct_id = bp.acct_id 
                AND bl.budget_year = bp.performance_year
            WHERE bl.budget_year = :year
            {$month_condition}
            ORDER BY bl.acct_desc ASC, bp.performance_month ASC
        ");
        
        $this->db->bind(':year', $year);
        if ($month) {
            $this->db->bind(':month', $month);
        }
        
        return $this->db->resultSet();
    }

    // public function getBudgetPerformanceo($year, $month = null) {
    //     // Build base SQL
    //     $sql = "
    //         SELECT 
    //             bl.acct_id,
    //             bl.acct_desc, 
    //     ";

    //     if ($month) {
    //         $month_field = strtolower(date('F', mktime(0, 0, 0, $month, 1))) . "_budget";
    //         $sql .= " bl.$month_field AS budgeted_amount, ";
    //     } else {
    //         // Sum across all 12 months if no month specified
    //         $sql .= " (
    //             bl.january_budget + bl.february_budget + bl.march_budget + bl.april_budget +
    //             bl.may_budget + bl.june_budget + bl.july_budget + bl.august_budget +
    //             bl.september_budget + bl.october_budget + bl.november_budget + bl.december_budget
    //         ) AS budgeted_amount, ";
    //     }

    //     $sql .= "
    //             COALESCE(SUM(agt.amount_paid), 0) AS actual_amount
    //         FROM budget_lines bl
    //         LEFT JOIN account_general_transaction_new agt 
    //             ON agt.credit_account = bl.acct_id
    //             AND YEAR(agt.date_of_payment) = :year
    //             " . ($month ? "AND MONTH(agt.date_of_payment) = :month" : "") . "
    //             AND (agt.approval_status = 'Approved' OR agt.approval_status = '')
    //         GROUP BY bl.acct_id, bl.acct_desc, budgeted_amount
    //     ";

    //     // Prepare query
    //     $this->db->query($sql);

    //     // Bind parameters
    //     $this->db->bind(':year', $year);
    //     if ($month) {
    //         $this->db->bind(':month', $month);
    //     }

    //     // Fetch results
    //     $rows = $this->db->resultSet();

    //     // Calculate variance & performance
    //     foreach ($rows as &$row) {
    //         $row['variance_amount'] = $row['actual_amount'] - $row['budgeted_amount'];
    //         $row['variance_percentage'] = $row['budgeted_amount'] > 0
    //             ? round(($row['variance_amount'] / $row['budgeted_amount']) * 100, 1)
    //             : 0;

    //         if ($row['actual_amount'] > $row['budgeted_amount']) {
    //             $row['performance_status'] = 'Above Budget';
    //         } elseif ($row['actual_amount'] == $row['budgeted_amount']) {
    //             $row['performance_status'] = 'On Budget';
    //         } else {
    //             $row['performance_status'] = 'Below Budget';
    //         }
    //     }

    //     return $rows;
    // }
    //modified perfect but shows 2024
    public function getBudgetPerformanceo($year, $month = null) {
        // Default to current year/month if not provided
        if ($year === null) {
            $year = date('Y');
        }
        if ($month !== null) {
            $month = (int)$month; // ensure integer (avoid mktime string issue)
        }

        // Build base SQL
        $sql = "
            SELECT 
                bl.acct_id,
                bl.acct_desc, 
        ";

        if ($month) {
            $month_field = strtolower(date('F', mktime(0, 0, 0, $month, 1))) . "_budget";
            $sql .= " bl.$month_field AS budgeted_amount, ";
        } else {
            // Sum across all 12 months if no month specified
            $sql .= " (
                bl.january_budget + bl.february_budget + bl.march_budget + bl.april_budget +
                bl.may_budget + bl.june_budget + bl.july_budget + bl.august_budget +
                bl.september_budget + bl.october_budget + bl.november_budget + bl.december_budget
            ) AS budgeted_amount, ";
        }

        $sql .= "
                COALESCE(SUM(agt.amount_paid), 0) AS actual_amount
            FROM budget_lines bl
            LEFT JOIN account_general_transaction_new agt 
                ON agt.credit_account = bl.acct_id
                AND YEAR(agt.date_of_payment) = :year
                " . ($month ? "AND MONTH(agt.date_of_payment) = :month" : "") . "
                AND (agt.approval_status = 'Approved' OR agt.approval_status = '')
            WHERE bl.budget_year = :year
            AND bl.status = 'Active'
            GROUP BY bl.acct_id, bl.acct_desc, budgeted_amount
        ";

        // Prepare query
        $this->db->query($sql);

        // Bind parameters
        $this->db->bind(':year', $year);
        if ($month) {
            $this->db->bind(':month', $month);
        }

        // Fetch results
        $rows = $this->db->resultSet();

        // Calculate variance & performance
        foreach ($rows as &$row) {
            $row['variance_amount'] = $row['actual_amount'] - $row['budgeted_amount'];
            $row['variance_percentage'] = $row['budgeted_amount'] > 0
                ? round(($row['variance_amount'] / $row['budgeted_amount']) * 100, 1)
                : 0;

            if ($row['actual_amount'] > $row['budgeted_amount']) {
                $row['performance_status'] = 'Above Budget';
            } elseif ($row['actual_amount'] == $row['budgeted_amount']) {
                $row['performance_status'] = 'On Budget';
            } else {
                $row['performance_status'] = 'Below Budget';
            }

            // ✅ Always set valid month & year
            if ($month) {
                $row['performance_month'] = $month;
            } else {
                $row['performance_month'] = (int)date('n'); // current month (1–12)
            }
            $row['performance_year'] = $year;
        }

        return $rows;
    }



    

    



    /*-----------------------------------------*/

    public function getactualvsbudgeted($month, $year) {
        $this->db->beginTransaction();

        try {
            // Get all active targets for the period
            $this->db->query("
                SELECT * FROM officer_monthly_targets 
                WHERE target_month = :month 
                AND target_year = :year 
                AND status = 'Active'
            ");
            
            $this->db->bind(':month', $month);
            $this->db->bind(':year', $year);
            $targets = $this->db->resultSet();

            $report = []; // officer by officer
            $monthTotals = ['target' => 0, 'achieved' => 0];
            $yearTotals  = ['target' => 0, 'achieved' => 0];

            foreach ($targets as $target) {
                // Get actual performance for the officer in the month
                $this->db->query("
                    SELECT 
                        COALESCE(SUM(amount_paid), 0) as achieved_amount,
                        COUNT(DISTINCT date_of_payment) as working_days,
                        COUNT(id) as total_transactions
                    FROM account_general_transaction_new 
                    WHERE remitting_id = :officer_id
                    AND credit_account = :acct_id
                    AND MONTH(date_of_payment) = :month 
                    AND YEAR(date_of_payment) = :year
                    AND (approval_status = 'Approved' OR approval_status = '')
                ");
                
                $this->db->bind(':officer_id', $target['officer_id']);
                $this->db->bind(':acct_id', $target['acct_id']);
                $this->db->bind(':month', $month);
                $this->db->bind(':year', $year);
                
                $performance = $this->db->single();

                // --- Derived statistics ---
                $targetAmount    = (float)$target['monthly_target'];
                $achievedAmount  = (float)$performance['achieved_amount'];
                $variance        = $achievedAmount - $targetAmount;
                $percentage      = $targetAmount > 0 ? round(($achievedAmount / $targetAmount) * 100, 2) : 0;
                $avgPerDay       = $performance['working_days'] > 0 ? round($achievedAmount / $performance['working_days'], 2) : 0;
                $txnPerDay       = $performance['working_days'] > 0 ? round($performance['total_transactions'] / $performance['working_days'], 2) : 0;

                // Officer rating
                if ($percentage >= 100) {
                    $rating = "Excellent";
                } elseif ($percentage >= 80) {
                    $rating = "Good";
                } elseif ($percentage >= 50) {
                    $rating = "Fair";
                } else {
                    $rating = "Poor";
                }

                // Save/update officer performance
                $this->db->query("
                    INSERT INTO officer_performance_tracking (
                        officer_id, performance_month, performance_year, acct_id,
                        target_amount, achieved_amount, working_days, total_transactions,
                        percentage_achieved, variance, rating
                    ) VALUES (
                        :officer_id, :month, :year, :acct_id,
                        :target_amount, :achieved_amount, :working_days, :total_transactions,
                        :percentage_achieved, :variance, :rating
                    ) ON DUPLICATE KEY UPDATE
                        target_amount = VALUES(target_amount),
                        achieved_amount = VALUES(achieved_amount),
                        working_days = VALUES(working_days),
                        total_transactions = VALUES(total_transactions),
                        percentage_achieved = VALUES(percentage_achieved),
                        variance = VALUES(variance),
                        rating = VALUES(rating)
                ");
                
                $this->db->bind(':officer_id', $target['officer_id']);
                $this->db->bind(':month', $month);
                $this->db->bind(':year', $year);
                $this->db->bind(':acct_id', $target['acct_id']);
                $this->db->bind(':target_amount', $targetAmount);
                $this->db->bind(':achieved_amount', $achievedAmount);
                $this->db->bind(':working_days', $performance['working_days']);
                $this->db->bind(':total_transactions', $performance['total_transactions']);
                $this->db->bind(':percentage_achieved', $percentage);
                $this->db->bind(':variance', $variance);
                $this->db->bind(':rating', $rating);
                $this->db->execute();

                // Collect per-officer data
                $report[] = [
                    'officer_id'        => $target['officer_id'],
                    'acct_id'           => $target['acct_id'],
                    'target'            => $targetAmount,
                    'achieved'          => $achievedAmount,
                    'variance'          => $variance,
                    'percentage'        => $percentage,
                    'rating'            => $rating,
                    'working_days'      => $performance['working_days'],
                    'transactions'      => $performance['total_transactions'],
                    'avg_per_day'       => $avgPerDay,
                    'transactions_day'  => $txnPerDay
                ];

                // --- Add to monthly totals ---
                $monthTotals['target']   += $targetAmount;
                $monthTotals['achieved'] += $achievedAmount;
            }

            // --- Compute YEAR totals (sum of achieved + target across all months) ---
            $this->db->query("
                SELECT 
                    MONTH(date_of_payment) as month,
                    COALESCE(SUM(amount_paid), 0) as total_achieved
                FROM account_general_transaction_new 
                WHERE YEAR(date_of_payment) = :year
                AND (approval_status = 'Approved' OR approval_status = '')
                GROUP BY MONTH(date_of_payment)
                ORDER BY month ASC
            ");
            $this->db->bind(':year', $year);
            $monthlyBreakdown = $this->db->resultSet();

            // Calculate yearly totals
            foreach ($monthlyBreakdown as $row) {
                $yearTotals['achieved'] += (float)$row['total_achieved'];
            }

            // Get year target (sum of all monthly targets in year)
            $this->db->query("
                SELECT COALESCE(SUM(monthly_target),0) as total_target
                FROM officer_monthly_targets
                WHERE target_year = :year
                AND status = 'Active'
            ");
            $this->db->bind(':year', $year);
            $targetSum = $this->db->single();
            $yearTotals['target'] = (float)$targetSum['total_target'];

            $this->db->endTransaction();

            return [
                'success' => true,
                'message' => 'Officer performance updated successfully!',
                'data'    => $report,          // per-officer performance
                'monthly' => $monthTotals,     // total budget achieved vs target in given month
                'yearly'  => [
                    'totals'     => $yearTotals,      // year totals
                    'breakdown'  => $monthlyBreakdown // achieved per month for charting
                ]
            ];

        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return ['success' => false, 'message' => 'Error updating performance: ' . $e->getMessage()];
        }
    }

    public function getYearlyTotalAchieved($year) {
        try {
            $this->db->query("
                SELECT COALESCE(SUM(amount_paid), 0) as total_achieved
                FROM account_general_transaction_new
                WHERE YEAR(date_of_payment) = :year
                AND (approval_status = 'Approved' OR approval_status = '')
            ");
            $this->db->bind(':year', $year);

            $result = $this->db->single();
            return (float) $result['total_achieved'];

        } catch (Exception $e) {
            return 0; // fallback if error
        }
    }

    /**
     * Get budget vs actual performance with real-time calculations
     */
    public function getBudgetPerformanceRealTime($year, $month = null) {
        $month_condition = $month ? "AND :month = :month" : "";
        
        // Get budget lines with real-time actual calculations
        $this->db->query("
            SELECT 
                bl.acct_id,
                bl.acct_desc,
                bl.budget_year,
                CASE 
                    WHEN :month IS NOT NULL THEN
                        CASE :month
                            WHEN 1 THEN bl.january_budget
                            WHEN 2 THEN bl.february_budget
                            WHEN 3 THEN bl.march_budget
                            WHEN 4 THEN bl.april_budget
                            WHEN 5 THEN bl.may_budget
                            WHEN 6 THEN bl.june_budget
                            WHEN 7 THEN bl.july_budget
                            WHEN 8 THEN bl.august_budget
                            WHEN 9 THEN bl.september_budget
                            WHEN 10 THEN bl.october_budget
                            WHEN 11 THEN bl.november_budget
                            WHEN 12 THEN bl.december_budget
                        END
                    ELSE bl.annual_budget
                END as budgeted_amount,
                COALESCE(actual_data.actual_amount, 0) as actual_amount,
                (COALESCE(actual_data.actual_amount, 0) - 
                    CASE 
                        WHEN :month IS NOT NULL THEN
                            CASE :month
                                WHEN 1 THEN bl.january_budget
                                WHEN 2 THEN bl.february_budget
                                WHEN 3 THEN bl.march_budget
                                WHEN 4 THEN bl.april_budget
                                WHEN 5 THEN bl.may_budget
                                WHEN 6 THEN bl.june_budget
                                WHEN 7 THEN bl.july_budget
                                WHEN 8 THEN bl.august_budget
                                WHEN 9 THEN bl.september_budget
                                WHEN 10 THEN bl.october_budget
                                WHEN 11 THEN bl.november_budget
                                WHEN 12 THEN bl.december_budget
                            END
                        ELSE bl.annual_budget
                    END) as variance_amount,
                CASE 
                    WHEN (CASE 
                        WHEN :month IS NOT NULL THEN
                            CASE :month
                                WHEN 1 THEN bl.january_budget
                                WHEN 2 THEN bl.february_budget
                                WHEN 3 THEN bl.march_budget
                                WHEN 4 THEN bl.april_budget
                                WHEN 5 THEN bl.may_budget
                                WHEN 6 THEN bl.june_budget
                                WHEN 7 THEN bl.july_budget
                                WHEN 8 THEN bl.august_budget
                                WHEN 9 THEN bl.september_budget
                                WHEN 10 THEN bl.october_budget
                                WHEN 11 THEN bl.november_budget
                                WHEN 12 THEN bl.december_budget
                            END
                        ELSE bl.annual_budget
                    END) > 0 THEN
                        ((COALESCE(actual_data.actual_amount, 0) - 
                            CASE 
                                WHEN :month IS NOT NULL THEN
                                    CASE :month
                                        WHEN 1 THEN bl.january_budget
                                        WHEN 2 THEN bl.february_budget
                                        WHEN 3 THEN bl.march_budget
                                        WHEN 4 THEN bl.april_budget
                                        WHEN 5 THEN bl.may_budget
                                        WHEN 6 THEN bl.june_budget
                                        WHEN 7 THEN bl.july_budget
                                        WHEN 8 THEN bl.august_budget
                                        WHEN 9 THEN bl.september_budget
                                        WHEN 10 THEN bl.october_budget
                                        WHEN 11 THEN bl.november_budget
                                        WHEN 12 THEN bl.december_budget
                                    END
                                ELSE bl.annual_budget
                            END) / 
                            CASE 
                                WHEN :month IS NOT NULL THEN
                                    CASE :month
                                        WHEN 1 THEN bl.january_budget
                                        WHEN 2 THEN bl.february_budget
                                        WHEN 3 THEN bl.march_budget
                                        WHEN 4 THEN bl.april_budget
                                        WHEN 5 THEN bl.may_budget
                                        WHEN 6 THEN bl.june_budget
                                        WHEN 7 THEN bl.july_budget
                                        WHEN 8 THEN bl.august_budget
                                        WHEN 9 THEN bl.september_budget
                                        WHEN 10 THEN bl.october_budget
                                        WHEN 11 THEN bl.november_budget
                                        WHEN 12 THEN bl.december_budget
                                    END
                                ELSE bl.annual_budget
                            END) * 100
                    ELSE 0
                END as variance_percentage,
                CASE 
                    WHEN COALESCE(actual_data.actual_amount, 0) > 
                        (CASE 
                            WHEN :month IS NOT NULL THEN
                                CASE :month
                                    WHEN 1 THEN bl.january_budget
                                    WHEN 2 THEN bl.february_budget
                                    WHEN 3 THEN bl.march_budget
                                    WHEN 4 THEN bl.april_budget
                                    WHEN 5 THEN bl.may_budget
                                    WHEN 6 THEN bl.june_budget
                                    WHEN 7 THEN bl.july_budget
                                    WHEN 8 THEN bl.august_budget
                                    WHEN 9 THEN bl.september_budget
                                    WHEN 10 THEN bl.october_budget
                                    WHEN 11 THEN bl.november_budget
                                    WHEN 12 THEN bl.december_budget
                                END
                            ELSE bl.annual_budget
                        END) * 1.05 THEN 'Above Budget'
                    WHEN COALESCE(actual_data.actual_amount, 0) >= 
                        (CASE 
                            WHEN :month IS NOT NULL THEN
                                CASE :month
                                    WHEN 1 THEN bl.january_budget
                                    WHEN 2 THEN bl.february_budget
                                    WHEN 3 THEN bl.march_budget
                                    WHEN 4 THEN bl.april_budget
                                    WHEN 5 THEN bl.may_budget
                                    WHEN 6 THEN bl.june_budget
                                    WHEN 7 THEN bl.july_budget
                                    WHEN 8 THEN bl.august_budget
                                    WHEN 9 THEN bl.september_budget
                                    WHEN 10 THEN bl.october_budget
                                    WHEN 11 THEN bl.november_budget
                                    WHEN 12 THEN bl.december_budget
                                END
                            ELSE bl.annual_budget
                        END) * 0.95 THEN 'On Budget'
                    ELSE 'Below Budget'
                END as performance_status,
                :month as performance_month,
                :year as performance_year
            FROM budget_lines bl
            LEFT JOIN (
                SELECT 
                    t.credit_account,
                    SUM(t.amount_paid) as actual_amount
                FROM account_general_transaction_new t
                WHERE YEAR(t.date_of_payment) = :year
                " . ($month ? "AND MONTH(t.date_of_payment) = :month" : "") . "
                AND (t.approval_status = 'Approved' OR t.approval_status = '')
                GROUP BY t.credit_account
            ) actual_data ON bl.acct_id = actual_data.credit_account
            WHERE bl.budget_year = :year
            AND bl.status = 'Active'
            ORDER BY bl.acct_desc ASC
        ");
        
        $this->db->bind(':year', $year);
        if ($month) {
            $this->db->bind(':month', $month);
        } else {
            $this->db->bind(':month', null);
        }
        
        return $this->db->resultSet();
    }

}
?>