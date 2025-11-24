<?php
class FinancialStatementsController {

    public function index() {
        require 'models/FinancialStatementsModel.php';
        $model = new FinancialStatementsModel();

        $page = isset($_GET['page']) ? $_GET['page'] : 'summary';

        // Page 1: Chart of Accounts Summary
        if ($page === 'summary') {
            $accounts = $model->chartOfAccountsSummary();
            require 'views/ifrs/chart_of_accounts.php';
        }

        // Page 2: Ledger View
        // Page 2: Ledger View
        elseif ($page === 'ledger') {
            if (!isset($_GET['acct_id']) || $_GET['acct_id'] === 'acct_desc') {
                // Redirect to summary if no account selected
                header('Location: ?action=ifrs&page=summary');
                exit;
            }
            $acct_id = intval($_GET['acct_id']);
            $acctName = $model->getAccountName($acct_id);
            $ledgerRows = $model->ledgerRows($acct_id);
            require 'views/ifrs/ledger_view.php';
        }

        // Page 3: Trial Balance
        elseif ($page === 'trial-balance') {
            $trialBalance = $model->trialBalance();
            require 'views/ifrs/trial_balance.php';
        }

        // Page 4: IFRS Financial Statements
        elseif ($page === 'statements') {
            if (isset($_GET['period'])) {
                $period = $_GET['period'];
                $ifrs = $model->generateIFRS($period);
            }
            require 'views/ifrs/statements.php';
        }
    }
}
?>

