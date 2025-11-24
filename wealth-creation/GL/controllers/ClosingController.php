<?php
class ClosingController {

    public function index() {
        require 'views/closing/index.php';
    }

    public function runClosing() {
        require 'models/ClosingModel.php';
        $model = new ClosingModel();

        $period = $_POST['period'];
        $result = $model->closePeriod($period);

        require 'views/closing/result.php';
    }
}
