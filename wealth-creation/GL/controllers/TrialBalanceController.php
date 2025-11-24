<?php
require_once 'BaseController.php';
require_once __DIR__ . '/../models/TrialBalanceModel.php';

class TrialBalanceController extends BaseController {

    public function index() {
        $fromDate = isset($_GET['from_date']) && $_GET['from_date'] ? $_GET['from_date'] : null;
        $toDate = isset($_GET['to_date']) && $_GET['to_date'] ? $_GET['to_date'] : null;

        $model = new TrialBalanceModel();
        $rows = $model->getTrialBalance($fromDate, $toDate);

        $this->view('trial_balance', array(
            'rows' => $rows,
            'fromDate' => $fromDate,
            'toDate' => $toDate
        ));
    }
}
?>

