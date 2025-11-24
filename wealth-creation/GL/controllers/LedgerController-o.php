<?php
require_once 'BaseController.php';
require_once __DIR__ . '/../models/LedgerModel.php';
require_once __DIR__ . '/../models/AccountModel.php';

class LedgerController extends BaseController {

    public function index() {
        $perPage = 60;
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $offset = ($page - 1) * $perPage;

        $model = new LedgerModel();
        $accounts = new AccountModel();

        $rows = $model->getGeneralLedger($offset, $perPage);
        $total = $model->countGeneralLedger();

        $this->view('ledger-2', array(
            'rows' => $rows,
            'page' => $page,
            'perPage' => $perPage,
            'totalRows' => $total,
            'accounts' => $accounts->getAllAccounts()
        ));
    }

    public function account() {
        $acct_id = intval($_GET['acct_id']);
        $model = new LedgerModel();
        $acctModel = new AccountModel();

        $rows = $model->getLedgerForAccount($acct_id);
        $acct = $acctModel->getAccount($acct_id);

        $this->view('ledger_account', array(
            'rows' => $rows,
            'account' => $acct
        ));
    }
}
?>
