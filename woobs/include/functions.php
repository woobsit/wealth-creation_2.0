<?php
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// function formatCurrency($amount) {
//     return '₦' . number_format($amount, 2);
// }

// function formatDate($date, $format = 'Y-m-d') {
//     return date($format, strtotime($date));
// }

function timeElapsed($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );

    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

function generateReceiptNumber() {
    return 'WRL' . date('Y') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

function logActivity($user_id, $action, $details = '') {
    try {
        $db = new Database();
        $db->query("INSERT INTO activity_logs (user_id, action, details, created_at) VALUES (:user_id, :action, :details, NOW())");
        $db->bind(':user_id', $user_id)
           ->bind(':action', $action)
           ->bind(':details', $details);
        $db->execute();
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

function sendAlert($user_id, $message, $type = 'info') {
    try {
        $db = new Database();
        $db->query("INSERT INTO alerts (user_id, message, type, created_at) VALUES (:user_id, :message, :type, NOW())");
        $db->bind(':user_id', $user_id)
           ->bind(':message', $message)
           ->bind(':type', $type);
        $db->execute();
    } catch (Exception $e) {
        error_log("Failed to send alert: " . $e->getMessage());
    }
}

function validateRequired($fields, $data) {
    $errors = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }
    return $errors;
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function isValidPhone($phone) {
    return preg_match('/^[0-9+\-\s()]{10,15}$/', $phone);
}

function getMonthName($monthNumber) {
    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
    
    return isset($months[$monthNumber]) ? $months[$monthNumber] : 'Unknown';
}

function calculatePercentage($part, $total) {
    if ($total == 0) return 0;
    return round(($part / $total) * 100, 2);
}
?>