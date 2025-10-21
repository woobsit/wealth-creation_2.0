<?php
require_once 'config/config.php';
requireLogin();

// Check if user has permission to add retirement
if (!in_array($_SESSION['department'], ['Accounts', 'Audit']) || $_SESSION['level'] < 3) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requisitionId = intval($_POST['requisition_id']);
    $amountRetired = floatval($_POST['amount_retired']);
    $amountReturned = floatval($_POST['amount_returned']);
    $description = sanitizeInput($_POST['description']);
    
    $retirement = new Retirement();
    $result = $retirement->create([
        'requisition_id' => $requisitionId,
        'amount_retired' => $amountRetired,
        'amount_returned' => $amountReturned,
        'description' => $description,
        'posted_by' => $_SESSION['user_id']
    ]);
    
    if ($result['success']) {
        // Update requisition status to completed if fully retired
        $requisitionModel = new Requisition();
        $req = $requisitionModel->getById($requisitionId);
        
        if ($req && $amountRetired >= $req['amount']) {
            $requisitionModel->updateStatus($requisitionId, 'completed');
        }
        
        redirect('requisition-details.php?id=' . $requisitionId . '&msg=retirement_added');
    } else {
        redirect('requisition-details.php?id=' . $requisitionId . '&error=' . urlencode($result['message']));
    }
} else {
    redirect('index.php');
}
?>