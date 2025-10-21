<?php
class Notification {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function create($userId, $type, $title, $message, $data = null) {
        try {
            $this->db->query('INSERT INTO notifications (user_id, type, title, message, data) 
                             VALUES (:user_id, :type, :title, :message, :data)');
            
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':type', $type);
            $this->db->bind(':title', $title);
            $this->db->bind(':message', $message);
            $this->db->bind(':data', $data ? json_encode($data) : null);
            
            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Create notification error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getUserNotifications($userId, $limit = 10) {
        try {
            $this->db->query('SELECT * FROM notifications 
                             WHERE user_id = :user_id 
                             ORDER BY created_at DESC 
                             LIMIT :limit');
            
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':limit', $limit);
            
            return $this->db->resultSet();
        } catch (Exception $e) {
            error_log("Get user notifications error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getUnreadCount($userId) {
        try {
            $this->db->query('SELECT COUNT(*) as count FROM notifications 
                             WHERE user_id = :user_id AND read_status = 0');
            
            $this->db->bind(':user_id', $userId);
            $result = $this->db->single();
            
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            error_log("Get unread count error: " . $e->getMessage());
            return 0;
        }
    }
    
    public function markAsRead($notificationId, $userId) {
        try {
            $this->db->query('UPDATE notifications SET read_status = 1 
                             WHERE id = :id AND user_id = :user_id');
            
            $this->db->bind(':id', $notificationId);
            $this->db->bind(':user_id', $userId);
            
            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Mark as read error: " . $e->getMessage());
            return false;
        }
    }
    
    public function markAllAsRead($userId) {
        try {
            $this->db->query('UPDATE notifications SET read_status = 1 
                             WHERE user_id = :user_id');
            
            $this->db->bind(':user_id', $userId);
            
            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Mark all as read error: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendRequisitionNotification($requisitionId, $action, $level = null) {
        try {
            // Get requisition details
            $this->db->query('SELECT r.*, u.full_name as created_by_name, u.email as created_by_email 
                             FROM requisitions r 
                             LEFT JOIN users u ON r.created_by = u.id 
                             WHERE r.id = :id');
            $this->db->bind(':id', $requisitionId);
            $requisition = $this->db->single();
            
            if (!$requisition) return false;
            
            $notifications = [];
            
            switch ($action) {
                case 'submitted':
                    // Notify approvers at current level
                    $approvers = $this->getApproversForLevel($requisition['current_approval_level'], $requisition['department']);
                    foreach ($approvers as $approver) {
                        $notifications[] = [
                            'user_id' => $approver['id'],
                            'type' => 'approval_required',
                            'title' => 'New Requisition Requires Approval',
                            'message' => "Requisition {$requisition['reference_number']} from {$requisition['created_by_name']} requires your approval.",
                            'data' => ['requisition_id' => $requisitionId, 'reference' => $requisition['reference_number']]
                        ];
                    }
                    break;
                    
                case 'approved':
                    // Notify originator
                    $notifications[] = [
                        'user_id' => $requisition['created_by'],
                        'type' => 'requisition_approved',
                        'title' => 'Requisition Approved',
                        'message' => "Your requisition {$requisition['reference_number']} has been approved at level {$level}.",
                        'data' => ['requisition_id' => $requisitionId, 'reference' => $requisition['reference_number']]
                    ];
                    
                    // If not final approval, notify next level approvers
                    if ($level < $requisition['final_approval_level']) {
                        $nextApprovers = $this->getApproversForLevel($level + 1, $requisition['department']);
                        foreach ($nextApprovers as $approver) {
                            $notifications[] = [
                                'user_id' => $approver['id'],
                                'type' => 'approval_required',
                                'title' => 'Requisition Requires Your Approval',
                                'message' => "Requisition {$requisition['reference_number']} has been approved at level {$level} and now requires your approval.",
                                'data' => ['requisition_id' => $requisitionId, 'reference' => $requisition['reference_number']]
                            ];
                        }
                    } else {
                        // Final approval - notify originator
                        $notifications[] = [
                            'user_id' => $requisition['created_by'],
                            'type' => 'requisition_final_approved',
                            'title' => 'Requisition Fully Approved',
                            'message' => "Your requisition {$requisition['reference_number']} has been fully approved and is ready for processing.",
                            'data' => ['requisition_id' => $requisitionId, 'reference' => $requisition['reference_number']]
                        ];
                    }
                    break;
                    
                case 'rejected':
                    // Notify originator
                    $notifications[] = [
                        'user_id' => $requisition['created_by'],
                        'type' => 'requisition_rejected',
                        'title' => 'Requisition Rejected',
                        'message' => "Your requisition {$requisition['reference_number']} has been rejected at level {$level}.",
                        'data' => ['requisition_id' => $requisitionId, 'reference' => $requisition['reference_number']]
                    ];
                    break;
            }
            
            // Create notifications
            foreach ($notifications as $notification) {
                $this->create(
                    $notification['user_id'],
                    $notification['type'],
                    $notification['title'],
                    $notification['message'],
                    $notification['data']
                );
                
                // Send email notification
                $this->sendEmailNotification($notification);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Send requisition notification error: " . $e->getMessage());
            return false;
        }
    }
    
    private function getApproversForLevel($level, $department) {
        try {
            $this->db->query('SELECT u.* FROM users u 
                             WHERE u.level >= :level 
                             AND u.has_roles LIKE :role 
                             AND u.status = :status
                             AND (u.department = :department OR :level >= 4)');
            
            $this->db->bind(':level', $level + 2); // Level 1 approval needs level 3+
            $this->db->bind(':role', '%approver%');
            $this->db->bind(':status', 'active');
            $this->db->bind(':department', $department);
            
            return $this->db->resultSet();
        } catch (Exception $e) {
            error_log("Get approvers for level error: " . $e->getMessage());
            return [];
        }
    }
    
    private function sendEmailNotification($notification) {
        try {
            // Get user email
            $this->db->query('SELECT email, full_name FROM users WHERE id = :id');
            $this->db->bind(':id', $notification['user_id']);
            $user = $this->db->single();
            
            if (!$user || !$user['email']) return false;
            
            $subject = $notification['title'];
            $message = "
                <html>
                <head>
                    <title>{$subject}</title>
                </head>
                <body>
                    <h2>{$subject}</h2>
                    <p>Dear {$user['full_name']},</p>
                    <p>{$notification['message']}</p>
                    <p>Please log in to the system to take appropriate action.</p>
                    <p>Best regards,<br>" . APP_NAME . "</p>
                </body>
                </html>
            ";
            
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: " . APP_NAME . " <noreply@requisition.com>" . "\r\n";
            
            return mail($user['email'], $subject, $message, $headers);
        } catch (Exception $e) {
            error_log("Send email notification error: " . $e->getMessage());
            return false;
        }
    }
}
?>