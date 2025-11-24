<?php
class PostingController {

    public function index() {
        require 'models/PostingModel.php';
        $model = new PostingModel();
        $role = $_SESSION['role'] ?? 'staff';
        
        if ($role === 'staff') {
            $transactions = $model->getPendingTransactions('staff');
            require 'posting/views/staff_review.php';
        } elseif ($role === 'fc_head') {
            $transactions = $model->getPendingTransactions('fc_head');
            require 'posting/views/fc_approval.php';
        } elseif ($role === 'audit') {
            $transactions = $model->getPendingTransactions('audit');
            require 'posting/views/audit_approval.php';
        }
    }

    public function staffApprove() {
        require 'models/PostingModel.php';
        $model = new PostingModel();
        $remit_id = intval($_POST['remit_id']);
        $action = $_POST['action'] ?? 'approve';
        
        $result = ($action === 'approve')
            ? $model->staffApprove($remit_id)
            : $model->staffReject($remit_id, $_POST['reason'] ?? '');
        
        require 'posting/views/result.php';
    }

    public function fcApprove() {
        require 'models/PostingModel.php';
        $model = new PostingModel();
        $remit_id = intval($_POST['remit_id']);
        $action = $_POST['action'] ?? 'approve';
        
        $result = ($action === 'approve')
            ? $model->fcApprove($remit_id)
            : $model->fcReject($remit_id, $_POST['reason'] ?? '');
        
        require 'posting/views/result.php';
    }

    public function auditApprove() {
        require 'models/PostingModel.php';
        $model = new PostingModel();
        $remit_id = intval($_POST['remit_id']);
        $action = $_POST['action'] ?? 'approve';
        
        $result = ($action === 'approve')
            ? $model->auditApprove($remit_id)
            : $model->auditReject($remit_id, $_POST['reason'] ?? '');
        
        require 'posting/views/result.php';
    }

    public function demo() {
        require 'posting/views/demo.php';
    }
}
?>