<?php
require_once 'config/config.php';
require_once 'config/Database.php';

$db = new Database(); 

require_once 'phpmailer/Exception.php';
require_once 'phpmailer/PHPMailer.php';
require_once 'phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer; 

$today = date('Y-m-d'); 
$yesterday = date('Y-m-d', strtotime('-2 day'));
$year = date('Y');
$month = date('n');

// Get all budgeted income lines for the current month
$db->query("SELECT income_line, daily_target FROM income_budget WHERE year = :year AND month = :month");
$db->bind(':year', $year);
$db->bind(':month', $month);
$budgets = $db->resultSet();

// Management emails to notify
$managementEmails = ['hhausa@yahoo.com','samuel.agbonifoh@thearenamarket.com','afolabi.oladele@thearenamarket.com','kenneth.nwachukwu@thearenamarket.com','joan.usman@thearenamarket.com','opeyemi.akinluyi@thearenamarket.com','emmanuel.okadigbo@thearenamarket.com','woobserp@gmail.com'];

try {		 
    $mail->isSMTP();                      // Set mailer to use SMTP 
    $mail->Host = 'smtp.gmail.com';    // Specify main and backup SMTP servers 
    $mail->SMTPAuth = true;               // Enable SMTP authentication 
    $mail->Username = 'woobserp@gmail.com';   // SMTP username 
    $mail->Password = 'cdkwjqjiseoosjhx';   // Your gmail app password
    $mail->SMTPSecure = 'ssl';            // Enable TLS encryption, `ssl` also accepted 
    $mail->Port = 465;                    // TCP port to connect to 
    // $mail->SMTPDebug = 2;
    // $mail->Debugoutput ='html';
			 
    $mail->setFrom('woobserp@gmail.com', 'ERP VARIABLE INCOME LINE ALERT SYSTEM');
    // $mail->addReplyTo('opeyemi.akinluyi@thearenamarket.com', 'Opeyemi Akinluyi'); 
    // $mail->addCC('emmanuel.okadigbo@thearenamarket.com'); 

    
    $message = "Variable Incomes Performance Notification,\n\n"
         . "The following income lines underperformed on <strong>" . $yesterday . "</strong> \n\n";

    $anyDeficit = false;

    foreach ($budgets as $budget) {
        $incomeLine = $budget['income_line'];
        switch($incomeLine) {
            case 'daily_trade':
                $incomeLineOutput = 'Daily Trade';
                break;
            case 'abattoir':
                $incomeLineOutput = 'Abattoir';
                break;
            case 'car_loading':
                $incomeLineOutput = 'Car Loading';
                break;
            case 'carpark':
                $incomeLineOutput = 'Car Park';
                break;
            case 'hawkers':
                $incomeLineOutput = 'Hawkers';
                break;
            case 'overnight_parking':
                $incomeLineOutput = 'Overnight Parking';
                break;
            case 'daily_trade_arrears':
                $incomeLineOutput = 'Daily Trade Arrears';
                break;
            case 'toilet_collection':
                $incomeLineOutput = 'Toilet Collection';
                break;
            case 'wheelbarrow':
                $incomeLineOutput = 'Wheelbarrow Ticket';
                break;
            case 'loading':
                $incomeLineOutput = 'Loading';
                break;
            case 'scroll_board':
                $incomeLineOutput = 'Scroll Board';
                break;
            case 'other_pos':
                $incomeLineOutput = 'Other POS';
                break;
            case 'general':
                $incomeLineOutput = 'General';
                break;
            default:
                $incomeLineOutput = $incomeLine;
                break;
        }
        $target = (float)$budget['daily_target'] + '500000.00';

        $db->query("SELECT SUM(amount_paid) AS total FROM account_general_transaction_new 
                    WHERE income_line = :income AND DATE(date_of_payment) = :yesterday");
        $db->bind(':income', $incomeLine);
        $db->bind(':yesterday', $yesterday);
        $result = $db->single();

        $actual = isset($result['total']) ? (float)$result['total'] : 0;

        if ($actual < $target) {
            $deficit = $target - $actual;
            $anyDeficit = true;

            // $subject = "Performance Alert: $incomeLineOutput";
            $message .= "<b>".$incomeLineOutput."</b> \n\n"
                //   . "Target: N" . number_format($target, 2) . "<br>"
                  . "Actual made: N" . number_format($actual, 2) . "<br>"
                  . "<br><br>";//"Deficit: N" . number_format($deficit, 2)

            // Log alert
            $db->query("INSERT INTO underperformance_alert_log 
                        (income_line, date, daily_target, actual_income, deficit, notified_to) 
                        VALUES (:line, :date, :target, :actual, :deficit, :notified)");
            $db->bind(':line', $incomeLine);
            $db->bind(':date', $today);
            $db->bind(':target', $target);
            $db->bind(':actual', $actual);
            $db->bind(':deficit', $deficit);
            $db->bind(':notified', implode(',', $managementEmails));
            $db->execute();
        }
    }

    if ($anyDeficit) {
        $message .= "Please investigate and take appropriate action.<br><br>This is AI automated alert from the ERP System.";
    
        foreach ($managementEmails as $email) {
            $mail->addAddress($email);
        }
        $mail->Subject = "Daily Performance Alert Summary";
        $mail->Body    = nl2br($message);
        $mail->isHTML(true);
        $mail->send();
        $mail->clearAddresses();
    }
    echo "Daily income check completed.";

} catch (Exception $e) {
    echo "Mailer Error: {$mail->ErrorInfo}";
}

