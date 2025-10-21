<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

$notification = new Notification();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    switch ($action) {
        case 'get':
            $notifications = $notification->getUserNotifications($_SESSION['user_id'], 10);
            echo json_encode(['success' => true, 'notifications' => $notifications]);
            break;
            
        case 'count':
            $count = $notification->getUnreadCount($_SESSION['user_id']);
            echo json_encode(['success' => true, 'count' => $count]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'mark_read':
            $notificationId = intval($_POST['notification_id']);
            $result = $notification->markAsRead($notificationId, $_SESSION['user_id']);
            echo json_encode(['success' => $result]);
            break;
            
        case 'mark_all_read':
            $result = $notification->markAllAsRead($_SESSION['user_id']);
            echo json_encode(['success' => $result]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}
?>