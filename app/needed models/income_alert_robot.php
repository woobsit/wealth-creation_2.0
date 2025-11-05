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

function getWorkingDaysInMonth($year, $month) {
    $workingDays = 0;
    $date = "$year-$month-01";
    $lastDay = date('t', strtotime($date));

    for ($day = 1; $day <= $lastDay; $day++) {
        $current = strtotime("$year-$month-$day");
        $dayOfWeek = date('w', $current); // 0 (Sunday) to 6 (Saturday)

        if ($dayOfWeek != 0) { // Exclude Sundays
            $workingDays++;
        }
    }

    return $workingDays;
}

$year = date('Y');
$month = strtolower(date('M')); //jan, feb
$monthNum = date('m'); // e.g., "06"
$daysInMonth = getWorkingDaysInMonth($year, $monthNum);
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

// Get monthly budget and compute daily target
$db->query("SELECT income_line, `$month` AS monthly_budget FROM income_line_budgets");
$budgets = $db->resultSet();

// Management emails to notify
$managementEmails = ['opeyemi.akinluyi@thearenamarket.com','emmanuel.okadigbo@thearenamarket.com','woobserp@gmail.com'];//'hhausa@yahoo.com','samuel.agbonifoh@thearenamarket.com','afolabi.oladele@thearenamarket.com','kenneth.nwachukwu@thearenamarket.com','joan.usman@thearenamarket.com',

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
        $monthlyBudget = (float)$budget['monthly_budget'];
        $dailyTarget = $monthlyBudget / $daysInMonth;
    
        // Step 3: Get acct_id from accounts table for this income line
        $db->query("SELECT acct_id, acct_alias FROM accounts WHERE acct_table_name = :incomeLine");
        $db->bind(':incomeLine', $incomeLine);
        $acctRow = $db->single(); // single row expected 
    
        if (!$acctRow) {
            echo "No account found for income line: $incomeLine<br>";
            continue; // skip to next income line
        }
    
        $acct_id = $acctRow['acct_id'];
        $acct_alias = $acctRow['acct_alias'];
    
        // Step 4: Get total income for that account for yesterday
        $db->query("SELECT SUM(amount_paid) AS total, income_line FROM account_general_transaction_new 
                    WHERE credit_account = :income AND DATE(date_of_payment) = :yesterday");
        $db->bind(':income', $acct_id);
        $db->bind(':yesterday', $yesterday);
        $result = $db->single();
        // print_r($result);
        // exit;
        switch($acct_alias) {
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
            case 'cleaning_fee':
                $incomeLineOutput = 'Cleaning Fee';
                break;   
            case 'offloading_fruit':
                $incomeLineOutput = 'Fruit Offloading';
                break;
            case 'parking_store':
                $incomeLineOutput = 'Parking Store';
                break;
            case 'ok_loading_offloading':
                $incomeLineOutput = 'Ok loading & offloading';
                break;
            case 'application_form':
                $incomeLineOutput = 'Application Form';
                break;
            case 'car_sticker':
                $incomeLineOutput = 'Car Sticker';
                break;
            case 'taxi_operators':
                $incomeLineOutput = 'Taxi Operators (Renewal)';
                break;
            case 'goods_loading_offloading':
                $incomeLineOutput = 'Goods Offloading';
                break;
            case 'food_seller_permit':
                $incomeLineOutput = 'Food Seller Permit';
                break;
            case 'offloading_truck':
                $incomeLineOutput = 'Truck Offloading';
                break;
            case 'work_permit':
                $incomeLineOutput = 'Work Permit';
                break;
            case 'process_fee':
                $incomeLineOutput = 'Process Fee';
                break;
            case 'signage':
                $incomeLineOutput = 'Signage';
                break;
            case 'fine':
                $incomeLineOutput = 'Fine';
                break;
            case 'shoe_loading':
                $incomeLineOutput = 'Shoe Loading';
                break;
            case 'forklift':
                $incomeLineOutput = 'Fork Lift';
                break;
            case 'water_front:':
                $incomeLineOutput = 'Water Front';
                break;
            case 'complaint_fee':
                $incomeLineOutput = 'Complaint Fee';
                break;
            case 'trade_promo':
                $incomeLineOutput = 'Trade Promo';
                break;
            case 'advert':
                $incomeLineOutput = 'Advert';
                break;
            case 'porters':
                $incomeLineOutput = 'Porters';
                break;
            case 'pomo':
                $incomeLineOutput = 'Pomo';
                break;
            case 'overshading':
                $incomeLineOutput = 'Overshading';
                break;
            case 'overshading':
                $incomeLineOutput = 'Overshading';
                break;
            case 'fowl_space':
                $incomeLineOutput = 'Fowl Space';
                break;
            case 'billboard':
                $incomeLineOutput = 'Billboard';
                break;
            case 'goat_meat_association':
                $incomeLineOutput = 'Goat Meat Association';
                break;
            case 'fruit_space':
                $incomeLineOutput = 'Fruit Space';
                break;
            case 'sample_space':
                $incomeLineOutput = 'Sample Space';
                break;
            case 'coconut_space':
                $incomeLineOutput = 'Coconut Space';
                break;
            case 'apple_loading':
                $incomeLineOutput = 'Apple Loading';
                break;
            case 'sunday_market':
                $incomeLineOutput = 'Sunday Market';
                break;
            case 'wall_breaking':
                $incomeLineOutput = 'Wall Breaking';
                break;
            case 'sublease':
                $incomeLineOutput = 'Sublease';
                break;
            case 'oil_offloading':
                $incomeLineOutput = 'Oil Offloading';
                break;
            case 'scrap_metal':
                $incomeLineOutput = 'Scrap Metal';
                break;
            case 'daily_trade_overnight':
                $incomeLineOutput = 'Daily Trade Overnight';
                break;
            case 'ground_fee':
                $incomeLineOutput = 'Ground Fee';
                break;
            case 'oil_space':
                $incomeLineOutput = 'Oil Space';
                break;
            case 'trade_permit':
                $incomeLineOutput = 'Trade Permit';
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
    
        $actual = isset($result['total']) ? (float)$result['total'] : 0;
    
        if ($actual < $dailyTarget) {
            $deficit = $dailyTarget - $actual;
            $anyDeficit = true;

            $subject = "Performance Alert: $incomeLineOutput";
            $message .= "<b>".$incomeLineOutput."</b> \n\n"
                  . "Monthly Budget: N" . number_format($monthlyBudget, 2) ."<br>"
                  . "Daily Target: N" . number_format($dailyTarget, 2) . "<br>"
                  . "Actual made: N" . number_format($actual, 2) . "<br>"
                  . "Deficit: N" . number_format($deficit, 2) . "<br>"
                  . "<br><br>";

            // Log alert
            $db->query("INSERT INTO underperformance_alert_log 
                        (income_line, date, daily_target, actual_income, deficit, notified_to) 
                        VALUES (:line, :date, :target, :actual, :deficit, :notified)");
            $db->bind(':line', $incomeLine);
            $db->bind(':date', $today);
            $db->bind(':target', $dailyTarget);
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