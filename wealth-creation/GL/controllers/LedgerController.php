<?php
require_once 'BaseController.php';
require_once __DIR__ . '/../models/LedgerModel.php';
require_once __DIR__ . '/../models/AccountModel.php';

class LedgerController extends BaseController {

    public function index() {
        $perPage = 100;
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $offset = ($page - 1) * $perPage;

        $acct_id = isset($_GET['acct_id']) ? intval($_GET['acct_id']) : null;
        $fromDate = isset($_GET['from_date']) && $_GET['from_date'] ? $_GET['from_date'] : null;
        $toDate = isset($_GET['to_date']) && $_GET['to_date'] ? $_GET['to_date'] : null;

        $model = new LedgerModel();
        $accounts = new AccountModel();

        $rows = $model->getGeneralLedger($offset, $perPage, $acct_id, $fromDate, $toDate);
        $total = $model->countGeneralLedger($acct_id, $fromDate, $toDate);

        $this->view('ledger', array(
            'rows' => $rows,
            'page' => $page,
            'perPage' => $perPage,
            'totalRows' => $total,
            'accounts' => $accounts->getAllAccounts(),
            'selectedAccount' => $acct_id,
            'fromDate' => $fromDate,
            'toDate' => $toDate
        ));
    }

    public function account() {
        $acct_id = intval($_GET['acct_id']);
        $fromDate = isset($_GET['from_date']) && $_GET['from_date'] ? $_GET['from_date'] : null;
        $toDate = isset($_GET['to_date']) && $_GET['to_date'] ? $_GET['to_date'] : null;

        $model = new LedgerModel();
        $acctModel = new AccountModel();

        $rows = $model->getLedgerForAccount($acct_id, $fromDate, $toDate);
        $acct = $acctModel->getAccount($acct_id);

        $this->view('ledger_account', array(
            'rows' => $rows,
            'account' => $acct,
            'fromDate' => $fromDate,
            'toDate' => $toDate
        ));
    }
}
?>
