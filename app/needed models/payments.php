<?php
error_reporting(E_ALL);
ini_set('log_errors',1);
ini_set('display_errors',1);
include 'include/session.php';

date_default_timezone_set('Africa/Lagos');

//Today as today
$current_date = date('Y-m-d');

//Today as any other day
//$current_date = "11/12/2023";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


//Processing Begins Here
$error = false;
$remit_id = "";

//General Processing
if ( isset($_POST['btn_post_general']) ) {
	$posting_officer_dept = $_POST['posting_officer_dept'];
	
	if ($posting_officer_dept == "Accounts"){
		$remit_id = "";
	} else {
		$remit_id = $_POST['remit_id'];
		if($remit_id == "" || $remit_id == " "){
			$error = true;
		} else {
			$remit_id = $_POST['remit_id'];
		}
	}
	
	$txref = time().mt_rand(0,9);
	
	$date_of_payment = $_POST['date_of_payment'];
	list($tid,$tim,$tiy) = explode("/",$date_of_payment);
	$date_of_payment = "$tiy-$tim-$tid";
	
	$receipt_no = $_POST['receipt_no'];
	$query = "SELECT * FROM account_general_transaction_new WHERE receipt_no='$receipt_no'";
	$result = mysqli_query($dbcon,$query);
	$receipt_data = @mysqli_fetch_array($result, MYSQLI_ASSOC);
	$receipt_posting_officer = $receipt_data['posting_officer_name'];
	$receipt_date_of_payment = $receipt_data['date_of_payment'];
	
	$count = mysqli_num_rows($result);
	if($count!=0){
		$error = true;
		$receipt_Error = "<h4><strong>ATTENTION:</strong> Transaction failed! The <strong>receipt No: $receipt_no</strong> you entered has already been used by $receipt_posting_officer on $receipt_date_of_payment!</h4>";
	}
	
	$amount = $_POST['amount_paid'];
	$amount_paid = preg_replace('/[,]/', '', $amount);
	
	$remitting_post = $_POST['remitting_staff'];
	list($remitting_id,$remitting_check) = explode("-",@$remitting_post);
	
	if ($remitting_check == "wc"){
		$squery = "SELECT * FROM staffs WHERE user_id='$remitting_id'";
		$sresult = mysqli_query($dbcon,$squery);
		$remitting_data = @mysqli_fetch_array($sresult, MYSQLI_ASSOC);
		$remitting_staff = $remitting_data['full_name'];
	} else {
		$squery = "SELECT * FROM staffs_others WHERE id='$remitting_id'";
		$sresult = @mysqli_query($dbcon,$squery);
		$remitting_data = @mysqli_fetch_array($sresult, MYSQLI_ASSOC);
		$remitting_staff = $remitting_data['full_name'];
	}

	$transaction_desc = $_POST['transaction_descr'];
	$transaction_desc = strip_tags($transaction_desc);
	$transaction_desc = htmlspecialchars($transaction_desc);
	
	$posting_officer_id = $_POST['posting_officer_id'];
	$posting_officer_name = $_POST['posting_officer_name'];
	
	if ($posting_officer_dept == "Wealth Creation"){
		//Check if the remittance balance remains unchanged  
		$amt_remitted = $_POST['amt_remitted'];

		$ca_query = "SELECT posting_officer_id, date_of_payment, payment_category, SUM(amount_paid) as amount_posted ";
		$ca_query .= "FROM account_general_transaction_new ";
		$ca_query .= "WHERE (posting_officer_id = '$posting_officer_id' AND payment_category='Other Collection' AND date_of_payment='$current_date') ";
		$ca_sum = @mysqli_query($dbcon,$ca_query);
		$ca_total = @mysqli_fetch_array($ca_sum, MYSQLI_ASSOC);
		
		$amount_posted = $ca_total['amount_posted'];
	
		$rm_query = "SELECT *, SUM(amount_paid) as amount_remitted ";
		$rm_query .= "FROM cash_remittance ";
		$rm_query .= "WHERE (remitting_officer_id = '$posting_officer_id' AND category='Other Collection' AND date='$current_date') ";
		$rm_sum = @mysqli_query($dbcon,$rm_query);
		$rm_total = @mysqli_fetch_array($rm_sum, MYSQLI_ASSOC);
		$amount_remitted = $rm_total['amount_remitted'];
		
		$unposted = $amount_remitted - $amount_posted;
		
		if($amount_paid > $unposted){
			$error = true;
			$amount_remitted_Error = "<h4><strong>ATTENTION:</strong> Transaction failed! The <strong>amount posted: &#8358 {$amount_paid}</strong> MUST be equal to<strong> &#8358 {$unposted} </strong>remittance balance!</h4>";
		}

		if($amt_remitted > $unposted){
			$error = true;

			//PHP Mail Script begins here 
			require ('phpmailer/Exception.php');
			require ('phpmailer/PHPMailer.php');
			require ('phpmailer/SMTP.php');

			 
			$mail = new PHPMailer; 
			 
			$mail->isSMTP();                      // Set mailer to use SMTP 
			$mail->Host = 'smtp.gmail.com';       // Specify main and backup SMTP servers 
			$mail->SMTPAuth = true;               // Enable SMTP authentication 
			$mail->Username = 'woobserp@gmail.com';   // SMTP username 
			$mail->Password = 'cdkwjqjiseoosjhx';   // Your gmail app password
			$mail->SMTPSecure = 'ssl';            // Enable TLS encryption, `ssl` also accepted 
			$mail->Port = 465;                    // TCP port to connect to 
			 
			// Sender info 
			$mail->setFrom('woobserp@gmail.com', 'Wealth Creation ERP'); 
			$mail->addReplyTo('ahmed.olusesi@thearenamarket.com', 'Ahmed Olusesi'); 
			 
			// Add a recipient 
			$mail->addAddress('emmanuel.okadigbo@thearenamarket.com'); 
			 
			$mail->addCC('ahmed.olusesi@thearenamarket.com'); 
			$mail->addBCC('opeyemi.akinluyi@thearenamarket.com');  
						 
			// Set email format to HTML 
			$mail->isHTML(true); 

			// Mail subject 
			$mail->Subject = $posting_officer_name.' Inconsistent Remittance Balance - Wealth Creation ERP'; 
			 
			// Mail body content
			$bodyContent = '<h3>Inconsistent Remittance Balance - '.$posting_officer_name.'</h3>';
			$bodyContent .= '<p>Be informed that the above officer attempted to post <strong>'.$transaction_desc.'</strong> of <strong>&#8358 '.$amount_paid.'</strong> with receipt no: <strong>'.$receipt_no.'</strong> using a posting menu with remittance balance of <strong>&#8358 '.$amt_remitted.'</strong> when the actual remittance balance is &#8358 <strong>'.$unposted.'</strong></p>'; 
			$bodyContent .= '<p>This email is automatically sent from the Wealth Creation ERP server</p>'; 
			$mail->Body    = $bodyContent; 
			 
			// Send email 
			if(!$mail->send()) { 
				echo 'Message could not be sent. Mailer Error: '.$mail->ErrorInfo; 
			} else { 
				echo 'Mail notification successfully sent to IT Dept!'; 
			} 
			//PHP Mail Script ends here
			
			
			$remittance_bal_Error = "<h4><strong>WARNING:</strong> Transaction failed! Your remittance balance is <strong>: &#8358 {$unposted} </strong> and NOT <strong>&#8358 {$amt_remitted}</strong>. Kindly <strong>CLOSE ALL DUPLICATE</strong> posting pages and <strong>RE-OPEN</strong> your posting page from the main navigation menu. Ensure you maintain a single posting page. <strong>Be WARNED!</strong> Any further attempt will automatically disable you! You are being watched!!!</h4>";
		}
	}
	
	
	if ($posting_officer_dept == "Accounts"){
		$leasing_post_status = "";
		$approval_status = "Pending";
		$verification_status = "Pending";
	} else {
		$leasing_post_status = "Pending";
		$approval_status = "";
		$verification_status = "";
	}
	
	if ($posting_officer_dept == "Accounts"){
		$debit_alias = $_POST['debit_account'];
		$credit_alias = $_POST['credit_account'];
	} else {
		$debit_alias = $_POST['debit_alias'];
		$credit_alias = $_POST['credit_alias'];
	}
	
	$balance = "";
	
	if (empty($debit_alias)) {
		$error = true;
		$debiterror = "Please select the debit account";
	}
	if (empty($credit_alias)) {
		$error = true;
		$crediterror = "Please select the credit account";
	}
	
	
if (!$error) {
	//Debit Account
	$query_acct1 = "SELECT * ";
	$query_acct1 .= "FROM accounts ";
	
	if ($posting_officer_dept == "Accounts"){
		$query_acct1 .= "WHERE acct_id = '$debit_alias'";
	} else {
		$query_acct1 .= "WHERE acct_alias = '$debit_alias'";
	}
	$acct_debit_table_set = mysqli_query($dbcon, $query_acct1);
	$acct_debit_table = mysqli_fetch_array($acct_debit_table_set, MYSQLI_ASSOC);
	
	$debit_account = $acct_debit_table["acct_id"];
	$db_debit_table = $acct_debit_table["acct_table_name"];
	
	
	//Credit Account
	$query_acct2 = "SELECT * ";
	$query_acct2 .= "FROM accounts ";
	
	if ($posting_officer_dept == "Accounts"){
		$query_acct2 .= "WHERE acct_id = '$credit_alias'";
	} else {
		$query_acct2 .= "WHERE acct_id = '$credit_alias'";
	}
	$acct_credit_table_set = mysqli_query($dbcon, $query_acct2);
	$acct_credit_table = mysqli_fetch_array($acct_credit_table_set, MYSQLI_ASSOC);
	
	$credit_account = $acct_credit_table["acct_id"];
	$credit_account_desc = $acct_credit_table["acct_desc"];
	$db_credit_table = $acct_credit_table["acct_table_name"];
	
	if ($posting_officer_dept == "Accounts"){
		$income_line = $credit_account_desc;
	} else {
		$income_line = $_POST['income_line'];
	}
	
	$transaction_desc = $credit_account_desc.' - '.$transaction_desc;
	
	$db_transaction_table = "account_general_transaction_new";
	
	date_default_timezone_set('Africa/Lagos');
	$now = date('Y-m-d H:i:s');
	
	$payment_category = "Other Collection";
	$wc_income_line = $_POST['income_line'];
	
		$query = "INSERT INTO $db_transaction_table (id,date_of_payment,transaction_desc,receipt_no,amount_paid,remitting_id,remitting_staff,posting_officer_id,posting_officer_name,posting_time,leasing_post_status,approval_status,verification_status,debit_account,credit_account,payment_category,plate_no,remit_id,income_line) VALUES('$txref','$date_of_payment','$transaction_desc','$receipt_no','$amount_paid','$remitting_id','$remitting_staff','$posting_officer_id','$posting_officer_name','$now','$leasing_post_status','$approval_status','$verification_status','$debit_account','$credit_account','$payment_category','','$remit_id','$wc_income_line')";
		
		$post_payment = mysqli_query($dbcon, $query);


		$dquery = "INSERT INTO $db_debit_table (id,acct_id,date,receipt_no,trans_desc,debit_amount,balance,approval_status) VALUES('$txref','$debit_account','$date_of_payment','$receipt_no','$transaction_desc','$amount_paid','$balance','$approval_status')";
		
		$debit_query = mysqli_query($dbcon, $dquery);
		
	
		$cquery = "INSERT INTO $db_credit_table (id,acct_id,date,receipt_no,trans_desc,credit_amount,balance,approval_status) VALUES('$txref','$credit_account','$date_of_payment','$receipt_no','$transaction_desc','$amount_paid','$balance','$approval_status')";
		
		$credit_query = mysqli_query($dbcon, $cquery);

		
if ($debit_query)
	{
		?>
		<script type="text/javascript">
		alert('Payment successfully posted for approval!');
		window.location.href='payments.php';
		</script>
		<?php
	}
	else
	{
		?>
		<script type="text/javascript">
		alert('Error occured while posting');
		window.location.href='payments.php';
		</script>
		<?php
	}
	
	
}
}




//Car Loading Processing
if ( isset($_POST['btn_post_car_loading']) ) {
	$posting_officer_dept = $_POST['posting_officer_dept'];
	
	if ($posting_officer_dept == "Accounts"){
		$remit_id = "";
	} else {
		$remit_id = $_POST['remit_id'];
		if($remit_id == "" || $remit_id == " "){
			$error = true;
		} else {
			$remit_id = $_POST['remit_id'];
		}
	}
	
	$income_line = $_POST['income_line'];
	
	$txref = time().mt_rand(0,9);
	
	$date_of_payment = $_POST['date_of_payment'];
	list($tid,$tim,$tiy) = explode("/",$date_of_payment);
	$date_of_payment = "$tiy-$tim-$tid";
	
	$receipt_no = $_POST['receipt_no'];
	$query = "SELECT * FROM account_general_transaction_new WHERE receipt_no='$receipt_no'";
	$result = mysqli_query($dbcon,$query);
	$receipt_data = @mysqli_fetch_array($result, MYSQLI_ASSOC);
	$receipt_posting_officer = $receipt_data['posting_officer_name'];
	$receipt_date_of_payment = $receipt_data['date_of_payment'];
	
	$count = mysqli_num_rows($result);
	if($count!=0){
		$error = true;
		$receipt_Error = "<h4><strong>ATTENTION:</strong> Transaction failed! The <strong>receipt No: $receipt_no</strong> you entered has already been used by $receipt_posting_officer on $receipt_date_of_payment!</h4>";
	}

	$no_of_tickets = $_POST['no_of_tickets'];
	
	$amount = $_POST['amount_paid'];
	$amount_paid = preg_replace('/[,]/', '', $amount);
	
	$remitting_post = $_POST['remitting_staff'];
	list($remitting_id,$remitting_check) = explode("-",@$remitting_post);
	
	if ($remitting_check == "wc"){
		$squery = "SELECT * FROM staffs WHERE user_id='$remitting_id'";
		$sresult = mysqli_query($dbcon,$squery);
		$remitting_data = @mysqli_fetch_array($sresult, MYSQLI_ASSOC);
		$remitting_staff = $remitting_data['full_name'];
	} else {
		$squery = "SELECT * FROM staffs_others WHERE id='$remitting_id'";
		$sresult = mysqli_query($dbcon,$squery);
		$remitting_data = @mysqli_fetch_array($sresult, MYSQLI_ASSOC);
		$remitting_staff = $remitting_data['full_name'];
	}
	
	$transaction_desc = $_POST['transaction_descr'];
	$transaction_desc = strip_tags($transaction_desc);
	$transaction_desc = htmlspecialchars($transaction_desc);
	
	$transaction_desc = $transaction_desc.' - '.$remitting_staff;
	
	$posting_officer_id = $_POST['posting_officer_id'];
	$posting_officer_name = $_POST['posting_officer_name'];
	
	
	if ($posting_officer_dept == "Wealth Creation"){
		//Check if the remittance balance remains unchanged  
		$amt_remitted = $_POST['amt_remitted'];

		$ca_query = "SELECT posting_officer_id, date_of_payment, payment_category, SUM(amount_paid) as amount_posted ";
		$ca_query .= "FROM account_general_transaction_new ";
		$ca_query .= "WHERE (posting_officer_id = '$posting_officer_id' AND payment_category='Other Collection' AND date_of_payment='$current_date') ";
		$ca_sum = @mysqli_query($dbcon,$ca_query);
		$ca_total = @mysqli_fetch_array($ca_sum, MYSQLI_ASSOC);
		
		$amount_posted = $ca_total['amount_posted'];
	
		$rm_query = "SELECT *, SUM(amount_paid) as amount_remitted ";
		$rm_query .= "FROM cash_remittance ";
		$rm_query .= "WHERE (remitting_officer_id = '$posting_officer_id' AND category='Other Collection' AND date='$current_date') ";
		$rm_sum = @mysqli_query($dbcon,$rm_query);
		$rm_total = @mysqli_fetch_array($rm_sum, MYSQLI_ASSOC);
		$amount_remitted = $rm_total['amount_remitted'];
		
		$unposted = $amount_remitted - $amount_posted;
		
		if($amount_paid > $unposted){
			$error = true;
			$amount_remitted_Error = "<h4><strong>ATTENTION:</strong> Transaction failed! The <strong>amount posted: &#8358 {$amount_paid}</strong> MUST be equal to<strong> &#8358 {$unposted} </strong>remittance balance!</h4>";
		}

		if($amt_remitted > $unposted){
			$error = true;

			//PHP Mail Script begins here 
			require ('phpmailer/Exception.php');
			require ('phpmailer/PHPMailer.php');
			require ('phpmailer/SMTP.php');

			 
			$mail = new PHPMailer; 
			 
			$mail->isSMTP();                      // Set mailer to use SMTP 
			$mail->Host = 'smtp.gmail.com';       // Specify main and backup SMTP servers 
			$mail->SMTPAuth = true;               // Enable SMTP authentication 
			$mail->Username = 'woobserp@gmail.com';   // SMTP username 
			$mail->Password = 'cdkwjqjiseoosjhx';   // Your gmail app password
			$mail->SMTPSecure = 'ssl';            // Enable TLS encryption, `ssl` also accepted 
			$mail->Port = 465;                    // TCP port to connect to 
			 
			// Sender info 
			$mail->setFrom('woobserp@gmail.com', 'Wealth Creation ERP'); 
			$mail->addReplyTo('dayo.adebajo@thearenamarket.com', 'Dayo Adebajo'); 
			 
			// Add a recipient 
			$mail->addAddress('dayo.adebajo@thearenamarket.com'); 
			 
			$mail->addCC('emmanuel.okadigbo@thearenamarket.com'); 
			//$mail->addBCC('dayo.adebajo@thearenamarket.com'); 
			 
			// Set email format to HTML 
			$mail->isHTML(true); 

			// Mail subject 
			$mail->Subject = $posting_officer_name.' Inconsistent Remittance Balance - Wealth Creation ERP'; 
			 
			// Mail body content
			$bodyContent = '<h3>Inconsistent Remittance Balance - '.$posting_officer_name.'</h3>';
			$bodyContent .= '<p>Be informed that the above officer attempted to post <strong>'.$transaction_desc.'</strong> of <strong>&#8358 '.$amount_paid.'</strong> with receipt no: <strong>'.$receipt_no.'</strong> using a posting menu with remittance balance of <strong>&#8358 '.$amt_remitted.'</strong> when the actual remittance balance is &#8358 <strong>'.$unposted.'</strong></p>'; 
			$bodyContent .= '<p>This email is automatically sent from the Wealth Creation ERP server</p>'; 
			$mail->Body    = $bodyContent; 
			 
			// Send email 
			if(!$mail->send()) { 
				echo 'Message could not be sent. Mailer Error: '.$mail->ErrorInfo; 
			} else { 
				echo 'Mail notification successfully sent to IT Dept!'; 
			} 
			//PHP Mail Script ends here
			
			
			$remittance_bal_Error = "<h4><strong>WARNING:</strong> Transaction failed! Your remittance balance is <strong>: &#8358 {$unposted} </strong> and NOT <strong>&#8358 {$amt_remitted}</strong>. Kindly <strong>CLOSE ALL DUPLICATE</strong> posting pages and <strong>RE-OPEN</strong> your posting page from the main navigation menu. Ensure you maintain a single posting page. <strong>Be WARNED!</strong> Any further attempt will automatically disable you! You are being watched!!!</h4>";
		}
	}
	
	
	if ($posting_officer_dept == "Accounts"){
		$leasing_post_status = "";
		$approval_status = "Pending";
		$verification_status = "Pending";
	} else {
		$leasing_post_status = "Pending";
		$approval_status = "";
		$verification_status = "";
	}
	
	if ($posting_officer_dept == "Accounts"){
		$debit_alias = $_POST['debit_account']; //acct_id
		$credit_alias = $_POST['credit_account']; //acct_id
	} else {
		$debit_alias = $_POST['debit_alias']; //alias
		$credit_alias = $_POST['credit_alias']; //alias
	}
	
	$balance = "";
	
	if (empty($debit_alias)) {
		$error = true;
		$debiterror = "Please select the debit account";
	}
	if (empty($credit_alias)) {
		$error = true;
		$crediterror = "Please select the credit account";
	}
	
	
if (!$error) {
	//Debit Account
	$query_acct1 = "SELECT * ";
	$query_acct1 .= "FROM accounts ";
	
	if ($posting_officer_dept == "Accounts"){
		$query_acct1 .= "WHERE acct_id = '$debit_alias'";
	} else {
		$query_acct1 .= "WHERE acct_alias = '$debit_alias'";
	}
	$acct_debit_table_set = mysqli_query($dbcon, $query_acct1);
	$acct_debit_table = mysqli_fetch_array($acct_debit_table_set, MYSQLI_ASSOC);
	
	$debit_account = $acct_debit_table["acct_id"];
	$db_debit_table = $acct_debit_table["acct_table_name"];
	
	
	//Credit Account
	$query_acct2 = "SELECT * ";
	$query_acct2 .= "FROM accounts ";
	
	if ($posting_officer_dept == "Accounts"){
		$query_acct2 .= "WHERE acct_id = '$credit_alias'";
	} else {
		$query_acct2 .= "WHERE acct_alias = '$credit_alias'";
	}
	$acct_credit_table_set = mysqli_query($dbcon, $query_acct2);
	$acct_credit_table = mysqli_fetch_array($acct_credit_table_set, MYSQLI_ASSOC);
	
	$credit_account = $acct_credit_table["acct_id"];
	$credit_account_desc = $acct_credit_table["acct_desc"];
	$db_credit_table = $acct_credit_table["acct_table_name"];
	
	if ($posting_officer_dept == "Accounts"){
		$income_line = $credit_account_desc;
	} else {
		$income_line = $_POST['income_line'];
	}

	
	$db_transaction_table = "account_general_transaction_new";
	
	date_default_timezone_set('Africa/Lagos');
	$now = date('Y-m-d H:i:s');
	
	$payment_category = "Other Collection";
	$wc_income_line = $_POST['income_line'];
		
		$query = "INSERT INTO $db_transaction_table (id,date_of_payment,transaction_desc,receipt_no,amount_paid,remitting_id,remitting_staff,posting_officer_id,posting_officer_name,posting_time,leasing_post_status,approval_status,verification_status,debit_account,credit_account,payment_category,no_of_tickets,remit_id,income_line) VALUES('$txref','$date_of_payment','$transaction_desc','$receipt_no','$amount_paid','$remitting_id','$remitting_staff','$posting_officer_id','$posting_officer_name','$now','$leasing_post_status','$approval_status','$verification_status','$debit_account','$credit_account','$payment_category','$no_of_tickets','$remit_id','$wc_income_line')";
		
		$post_payment = mysqli_query($dbcon, $query);


		$dquery = "INSERT INTO $db_debit_table (id,acct_id,date,receipt_no,trans_desc,debit_amount,balance,approval_status) VALUES('$txref','$debit_account','$date_of_payment','$receipt_no','$transaction_desc','$amount_paid','$balance','$approval_status')";
		
		$debit_query = mysqli_query($dbcon, $dquery);
		
	
		$cquery = "INSERT INTO $db_credit_table (id,acct_id,date,receipt_no,trans_desc,credit_amount,balance,approval_status) VALUES('$txref','$credit_account','$date_of_payment','$receipt_no','$transaction_desc','$amount_paid','$balance','$approval_status')";
		
		$credit_query = mysqli_query($dbcon, $cquery);

		
if ($debit_query)
	{
		?>
		<script type="text/javascript">
		alert('Payment successfully posted for approval!');
		window.location.href='payments.php';
		</script>
		<?php
	}
	else
	{
		?>
		<script type="text/javascript">
		alert('Error occured while posting');
		window.location.href='payments.php';
		</script>
		<?php
	}
}
}




//Hawkers Processing
if ( isset($_POST['btn_post_hawkers']) ) {
	$posting_officer_dept = $_POST['posting_officer_dept'];
	
	if ($posting_officer_dept == "Accounts"){
		$remit_id = "";
	} else {
		$remit_id = $_POST['remit_id'];
		if($remit_id == "" || $remit_id == " "){
			$error = true;
		} else {
			$remit_id = $_POST['remit_id'];
		}
	}
	
	$income_line = $_POST['income_line'];
	
	$txref = time().mt_rand(0,9);
	
	$date_of_payment = $_POST['date_of_payment'];
	list($tid,$tim,$tiy) = explode("/",$date_of_payment);
	$date_of_payment = "$tiy-$tim-$tid";
	
	$receipt_no = $_POST['receipt_no'];
	$query = "SELECT * FROM account_general_transaction_new WHERE receipt_no='$receipt_no'";
	$result = mysqli_query($dbcon,$query);
	$receipt_data = @mysqli_fetch_array($result, MYSQLI_ASSOC);
	$receipt_posting_officer = $receipt_data['posting_officer_name'];
	$receipt_date_of_payment = $receipt_data['date_of_payment'];
	
	$count = mysqli_num_rows($result);
	if($count!=0){
		$error = true;
		$receipt_Error = "<h4><strong>ATTENTION:</strong> Transaction failed! The <strong>receipt No: $receipt_no</strong> you entered has already been used by $receipt_posting_officer on $receipt_date_of_payment!</h4>";
	}

	$no_of_tickets = $_POST['no_of_tickets'];
	
	$amount = $_POST['amount_paid'];
	$amount_paid = preg_replace('/[,]/', '', $amount);
	
	$remitting_post = $_POST['remitting_staff'];
	list($remitting_id,$remitting_check) = explode("-",@$remitting_post);
	
	if ($remitting_check == "wc"){
		$squery = "SELECT * FROM staffs WHERE user_id='$remitting_id'";
		$sresult = mysqli_query($dbcon,$squery);
		$remitting_data = @mysqli_fetch_array($sresult, MYSQLI_ASSOC);
		$remitting_staff = $remitting_data['full_name'];
	} elseif ($remitting_check == "os"){
		$squery = "SELECT * FROM staffs WHERE user_id='$remitting_id'";
		$sresult = mysqli_query($dbcon,$squery);
		$remitting_data = @mysqli_fetch_array($sresult, MYSQLI_ASSOC);
		$remitting_staff = $remitting_data['full_name'];
	} else {
		$squery = "SELECT * FROM staffs_others WHERE id='$remitting_id'";
		$sresult = mysqli_query($dbcon,$squery);
		$remitting_data = @mysqli_fetch_array($sresult, MYSQLI_ASSOC);
		$remitting_staff = $remitting_data['full_name'];
	}
	
	$transaction_desc = $_POST['transaction_descr'];
	$transaction_desc = strip_tags($transaction_desc);
	$transaction_desc = htmlspecialchars($transaction_desc);
	
	$transaction_desc = $transaction_desc.' - '.$remitting_staff;
	
	$posting_officer_id = $_POST['posting_officer_id'];
	$posting_officer_name = $_POST['posting_officer_name'];
	
	
	if ($posting_officer_dept == "Wealth Creation"){
		//Check if the remittance balance remains unchanged  
		$amt_remitted = $_POST['amt_remitted'];

		$ca_query = "SELECT posting_officer_id, date_of_payment, payment_category, SUM(amount_paid) as amount_posted ";
		$ca_query .= "FROM account_general_transaction_new ";
		$ca_query .= "WHERE (posting_officer_id = '$posting_officer_id' AND payment_category='Other Collection' AND date_of_payment='$current_date') ";
		$ca_sum = @mysqli_query($dbcon,$ca_query);
		$ca_total = @mysqli_fetch_array($ca_sum, MYSQLI_ASSOC);
		
		$amount_posted = $ca_total['amount_posted'];
	
		$rm_query = "SELECT *, SUM(amount_paid) as amount_remitted ";
		$rm_query .= "FROM cash_remittance ";
		$rm_query .= "WHERE (remitting_officer_id = '$posting_officer_id' AND category='Other Collection' AND date='$current_date') ";
		$rm_sum = @mysqli_query($dbcon,$rm_query);
		$rm_total = @mysqli_fetch_array($rm_sum, MYSQLI_ASSOC);
		$amount_remitted = $rm_total['amount_remitted'];
		
		$unposted = $amount_remitted - $amount_posted;
		
		if($amount_paid > $unposted){
			$error = true;
			$amount_remitted_Error = "<h4><strong>ATTENTION:</strong> Transaction failed! The <strong>amount posted: &#8358 {$amount_paid}</strong> MUST be equal to<strong> &#8358 {$unposted} </strong>remittance balance!</h4>";
		}

		if($amt_remitted > $unposted){
			$error = true;

			//PHP Mail Script begins here 
			require ('phpmailer/Exception.php');
			require ('phpmailer/PHPMailer.php');
			require ('phpmailer/SMTP.php');

			 
			$mail = new PHPMailer; 
			 
			$mail->isSMTP();                      // Set mailer to use SMTP 
			$mail->Host = 'smtp.gmail.com';       // Specify main and backup SMTP servers 
			$mail->SMTPAuth = true;               // Enable SMTP authentication 
			$mail->Username = 'woobserp@gmail.com';   // SMTP username 
			$mail->Password = 'cdkwjqjiseoosjhx';   // Your gmail app password
			$mail->SMTPSecure = 'ssl';            // Enable TLS encryption, `ssl` also accepted 
			$mail->Port = 465;                    // TCP port to connect to 
			 
			// Sender info 
			$mail->setFrom('woobserp@gmail.com', 'Wealth Creation ERP'); 
			$mail->addReplyTo('dayo.adebajo@thearenamarket.com', 'Dayo Adebajo'); 
			 
			// Add a recipient 
			$mail->addAddress('dayo.adebajo@thearenamarket.com'); 
			 
			$mail->addCC('emmanuel.okadigbo@thearenamarket.com'); 
			//$mail->addBCC('dayo.adebajo@thearenamarket.com'); 
			 
			// Set email format to HTML 
			$mail->isHTML(true); 

			// Mail subject 
			$mail->Subject = $posting_officer_name.' Inconsistent Remittance Balance - Wealth Creation ERP'; 
			 
			// Mail body content
			$bodyContent = '<h3>Inconsistent Remittance Balance - '.$posting_officer_name.'</h3>';
			$bodyContent .= '<p>Be informed that the above officer attempted to post <strong>'.$transaction_desc.'</strong> of <strong>&#8358 '.$amount_paid.'</strong> with receipt no: <strong>'.$receipt_no.'</strong> using a posting menu with remittance balance of <strong>&#8358 '.$amt_remitted.'</strong> when the actual remittance balance is &#8358 <strong>'.$unposted.'</strong></p>'; 
			$bodyContent .= '<p>This email is automatically sent from the Wealth Creation ERP server</p>'; 
			$mail->Body    = $bodyContent; 
			 
			// Send email 
			if(!$mail->send()) { 
				echo 'Message could not be sent. Mailer Error: '.$mail->ErrorInfo; 
			} else { 
				echo 'Mail notification successfully sent to IT Dept!'; 
			} 
			//PHP Mail Script ends here
			
			
			$remittance_bal_Error = "<h4><strong>WARNING:</strong> Transaction failed! Your remittance balance is <strong>: &#8358 {$unposted} </strong> and NOT <strong>&#8358 {$amt_remitted}</strong>. Kindly <strong>CLOSE ALL DUPLICATE</strong> posting pages and <strong>RE-OPEN</strong> your posting page from the main navigation menu. Ensure you maintain a single posting page. <strong>Be WARNED!</strong> Any further attempt will automatically disable you! You are being watched!!!</h4>";
		}
	}
	
	
	if ($posting_officer_dept == "Accounts"){
		$leasing_post_status = "";
		$approval_status = "Pending";
		$verification_status = "Pending";
	} else {
		$leasing_post_status = "Pending";
		$approval_status = "";
		$verification_status = "";
	}
	
	if ($posting_officer_dept == "Accounts"){
		$debit_alias = $_POST['debit_account'];
		$credit_alias = $_POST['credit_account'];
	} else {
		$debit_alias = $_POST['debit_alias'];
		$credit_alias = $_POST['credit_alias'];
	}
	
	$balance = "";
	
	if (empty($debit_alias)) {
		$error = true;
		$debiterror = "Please select the debit account";
	}
	if (empty($credit_alias)) {
		$error = true;
		$crediterror = "Please select the credit account";
	}
	
	
if (!$error) {
	//Debit Account
	$query_acct1 = "SELECT * ";
	$query_acct1 .= "FROM accounts ";
	
	if ($posting_officer_dept == "Accounts"){
		$query_acct1 .= "WHERE acct_id = '$debit_alias'";
	} else {
		$query_acct1 .= "WHERE acct_alias = '$debit_alias'";
	}
	$acct_debit_table_set = mysqli_query($dbcon, $query_acct1);
	$acct_debit_table = mysqli_fetch_array($acct_debit_table_set, MYSQLI_ASSOC);
	
	$debit_account = $acct_debit_table["acct_id"];
	$db_debit_table = $acct_debit_table["acct_table_name"];
	
	
	//Credit Account
	$query_acct2 = "SELECT * ";
	$query_acct2 .= "FROM accounts ";
	
	if ($posting_officer_dept == "Accounts"){
		$query_acct2 .= "WHERE acct_id = '$credit_alias'";
	} else {
		$query_acct2 .= "WHERE acct_alias = '$credit_alias'";
	}
	$acct_credit_table_set = mysqli_query($dbcon, $query_acct2);
	$acct_credit_table = mysqli_fetch_array($acct_credit_table_set, MYSQLI_ASSOC);
	
	$credit_account = $acct_credit_table["acct_id"];
	$credit_account_desc = $acct_credit_table["acct_desc"];
	$db_credit_table = $acct_credit_table["acct_table_name"];
	
	if ($posting_officer_dept == "Accounts"){
		$income_line = $credit_account_desc;
	} else {
		$income_line = $_POST['income_line'];
	}

	
	$db_transaction_table = "account_general_transaction_new";
	
	date_default_timezone_set('Africa/Lagos');
	$now = date('Y-m-d H:i:s');
	
	$payment_category = "Other Collection";
	$wc_income_line = $_POST['income_line'];
		
		$query = "INSERT INTO $db_transaction_table (id,date_of_payment,transaction_desc,receipt_no,amount_paid,remitting_id,remitting_staff,posting_officer_id,posting_officer_name,posting_time,leasing_post_status,approval_status,verification_status,debit_account,credit_account,payment_category,no_of_tickets,remit_id,income_line) VALUES('$txref','$date_of_payment','$transaction_desc','$receipt_no','$amount_paid','$remitting_id','$remitting_staff','$posting_officer_id','$posting_officer_name','$now','$leasing_post_status','$approval_status','$verification_status','$debit_account','$credit_account','$payment_category','$no_of_tickets','$remit_id','$wc_income_line')";
		
		$post_payment = mysqli_query($dbcon, $query);


		$dquery = "INSERT INTO $db_debit_table (id,acct_id,date,receipt_no,trans_desc,debit_amount,balance,approval_status) VALUES('$txref','$debit_account','$date_of_payment','$receipt_no','$transaction_desc','$amount_paid','$balance','$approval_status')";
		
		$debit_query = mysqli_query($dbcon, $dquery);
		
	
		$cquery = "INSERT INTO $db_credit_table (id,acct_id,date,receipt_no,trans_desc,credit_amount,balance,approval_status) VALUES('$txref','$credit_account','$date_of_payment','$receipt_no','$transaction_desc','$amount_paid','$balance','$approval_status')";
		
		$credit_query = mysqli_query($dbcon, $cquery);

		
if ($debit_query)
	{
		?>
		<script type="text/javascript">
		alert('Payment successfully posted for approval!');
		window.location.href='payments.php';
		</script>
		<?php
	}
	else
	{
		?>
		<script type="text/javascript">
		alert('Error occured while posting');
		window.location.href='payments.php';
		</script>
		<?php
	}
}
}



//Car Park Processing
if ( isset($_POST['btn_post_car_park']) ) {
	$posting_officer_dept = $_POST['posting_officer_dept'];
	
	if ($posting_officer_dept == "Accounts"){
		$remit_id = "";
	} else {
		$remit_id = $_POST['remit_id'];
		if($remit_id == "" || $remit_id == " "){
			$error = true;
		} else {
			$remit_id = $_POST['remit_id'];
		}
	}
	
	$income_line = $_POST['income_line'];
	
	$txref = time().mt_rand(0,9);
	
	$date_of_payment = $_POST['date_of_payment'];
	list($tid,$tim,$tiy) = explode("/",$date_of_payment);
	$date_of_payment = "$tiy-$tim-$tid";
	
	$category = $_POST['category'];
	
	$receipt_no = $_POST['receipt_no'];
	$query = "SELECT * FROM account_general_transaction_new WHERE receipt_no='$receipt_no'";
	$result = mysqli_query($dbcon,$query);
	$receipt_data = @mysqli_fetch_array($result, MYSQLI_ASSOC);
	$receipt_posting_officer = $receipt_data['posting_officer_name'];
	$receipt_date_of_payment = $receipt_data['date_of_payment'];
	
	$count = mysqli_num_rows($result);
	if($count!=0){
		$error = true;
		$receipt_Error = "<h4><strong>ATTENTION:</strong> Transaction failed! The <strong>receipt No: $receipt_no</strong> you entered has already been used by $receipt_posting_officer on $receipt_date_of_payment!</h4>";
	}
	
	$ticket_category = $_POST['ticket_category'];

	$no_of_tickets = $_POST['no_of_tickets'];
	
	$amount = $_POST['amount_paid'];
	$amount_paid = preg_replace('/[,]/', '', $amount);
	
	$remitting_post = $_POST['remitting_staff'];
	list($remitting_id,$remitting_check) = explode("-",@$remitting_post);
	
	if ($remitting_check == "wc"){
		$squery = "SELECT * FROM staffs WHERE user_id='$remitting_id'";
		$sresult = mysqli_query($dbcon,$squery);
		$remitting_data = @mysqli_fetch_array($sresult, MYSQLI_ASSOC);
		$remitting_staff = $remitting_data['full_name'];
	} else {
		$squery = "SELECT * FROM staffs_others WHERE id='$remitting_id'";
		$sresult = mysqli_query($dbcon,$squery);
		$remitting_data = @mysqli_fetch_array($sresult, MYSQLI_ASSOC);
		$remitting_staff = $remitting_data['full_name'];
	}
	
	$transaction_desc = $category.' - '.$remitting_staff;
	$transaction_desc = htmlspecialchars($transaction_desc);
	
	$posting_officer_id = $_POST['posting_officer_id'];
	$posting_officer_name = $_POST['posting_officer_name'];
	
	
	if ($posting_officer_dept == "Wealth Creation"){
		//Check if the remittance balance remains unchanged  
		$amt_remitted = $_POST['amt_remitted'];

		$ca_query = "SELECT posting_officer_id, date_of_payment, payment_category, SUM(amount_paid) as amount_posted ";
		$ca_query .= "FROM account_general_transaction_new ";
		$ca_query .= "WHERE (posting_officer_id = '$posting_officer_id' AND payment_category='Other Collection' AND date_of_payment='$current_date') ";
		$ca_sum = @mysqli_query($dbcon,$ca_query);
		$ca_total = @mysqli_fetch_array($ca_sum, MYSQLI_ASSOC);
		
		$amount_posted = $ca_total['amount_posted'];
	
		$rm_query = "SELECT *, SUM(amount_paid) as amount_remitted ";
		$rm_query .= "FROM cash_remittance ";
		$rm_query .= "WHERE (remitting_officer_id = '$posting_officer_id' AND category='Other Collection' AND date='$current_date') ";
		$rm_sum = @mysqli_query($dbcon,$rm_query);
		$rm_total = @mysqli_fetch_array($rm_sum, MYSQLI_ASSOC);
		$amount_remitted = $rm_total['amount_remitted'];
		
		$unposted = $amount_remitted - $amount_posted;
		
		if($amount_paid > $unposted){
			$error = true;
			$amount_remitted_Error = "<h4><strong>ATTENTION:</strong> Transaction failed! The <strong>amount posted: &#8358 {$amount_paid}</strong> MUST be equal to<strong> &#8358 {$unposted} </strong>remittance balance!</h4>";
		}

		if($amt_remitted > $unposted){
			$error = true;

			//PHP Mail Script begins here 
			require ('phpmailer/Exception.php');
			require ('phpmailer/PHPMailer.php');
			require ('phpmailer/SMTP.php');

			 
			$mail = new PHPMailer; 
			 
			$mail->isSMTP();                      // Set mailer to use SMTP 
			$mail->Host = 'smtp.gmail.com';       // Specify main and backup SMTP servers 
			$mail->SMTPAuth = true;               // Enable SMTP authentication 
			$mail->Username = 'woobserp@gmail.com';   // SMTP username 
			$mail->Password = 'cdkwjqjiseoosjhx';   // Your gmail app password
			$mail->SMTPSecure = 'ssl';            // Enable TLS encryption, `ssl` also accepted 
			$mail->Port = 465;                    // TCP port to connect to 
			 
			// Sender info 
			$mail->setFrom('woobserp@gmail.com', 'Wealth Creation ERP'); 
			$mail->addReplyTo('dayo.adebajo@thearenamarket.com', 'Dayo Adebajo'); 
			 
			// Add a recipient 
			$mail->addAddress('dayo.adebajo@thearenamarket.com'); 
			 
			$mail->addCC('emmanuel.okadigbo@thearenamarket.com'); 
			//$mail->addBCC('dayo.adebajo@thearenamarket.com'); 
			 
			// Set email format to HTML 
			$mail->isHTML(true); 

			// Mail subject 
			$mail->Subject = $posting_officer_name.' Inconsistent Remittance Balance - Wealth Creation ERP'; 
			 
			// Mail body content
			$bodyContent = '<h3>Inconsistent Remittance Balance - '.$posting_officer_name.'</h3>';
			$bodyContent .= '<p>Be informed that the above officer attempted to post <strong>'.$transaction_desc.'</strong> of <strong>&#8358 '.$amount_paid.'</strong> with receipt no: <strong>'.$receipt_no.'</strong> using a posting menu with remittance balance of <strong>&#8358 '.$amt_remitted.'</strong> when the actual remittance balance is &#8358 <strong>'.$unposted.'</strong></p>'; 
			$bodyContent .= '<p>This email is automatically sent from the Wealth Creation ERP server</p>'; 
			$mail->Body    = $bodyContent; 
			 
			// Send email 
			if(!$mail->send()) { 
				echo 'Message could not be sent. Mailer Error: '.$mail->ErrorInfo; 
			} else { 
				echo 'Mail notification successfully sent to IT Dept!'; 
			} 
			//PHP Mail Script ends here
			
			
			$remittance_bal_Error = "<h4><strong>WARNING:</strong> Transaction failed! Your remittance balance is <strong>: &#8358 {$unposted} </strong> and NOT <strong>&#8358 {$amt_remitted}</strong>. Kindly <strong>CLOSE ALL DUPLICATE</strong> posting pages and <strong>RE-OPEN</strong> your posting page from the main navigation menu. Ensure you maintain a single posting page. <strong>Be WARNED!</strong> Any further attempt will automatically disable you! You are being watched!!!</h4>";
		}
	}
	
	
	if ($posting_officer_dept == "Accounts"){
		$leasing_post_status = "";
		$approval_status = "Pending";
		$verification_status = "Pending";
	} else {
		$leasing_post_status = "Pending";
		$approval_status = "";
		$verification_status = "";
	}
	
	if ($posting_officer_dept == "Accounts"){
		$debit_alias = $_POST['debit_account'];
		$credit_alias = $_POST['credit_account'];
	} else {
		$debit_alias = $_POST['debit_alias'];
		$credit_alias = $_POST['credit_alias'];
	}
	
	$balance = "";
	
	if (empty($debit_alias)) {
		$error = true;
		$debiterror = "Please select the debit account";
	}
	if (empty($credit_alias)) {
		$error = true;
		$crediterror = "Please select the credit account";
	}
	
	
if (!$error) {
	//Debit Account
	$query_acct1 = "SELECT * ";
	$query_acct1 .= "FROM accounts ";
	
	if ($posting_officer_dept == "Accounts"){
		$query_acct1 .= "WHERE acct_id = '$debit_alias'";
	} else {
		$query_acct1 .= "WHERE acct_alias = '$debit_alias'";
	}
	$acct_debit_table_set = mysqli_query($dbcon, $query_acct1);
	$acct_debit_table = mysqli_fetch_array($acct_debit_table_set, MYSQLI_ASSOC);
	
	$debit_account = $acct_debit_table["acct_id"];
	$db_debit_table = $acct_debit_table["acct_table_name"];
	
	
	//Credit Account
	$query_acct2 = "SELECT * ";
	$query_acct2 .= "FROM accounts ";
	
	if ($posting_officer_dept == "Accounts"){
		$query_acct2 .= "WHERE acct_id = '$credit_alias'";
	} else {
		$query_acct2 .= "WHERE acct_alias = '$credit_alias'";
	}
	$acct_credit_table_set = mysqli_query($dbcon, $query_acct2);
	$acct_credit_table = mysqli_fetch_array($acct_credit_table_set, MYSQLI_ASSOC);
	
	$credit_account = $acct_credit_table["acct_id"];
	$credit_account_desc = $acct_credit_table["acct_desc"];
	$db_credit_table = $acct_credit_table["acct_table_name"];
	
	if ($posting_officer_dept == "Accounts"){
		$income_line = $credit_account_desc;
	} else {
		$income_line = $_POST['income_line'];
	}

	
	$db_transaction_table = "account_general_transaction_new";
	
	date_default_timezone_set('Africa/Lagos');
	$now = date('Y-m-d H:i:s');
	
	
	$payment_category = "Other Collection";
	$wc_income_line = $_POST['income_line'];
		
		$query = "INSERT INTO $db_transaction_table (id,date_of_payment,ticket_category,transaction_desc,receipt_no,amount_paid,remitting_id,remitting_staff,posting_officer_id,posting_officer_name,posting_time,leasing_post_status,approval_status,verification_status,debit_account,credit_account,payment_category,no_of_tickets,remit_id,income_line) VALUES('$txref','$date_of_payment','$ticket_category','$transaction_desc','$receipt_no','$amount_paid','$remitting_id','$remitting_staff','$posting_officer_id','$posting_officer_name','$now','$leasing_post_status','$approval_status','$verification_status','$debit_account','$credit_account','$payment_category','$no_of_tickets','$remit_id','$wc_income_line')";
		
		$post_payment = mysqli_query($dbcon, $query);


		$dquery = "INSERT INTO $db_debit_table (id,acct_id,date,receipt_no,trans_desc,debit_amount,balance,approval_status) VALUES('$txref','$debit_account','$date_of_payment','$receipt_no','$transaction_desc','$amount_paid','$balance','$approval_status')";
		
		$debit_query = mysqli_query($dbcon, $dquery);
		
	
		$cquery = "INSERT INTO $db_credit_table (id,acct_id,date,receipt_no,trans_desc,credit_amount,balance,approval_status) VALUES('$txref','$credit_account','$date_of_payment','$receipt_no','$transaction_desc','$amount_paid','$balance','$approval_status')";
		
		$credit_query = mysqli_query($dbcon, $cquery);

		
if ($debit_query)
	{
		?>
		<script type="text/javascript">
		alert('Payment successfully posted for approval!');
		window.location.href='payments.php';
		</script>
		<?php
	}
	else
	{
		?>
		<script type="text/javascript">
		alert('Error occured while posting');
		window.location.href='payments.php';
		</script>
		<?php
	}
}
}




//WheelBarrow Processing
if ( isset($_POST['btn_post_wheelbarrow']) ) {
	$posting_officer_dept = $_POST['posting_officer_dept'];
	
	if ($posting_officer_dept == "Accounts"){
		$remit_id = "";
	} else {
		$remit_id = $_POST['remit_id'];
		if($remit_id == "" || $remit_id == " "){
			$error = true;
		} else {
			$remit_id = $_POST['remit_id'];
		}
	}
	
	$income_line = $_POST['income_line'];
	
	$txref = time().mt_rand(0,9);
	
	$date_of_payment = $_POST['date_of_payment'];
	list($tid,$tim,$tiy) = explode("/",$date_of_payment);
	$date_of_payment = "$tiy-$tim-$tid";
	
	$receipt_no = $_POST['receipt_no'];
	$query = "SELECT * FROM account_general_transaction_new WHERE receipt_no='$receipt_no'";
	$result = mysqli_query($dbcon,$query);
	$receipt_data = @mysqli_fetch_array($result, MYSQLI_ASSOC);
	$receipt_posting_officer = $receipt_data['posting_officer_name'];
	$receipt_date_of_payment = $receipt_data['date_of_payment'];
	
	$count = mysqli_num_rows($result);
	if($count!=0){
		$error = true;
		$receipt_Error = "<h4><strong>ATTENTION:</strong> Transaction failed! The <strong>receipt No: $receipt_no</strong> you entered has already been used by $receipt_posting_officer on $receipt_date_of_payment!</h4>";
	}
	
	
	$no_of_tickets = $_POST['no_of_tickets'];
	
	$amount = $_POST['amount_paid'];
	
	$amount_paid = preg_replace('/[,]/', '', $amount);
	
	$remitting_post = $_POST['remitting_staff'];
	list($remitting_id,$remitting_check) = explode("-",@$remitting_post);
	
	if ($remitting_check == "wc"){
		$squery = "SELECT * FROM staffs WHERE user_id='$remitting_id'";
		$sresult = mysqli_query($dbcon,$squery);
		$remitting_data = @mysqli_fetch_array($sresult, MYSQLI_ASSOC);
		$remitting_staff = $remitting_data['full_name'];
	} elseif ($remitting_check == "os"){
		$squery = "SELECT * FROM staffs WHERE user_id='$remitting_id'";
		$sresult = mysqli_query($dbcon,$squery);
		$remitting_data = @mysqli_fetch_array($sresult, MYSQLI_ASSOC);
		$remitting_staff = $remitting_data['full_name'];
	} else {
		$squery = "SELECT * FROM staffs_others WHERE id='$remitting_id'";
		$sresult = mysqli_query($dbcon,$squery);
		$remitting_data = @mysqli_fetch_array($sresult, MYSQLI_ASSOC);
		$remitting_staff = $remitting_data['full_name'];
	}
	
	$transaction_desc = $_POST['transaction_descr'];
	$transaction_desc = strip_tags($transaction_desc);
	$transaction_desc = htmlspecialchars($transaction_desc);
	
	$transaction_desc = $transaction_desc.' - '.$remitting_staff;
	
	$posting_officer_id = $_POST['posting_officer_id'];
	$posting_officer_name = $_POST['posting_officer_name'];
	
	
	if ($posting_officer_dept == "Wealth Creation"){
		//Check if the remittance balance remains unchanged  
		$amt_remitted = $_POST['amt_remitted'];

		$ca_query = "SELECT posting_officer_id, date_of_payment, payment_category, SUM(amount_paid) as amount_posted ";
		$ca_query .= "FROM account_general_transaction_new ";
		$ca_query .= "WHERE (posting_officer_id = '$posting_officer_id' AND payment_category='Other Collection' AND date_of_payment='$current_date') ";
		$ca_sum = @mysqli_query($dbcon,$ca_query);
		$ca_total = @mysqli_fetch_array($ca_sum, MYSQLI_ASSOC);
		
		$amount_posted = $ca_total['amount_posted'];
	
		$rm_query = "SELECT *, SUM(amount_paid) as amount_remitted ";
		$rm_query .= "FROM cash_remittance ";
		$rm_query .= "WHERE (remitting_officer_id = '$posting_officer_id' AND category='Other Collection' AND date='$current_date') ";
		$rm_sum = @mysqli_query($dbcon,$rm_query);
		$rm_total = @mysqli_fetch_array($rm_sum, MYSQLI_ASSOC);
		$amount_remitted = $rm_total['amount_remitted'];
		
		$unposted = $amount_remitted - $amount_posted;
		
		if($amount_paid > $unposted){
			$error = true;
			$amount_remitted_Error = "<h4><strong>ATTENTION:</strong> Transaction failed! The <strong>amount posted: &#8358 {$amount_paid}</strong> MUST be equal to<strong> &#8358 {$unposted} </strong>remittance balance!</h4>";
		}

		if($amt_remitted > $unposted){
			$error = true;

			//PHP Mail Script begins here 
			require ('phpmailer/Exception.php');
			require ('phpmailer/PHPMailer.php');
			require ('phpmailer/SMTP.php');

			 
			$mail = new PHPMailer; 
			 
			$mail->isSMTP();                      // Set mailer to use SMTP 
			$mail->Host = 'smtp.gmail.com';       // Specify main and backup SMTP servers 
			$mail->SMTPAuth = true;               // Enable SMTP authentication 
			$mail->Username = 'woobserp@gmail.com';   // SMTP username 
			$mail->Password = 'cdkwjqjiseoosjhx';   // Your gmail app password
			$mail->SMTPSecure = 'ssl';            // Enable TLS encryption, `ssl` also accepted 
			$mail->Port = 465;                    // TCP port to connect to 
			 
			// Sender info 
			$mail->setFrom('woobserp@gmail.com', 'Wealth Creation ERP'); 
			$mail->addReplyTo('dayo.adebajo@thearenamarket.com', 'Dayo Adebajo'); 
			 
			// Add a recipient 
			$mail->addAddress('dayo.adebajo@thearenamarket.com'); 
			 
			$mail->addCC('emmanuel.okadigbo@thearenamarket.com'); 
			//$mail->addBCC('dayo.adebajo@thearenamarket.com'); 
			 
			// Set email format to HTML 
			$mail->isHTML(true); 

			// Mail subject 
			$mail->Subject = $posting_officer_name.' Inconsistent Remittance Balance - Wealth Creation ERP'; 
			 
			// Mail body content
			$bodyContent = '<h3>Inconsistent Remittance Balance - '.$posting_officer_name.'</h3>';
			$bodyContent .= '<p>Be informed that the above officer attempted to post <strong>'.$transaction_desc.'</strong> of <strong>&#8358 '.$amount_paid.'</strong> with receipt no: <strong>'.$receipt_no.'</strong> using a posting menu with remittance balance of <strong>&#8358 '.$amt_remitted.'</strong> when the actual remittance balance is &#8358 <strong>'.$unposted.'</strong></p>'; 
			$bodyContent .= '<p>This email is automatically sent from the Wealth Creation ERP server</p>'; 
			$mail->Body    = $bodyContent; 
			 
			// Send email 
			if(!$mail->send()) { 
				echo 'Message could not be sent. Mailer Error: '.$mail->ErrorInfo; 
			} else { 
				echo 'Mail notification successfully sent to IT Dept!'; 
			} 
			//PHP Mail Script ends here
			
			
			$remittance_bal_Error = "<h4><strong>WARNING:</strong> Transaction failed! Your remittance balance is <strong>: &#8358 {$unposted} </strong> and NOT <strong>&#8358 {$amt_remitted}</strong>. Kindly <strong>CLOSE ALL DUPLICATE</strong> posting pages and <strong>RE-OPEN</strong> your posting page from the main navigation menu. Ensure you maintain a single posting page. <strong>Be WARNED!</strong> Any further attempt will automatically disable you! You are being watched!!!</h4>";
		}
	}
	
	
	if ($posting_officer_dept == "Accounts"){
		$leasing_post_status = "";
		$approval_status = "Pending";
		$verification_status = "Pending";
	} else {
		$leasing_post_status = "Pending";
		$approval_status = "";
		$verification_status = "";
	}
	
	if ($posting_officer_dept == "Accounts"){
		$debit_alias = $_POST['debit_account'];
		$credit_alias = $_POST['credit_account'];
	} else {
		$debit_alias = $_POST['debit_alias'];
		$credit_alias = $_POST['credit_alias'];
	}
	
	$balance = "";
	
	if (empty($debit_alias)) {
		$error = true;
		$debiterror = "Please select the debit account";
	}
	if (empty($credit_alias)) {
		$error = true;
		$crediterror = "Please select the credit account";
	}
	
	
if (!$error) {
	//Debit Account
	$query_acct1 = "SELECT * ";
	$query_acct1 .= "FROM accounts ";
	
	if ($posting_officer_dept == "Accounts"){
		$query_acct1 .= "WHERE acct_id = '$debit_alias'";
	} else {
		$query_acct1 .= "WHERE acct_alias = '$debit_alias'";
	}
	$acct_debit_table_set = mysqli_query($dbcon, $query_acct1);
	$acct_debit_table = mysqli_fetch_array($acct_debit_table_set, MYSQLI_ASSOC);
	
	$debit_account = $acct_debit_table["acct_id"];
	$db_debit_table = $acct_debit_table["acct_table_name"];
	
	
	//Credit Account
	$query_acct2 = "SELECT * ";
	$query_acct2 .= "FROM accounts ";
	
	if ($posting_officer_dept == "Accounts"){
		$query_acct2 .= "WHERE acct_id = '$credit_alias'";
	} else {
		$query_acct2 .= "WHERE acct_alias = '$credit_alias'";
	}
	$acct_credit_table_set = mysqli_query($dbcon, $query_acct2);
	$acct_credit_table = mysqli_fetch_array($acct_credit_table_set, MYSQLI_ASSOC);
	
	$credit_account = $acct_credit_table["acct_id"];
	$credit_account_desc = $acct_credit_table["acct_desc"];
	$db_credit_table = $acct_credit_table["acct_table_name"];
	
	if ($posting_officer_dept == "Accounts"){
		$income_line = $credit_account_desc;
	} else {
		$income_line = $_POST['income_line'];
	}

	
	$db_transaction_table = "account_general_transaction_new";
	
	date_default_timezone_set('Africa/Lagos');
	$now = date('Y-m-d H:i:s');
	
	$payment_category = "Other Collection";
	$wc_income_line = $_POST['income_line'];
		
		$query = "INSERT INTO $db_transaction_table (id,date_of_payment,transaction_desc,receipt_no,amount_paid,remitting_id,remitting_staff,posting_officer_id,posting_officer_name,posting_time,leasing_post_status,approval_status,verification_status,debit_account,credit_account,payment_category,no_of_tickets,remit_id,income_line) VALUES('$txref','$date_of_payment','$transaction_desc','$receipt_no','$amount_paid','$remitting_id','$remitting_staff','$posting_officer_id','$posting_officer_name','$now','$leasing_post_status','$approval_status','$verification_status','$debit_account','$credit_account','$payment_category','$no_of_tickets','$remit_id','$wc_income_line')";
		
		$post_payment = mysqli_query($dbcon, $query);


		$dquery = "INSERT INTO $db_debit_table (id,acct_id,date,receipt_no,trans_desc,debit_amount,balance,approval_status) VALUES('$txref','$debit_account','$date_of_payment','$receipt_no','$transaction_desc','$amount_paid','$balance','$approval_status')";
		
		$debit_query = mysqli_query($dbcon, $dquery);
		
	
		$cquery = "INSERT INTO $db_credit_table (id,acct_id,date,receipt_no,trans_desc,credit_amount,balance,approval_status) VALUES('$txref','$credit_account','$date_of_payment','$receipt_no','$transaction_desc','$amount_paid','$balance','$approval_status')";
		
		$credit_query = mysqli_query($dbcon, $cquery);

		
if ($debit_query)
	{
		?>
		<script type="text/javascript">
		alert('Payment successfully posted for approval!');
		window.location.href='payments.php';
		</script>
		<?php
	}
	else
	{
		?>
		<script type="text/javascript">
		alert('Error occured while posting');
		window.location.href='payments.php';
		</script>
		<?php
	}
}
}

//Scroll Board Processing
if (isset($_POST['btn_post_scroll_board'])) {
	$posting_officer_dept = $_POST['posting_officer_dept'];
	if ($posting_officer_dept == "Accounts"){
		$remit_id = "";
	} else {
		$remit_id = $_POST['remit_id'];
		if($remit_id == "" || $remit_id == " "){
			$error = true;
		} else {
			$remit_id = $_POST['remit_id'];
		}
	}
	$income_line = $_POST['income_line'];
	$txref = time().mt_rand(0,9);
	$date_of_payment = $_POST['date_of_payment'];
	// print_r($date_of_payment);
	// exit;
	// list($tid,$tim,$tiy) = explode("/",$date_of_payment);
	// $date_of_payment = "$tiy-$tim-$tid";
	$receipt_no = $_POST['receipt_no'];
	$board_name = $_POST['board_name'];
	$allocated_to = $_POST['allocated_to'];
	$expected_rent_monthly = $_POST['expected_rent_monthly'];
	$expected_rent_yearly = $_POST['expected_rent_yearly'];
	$start_date = $_POST['start_date'];
	$end_date = $_POST['end_date'];
	$add_transaction_desc = isset($_POST['add_transaction_desc']) ? $_POST['add_transaction_desc'] : '';
	$payment_type = $_POST['payment_type'];
	//checks account general transaction new for existing receipt
	$query = "SELECT * FROM account_general_transaction_new WHERE receipt_no='$receipt_no'";
	$result = mysqli_query($dbcon,$query);
	$receipt_data = @mysqli_fetch_array($result, MYSQLI_ASSOC);
	$receipt_posting_officer = $receipt_data['posting_officer_name'];
	$receipt_date_of_payment = $receipt_data['date_of_payment'];
	
	$count = mysqli_num_rows($result);
	if($count!=0){
		$error = true;
		$receipt_Error = "<h4><strong>ATTENTION:</strong> Transaction failed! The <strong>receipt No: $receipt_no</strong> you entered has already been used by $receipt_posting_officer on $receipt_date_of_payment!</h4>";
	}
	// // $ticket_category = $_POST['ticket_category'];
	
	// $no_of_tickets = $_POST['no_of_tickets'];
	
	$amount = $_POST['amount_paid'];
	$amount_paid = preg_replace('/[,]/', '', $amount);
	
	$remitting_post = $_POST['remitting_staff'];
	list($remitting_id,$remitting_check) = explode("-",@$remitting_post);
	if ($remitting_check == "wc"){
		$squery = "SELECT * FROM staffs WHERE user_id='$remitting_id'";
		$sresult = mysqli_query($dbcon,$squery);
		$remitting_data = @mysqli_fetch_array($sresult, MYSQLI_ASSOC);
		$remitting_staff = $remitting_data['full_name'];
	} else {
		$squery = "SELECT * FROM staffs_others WHERE id='$remitting_id'";
		$sresult = mysqli_query($dbcon,$squery);
		$remitting_data = @mysqli_fetch_array($sresult, MYSQLI_ASSOC);
		$remitting_staff = $remitting_data['full_name'];
	}
	$transaction_desc = $_POST['transaction_descr'];
	$transaction_desc = strip_tags($transaction_desc);
	$transaction_desc = htmlspecialchars($transaction_desc);
	$transaction_desc = $transaction_desc.' ('.$board_name.'-'.$allocated_to.') - '.$remitting_staff;

	$posting_officer_id = $_POST['posting_officer_id'];
	$posting_officer_name = $_POST['posting_officer_name'];

	if ($posting_officer_dept == "Accounts"){
		$leasing_post_status = "";
		$approval_status = "Pending";
		$verification_status = "Pending";
	} else {
		$leasing_post_status = "Pending";
		$approval_status = "";
		$verification_status = "";
	}

	if ($posting_officer_dept == "Accounts"){
		$debit_alias = $_POST['debit_account'];
		$credit_alias = $_POST['credit_account'];
	} else {
		$debit_alias = $_POST['debit_alias'];
		$credit_alias = $_POST['credit_alias'];
	}
	$balance = "";
	
	if (empty($debit_alias)) {
		$error = true;
		$debiterror = "Please select the debit account";
	}
	if (empty($credit_alias)) {
		$error = true;
		$crediterror = "Please select the credit account";
	}

	if (!$error) {
		//Debit Account
		$query_acct1 = "SELECT * ";
		$query_acct1 .= "FROM accounts ";
		
		if ($posting_officer_dept == "Accounts"){
			$query_acct1 .= "WHERE acct_id = '$debit_alias'";
		} else {
			$query_acct1 .= "WHERE acct_alias = '$debit_alias'";
		}
		$acct_debit_table_set = mysqli_query($dbcon, $query_acct1);
		$acct_debit_table = mysqli_fetch_array($acct_debit_table_set, MYSQLI_ASSOC);
		
		$debit_account = $acct_debit_table["acct_id"];
		$db_debit_table = $acct_debit_table["acct_table_name"];

			//Credit Account
		$query_acct2 = "SELECT * ";
		$query_acct2 .= "FROM accounts ";
		
		if ($posting_officer_dept == "Accounts"){
			$query_acct2 .= "WHERE acct_id = '$credit_alias'";
		} else {
			$query_acct2 .= "WHERE acct_alias = '$credit_alias'";
		}
		$acct_credit_table_set = mysqli_query($dbcon, $query_acct2);
		$acct_credit_table = mysqli_fetch_array($acct_credit_table_set, MYSQLI_ASSOC);
		
		$credit_account = $acct_credit_table["acct_id"];
		$credit_account_desc = $acct_credit_table["acct_desc"];
		$db_credit_table = $acct_credit_table["acct_table_name"];

		if ($posting_officer_dept == "Accounts") {
			$income_line = $credit_account_desc;
		} else {
			$income_line = $_POST['income_line'];
		}

		if ($posting_officer_dept == "Accounts") {
			$income_line = $credit_account_desc;
		} else {
			$income_line = $_POST['income_line'];
		}
		
		$db_transaction_table = "account_general_transaction_new";
		$db_scrollboard_rentals_collection_analysis_table = "scrollboard_rentals_collection_analysis";

		date_default_timezone_set('Africa/Lagos');
		$now = date('Y-m-d H:i:s');
		
		$payment_category = "Scroll Board";
		$wc_income_line = isset($_POST['income_line']) ? $_POST['income_line'] : 'scroll_board';

		$query = "INSERT INTO $db_transaction_table (id,date_of_payment,transaction_desc,receipt_no, payment_type, amount_paid, remitting_id, remitting_staff, posting_officer_id, posting_officer_name, posting_time,leasing_post_status,approval_status,verification_status,debit_account,credit_account,payment_category,no_of_tickets,remit_id,income_line) VALUES ('$txref','$date_of_payment','$transaction_desc','$receipt_no','$payment_type', '$amount_paid','$remitting_id','$remitting_staff','$posting_officer_id','$posting_officer_name','$now','$leasing_post_status','$approval_status','$verification_status','$debit_account','$credit_account','$payment_category','NULL','$remit_id','$wc_income_line')";
		$post_payment = mysqli_query($dbcon, $query);

		
		$post_scrollboard_collection = "INSERT INTO $db_scrollboard_rentals_collection_analysis_table (trans_id,shop_no,customer_name,date_of_payment,rent_start, rent_due,amount_paid,receipt_no,expected_rent_monthly,payment_status,posting_time,posting_officer_id,posting_officer_name) VALUES('$txref','$board_name','$allocated_to','$date_of_payment','$start_date','$end_date','$amount_paid','$receipt_no','$expected_rent_monthly','Paid','$now','$posting_officer_id','$posting_officer_name')";
		$post_scrollboard_collection = @mysqli_query($dbcon, $post_scrollboard_collection);
		
		$dquery = "INSERT INTO $db_debit_table (id,acct_id,`date`,receipt_no,trans_desc,debit_amount,balance,approval_status) VALUES('$txref','$debit_account','$date_of_payment','$receipt_no','$transaction_desc','$amount_paid','$balance','$approval_status')";
		$debit_query = mysqli_query($dbcon, $dquery);

		$cquery = "INSERT INTO $db_credit_table (id,acct_id,`date`,receipt_no,trans_desc,credit_amount,balance,approval_status) VALUES('$txref','$credit_account','$date_of_payment','$receipt_no','$transaction_desc','$amount_paid','$balance','$approval_status')";
		$credit_query = mysqli_query($dbcon, $cquery);

		if ($debit_query) {
			?>
				<script type="text/javascript">
				alert('Payment successfully posted for approval!');
				window.location.href='payments.php';
				</script>
			<?php	
		} else {
			?>
				<script type="text/javascript">
				alert('Error occured while posting');
				window.location.href='payments.php';
				</script>
			<?php
		}
	}

}

//Daily Trade Processing
if (isset($_POST['btn_post_daily_trade'])) {
	$posting_officer_dept = $_POST['posting_officer_dept'];
	
	if ($posting_officer_dept == "Accounts"){
		$remit_id = "";
	} else {
		$remit_id = $_POST['remit_id'];
		if($remit_id == "" || $remit_id == " "){
			$error = true;
		} else {
			$remit_id = $_POST['remit_id'];
		}
	}
	
	$income_line = $_POST['income_line'];
	
	$txref = time().mt_rand(0,9);
	
	$date_of_payment = $_POST['date_of_payment'];
	list($tid,$tim,$tiy) = explode("/",$date_of_payment);
	$date_of_payment = "$tiy-$tim-$tid";
	
	$receipt_no = $_POST['receipt_no'];
	$query = "SELECT * FROM account_general_transaction_new WHERE receipt_no='$receipt_no'";
	$result = mysqli_query($dbcon,$query);
	$receipt_data = @mysqli_fetch_array($result, MYSQLI_ASSOC);
	$receipt_posting_officer = $receipt_data['posting_officer_name'];
	$receipt_date_of_payment = $receipt_data['date_of_payment'];
	
	$count = mysqli_num_rows($result);
	if($count!=0){
		$error = true;
		$receipt_Error = "<h4><strong>ATTENTION:</strong> Transaction failed! The <strong>receipt No: $receipt_no</strong> you entered has already been used by $receipt_posting_officer on $receipt_date_of_payment!</h4>";
	}
	
	$ticket_category = $_POST['ticket_category'];
	
	$no_of_tickets = $_POST['no_of_tickets'];
	
	$amount = $_POST['amount_paid'];
	
	$amount_paid = preg_replace('/[,]/', '', $amount);
	
	$remitting_post = $_POST['remitting_staff'];
	list($remitting_id,$remitting_check) = explode("-",@$remitting_post);
	
	if ($remitting_check == "wc"){
		$squery = "SELECT * FROM staffs WHERE user_id='$remitting_id'";
		$sresult = mysqli_query($dbcon,$squery);
		$remitting_data = @mysqli_fetch_array($sresult, MYSQLI_ASSOC);
		$remitting_staff = $remitting_data['full_name'];
	} else {
		$squery = "SELECT * FROM staffs_others WHERE id='$remitting_id'";
		$sresult = mysqli_query($dbcon,$squery);
		$remitting_data = @mysqli_fetch_array($sresult, MYSQLI_ASSOC);
		$remitting_staff = $remitting_data['full_name'];
	}
	
	$transaction_desc = $_POST['transaction_descr'];
	$transaction_desc = strip_tags($transaction_desc);
	$transaction_desc = htmlspecialchars($transaction_desc);
	
	$transaction_desc = $transaction_desc.' ('.$ticket_category.') - '.$remitting_staff;
	
	$posting_officer_id = $_POST['posting_officer_id'];
	$posting_officer_name = $_POST['posting_officer_name'];
	
	
	if ($posting_officer_dept == "Wealth Creation"){
		//Check if the remittance balance remains unchanged  
		$amt_remitted = $_POST['amt_remitted'];

		$ca_query = "SELECT posting_officer_id, date_of_payment, payment_category, SUM(amount_paid) as amount_posted ";
		$ca_query .= "FROM account_general_transaction_new ";
		$ca_query .= "WHERE (posting_officer_id = '$posting_officer_id' AND payment_category='Other Collection' AND date_of_payment='$current_date') ";
		$ca_sum = @mysqli_query($dbcon,$ca_query);
		$ca_total = @mysqli_fetch_array($ca_sum, MYSQLI_ASSOC);
		
		$amount_posted = $ca_total['amount_posted'];
	
		$rm_query = "SELECT *, SUM(amount_paid) as amount_remitted ";
		$rm_query .= "FROM cash_remittance ";
		$rm_query .= "WHERE (remitting_officer_id = '$posting_officer_id' AND category='Other Collection' AND date='$current_date') ";
		$rm_sum = @mysqli_query($dbcon,$rm_query);
		$rm_total = @mysqli_fetch_array($rm_sum, MYSQLI_ASSOC);
		$amount_remitted = $rm_total['amount_remitted'];
		
		$unposted = $amount_remitted - $amount_posted;
		
		if($amount_paid > $unposted){
			$error = true;
			$amount_remitted_Error = "<h4><strong>ATTENTION:</strong> Transaction failed! The <strong>amount posted: &#8358 {$amount_paid}</strong> MUST be equal to<strong> &#8358 {$unposted} </strong>remittance balance!</h4>";
		}

		if($amt_remitted > $unposted){
			$error = true;

			//PHP Mail Script begins here 
			require ('phpmailer/Exception.php');
			require ('phpmailer/PHPMailer.php');
			require ('phpmailer/SMTP.php');

			 
			$mail = new PHPMailer; 
			 
			$mail->isSMTP();                      // Set mailer to use SMTP 
			$mail->Host = 'smtp.gmail.com';       // Specify main and backup SMTP servers 
			$mail->SMTPAuth = true;               // Enable SMTP authentication 
			$mail->Username = 'woobserp@gmail.com';   // SMTP username 
			$mail->Password = 'cdkwjqjiseoosjhx';   // Your gmail app password
			$mail->SMTPSecure = 'ssl';            // Enable TLS encryption, `ssl` also accepted 
			$mail->Port = 465;                    // TCP port to connect to 
			 
			// Sender info 
			$mail->setFrom('woobserp@gmail.com', 'Wealth Creation ERP'); 
			$mail->addReplyTo('dayo.adebajo@thearenamarket.com', 'Dayo Adebajo'); 
			 
			// Add a recipient 
			$mail->addAddress('dayo.adebajo@thearenamarket.com'); 
			 
			$mail->addCC('emmanuel.okadigbo@thearenamarket.com'); 
			//$mail->addBCC('dayo.adebajo@thearenamarket.com'); 
			 
			// Set email format to HTML 
			$mail->isHTML(true); 

			// Mail subject 
			$mail->Subject = $posting_officer_name.' Inconsistent Remittance Balance - Wealth Creation ERP'; 
			 
			// Mail body content
			$bodyContent = '<h3>Inconsistent Remittance Balance - '.$posting_officer_name.'</h3>';
			$bodyContent .= '<p>Be informed that the above officer attempted to post <strong>'.$transaction_desc.'</strong> of <strong>&#8358 '.$amount_paid.'</strong> with receipt no: <strong>'.$receipt_no.'</strong> using a posting menu with remittance balance of <strong>&#8358 '.$amt_remitted.'</strong> when the actual remittance balance is &#8358 <strong>'.$unposted.'</strong></p>'; 
			$bodyContent .= '<p>This email is automatically sent from the Wealth Creation ERP server</p>'; 
			$mail->Body    = $bodyContent; 
			 
			// Send email 
			if(!$mail->send()) { 
				echo 'Message could not be sent. Mailer Error: '.$mail->ErrorInfo; 
			} else { 
				echo 'Mail notification successfully sent to IT Dept!'; 
			} 
			//PHP Mail Script ends here
			
			
			$remittance_bal_Error = "<h4><strong>WARNING:</strong> Transaction failed! Your remittance balance is <strong>: &#8358 {$unposted} </strong> and NOT <strong>&#8358 {$amt_remitted}</strong>. Kindly <strong>CLOSE ALL DUPLICATE</strong> posting pages and <strong>RE-OPEN</strong> your posting page from the main navigation menu. Ensure you maintain a single posting page. <strong>Be WARNED!</strong> Any further attempt will automatically disable you! You are being watched!!!</h4>";
		}
	}
	
	
	if ($posting_officer_dept == "Accounts"){
		$leasing_post_status = "";
		$approval_status = "Pending";
		$verification_status = "Pending";
	} else {
		$leasing_post_status = "Pending";
		$approval_status = "";
		$verification_status = "";
	}
	
	if ($posting_officer_dept == "Accounts"){
		$debit_alias = $_POST['debit_account'];
		$credit_alias = $_POST['credit_account'];
	} else {
		$debit_alias = $_POST['debit_alias'];
		$credit_alias = $_POST['credit_alias'];
	}
	
	$balance = "";
	
	if (empty($debit_alias)) {
		$error = true;
		$debiterror = "Please select the debit account";
	}
	if (empty($credit_alias)) {
		$error = true;
		$crediterror = "Please select the credit account";
	}
	
	
if (!$error) {
	//Debit Account
	$query_acct1 = "SELECT * ";
	$query_acct1 .= "FROM accounts ";
	
	if ($posting_officer_dept == "Accounts"){
		$query_acct1 .= "WHERE acct_id = '$debit_alias'";
	} else {
		$query_acct1 .= "WHERE acct_alias = '$debit_alias'";
	}
	$acct_debit_table_set = mysqli_query($dbcon, $query_acct1);
	$acct_debit_table = mysqli_fetch_array($acct_debit_table_set, MYSQLI_ASSOC);
	
	$debit_account = $acct_debit_table["acct_id"];
	$db_debit_table = $acct_debit_table["acct_table_name"];
	
	
	//Credit Account
	$query_acct2 = "SELECT * ";
	$query_acct2 .= "FROM accounts ";
	
	if ($posting_officer_dept == "Accounts"){
		$query_acct2 .= "WHERE acct_id = '$credit_alias'";
	} else {
		$query_acct2 .= "WHERE acct_alias = '$credit_alias'";
	}
	$acct_credit_table_set = mysqli_query($dbcon, $query_acct2);
	$acct_credit_table = mysqli_fetch_array($acct_credit_table_set, MYSQLI_ASSOC);
	
	$credit_account = $acct_credit_table["acct_id"];
	$credit_account_desc = $acct_credit_table["acct_desc"];
	$db_credit_table = $acct_credit_table["acct_table_name"];
	
	if ($posting_officer_dept == "Accounts"){
		$income_line = $credit_account_desc;
	} else {
		$income_line = $_POST['income_line'];
	}

	
	$db_transaction_table = "account_general_transaction_new";
	
	date_default_timezone_set('Africa/Lagos');
	$now = date('Y-m-d H:i:s');
	
	$payment_category = "Other Collection";
	$wc_income_line = $_POST['income_line'];
		
		$query = "INSERT INTO $db_transaction_table (id,date_of_payment,ticket_category,transaction_desc,receipt_no,amount_paid,remitting_id,remitting_staff,posting_officer_id,posting_officer_name,posting_time,leasing_post_status,approval_status,verification_status,debit_account,credit_account,payment_category,no_of_tickets,remit_id,income_line) VALUES('$txref','$date_of_payment','$ticket_category','$transaction_desc','$receipt_no','$amount_paid','$remitting_id','$remitting_staff','$posting_officer_id','$posting_officer_name','$now','$leasing_post_status','$approval_status','$verification_status','$debit_account','$credit_account','$payment_category','$no_of_tickets','$remit_id','$wc_income_line')";
		
		$post_payment = mysqli_query($dbcon, $query);


		$dquery = "INSERT INTO $db_debit_table (id,acct_id,date,receipt_no,trans_desc,debit_amount,balance,approval_status) VALUES('$txref','$debit_account','$date_of_payment','$receipt_no','$transaction_desc','$amount_paid','$balance','$approval_status')";
		
		$debit_query = mysqli_query($dbcon, $dquery);
		
	
		$cquery = "INSERT INTO $db_credit_table (id,acct_id,date,receipt_no,trans_desc,credit_amount,balance,approval_status) VALUES('$txref','$credit_account','$date_of_payment','$receipt_no','$transaction_desc','$amount_paid','$balance','$approval_status')";
		
		$credit_query = mysqli_query($dbcon, $cquery);

		
if ($debit_query)
	{
		?>
		<script type="text/javascript">
		alert('Payment successfully posted for approval!');
		window.location.href='payments.php';
		</script>
		<?php
	}
	else
	{
		?>
		<script type="text/javascript">
		alert('Error occured while posting');
		window.location.href='payments.php';
		</script>
		<?php
	}
}
}



//Toilet Collection Processing
if ( isset($_POST['btn_post_toilet_collection']) ) {
	$posting_officer_dept = $_POST['posting_officer_dept'];
	
	if ($posting_officer_dept == "Accounts"){
		$remit_id = "";
	} else {
		$remit_id = $_POST['remit_id'];
		if($remit_id == "" || $remit_id == " "){
			$error = true;
		} else {
			$remit_id = $_POST['remit_id'];
		}
	}
	
	$income_line = $_POST['income_line'];
	
	$txref = time().mt_rand(0,9);
	
	$date_of_payment = $_POST['date_of_payment'];
	list($tid,$tim,$tiy) = explode("/",$date_of_payment);
	$date_of_payment = "$tiy-$tim-$tid";
	
	$receipt_no = $_POST['receipt_no'];
	$query = "SELECT * FROM account_general_transaction_new WHERE receipt_no='$receipt_no'";
	$result = mysqli_query($dbcon,$query);
	$receipt_data = @mysqli_fetch_array($result, MYSQLI_ASSOC);
	$receipt_posting_officer = $receipt_data['posting_officer_name'];
	$receipt_date_of_payment = $receipt_data['date_of_payment'];
	
	$count = mysqli_num_rows($result);
	if($count!=0){
		$error = true;
		$receipt_Error = "<h4><strong>ATTENTION:</strong> Transaction failed! The <strong>receipt No: $receipt_no</strong> you entered has already been used by $receipt_posting_officer on $receipt_date_of_payment!</h4>";
	}
	
	$ticket_category = $_POST['ticket_category'];
	
	$amount = $_POST['amount_paid'];
	
	$amount_paid = preg_replace('/[,]/', '', $amount);
	
	$remitting_post = $_POST['remitting_staff'];
	list($remitting_id,$remitting_check) = explode("-",@$remitting_post);
	
	if ($remitting_check == "wc"){
		$squery = "SELECT * FROM staffs WHERE user_id='$remitting_id'";
		$sresult = mysqli_query($dbcon,$squery);
		$remitting_data = @mysqli_fetch_array($sresult, MYSQLI_ASSOC);
		$remitting_staff = $remitting_data['full_name'];
	} else {
		$squery = "SELECT * FROM staffs_others WHERE id='$remitting_id'";
		$sresult = mysqli_query($dbcon,$squery);
		$remitting_data = @mysqli_fetch_array($sresult, MYSQLI_ASSOC);
		$remitting_staff = $remitting_data['full_name'];
	}
	
	$transaction_desc = $_POST['transaction_descr'];
	$transaction_desc = strip_tags($transaction_desc);
	$transaction_desc = htmlspecialchars($transaction_desc);
	
	$transaction_desc = $transaction_desc.' ('.$ticket_category.') - '.$remitting_staff;
	
	$posting_officer_id = $_POST['posting_officer_id'];
	$posting_officer_name = $_POST['posting_officer_name'];
	
	
	if ($posting_officer_dept == "Wealth Creation"){
		//Check if the remittance balance remains unchanged  
		$amt_remitted = $_POST['amt_remitted'];

		$ca_query = "SELECT posting_officer_id, date_of_payment, payment_category, SUM(amount_paid) as amount_posted ";
		$ca_query .= "FROM account_general_transaction_new ";
		$ca_query .= "WHERE (posting_officer_id = '$posting_officer_id' AND payment_category='Other Collection' AND date_of_payment='$current_date') ";
		$ca_sum = @mysqli_query($dbcon,$ca_query);
		$ca_total = @mysqli_fetch_array($ca_sum, MYSQLI_ASSOC);
		
		$amount_posted = $ca_total['amount_posted'];
	
		$rm_query = "SELECT *, SUM(amount_paid) as amount_remitted ";
		$rm_query .= "FROM cash_remittance ";
		$rm_query .= "WHERE (remitting_officer_id = '$posting_officer_id' AND category='Other Collection' AND date='$current_date') ";
		$rm_sum = @mysqli_query($dbcon,$rm_query);
		$rm_total = @mysqli_fetch_array($rm_sum, MYSQLI_ASSOC);
		$amount_remitted = $rm_total['amount_remitted'];
		
		$unposted = $amount_remitted - $amount_posted;
		
		if($amount_paid > $unposted){
			$error = true;
			$amount_remitted_Error = "<h4><strong>ATTENTION:</strong> Transaction failed! The <strong>amount posted: &#8358 {$amount_paid}</strong> MUST be equal to<strong> &#8358 {$unposted} </strong>remittance balance!</h4>";
		}

		if($amt_remitted > $unposted){
			$error = true;

			//PHP Mail Script begins here 
			require ('phpmailer/Exception.php');
			require ('phpmailer/PHPMailer.php');
			require ('phpmailer/SMTP.php');

			 
			$mail = new PHPMailer; 
			 
			$mail->isSMTP();                      // Set mailer to use SMTP 
			$mail->Host = 'smtp.gmail.com';       // Specify main and backup SMTP servers 
			$mail->SMTPAuth = true;               // Enable SMTP authentication 
			$mail->Username = 'woobserp@gmail.com';   // SMTP username 
			$mail->Password = 'cdkwjqjiseoosjhx';   // Your gmail app password
			$mail->SMTPSecure = 'ssl';            // Enable TLS encryption, `ssl` also accepted 
			$mail->Port = 465;                    // TCP port to connect to 
			 
			// Sender info 
			$mail->setFrom('woobserp@gmail.com', 'Wealth Creation ERP'); 
			$mail->addReplyTo('dayo.adebajo@thearenamarket.com', 'Dayo Adebajo'); 
			 
			// Add a recipient 
			$mail->addAddress('dayo.adebajo@thearenamarket.com'); 
			 
			$mail->addCC('emmanuel.okadigbo@thearenamarket.com'); 
			//$mail->addBCC('dayo.adebajo@thearenamarket.com'); 
			 
			// Set email format to HTML 
			$mail->isHTML(true); 

			// Mail subject 
			$mail->Subject = $posting_officer_name.' Inconsistent Remittance Balance - Wealth Creation ERP'; 
			 
			// Mail body content
			$bodyContent = '<h3>Inconsistent Remittance Balance - '.$posting_officer_name.'</h3>';
			$bodyContent .= '<p>Be informed that the above officer attempted to post <strong>'.$transaction_desc.'</strong> of <strong>&#8358 '.$amount_paid.'</strong> with receipt no: <strong>'.$receipt_no.'</strong> using a posting menu with remittance balance of <strong>&#8358 '.$amt_remitted.'</strong> when the actual remittance balance is &#8358 <strong>'.$unposted.'</strong></p>'; 
			$bodyContent .= '<p>This email is automatically sent from the Wealth Creation ERP server</p>'; 
			$mail->Body    = $bodyContent; 
			 
			// Send email 
			if(!$mail->send()) { 
				echo 'Message could not be sent. Mailer Error: '.$mail->ErrorInfo; 
			} else { 
				echo 'Mail notification successfully sent to IT Dept!'; 
			} 
			//PHP Mail Script ends here
			
			
			$remittance_bal_Error = "<h4><strong>WARNING:</strong> Transaction failed! Your remittance balance is <strong>: &#8358 {$unposted} </strong> and NOT <strong>&#8358 {$amt_remitted}</strong>. Kindly <strong>CLOSE ALL DUPLICATE</strong> posting pages and <strong>RE-OPEN</strong> your posting page from the main navigation menu. Ensure you maintain a single posting page. <strong>Be WARNED!</strong> Any further attempt will automatically disable you! You are being watched!!!</h4>";
		}
	}
	
	
	if ($posting_officer_dept == "Accounts"){
		$leasing_post_status = "";
		$approval_status = "Pending";
		$verification_status = "Pending";
	} else {
		$leasing_post_status = "Pending";
		$approval_status = "";
		$verification_status = "";
	}
	
	if ($posting_officer_dept == "Accounts"){
		$debit_alias = $_POST['debit_account'];
		$credit_alias = $_POST['credit_account'];
	} else {
		$debit_alias = $_POST['debit_alias'];
		$credit_alias = $_POST['credit_alias'];
	}
	
	$balance = "";
	
	if (empty($debit_alias)) {
		$error = true;
		$debiterror = "Please select the debit account";
	}
	if (empty($credit_alias)) {
		$error = true;
		$crediterror = "Please select the credit account";
	}
	
	
if (!$error) {
	//Debit Account
	$query_acct1 = "SELECT * ";
	$query_acct1 .= "FROM accounts ";
	
	if ($posting_officer_dept == "Accounts"){
		$query_acct1 .= "WHERE acct_id = '$debit_alias'";
	} else {
		$query_acct1 .= "WHERE acct_alias = '$debit_alias'";
	}
	$acct_debit_table_set = mysqli_query($dbcon, $query_acct1);
	$acct_debit_table = mysqli_fetch_array($acct_debit_table_set, MYSQLI_ASSOC);
	
	$debit_account = $acct_debit_table["acct_id"];
	$db_debit_table = $acct_debit_table["acct_table_name"];
	
	
	//Credit Account
	$query_acct2 = "SELECT * ";
	$query_acct2 .= "FROM accounts ";
	
	if ($posting_officer_dept == "Accounts"){
		$query_acct2 .= "WHERE acct_id = '$credit_alias'";
	} else {
		$query_acct2 .= "WHERE acct_alias = '$credit_alias'";
	}
	$acct_credit_table_set = mysqli_query($dbcon, $query_acct2);
	$acct_credit_table = mysqli_fetch_array($acct_credit_table_set, MYSQLI_ASSOC);
	
	$credit_account = $acct_credit_table["acct_id"];
	$credit_account_desc = $acct_credit_table["acct_desc"];
	$db_credit_table = $acct_credit_table["acct_table_name"];
	
	if ($posting_officer_dept == "Accounts"){
		$income_line = $credit_account_desc;
	} else {
		$income_line = $_POST['income_line'];
	}

	
	$db_transaction_table = "account_general_transaction_new";
	
	date_default_timezone_set('Africa/Lagos');
	$now = date('Y-m-d H:i:s');
	
	$payment_category = "Other Collection";
	$wc_income_line = $_POST['income_line'];
		
		$query = "INSERT INTO $db_transaction_table (id,date_of_payment,ticket_category,transaction_desc,receipt_no,amount_paid,remitting_id,remitting_staff,posting_officer_id,posting_officer_name,posting_time,leasing_post_status,approval_status,verification_status,debit_account,credit_account,payment_category,remit_id,income_line) VALUES('$txref','$date_of_payment','$ticket_category','$transaction_desc','$receipt_no','$amount_paid','$remitting_id','$remitting_staff','$posting_officer_id','$posting_officer_name','$now','$leasing_post_status','$approval_status','$verification_status','$debit_account','$credit_account','$payment_category','$remit_id','$wc_income_line')";
		
		$post_payment = mysqli_query($dbcon, $query);


		$dquery = "INSERT INTO $db_debit_table (id,acct_id,date,receipt_no,trans_desc,debit_amount,balance,approval_status) VALUES('$txref','$debit_account','$date_of_payment','$receipt_no','$transaction_desc','$amount_paid','$balance','$approval_status')";
		
		$debit_query = mysqli_query($dbcon, $dquery);
		
	
		$cquery = "INSERT INTO $db_credit_table (id,acct_id,date,receipt_no,trans_desc,credit_amount,balance,approval_status) VALUES('$txref','$credit_account','$date_of_payment','$receipt_no','$transaction_desc','$amount_paid','$balance','$approval_status')";
		
		$credit_query = mysqli_query($dbcon, $cquery);

		
if ($debit_query)
	{
		?>
		<script type="text/javascript">
		alert('Payment successfully posted for approval!');
		window.location.href='payments.php';
		</script>
		<?php
	}
	else
	{
		?>
		<script type="text/javascript">
		alert('Error occured while posting');
		window.location.href='payments.php';
		</script>
		<?php
	}
}
}



//Abattoir Processing
if ( isset($_POST['btn_post_abattoir']) ) {
	$posting_officer_dept = $_POST['posting_officer_dept'];
	
	if ($posting_officer_dept == "Accounts"){
		$remit_id = "";
	} else {
		$remit_id = $_POST['remit_id'];
		if($remit_id == "" || $remit_id == " "){
			$error = true;
		} else {
			$remit_id = $_POST['remit_id'];
		}
	}
	
	
	$income_line = $_POST['income_line'];
	
	$txref = time().mt_rand(0,9);
	
	$date_of_payment = $_POST['date_of_payment'];
	list($tid,$tim,$tiy) = explode("/",$date_of_payment);
	$date_of_payment = "$tiy-$tim-$tid";
	
	$receipt_no = $_POST['receipt_no'];
	$query = "SELECT * FROM account_general_transaction_new WHERE receipt_no='$receipt_no'";
	$result = mysqli_query($dbcon,$query);
	$receipt_data = @mysqli_fetch_array($result, MYSQLI_ASSOC);
	$receipt_posting_officer = $receipt_data['posting_officer_name'];
	$receipt_date_of_payment = $receipt_data['date_of_payment'];
	
	$count = mysqli_num_rows($result);
	if($count!=0){
		$error = true;
		$receipt_Error = "<h4><strong>ATTENTION:</strong> Transaction failed! The <strong>receipt No: $receipt_no</strong> you entered has already been used by $receipt_posting_officer on $receipt_date_of_payment!</h4>";
	}
	
	$category = $_POST['category'];
	
	$quantity = $_POST['quantity'];
	
	$amount = $_POST['amount_paid'];
	
	$amount_paid = preg_replace('/[,]/', '', $amount);
	
	$remitting_post = $_POST['remitting_staff'];
	list($remitting_id,$remitting_check) = explode("-",@$remitting_post);
	
	if ($remitting_check == "wc"){
		$squery = "SELECT * FROM staffs WHERE user_id='$remitting_id'";
		$sresult = mysqli_query($dbcon,$squery);
		$remitting_data = @mysqli_fetch_array($sresult, MYSQLI_ASSOC);
		$remitting_staff = $remitting_data['full_name'];
	} else {
		$squery = "SELECT * FROM staffs_others WHERE id='$remitting_id'";
		$sresult = mysqli_query($dbcon,$squery);
		$remitting_data = @mysqli_fetch_array($sresult, MYSQLI_ASSOC);
		$remitting_staff = $remitting_data['full_name'];
	}
	
	$transaction_desc = $_POST['transaction_descr'];
	$transaction_desc = strip_tags($transaction_desc);
	$transaction_desc = htmlspecialchars($transaction_desc);
	
	$transaction_desc = $transaction_desc.' - '.$quantity.' '.$category;
	
	$posting_officer_id = $_POST['posting_officer_id'];
	$posting_officer_name = $_POST['posting_officer_name'];
	
	
	if ($posting_officer_dept == "Wealth Creation"){
		//Check if the remittance balance remains unchanged  
		$amt_remitted = $_POST['amt_remitted'];

		$ca_query = "SELECT posting_officer_id, date_of_payment, payment_category, SUM(amount_paid) as amount_posted ";
		$ca_query .= "FROM account_general_transaction_new ";
		$ca_query .= "WHERE (posting_officer_id = '$posting_officer_id' AND payment_category='Other Collection' AND date_of_payment='$current_date') ";
		$ca_sum = @mysqli_query($dbcon,$ca_query);
		$ca_total = @mysqli_fetch_array($ca_sum, MYSQLI_ASSOC);
		
		$amount_posted = $ca_total['amount_posted'];
	
		$rm_query = "SELECT *, SUM(amount_paid) as amount_remitted ";
		$rm_query .= "FROM cash_remittance ";
		$rm_query .= "WHERE (remitting_officer_id = '$posting_officer_id' AND category='Other Collection' AND date='$current_date') ";
		$rm_sum = @mysqli_query($dbcon,$rm_query);
		$rm_total = @mysqli_fetch_array($rm_sum, MYSQLI_ASSOC);
		$amount_remitted = $rm_total['amount_remitted'];
		
		$unposted = $amount_remitted - $amount_posted;
		
		if($amount_paid > $unposted){
			$error = true;
			$amount_remitted_Error = "<h4><strong>ATTENTION:</strong> Transaction failed! The <strong>amount posted: &#8358 {$amount_paid}</strong> MUST be equal to<strong> &#8358 {$unposted} </strong>remittance balance!</h4>";
		}

		if($amt_remitted > $unposted){
			$error = true;

			//PHP Mail Script begins here 
			require ('phpmailer/Exception.php');
			require ('phpmailer/PHPMailer.php');
			require ('phpmailer/SMTP.php');

			 
			$mail = new PHPMailer; 
			 
			$mail->isSMTP();                      // Set mailer to use SMTP 
			$mail->Host = 'smtp.gmail.com';       // Specify main and backup SMTP servers 
			$mail->SMTPAuth = true;               // Enable SMTP authentication 
			$mail->Username = 'woobserp@gmail.com';   // SMTP username 
			$mail->Password = 'cdkwjqjiseoosjhx';   // Your gmail app password
			$mail->SMTPSecure = 'ssl';            // Enable TLS encryption, `ssl` also accepted 
			$mail->Port = 465;                    // TCP port to connect to 
			 
			// Sender info 
			$mail->setFrom('woobserp@gmail.com', 'Wealth Creation ERP'); 
			$mail->addReplyTo('dayo.adebajo@thearenamarket.com', 'Dayo Adebajo'); 
			 
			// Add a recipient 
			$mail->addAddress('dayo.adebajo@thearenamarket.com'); 
			 
			$mail->addCC('emmanuel.okadigbo@thearenamarket.com'); 
			//$mail->addBCC('dayo.adebajo@thearenamarket.com'); 
			 
			// Set email format to HTML 
			$mail->isHTML(true); 

			// Mail subject 
			$mail->Subject = $posting_officer_name.' Inconsistent Remittance Balance - Wealth Creation ERP'; 
			 
			// Mail body content
			$bodyContent = '<h3>Inconsistent Remittance Balance - '.$posting_officer_name.'</h3>';
			$bodyContent .= '<p>Be informed that the above officer attempted to post <strong>'.$transaction_desc.'</strong> of <strong>&#8358 '.$amount_paid.'</strong> with receipt no: <strong>'.$receipt_no.'</strong> using a posting menu with remittance balance of <strong>&#8358 '.$amt_remitted.'</strong> when the actual remittance balance is &#8358 <strong>'.$unposted.'</strong></p>'; 
			$bodyContent .= '<p>This email is automatically sent from the Wealth Creation ERP server</p>'; 
			$mail->Body    = $bodyContent; 
			 
			// Send email 
			if(!$mail->send()) { 
				echo 'Message could not be sent. Mailer Error: '.$mail->ErrorInfo; 
			} else { 
				echo 'Mail notification successfully sent to IT Dept!'; 
			} 
			//PHP Mail Script ends here
			
			
			$remittance_bal_Error = "<h4><strong>WARNING:</strong> Transaction failed! Your remittance balance is <strong>: &#8358 {$unposted} </strong> and NOT <strong>&#8358 {$amt_remitted}</strong>. Kindly <strong>CLOSE ALL DUPLICATE</strong> posting pages and <strong>RE-OPEN</strong> your posting page from the main navigation menu. Ensure you maintain a single posting page. <strong>Be WARNED!</strong> Any further attempt will automatically disable you! You are being watched!!!</h4>";
		}
	}
	
	
	if ($posting_officer_dept == "Accounts"){
		$leasing_post_status = "";
		$approval_status = "Pending";
		$verification_status = "Pending";
	} else {
		$leasing_post_status = "Pending";
		$approval_status = "";
		$verification_status = "";
	}
	
	if ($posting_officer_dept == "Accounts"){
		$debit_alias = $_POST['debit_account'];
		$credit_alias = $_POST['credit_account'];
	} else {
		$debit_alias = $_POST['debit_alias'];
		$credit_alias = $_POST['credit_alias'];
	}
	
	$balance = "";
	
	if (empty($debit_alias)) {
		$error = true;
		$debiterror = "Please select the debit account";
	}
	if (empty($credit_alias)) {
		$error = true;
		$crediterror = "Please select the credit account";
	}
	
	
if (!$error) {
	//Debit Account
	$query_acct1 = "SELECT * ";
	$query_acct1 .= "FROM accounts ";
	
	if ($posting_officer_dept == "Accounts"){
		$query_acct1 .= "WHERE acct_id = '$debit_alias'";
	} else {
		$query_acct1 .= "WHERE acct_alias = '$debit_alias'";
	}
	$acct_debit_table_set = mysqli_query($dbcon, $query_acct1);
	$acct_debit_table = mysqli_fetch_array($acct_debit_table_set, MYSQLI_ASSOC);
	
	$debit_account = $acct_debit_table["acct_id"];
	$db_debit_table = $acct_debit_table["acct_table_name"];
	
	
	//Credit Account
	$query_acct2 = "SELECT * ";
	$query_acct2 .= "FROM accounts ";
	
	if ($posting_officer_dept == "Accounts"){
		$query_acct2 .= "WHERE acct_id = '$credit_alias'";
	} else {
		$query_acct2 .= "WHERE acct_alias = '$credit_alias'";
	}
	$acct_credit_table_set = mysqli_query($dbcon, $query_acct2);
	$acct_credit_table = mysqli_fetch_array($acct_credit_table_set, MYSQLI_ASSOC);
	
	$credit_account = $acct_credit_table["acct_id"];
	$credit_account_desc = $acct_credit_table["acct_desc"];
	$db_credit_table = $acct_credit_table["acct_table_name"];
	
	if ($posting_officer_dept == "Accounts"){
		$income_line = $credit_account_desc;
	} else {
		$income_line = $_POST['income_line'];
	}

	
	$db_transaction_table = "account_general_transaction_new";
	
	date_default_timezone_set('Africa/Lagos');
	$now = date('Y-m-d H:i:s');
	
	
	$payment_category = "Other Collection";
	$wc_income_line = $_POST['income_line'];
		
		$query = "INSERT INTO $db_transaction_table (id,date_of_payment,ticket_category,transaction_desc,receipt_no,amount_paid,remitting_id,remitting_staff,posting_officer_id,posting_officer_name,posting_time,leasing_post_status,approval_status,verification_status,debit_account,credit_account,payment_category,no_of_tickets,remit_id,income_line) VALUES('$txref','$date_of_payment','$category','$transaction_desc','$receipt_no','$amount_paid','$remitting_id','$remitting_staff','$posting_officer_id','$posting_officer_name','$now','$leasing_post_status','$approval_status','$verification_status','$debit_account','$credit_account','$payment_category','$quantity','$remit_id','$wc_income_line')";
		
		$post_payment = mysqli_query($dbcon, $query);


		$dquery = "INSERT INTO $db_debit_table (id,acct_id,date,receipt_no,trans_desc,debit_amount,balance,approval_status) VALUES('$txref','$debit_account','$date_of_payment','$receipt_no','$transaction_desc','$amount_paid','$balance','$approval_status')";
		
		$debit_query = mysqli_query($dbcon, $dquery);
		
	
		$cquery = "INSERT INTO $db_credit_table (id,acct_id,date,receipt_no,trans_desc,credit_amount,balance,approval_status) VALUES('$txref','$credit_account','$date_of_payment','$receipt_no','$transaction_desc','$amount_paid','$balance','$approval_status')";
		
		$credit_query = mysqli_query($dbcon, $cquery);

		
if ($debit_query)
	{
		?>
		<script type="text/javascript">
		alert('Payment successfully posted for approval!');
		window.location.href='payments.php';
		</script>
		<?php
	}
	else
	{
		?>
		<script type="text/javascript">
		alert('Error occured while posting');
		window.location.href='payments.php';
		</script>
		<?php
	}
}
}



//Daily Trade arrears Processing
if (isset($_POST['btn_post_daily_trade_arrears'])) {
	$posting_officer_dept = $_POST['posting_officer_dept'];
	
	if ($posting_officer_dept == "Accounts"){
		$remit_id = "";
	} else {
		$remit_id = $_POST['remit_id'];
		if($remit_id == "" || $remit_id == " "){
			$error = true;
		} else {
			$remit_id = $_POST['remit_id'];
		}
	}
	
	$income_line = $_POST['income_line'];
	
	$txref = time().mt_rand(0,9);
	
	$date_of_payment = $_POST['date_of_payment'];
	list($tid,$tim,$tiy) = explode("/",$date_of_payment);
	$date_of_payment = "$tiy-$tim-$tid";
	
	$receipt_no = $_POST['receipt_no'];
	$query = "SELECT * FROM account_general_transaction_new WHERE receipt_no='$receipt_no'";
	$result = mysqli_query($dbcon,$query);
	$receipt_data = @mysqli_fetch_array($result, MYSQLI_ASSOC);
	$receipt_posting_officer = $receipt_data['posting_officer_name'];
	$receipt_date_of_payment = $receipt_data['date_of_payment'];
	
	$count = mysqli_num_rows($result);
	if($count!=0){
		$error = true;
		$receipt_Error = "<h4><strong>ATTENTION:</strong> Transaction failed! The <strong>receipt No: $receipt_no</strong> you entered has already been used by $receipt_posting_officer on $receipt_date_of_payment!</h4>";
	}
	
	$ticket_category = $_POST['ticket_category'];
	
	$no_of_tickets = $_POST['no_of_tickets'];
	
	$amount = $_POST['amount_paid'];
	
	$amount_paid = preg_replace('/[,]/', '', $amount);
	
	$remitting_post = $_POST['remitting_staff'];
	list($remitting_id,$remitting_check) = explode("-",@$remitting_post);
	
	if ($remitting_check == "wc"){
		$squery = "SELECT * FROM staffs WHERE user_id='$remitting_id'";
		$sresult = mysqli_query($dbcon,$squery);
		$remitting_data = @mysqli_fetch_array($sresult, MYSQLI_ASSOC);
		$remitting_staff = $remitting_data['full_name'];
	} else {
		$squery = "SELECT * FROM staffs_others WHERE id='$remitting_id'";
		$sresult = mysqli_query($dbcon,$squery);
		$remitting_data = @mysqli_fetch_array($sresult, MYSQLI_ASSOC);
		$remitting_staff = $remitting_data['full_name'];
	}
	
	$transaction_desc = $_POST['transaction_descr'];
	$transaction_desc = strip_tags($transaction_desc);
	$transaction_desc = htmlspecialchars($transaction_desc);
	
	$transaction_desc = $transaction_desc.' ('.$ticket_category.') - '.$remitting_staff;
	
	$posting_officer_id = $_POST['posting_officer_id'];
	$posting_officer_name = $_POST['posting_officer_name'];
	
	
	if ($posting_officer_dept == "Wealth Creation"){
		//Check if the remittance balance remains unchanged  
		$amt_remitted = $_POST['amt_remitted'];

		$ca_query = "SELECT posting_officer_id, date_of_payment, payment_category, SUM(amount_paid) as amount_posted ";
		$ca_query .= "FROM account_general_transaction_new ";
		$ca_query .= "WHERE (posting_officer_id = '$posting_officer_id' AND payment_category='Other Collection' AND date_of_payment='$current_date') ";
		$ca_sum = @mysqli_query($dbcon,$ca_query);
		$ca_total = @mysqli_fetch_array($ca_sum, MYSQLI_ASSOC);
		
		$amount_posted = $ca_total['amount_posted'];
	
		$rm_query = "SELECT *, SUM(amount_paid) as amount_remitted ";
		$rm_query .= "FROM cash_remittance ";
		$rm_query .= "WHERE (remitting_officer_id = '$posting_officer_id' AND category='Other Collection' AND date='$current_date') ";
		$rm_sum = @mysqli_query($dbcon,$rm_query);
		$rm_total = @mysqli_fetch_array($rm_sum, MYSQLI_ASSOC);
		$amount_remitted = $rm_total['amount_remitted'];
		
		$unposted = $amount_remitted - $amount_posted;
		
		if($amount_paid > $unposted){
			$error = true;
			$amount_remitted_Error = "<h4><strong>ATTENTION:</strong> Transaction failed! The <strong>amount posted: &#8358 {$amount_paid}</strong> MUST be equal to<strong> &#8358 {$unposted} </strong>remittance balance!</h4>";
		}

		if($amt_remitted > $unposted){
			$error = true;
			$remittance_bal_Error = "<h4><strong>WARNING:</strong> Transaction failed! Your remittance balance is <strong>: &#8358 {$unposted} </strong> and NOT <strong>&#8358 {$amt_remitted}</strong>. Kindly <strong>CLOSE ALL DUPLICATE</strong> posting pages and <strong>RE-OPEN</strong> your posting page from the main navigation menu. Ensure you maintain a single posting page. <strong>Be WARNED!</strong> Any further attempt will automatically disable you! You are being watched!!!</h4>";
		}
	}
	
	
	if ($posting_officer_dept == "Accounts"){
		$leasing_post_status = "";
		$approval_status = "Pending";
		$verification_status = "Pending";
	} else {
		$leasing_post_status = "Pending";
		$approval_status = "";
		$verification_status = "";
	}
	
	if ($posting_officer_dept == "Accounts"){
		$debit_alias = $_POST['debit_account'];
		$credit_alias = $_POST['credit_account'];
	} else {
		$debit_alias = $_POST['debit_alias'];
		$credit_alias = $_POST['credit_alias'];
	}
	
	$balance = "";
	
	if (empty($debit_alias)) {
		$error = true;
		$debiterror = "Please select the debit account";
	}
	if (empty($credit_alias)) {
		$error = true;
		$crediterror = "Please select the credit account";
	}
	
	
if (!$error) {
	//Debit Account
	$query_acct1 = "SELECT * ";
	$query_acct1 .= "FROM accounts ";
	
	if ($posting_officer_dept == "Accounts"){
		$query_acct1 .= "WHERE acct_id = '$debit_alias'";
	} else {
		$query_acct1 .= "WHERE acct_alias = '$debit_alias'";
	}
	$acct_debit_table_set = mysqli_query($dbcon, $query_acct1);
	$acct_debit_table = mysqli_fetch_array($acct_debit_table_set, MYSQLI_ASSOC);
	
	$debit_account = $acct_debit_table["acct_id"];
	$db_debit_table = $acct_debit_table["acct_table_name"];
	
	
	//Credit Account
	$query_acct2 = "SELECT * ";
	$query_acct2 .= "FROM accounts ";
	
	if ($posting_officer_dept == "Accounts"){
		$query_acct2 .= "WHERE acct_id = '$credit_alias'";
	} else {
		$query_acct2 .= "WHERE acct_alias = '$credit_alias'";
	}
	$acct_credit_table_set = mysqli_query($dbcon, $query_acct2);
	$acct_credit_table = mysqli_fetch_array($acct_credit_table_set, MYSQLI_ASSOC);
	
	$credit_account = $acct_credit_table["acct_id"];
	$credit_account_desc = $acct_credit_table["acct_desc"];
	$db_credit_table = $acct_credit_table["acct_table_name"];
	
	if ($posting_officer_dept == "Accounts"){
		$income_line = $credit_account_desc;
	} else {
		$income_line = $_POST['income_line'];
	}

	
	$db_transaction_table = "account_general_transaction_new";
	
	date_default_timezone_set('Africa/Lagos');
	$now = date('Y-m-d H:i:s');
	
	$payment_category = "Other Collection";
	$wc_income_line = $_POST['income_line'];
		
		$query = "INSERT INTO $db_transaction_table (id,date_of_payment,ticket_category,transaction_desc,receipt_no,amount_paid,remitting_id,remitting_staff,posting_officer_id,posting_officer_name,posting_time,leasing_post_status,approval_status,verification_status,debit_account,credit_account,payment_category,no_of_tickets,remit_id,income_line) VALUES('$txref','$date_of_payment','$ticket_category','$transaction_desc','$receipt_no','$amount_paid','$remitting_id','$remitting_staff','$posting_officer_id','$posting_officer_name','$now','$leasing_post_status','$approval_status','$verification_status','$debit_account','$credit_account','$payment_category','$no_of_tickets','$remit_id','$wc_income_line')";
		
		$post_payment = mysqli_query($dbcon, $query);


		$dquery = "INSERT INTO $db_debit_table (id,acct_id,date,receipt_no,trans_desc,debit_amount,balance,approval_status) VALUES('$txref','$debit_account','$date_of_payment','$receipt_no','$transaction_desc','$amount_paid','$balance','$approval_status')";
		
		$debit_query = mysqli_query($dbcon, $dquery);
		
	
		$cquery = "INSERT INTO $db_credit_table (id,acct_id,date,receipt_no,trans_desc,credit_amount,balance,approval_status) VALUES('$txref','$credit_account','$date_of_payment','$receipt_no','$transaction_desc','$amount_paid','$balance','$approval_status')";
		
		$credit_query = mysqli_query($dbcon, $cquery);

		
if ($debit_query)
	{
		?>
		<script type="text/javascript">
		alert('Payment successfully posted for approval!');
		window.location.href='payments.php';
		</script>
		<?php
	}
	else
	{
		?>
		<script type="text/javascript">
		alert('Error occured while posting');
		window.location.href='payments.php';
		</script>
		<?php
	}
}
}


//Othe POS Processing
if ( isset($_POST['btn_post_other_pos']) ) {
	// print_r($_POST);
	// exit;
	$remit_id = "";
	$posting_officer_dept = $_POST['posting_officer_dept'];
	
	if ($posting_officer_dept == "Accounts"){
		$remit_id = "";
	} else {
		$remit_id = $_POST['remit_id'];
		if($remit_id == "" || $remit_id == " "){
			$error = true;
		} else {
			$remit_id = $_POST['remit_id'];
		}
	}
	
	$income_line = $_POST['income_line'];
	
	$txref = time().mt_rand(0,9);
	
	$date_of_payment = $_POST['date_of_payment'];
	list($tid,$tim,$tiy) = explode("/",$date_of_payment);
	$date_of_payment = "$tiy-$tim-$tid";
	
	$receipt_no = $_POST['receipt_no'];
	$query = "SELECT * FROM account_general_transaction_new WHERE receipt_no='$receipt_no'";
	$result = mysqli_query($dbcon,$query);
	$receipt_data = @mysqli_fetch_array($result, MYSQLI_ASSOC);
	$receipt_posting_officer = $receipt_data['posting_officer_name'];
	$receipt_date_of_payment = $receipt_data['date_of_payment'];
	
	$count = mysqli_num_rows($result);
	if($count!=0){
		$error = true;
		$receipt_Error = "<h4><strong>ATTENTION:</strong> Transaction failed! The <strong>receipt No: $receipt_no</strong> you entered has already been used by $receipt_posting_officer on $receipt_date_of_payment!</h4>";
	}

	$no_of_tickets = $_POST['no_of_tickets'];
	
	$amount = $_POST['amount_paid'];
	$amount_paid = preg_replace('/[,]/', '', $amount);
	
	$remitting_post = $_POST['remitting_staff'];
	list($remitting_id,$remitting_check) = explode("-",@$remitting_post);
	
	if ($remitting_check == "wc"){
		$squery = "SELECT * FROM staffs WHERE user_id='$remitting_id'";
		$sresult = mysqli_query($dbcon,$squery);
		$remitting_data = @mysqli_fetch_array($sresult, MYSQLI_ASSOC);
		$remitting_staff = $remitting_data['full_name'];
	} elseif ($remitting_check == "os"){
		$squery = "SELECT * FROM staffs WHERE user_id='$remitting_id'";
		$sresult = mysqli_query($dbcon,$squery);
		$remitting_data = @mysqli_fetch_array($sresult, MYSQLI_ASSOC);
		$remitting_staff = $remitting_data['full_name'];
	} else {
		$squery = "SELECT * FROM staffs_others WHERE id='$remitting_id'";
		$sresult = mysqli_query($dbcon,$squery);
		$remitting_data = @mysqli_fetch_array($sresult, MYSQLI_ASSOC);
		$remitting_staff = $remitting_data['full_name'];
	}
	
	$transaction_desc = $_POST['transaction_descr'];
	$transaction_desc = strip_tags($transaction_desc);
	$transaction_desc = htmlspecialchars($transaction_desc);
	
	$transaction_desc = $transaction_desc.' - '.$remitting_staff;
	
	$posting_officer_id = $_POST['posting_officer_id'];
	$posting_officer_name = $_POST['posting_officer_name'];
	
	
	if ($posting_officer_dept == "Wealth Creation"){
		//Check if the remittance balance remains unchanged  
		$amt_remitted = $_POST['amt_remitted'];

		$ca_query = "SELECT posting_officer_id, date_of_payment, payment_category, SUM(amount_paid) as amount_posted ";
		$ca_query .= "FROM account_general_transaction_new ";
		$ca_query .= "WHERE (posting_officer_id = '$posting_officer_id' AND payment_category='Other Collection' AND date_of_payment='$current_date') ";
		$ca_sum = @mysqli_query($dbcon,$ca_query);
		$ca_total = @mysqli_fetch_array($ca_sum, MYSQLI_ASSOC);
		
		$amount_posted = $ca_total['amount_posted'];
	
		$rm_query = "SELECT *, SUM(amount_paid) as amount_remitted ";
		$rm_query .= "FROM cash_remittance ";
		$rm_query .= "WHERE (remitting_officer_id = '$posting_officer_id' AND category='Other Collection' AND date='$current_date') ";
		$rm_sum = @mysqli_query($dbcon,$rm_query);
		$rm_total = @mysqli_fetch_array($rm_sum, MYSQLI_ASSOC);
		$amount_remitted = $rm_total['amount_remitted'];
		
		$unposted = $amount_remitted - $amount_posted;
		
		if($amount_paid > $unposted){
			$error = true;
			$amount_remitted_Error = "<h4><strong>ATTENTION:</strong> Transaction failed! The <strong>amount posted: &#8358 {$amount_paid}</strong> MUST be equal to<strong> &#8358 {$unposted} </strong>remittance balance!</h4>";
		}

		if($amt_remitted > $unposted){
			$error = true;
			$remittance_bal_Error = "<h4><strong>WARNING:</strong> Transaction failed! Your remittance balance is <strong>: &#8358 {$unposted} </strong> and NOT <strong>&#8358 {$amt_remitted}</strong>. Kindly <strong>CLOSE ALL DUPLICATE</strong> posting pages and <strong>RE-OPEN</strong> your posting page from the main navigation menu. Ensure you maintain a single posting page. <strong>Be WARNED!</strong> Any further attempt will automatically disable you! You are being watched!!!</h4>";
		}
	}
	
	
	if ($posting_officer_dept == "Accounts"){
		$leasing_post_status = "";
		$approval_status = "Pending";
		$verification_status = "Pending";
	} else {
		$leasing_post_status = "Pending";
		$approval_status = "";
		$verification_status = "";
	}
	
	if ($posting_officer_dept == "Accounts"){
		$debit_alias = $_POST['debit_account'];
		$credit_alias = $_POST['credit_account'];
	} else {
		$debit_alias = $_POST['debit_alias'];
		$credit_alias = $_POST['credit_alias'];
	}
	
	$balance = "";
	
	if (empty($debit_alias)) {
		$error = true;
		$debiterror = "Please select the debit account";
	}
	if (empty($credit_alias)) {
		$error = true;
		$crediterror = "Please select the credit account";
	}
	
	
if (!$error) {
	//Debit Account
	$query_acct1 = "SELECT * ";
	$query_acct1 .= "FROM accounts ";
	
	if ($posting_officer_dept == "Accounts"){
		$query_acct1 .= "WHERE acct_id = '$debit_alias'";
	} else {
		$query_acct1 .= "WHERE acct_alias = '$debit_alias'";
	}
	$acct_debit_table_set = mysqli_query($dbcon, $query_acct1);
	$acct_debit_table = mysqli_fetch_array($acct_debit_table_set, MYSQLI_ASSOC);
	
	$debit_account = $acct_debit_table["acct_id"];
	$db_debit_table = $acct_debit_table["acct_table_name"];
	
	
	//Credit Account
	$query_acct2 = "SELECT * ";
	$query_acct2 .= "FROM accounts ";
	
	if ($posting_officer_dept == "Accounts"){
		$query_acct2 .= "WHERE acct_id = '$credit_alias'";
	} else {
		$query_acct2 .= "WHERE acct_alias = '$credit_alias'";
	}
	$acct_credit_table_set = mysqli_query($dbcon, $query_acct2);
	$acct_credit_table = mysqli_fetch_array($acct_credit_table_set, MYSQLI_ASSOC);
	
	$credit_account = $acct_credit_table["acct_id"];
	$credit_account_desc = $acct_credit_table["acct_desc"];
	$db_credit_table = $acct_credit_table["acct_table_name"];
	
	if ($posting_officer_dept == "Accounts"){
		$income_line = $credit_account_desc;
	} else {
		$income_line = $_POST['income_line'];
	}

	
	$db_transaction_table = "account_general_transaction_new";
	
	date_default_timezone_set('Africa/Lagos');
	$now = date('Y-m-d H:i:s');
	
	$payment_category = "Other POS Ticket";
	$wc_income_line = $_POST['income_line'];
		
		$query = "INSERT INTO $db_transaction_table (id,date_of_payment,transaction_desc,receipt_no,amount_paid,remitting_id,remitting_staff,posting_officer_id,posting_officer_name,posting_time,leasing_post_status,approval_status,verification_status,debit_account,credit_account,payment_category,no_of_tickets,remit_id,income_line) VALUES('$txref','$date_of_payment','$transaction_desc','$receipt_no','$amount_paid','$remitting_id','$remitting_staff','$posting_officer_id','$posting_officer_name','$now','$leasing_post_status','$approval_status','$verification_status','$debit_account','$credit_account','$payment_category','$no_of_tickets','$remit_id','$wc_income_line')";
		
		$post_payment = mysqli_query($dbcon, $query);


		$dquery = "INSERT INTO $db_debit_table (id,acct_id,date,receipt_no,trans_desc,debit_amount,balance,approval_status) VALUES('$txref','$debit_account','$date_of_payment','$receipt_no','$transaction_desc','$amount_paid','$balance','$approval_status')";
		
		$debit_query = mysqli_query($dbcon, $dquery);
		
	
		$cquery = "INSERT INTO $db_credit_table (id,acct_id,date,receipt_no,trans_desc,credit_amount,balance,approval_status) VALUES('$txref','$credit_account','$date_of_payment','$receipt_no','$transaction_desc','$amount_paid','$balance','$approval_status')";
		
		$credit_query = mysqli_query($dbcon, $cquery);

		
if ($debit_query)
	{
		?>
		<script type="text/javascript">
		alert('Payment successfully posted for approval!');
		window.location.href='payments.php';
		</script>
		<?php
	}
	else
	{
		?>
		<script type="text/javascript">
		alert('Error occured while posting');
		window.location.href='payments.php';
		</script>
		<?php
	}
}
}



//Loading and Offloading Processing
if ( isset($_POST['btn_post_loading']) ) {
	$posting_officer_dept = $_POST['posting_officer_dept'];
	
	if ($posting_officer_dept == "Accounts"){
		$remit_id = "";
	} else {
		$remit_id = $_POST['remit_id'];
		if($remit_id == "" || $remit_id == " "){
			$error = true;
		} else {
			$remit_id = $_POST['remit_id'];
		}
	}
	
	
	$income_line = $_POST['income_line'];
	
	$txref = time().mt_rand(0,9);
	
	$date_of_payment = $_POST['date_of_payment'];
	list($tid,$tim,$tiy) = explode("/",$date_of_payment);
	$date_of_payment = "$tiy-$tim-$tid";
	
	$category = $_POST['category'];
	$plate_no = $_POST['plate_no'];
	$no_of_days = $_POST['no_of_days'];
	
	$receipt_no = $_POST['receipt_no'];
	$query = "SELECT * FROM account_general_transaction_new WHERE receipt_no='$receipt_no'";
	$result = mysqli_query($dbcon,$query);
	$receipt_data = @mysqli_fetch_array($result, MYSQLI_ASSOC);
	$receipt_posting_officer = $receipt_data['posting_officer_name'];
	$receipt_date_of_payment = $receipt_data['date_of_payment'];
	
	$count = mysqli_num_rows($result);
	if($count!=0){
		$error = true;
		$receipt_Error = "<h4><strong>ATTENTION:</strong> Transaction failed! The <strong>receipt No: $receipt_no</strong> you entered has already been used by $receipt_posting_officer on $receipt_date_of_payment!</h4>";
	}
	
	$amount = $_POST['amount_paid'];
	$amount_paid = preg_replace('/[,]/', '', $amount);
	
	$remitting_post = $_POST['remitting_staff'];
	list($remitting_id,$remitting_check) = explode("-",@$remitting_post);
	
	if ($remitting_check == "wc"){
		$squery = "SELECT * FROM staffs WHERE user_id='$remitting_id'";
		$sresult = mysqli_query($dbcon,$squery);
		$remitting_data = @mysqli_fetch_array($sresult, MYSQLI_ASSOC);
		$remitting_staff = $remitting_data['full_name'];
	} else {
		$squery = "SELECT * FROM staffs_others WHERE id='$remitting_id'";
		$sresult = mysqli_query($dbcon,$squery);
		$remitting_data = @mysqli_fetch_array($sresult, MYSQLI_ASSOC);
		$remitting_staff = $remitting_data['full_name'];
	}
	
	$transaction_desc = $category;
	$transaction_desc = htmlspecialchars($transaction_desc);
	
	$posting_officer_id = $_POST['posting_officer_id'];
	$posting_officer_name = $_POST['posting_officer_name'];
	
	
	if ($posting_officer_dept == "Wealth Creation"){
		//Check if the remittance balance remains unchanged  
		$amt_remitted = $_POST['amt_remitted'];

		$ca_query = "SELECT posting_officer_id, date_of_payment, payment_category, SUM(amount_paid) as amount_posted ";
		$ca_query .= "FROM account_general_transaction_new ";
		$ca_query .= "WHERE (posting_officer_id = '$posting_officer_id' AND payment_category='Other Collection' AND date_of_payment='$current_date') ";
		$ca_sum = @mysqli_query($dbcon,$ca_query);
		$ca_total = @mysqli_fetch_array($ca_sum, MYSQLI_ASSOC);
		
		$amount_posted = $ca_total['amount_posted'];
	
		$rm_query = "SELECT *, SUM(amount_paid) as amount_remitted ";
		$rm_query .= "FROM cash_remittance ";
		$rm_query .= "WHERE (remitting_officer_id = '$posting_officer_id' AND category='Other Collection' AND date='$current_date') ";
		$rm_sum = @mysqli_query($dbcon,$rm_query);
		$rm_total = @mysqli_fetch_array($rm_sum, MYSQLI_ASSOC);
		$amount_remitted = $rm_total['amount_remitted'];
		
		$unposted = $amount_remitted - $amount_posted;
		
		if($amount_paid > $unposted){
			$error = true;
			$amount_remitted_Error = "<h4><strong>ATTENTION:</strong> Transaction failed! The <strong>amount posted: &#8358 {$amount_paid}</strong> MUST be equal to<strong> &#8358 {$unposted} </strong>remittance balance!</h4>";
		}

		if($amt_remitted > $unposted){
			$error = true;

			//PHP Mail Script begins here 
			require ('phpmailer/Exception.php');
			require ('phpmailer/PHPMailer.php');
			require ('phpmailer/SMTP.php');

			 
			$mail = new PHPMailer; 
			 
			$mail->isSMTP();                      // Set mailer to use SMTP 
			$mail->Host = 'smtp.gmail.com';       // Specify main and backup SMTP servers 
			$mail->SMTPAuth = true;               // Enable SMTP authentication 
			$mail->Username = 'woobserp@gmail.com';   // SMTP username 
			$mail->Password = 'cdkwjqjiseoosjhx';   // Your gmail app password
			$mail->SMTPSecure = 'ssl';            // Enable TLS encryption, `ssl` also accepted 
			$mail->Port = 465;                    // TCP port to connect to 
			 
			// Sender info 
			$mail->setFrom('woobserp@gmail.com', 'Wealth Creation ERP'); 
			$mail->addReplyTo('dayo.adebajo@thearenamarket.com', 'Dayo Adebajo'); 
			 
			// Add a recipient 
			$mail->addAddress('dayo.adebajo@thearenamarket.com'); 
			 
			$mail->addCC('emmanuel.okadigbo@thearenamarket.com'); 
			//$mail->addBCC('dayo.adebajo@thearenamarket.com'); 
			 
			// Set email format to HTML 
			$mail->isHTML(true); 

			// Mail subject 
			$mail->Subject = $posting_officer_name.' Inconsistent Remittance Balance - Wealth Creation ERP'; 
			 
			// Mail body content
			$bodyContent = '<h3>Inconsistent Remittance Balance - '.$posting_officer_name.'</h3>';
			$bodyContent .= '<p>Be informed that the above officer attempted to post <strong>'.$transaction_desc.'</strong> of <strong>&#8358 '.$amount_paid.'</strong> with receipt no: <strong>'.$receipt_no.'</strong> using a posting menu with remittance balance of <strong>&#8358 '.$amt_remitted.'</strong> when the actual remittance balance is &#8358 <strong>'.$unposted.'</strong></p>'; 
			$bodyContent .= '<p>This email is automatically sent from the Wealth Creation ERP server</p>'; 
			$mail->Body    = $bodyContent; 
			 
			// Send email 
			if(!$mail->send()) { 
				echo 'Message could not be sent. Mailer Error: '.$mail->ErrorInfo; 
			} else { 
				echo 'Mail notification successfully sent to IT Dept!'; 
			} 
			//PHP Mail Script ends here
			
			
			$remittance_bal_Error = "<h4><strong>WARNING:</strong> Transaction failed! Your remittance balance is <strong>: &#8358 {$unposted} </strong> and NOT <strong>&#8358 {$amt_remitted}</strong>. Kindly <strong>CLOSE ALL DUPLICATE</strong> posting pages and <strong>RE-OPEN</strong> your posting page from the main navigation menu. Ensure you maintain a single posting page. <strong>Be WARNED!</strong> Any further attempt will automatically disable you! You are being watched!!!</h4>";
		}
	}
	
	
	if ($posting_officer_dept == "Accounts"){
		$leasing_post_status = "";
		$approval_status = "Pending";
		$verification_status = "Pending";
	} else {
		$leasing_post_status = "Pending";
		$approval_status = "";
		$verification_status = "";
	}
	
	//$debit_alias = $_POST['debit_alias'];
	//$credit_alias = $_POST['credit_account'];
	//$balance = "";
	
	if ($posting_officer_dept == "Accounts"){
		$debit_alias = $_POST['debit_account'];
		$credit_alias = $_POST['credit_account'];
	} else {
		$debit_alias = $_POST['debit_alias'];
		$credit_alias = $_POST['credit_account'];
	}
	
	$balance = "";
	
	
	if (empty($debit_alias)) {
		$error = true;
		$debiterror = "Please select the debit account";
	}
	if (empty($credit_alias)) {
		$error = true;
		$crediterror = "Please select the credit account";
	}
	
	
if (!$error) {
	//Debit Account
	$query_acct1 = "SELECT * ";
	$query_acct1 .= "FROM accounts ";
	
	if ($posting_officer_dept == "Accounts"){
		$query_acct1 .= "WHERE acct_id = '$debit_alias'";
	} else {
		$query_acct1 .= "WHERE acct_alias = '$debit_alias'";
	}
	$acct_debit_table_set = mysqli_query($dbcon, $query_acct1);
	$acct_debit_table = mysqli_fetch_array($acct_debit_table_set, MYSQLI_ASSOC);
	
	$debit_account = $acct_debit_table["acct_id"];
	$db_debit_table = $acct_debit_table["acct_table_name"];
	
	
	//Credit Account
	$query_acct2 = "SELECT * ";
	$query_acct2 .= "FROM accounts ";
	
	if ($posting_officer_dept == "Accounts"){
		$query_acct2 .= "WHERE acct_id = '$credit_alias'";
	} else {
		$query_acct2 .= "WHERE acct_id = '$credit_alias'";
	}
	$acct_credit_table_set = mysqli_query($dbcon, $query_acct2);
	$acct_credit_table = mysqli_fetch_array($acct_credit_table_set, MYSQLI_ASSOC);
	
	$credit_account = $acct_credit_table["acct_id"];
	$credit_account_desc = $acct_credit_table["acct_desc"];
	$db_credit_table = $acct_credit_table["acct_table_name"];
	
	if ($posting_officer_dept == "Accounts"){
		$income_line = $credit_account_desc;
	} else {
		$income_line = $_POST['income_line'];
	}

	
	$db_transaction_table = "account_general_transaction_new";
	
	date_default_timezone_set('Africa/Lagos');
	$now = date('Y-m-d H:i:s');
	
	$payment_category = "Other Collection";
	$wc_income_line = $_POST['income_line'];
		
		$query = "INSERT INTO $db_transaction_table (id,date_of_payment,transaction_desc,receipt_no,amount_paid,remitting_id,remitting_staff,posting_officer_id,posting_officer_name,posting_time,leasing_post_status,approval_status,verification_status,debit_account,credit_account,payment_category,plate_no,no_of_days,remit_id,income_line) VALUES('$txref','$date_of_payment','$transaction_desc','$receipt_no','$amount_paid','$remitting_id','$remitting_staff','$posting_officer_id','$posting_officer_name','$now','$leasing_post_status','$approval_status','$verification_status','$debit_account','$credit_account','$payment_category','$plate_no','$no_of_days','$remit_id','$wc_income_line')";
		
		$post_payment = mysqli_query($dbcon, $query);


		$dquery = "INSERT INTO $db_debit_table (id,acct_id,date,receipt_no,trans_desc,debit_amount,balance,approval_status) VALUES('$txref','$debit_account','$date_of_payment','$receipt_no','$transaction_desc','$amount_paid','$balance','$approval_status')";
		
		$debit_query = mysqli_query($dbcon, $dquery);
		
	
		$cquery = "INSERT INTO $db_credit_table (id,acct_id,date,receipt_no,trans_desc,credit_amount,balance,approval_status) VALUES('$txref','$credit_account','$date_of_payment','$receipt_no','$transaction_desc','$amount_paid','$balance','$approval_status')";
		
		$credit_query = mysqli_query($dbcon, $cquery);

		
if ($debit_query)
	{
		?>
		<script type="text/javascript">
		alert('Payment successfully posted for approval!');
		window.location.href='payments.php';
		</script>
		<?php
	}
	else
	{
		?>
		<script type="text/javascript">
		alert('Error occured while posting');
		window.location.href='payments.php';
		</script>
		<?php
	}
}
}



//Overnight Parking Processing
if ( isset($_POST['btn_post_overnight_parking']) ) {
	$posting_officer_dept = $_POST['posting_officer_dept'];
	
	if ($posting_officer_dept == "Accounts"){
		$remit_id = "";
	} else {
		$remit_id = $_POST['remit_id'];
		if($remit_id == "" || $remit_id == " "){
			$error = true;
		} else {
			$remit_id = $_POST['remit_id'];
		}
	}
	
	
	$income_line = $_POST['income_line'];
	
	$txref = time().mt_rand(0,9);
	
	$date_of_payment = $_POST['date_of_payment'];
	list($tid,$tim,$tiy) = explode("/",$date_of_payment);
	$date_of_payment = "$tiy-$tim-$tid";
	
	$type_category = $_POST['type'];
	
	if ($type_category == "Vehicle") {
		$category = "Vehicle";
		$plate_no = $_POST['plate_no'];
		$no_of_nights = $_POST['no_of_nights'];
		
		$transaction_desc = $_POST['vehicle_category'];
		$transaction_desc = htmlspecialchars($transaction_desc);
	} elseif ($type_category == "Forklift Operator") {
		$category = "Forklift Operator";
		$plate_no = "";
		$no_of_nights = $_POST['no_of_nights'];
		
		$transaction_desc = $_POST['transaction_descr'];
		$transaction_desc = htmlspecialchars($transaction_desc);
	} elseif ($type_category == "Artisan") {
		$category = $_POST['artisan_category'];
		$plate_no = "";
		$no_of_nights = $_POST['no_of_nights'];
		
		$transaction_desc = $_POST['transaction_descr'];
		$transaction_desc = htmlspecialchars($transaction_desc);
	} else {
		$error = true;
		$category = "";
		$plate_no = "";
		$no_of_nights = "";
		$transaction_desc = "";
	}
	
	
	
	$receipt_no = $_POST['receipt_no'];
	$query = "SELECT * FROM account_general_transaction_new WHERE receipt_no='$receipt_no'";
	$result = mysqli_query($dbcon,$query);
	$receipt_data = @mysqli_fetch_array($result, MYSQLI_ASSOC);
	$receipt_posting_officer = $receipt_data['posting_officer_name'];
	$receipt_date_of_payment = $receipt_data['date_of_payment'];
	
	$count = mysqli_num_rows($result);
	if($count!=0){
		$error = true;
		$receipt_Error = "<h4><strong>ATTENTION:</strong> Transaction failed! The <strong>receipt No: $receipt_no</strong> you entered has already been used by $receipt_posting_officer on $receipt_date_of_payment!</h4>";
	}
	
	$amount = $_POST['amount_paid'];
	$amount_paid = preg_replace('/[,]/', '', $amount);
	
	$remitting_post = $_POST['remitting_staff'];
	list($remitting_id,$remitting_check) = explode("-",@$remitting_post);
	
	if ($remitting_check == "wc"){
		$squery = "SELECT * FROM staffs WHERE user_id='$remitting_id'";
		$sresult = mysqli_query($dbcon,$squery);
		$remitting_data = @mysqli_fetch_array($sresult, MYSQLI_ASSOC);
		$remitting_staff = $remitting_data['full_name'];
	} else {
		$squery = "SELECT * FROM staffs_others WHERE id='$remitting_id'";
		$sresult = mysqli_query($dbcon,$squery);
		$remitting_data = @mysqli_fetch_array($sresult, MYSQLI_ASSOC);
		$remitting_staff = $remitting_data['full_name'];
	}
	
	
	
	$posting_officer_id = $_POST['posting_officer_id'];
	$posting_officer_name = $_POST['posting_officer_name'];
	
	
	if ($posting_officer_dept == "Wealth Creation"){
		//Check if the remittance balance remains unchanged  
		$amt_remitted = $_POST['amt_remitted'];

		$ca_query = "SELECT posting_officer_id, date_of_payment, payment_category, SUM(amount_paid) as amount_posted ";
		$ca_query .= "FROM account_general_transaction_new ";
		$ca_query .= "WHERE (posting_officer_id = '$posting_officer_id' AND payment_category='Other Collection' AND date_of_payment='$current_date') ";
		$ca_sum = @mysqli_query($dbcon,$ca_query);
		$ca_total = @mysqli_fetch_array($ca_sum, MYSQLI_ASSOC);
		
		$amount_posted = $ca_total['amount_posted'];
	
		$rm_query = "SELECT *, SUM(amount_paid) as amount_remitted ";
		$rm_query .= "FROM cash_remittance ";
		$rm_query .= "WHERE (remitting_officer_id = '$posting_officer_id' AND category='Other Collection' AND date='$current_date') ";
		$rm_sum = @mysqli_query($dbcon,$rm_query);
		$rm_total = @mysqli_fetch_array($rm_sum, MYSQLI_ASSOC);
		$amount_remitted = $rm_total['amount_remitted'];
		
		$unposted = $amount_remitted - $amount_posted;
		
		if($amount_paid > $unposted){
			$error = true;
			$amount_remitted_Error = "<h4><strong>ATTENTION:</strong> Transaction failed! The <strong>amount posted: &#8358 {$amount_paid}</strong> MUST be equal to<strong> &#8358 {$unposted} </strong>remittance balance!</h4>";
		}

		if($amt_remitted > $unposted){
			$error = true;

			//PHP Mail Script begins here 
			require ('phpmailer/Exception.php');
			require ('phpmailer/PHPMailer.php');
			require ('phpmailer/SMTP.php');

			 
			$mail = new PHPMailer; 
			 
			$mail->isSMTP();                      // Set mailer to use SMTP 
			$mail->Host = 'smtp.gmail.com';       // Specify main and backup SMTP servers 
			$mail->SMTPAuth = true;               // Enable SMTP authentication 
			$mail->Username = 'woobserp@gmail.com';   // SMTP username 
			$mail->Password = 'cdkwjqjiseoosjhx';   // Your gmail app password
			$mail->SMTPSecure = 'ssl';            // Enable TLS encryption, `ssl` also accepted 
			$mail->Port = 465;                    // TCP port to connect to 
			 
			// Sender info 
			$mail->setFrom('woobserp@gmail.com', 'Wealth Creation ERP'); 
			$mail->addReplyTo('dayo.adebajo@thearenamarket.com', 'Dayo Adebajo'); 
			 
			// Add a recipient 
			$mail->addAddress('dayo.adebajo@thearenamarket.com'); 
			 
			$mail->addCC('emmanuel.okadigbo@thearenamarket.com'); 
			//$mail->addBCC('dayo.adebajo@thearenamarket.com'); 
			 
			// Set email format to HTML 
			$mail->isHTML(true); 

			// Mail subject 
			$mail->Subject = $posting_officer_name.' Inconsistent Remittance Balance - Wealth Creation ERP'; 
			 
			// Mail body content
			$bodyContent = '<h3>Inconsistent Remittance Balance - '.$posting_officer_name.'</h3>';
			$bodyContent .= '<p>Be informed that the above officer attempted to post <strong>'.$transaction_desc.'</strong> of <strong>&#8358 '.$amount_paid.'</strong> with receipt no: <strong>'.$receipt_no.'</strong> using a posting menu with remittance balance of <strong>&#8358 '.$amt_remitted.'</strong> when the actual remittance balance is &#8358 <strong>'.$unposted.'</strong></p>'; 
			$bodyContent .= '<p>This email is automatically sent from the Wealth Creation ERP server</p>'; 
			$mail->Body    = $bodyContent; 
			 
			// Send email 
			if(!$mail->send()) { 
				echo 'Message could not be sent. Mailer Error: '.$mail->ErrorInfo; 
			} else { 
				echo 'Mail notification successfully sent to IT Dept!'; 
			} 
			//PHP Mail Script ends here
			
			
			$remittance_bal_Error = "<h4><strong>WARNING:</strong> Transaction failed! Your remittance balance is <strong>: &#8358 {$unposted} </strong> and NOT <strong>&#8358 {$amt_remitted}</strong>. Kindly <strong>CLOSE ALL DUPLICATE</strong> posting pages and <strong>RE-OPEN</strong> your posting page from the main navigation menu. Ensure you maintain a single posting page. <strong>Be WARNED!</strong> Any further attempt will automatically disable you! You are being watched!!!</h4>";
		}
	}
	
	
	if ($posting_officer_dept == "Accounts"){
		$leasing_post_status = "";
		$approval_status = "Pending";
		$verification_status = "Pending";
	} else {
		$leasing_post_status = "Pending";
		$approval_status = "";
		$verification_status = "";
	}
	
	if ($posting_officer_dept == "Accounts"){
		$debit_alias = $_POST['debit_account'];
		$credit_alias = $_POST['credit_account'];
	} else {
		$debit_alias = $_POST['debit_alias'];
		$credit_alias = $_POST['credit_alias'];
	}
	
	$balance = "";
	
	if (empty($debit_alias)) {
		$error = true;
		$debiterror = "Please select the debit account";
	}
	if (empty($credit_alias)) {
		$error = true;
		$crediterror = "Please select the credit account";
	}
	
	
if (!$error) {
	//Debit Account
	$query_acct1 = "SELECT * ";
	$query_acct1 .= "FROM accounts ";
	
	if ($posting_officer_dept == "Accounts"){
		$query_acct1 .= "WHERE acct_id = '$debit_alias'";
	} else {
		$query_acct1 .= "WHERE acct_alias = '$debit_alias'";
	}
	$acct_debit_table_set = mysqli_query($dbcon, $query_acct1);
	$acct_debit_table = mysqli_fetch_array($acct_debit_table_set, MYSQLI_ASSOC);
	
	$debit_account = $acct_debit_table["acct_id"];
	$db_debit_table = $acct_debit_table["acct_table_name"];
	
	
	//Credit Account
	$query_acct2 = "SELECT * ";
	$query_acct2 .= "FROM accounts ";
	
	if ($posting_officer_dept == "Accounts"){
		$query_acct2 .= "WHERE acct_id = '$credit_alias'";
	} else {
		$query_acct2 .= "WHERE acct_alias = '$credit_alias'";
	}
	$acct_credit_table_set = mysqli_query($dbcon, $query_acct2);
	$acct_credit_table = mysqli_fetch_array($acct_credit_table_set, MYSQLI_ASSOC);
	
	$credit_account = $acct_credit_table["acct_id"];
	$credit_account_desc = $acct_credit_table["acct_desc"];
	$db_credit_table = $acct_credit_table["acct_table_name"];
	
	if ($posting_officer_dept == "Accounts"){
		$income_line = $credit_account_desc;
	} else {
		$income_line = $_POST['income_line'];
	}

	
	$db_transaction_table = "account_general_transaction_new";
	
	date_default_timezone_set('Africa/Lagos');
	$now = date('Y-m-d H:i:s');
	
	$payment_category = "Other Collection";
	$wc_income_line = $_POST['income_line'];
		
		$query = "INSERT INTO $db_transaction_table (id,date_of_payment,ticket_category,transaction_desc,receipt_no,amount_paid,remitting_id,remitting_staff,posting_officer_id,posting_officer_name,posting_time,leasing_post_status,approval_status,verification_status,debit_account,credit_account,payment_category,plate_no,no_of_nights,remit_id,income_line) VALUES('$txref','$date_of_payment','$category','$transaction_desc','$receipt_no','$amount_paid','$remitting_id','$remitting_staff','$posting_officer_id','$posting_officer_name','$now','$leasing_post_status','$approval_status','$verification_status','$debit_account','$credit_account','$payment_category','$plate_no','$no_of_nights','$remit_id','$wc_income_line')";
		
		$post_payment = mysqli_query($dbcon, $query);


		$dquery = "INSERT INTO $db_debit_table (id,acct_id,date,receipt_no,trans_desc,debit_amount,balance,approval_status) VALUES('$txref','$debit_account','$date_of_payment','$receipt_no','$transaction_desc','$amount_paid','$balance','$approval_status')";
		
		$debit_query = mysqli_query($dbcon, $dquery);
		
	
		$cquery = "INSERT INTO $db_credit_table (id,acct_id,date,receipt_no,trans_desc,credit_amount,balance,approval_status) VALUES('$txref','$credit_account','$date_of_payment','$receipt_no','$transaction_desc','$amount_paid','$balance','$approval_status')";
		
		$credit_query = mysqli_query($dbcon, $cquery);

		
if ($debit_query)
	{
		?>
		<script type="text/javascript">
		alert('Payment successfully posted for approval!');
		window.location.href='payments.php';
		</script>
		<?php
	}
	else
	{
		?>
		<script type="text/javascript">
		alert('Error occured while posting');
		window.location.href='payments.php';
		</script>
		<?php
	}
}
}



//Car Sticker Processing
if ( isset($_POST['btn_post_car_sticker']) ) {
	$posting_officer_dept = $_POST['posting_officer_dept'];
	
	if ($posting_officer_dept == "Accounts"){
		$remit_id = "";
	} else {
		$remit_id = $_POST['remit_id'];
		if($remit_id == "" || $remit_id == " "){
			$error = true;
		} else {
			$remit_id = $_POST['remit_id'];
		}
	}
	
	
	$income_line = $_POST['income_line'];
	
	$txref = time().mt_rand(0,9);
	
	$selected_shop_no = $_POST['shop_no'];
	list($shop_no,$customer_name) = explode("-",$selected_shop_no);

	$date_of_payment = $_POST['date_of_payment'];
	list($tid,$tim,$tiy) = explode("/",$date_of_payment);
	$date_of_payment = "$tiy-$tim-$tid";
	
	
	$sticker_no = $_POST['sticker_no'];
	$plate_no = $_POST['plate_no'];
	
	$receipt_no = $_POST['receipt_no'];
	$query = "SELECT * FROM account_general_transaction_new WHERE receipt_no='$receipt_no'";
	$result = mysqli_query($dbcon,$query);
	$receipt_data = @mysqli_fetch_array($result, MYSQLI_ASSOC);
	$receipt_posting_officer = $receipt_data['posting_officer_name'];
	$receipt_date_of_payment = $receipt_data['date_of_payment'];
	
	$count = mysqli_num_rows($result);
	if($count!=0){
		$error = true;
		$receipt_Error = "<h4><strong>ATTENTION:</strong> Transaction failed! The <strong>receipt No: $receipt_no</strong> you entered has already been used by $receipt_posting_officer on $receipt_date_of_payment!</h4>";
	}
	
	$amount = $_POST['amount_paid'];
	$amount_paid = preg_replace('/[,]/', '', $amount);
	
	$transaction_desc = "Car Sticker ($sticker_no) $shop_no - $customer_name";
	$transaction_desc = htmlspecialchars($transaction_desc);
	
	$posting_officer_id = $_POST['posting_officer_id'];
	$posting_officer_name = $_POST['posting_officer_name'];
	
	
	if ($posting_officer_dept == "Wealth Creation"){
		//Check if the remittance balance remains unchanged  
		$amt_remitted = $_POST['amt_remitted'];

		$ca_query = "SELECT posting_officer_id, date_of_payment, payment_category, SUM(amount_paid) as amount_posted ";
		$ca_query .= "FROM account_general_transaction_new ";
		$ca_query .= "WHERE (posting_officer_id = '$posting_officer_id' AND payment_category='Other Collection' AND date_of_payment='$current_date') ";
		$ca_sum = @mysqli_query($dbcon,$ca_query);
		$ca_total = @mysqli_fetch_array($ca_sum, MYSQLI_ASSOC);
		
		$amount_posted = $ca_total['amount_posted'];
	
		$rm_query = "SELECT *, SUM(amount_paid) as amount_remitted ";
		$rm_query .= "FROM cash_remittance ";
		$rm_query .= "WHERE (remitting_officer_id = '$posting_officer_id' AND category='Other Collection' AND date='$current_date') ";
		$rm_sum = @mysqli_query($dbcon,$rm_query);
		$rm_total = @mysqli_fetch_array($rm_sum, MYSQLI_ASSOC);
		$amount_remitted = $rm_total['amount_remitted'];
		
		$unposted = $amount_remitted - $amount_posted;
		
		if($amount_paid > $unposted){
			$error = true;
			$amount_remitted_Error = "<h4><strong>ATTENTION:</strong> Transaction failed! The <strong>amount posted: &#8358 {$amount_paid}</strong> MUST be equal to<strong> &#8358 {$unposted} </strong>remittance balance!</h4>";
		}

		if($amt_remitted > $unposted){
			$error = true;

			//PHP Mail Script begins here 
			require ('phpmailer/Exception.php');
			require ('phpmailer/PHPMailer.php');
			require ('phpmailer/SMTP.php');

			 
			$mail = new PHPMailer; 
			 
			$mail->isSMTP();                      // Set mailer to use SMTP 
			$mail->Host = 'smtp.gmail.com';       // Specify main and backup SMTP servers 
			$mail->SMTPAuth = true;               // Enable SMTP authentication 
			$mail->Username = 'woobserp@gmail.com';   // SMTP username 
			$mail->Password = 'cdkwjqjiseoosjhx';   // Your gmail app password
			$mail->SMTPSecure = 'ssl';            // Enable TLS encryption, `ssl` also accepted 
			$mail->Port = 465;                    // TCP port to connect to 
			 
			// Sender info 
			$mail->setFrom('woobserp@gmail.com', 'Wealth Creation ERP'); 
			$mail->addReplyTo('dayo.adebajo@thearenamarket.com', 'Dayo Adebajo'); 
			 
			// Add a recipient 
			$mail->addAddress('dayo.adebajo@thearenamarket.com'); 
			 
			$mail->addCC('emmanuel.okadigbo@thearenamarket.com'); 
			//$mail->addBCC('dayo.adebajo@thearenamarket.com'); 
			 
			// Set email format to HTML 
			$mail->isHTML(true); 

			// Mail subject 
			$mail->Subject = $posting_officer_name.' Inconsistent Remittance Balance - Wealth Creation ERP'; 
			 
			// Mail body content
			$bodyContent = '<h3>Inconsistent Remittance Balance - '.$posting_officer_name.'</h3>';
			$bodyContent .= '<p>Be informed that the above officer attempted to post <strong>'.$transaction_desc.'</strong> of <strong>&#8358 '.$amount_paid.'</strong> with receipt no: <strong>'.$receipt_no.'</strong> using a posting menu with remittance balance of <strong>&#8358 '.$amt_remitted.'</strong> when the actual remittance balance is &#8358 <strong>'.$unposted.'</strong></p>'; 
			$bodyContent .= '<p>This email is automatically sent from the Wealth Creation ERP server</p>'; 
			$mail->Body    = $bodyContent; 
			 
			// Send email 
			if(!$mail->send()) { 
				echo 'Message could not be sent. Mailer Error: '.$mail->ErrorInfo; 
			} else { 
				echo 'Mail notification successfully sent to IT Dept!'; 
			} 
			//PHP Mail Script ends here
			
			
			$remittance_bal_Error = "<h4><strong>WARNING:</strong> Transaction failed! Your remittance balance is <strong>: &#8358 {$unposted} </strong> and NOT <strong>&#8358 {$amt_remitted}</strong>. Kindly <strong>CLOSE ALL DUPLICATE</strong> posting pages and <strong>RE-OPEN</strong> your posting page from the main navigation menu. Ensure you maintain a single posting page. <strong>Be WARNED!</strong> Any further attempt will automatically disable you! You are being watched!!!</h4>";
		}
	}
	
	
	if ($posting_officer_dept == "Accounts"){
		$leasing_post_status = "";
		$approval_status = "Pending";
		$verification_status = "Pending";
	} else {
		$leasing_post_status = "Pending";
		$approval_status = "";
		$verification_status = "";
	}
	
	if ($posting_officer_dept == "Accounts"){
		$debit_alias = $_POST['debit_account'];
		$credit_alias = $_POST['credit_account'];
	} else {
		$debit_alias = $_POST['debit_alias'];
		$credit_alias = $_POST['credit_alias'];
	}
	
	$balance = "";
	
	if (empty($debit_alias)) {
		$error = true;
		$debiterror = "Please select the debit account";
	}
	if (empty($credit_alias)) {
		$error = true;
		$crediterror = "Please select the credit account";
	}
	
	
if (!$error) {
	//Debit Account
	$query_acct1 = "SELECT * ";
	$query_acct1 .= "FROM accounts ";
	
	if ($posting_officer_dept == "Accounts"){
		$query_acct1 .= "WHERE acct_id = '$debit_alias'";
	} else {
		$query_acct1 .= "WHERE acct_alias = '$debit_alias'";
	}
	$acct_debit_table_set = mysqli_query($dbcon, $query_acct1);
	$acct_debit_table = mysqli_fetch_array($acct_debit_table_set, MYSQLI_ASSOC);
	
	$debit_account = $acct_debit_table["acct_id"];
	$db_debit_table = $acct_debit_table["acct_table_name"];
	
	
	//Credit Account
	$query_acct2 = "SELECT * ";
	$query_acct2 .= "FROM accounts ";
	
	if ($posting_officer_dept == "Accounts"){
		$query_acct2 .= "WHERE acct_id = '$credit_alias'";
	} else {
		$query_acct2 .= "WHERE acct_alias = '$credit_alias'";
	}
	$acct_credit_table_set = mysqli_query($dbcon, $query_acct2);
	$acct_credit_table = mysqli_fetch_array($acct_credit_table_set, MYSQLI_ASSOC);
	
	$credit_account = $acct_credit_table["acct_id"];
	$credit_account_desc = $acct_credit_table["acct_desc"];
	$db_credit_table = $acct_credit_table["acct_table_name"];
	
	if ($posting_officer_dept == "Accounts"){
		$income_line = $credit_account_desc;
	} else {
		$income_line = $_POST['income_line'];
	}
	
	
	$db_transaction_table = "account_general_transaction_new";
	
	date_default_timezone_set('Africa/Lagos');
	$now = date('Y-m-d H:i:s');
	
	$payment_category = "Other Collection";
	$wc_income_line = $_POST['income_line'];
		
		$query = "INSERT INTO $db_transaction_table (id,date_of_payment,transaction_desc,receipt_no,amount_paid,remitting_id,remitting_staff,posting_officer_id,posting_officer_name,posting_time,leasing_post_status,approval_status,verification_status,debit_account,credit_account,payment_category,plate_no,sticker_no,remit_id,income_line) VALUES('$txref','$date_of_payment','$transaction_desc','$receipt_no','$amount_paid','','','$posting_officer_id','$posting_officer_name','$now','$leasing_post_status','$approval_status','$verification_status','$debit_account','$credit_account','$payment_category','$plate_no','$sticker_no','$remit_id','$wc_income_line')"; 
		$post_payment = mysqli_query($dbcon, $query);
		
		
		$st_query = "UPDATE car_sticker SET trans_id='$txref', shop_no='$shop_no', customer_name='$customer_name', date_of_payment='$date_of_payment', receipt_no='$receipt_no', status='Sold' WHERE sticker_no='$sticker_no'";
		$st_query = @mysqli_query($dbcon, $st_query);


		$dquery = "INSERT INTO $db_debit_table (id,acct_id,date,receipt_no,trans_desc,debit_amount,balance,approval_status) VALUES('$txref','$debit_account','$date_of_payment','$receipt_no','$transaction_desc','$amount_paid','$balance','$approval_status')";
		
		$debit_query = mysqli_query($dbcon, $dquery);
		
	
		$cquery = "INSERT INTO $db_credit_table (id,acct_id,date,receipt_no,trans_desc,credit_amount,balance,approval_status) VALUES('$txref','$credit_account','$date_of_payment','$receipt_no','$transaction_desc','$amount_paid','$balance','$approval_status')";
		
		$credit_query = mysqli_query($dbcon, $cquery);

if ($debit_query)
	{
		?>
		<script type="text/javascript">
		alert('Payment successfully posted for approval!');
		window.location.href='payments.php';
		</script>
		<?php
	}
	else
	{
		?>
		<script type="text/javascript">
		alert('Error occured while posting');
		window.location.href='payments.php';
		</script>
		<?php
	}
}
}

//Processing Ends Here





if(isset($_GET["income_line"])) {
	$income_line = $_GET["income_line"];

//General Income Line	
	if ($income_line == "general") {
		$income_line_desc = "General Income Lines";

//Scrollboard Income Line
	} elseif ($income_line == "scroll_board") {
		$alias = "scroll_board";
		$income_line_desc = "Scroll Board";

//Car Sticker	
	} elseif ($income_line == "car_sticker") {
		$alias = "car_sticker";
		$income_line_desc = "Car Sticker";
		
//Car Loading	
	} elseif ($income_line == "car_loading") {
		$alias = "car_loading";
		$income_line_desc = "Car Loading";
		$transaction_descr = $income_line_desc;
?>
<script type="text/javascript">
	function loadCalc() {

	var no_of_ticket  = isNaN(parseFloat(document.getElementById('no_of_tickets').value))? 0 : parseFloat(document.getElementById('no_of_tickets').value);

	var total_ticket_amount = (no_of_ticket * 1000);
	document.getElementById('amount_paid').value = total_ticket_amount;
	}
</script>
	
<?php	
//Hawkers	
	} elseif ($income_line == "hawkers") {
		$alias = "hawkers";
		$income_line_desc = "Hawker Tickets";
		$transaction_descr = $income_line_desc;
?>
<script type="text/javascript">
	function loadCalc() {

	var no_of_ticket  = isNaN(parseFloat(document.getElementById('no_of_tickets').value))? 0 : parseFloat(document.getElementById('no_of_tickets').value);

	var total_ticket_amount = (no_of_ticket * 200);
	document.getElementById('amount_paid').value = total_ticket_amount;
	}
</script>


<?php	
//Other POS	
	} elseif ($income_line == "other_pos") {
		$alias = "other_pos";
		$income_line_desc = "Other POS Tickets";
		$transaction_descr = $income_line_desc;
?>
<script type="text/javascript">
	function loadCalc() {

	var no_of_ticket  = isNaN(parseFloat(document.getElementById('no_of_tickets').value))? 0 : parseFloat(document.getElementById('no_of_tickets').value);

	var total_ticket_amount = (no_of_ticket * 300);
	document.getElementById('amount_paid').value = total_ticket_amount;
	}
</script>

<?php	
//Car Park	
	} elseif ($income_line == "car_park") {
		$alias = "carpark";
		$income_line_desc = "Car Park Tickets";
		$transaction_descr = $income_line_desc;
?>
<script type="text/javascript">
	function loadCalc() {
		
	var ticket_category  = isNaN(parseFloat(document.getElementById('ticket_category').value))? 0 : parseFloat(document.getElementById('ticket_category').value);

	var no_of_ticket  = isNaN(parseFloat(document.getElementById('no_of_tickets').value))? 0 : parseFloat(document.getElementById('no_of_tickets').value);

	var total_ticket_amount = (no_of_ticket * ticket_category);
	document.getElementById('amount_paid').value = total_ticket_amount;
	}
</script>
	
	
<?php
//WheelBarrow	
	} elseif ($income_line == "wheelbarrow") {
		$alias = "wheelbarrow";
		$income_line_desc = "WheelBarrow Tickets";
		$transaction_descr = $income_line_desc;
?>
<script type="text/javascript">
	function loadCalc() {

	var no_of_ticket  = isNaN(parseFloat(document.getElementById('no_of_tickets').value))? 0 : parseFloat(document.getElementById('no_of_tickets').value);

	var total_ticket_amount = (no_of_ticket * 300);
	document.getElementById('amount_paid').value = total_ticket_amount;
	}
</script>

<?php
//Daily Trade 
	} elseif ($income_line == "daily_trade") {
		$alias = "daily_trade";
		$income_line_desc = "Daily Trade Tickets";
		$transaction_descr = $income_line_desc;
?>
<script type="text/javascript">
	function loadCalc() {
	
	var ticket_category  = isNaN(parseFloat(document.getElementById('ticket_category').value))? 0 : parseFloat(document.getElementById('ticket_category').value);
	
	var no_of_ticket  = isNaN(parseFloat(document.getElementById('no_of_tickets').value))? 0 : parseFloat(document.getElementById('no_of_tickets').value);

	var total_ticket_amount = (no_of_ticket * ticket_category);
	document.getElementById('amount_paid').value = total_ticket_amount;
	}
</script>

<?php
//Daily Trade Arrears
	} elseif ($income_line == "daily_trade_arrears") {
		$alias = "daily_trade_arrears";
		$income_line_desc = "Daily Trade Arrears Tickets";
		$transaction_descr = $income_line_desc;
?>
<script type="text/javascript">
	function loadCalc() {
	
	var ticket_category  = isNaN(parseFloat(document.getElementById('ticket_category').value))? 0 : parseFloat(document.getElementById('ticket_category').value);
	
	var no_of_ticket  = isNaN(parseFloat(document.getElementById('no_of_tickets').value))? 0 : parseFloat(document.getElementById('no_of_tickets').value);

	var total_ticket_amount = (no_of_ticket * ticket_category);
	document.getElementById('amount_paid').value = total_ticket_amount;
	}
</script>



<?php
//Abattoir
	} elseif ($income_line == "abattoir") {
		$alias = "abattoir";
		$income_line_desc = "Abattoir";
		$transaction_descr = $income_line_desc;
?>
<script type="text/javascript">
	function loadCalc() {
		
	var category = document.getElementById('category').value;
	var unit_cost = 0;
	
	if (category == "Cows Killed"){
		unit_cost = 1800;
	} else if (category == "Cows Takeaway"){
		unit_cost = 1000;
	} else if (category == "Goats Killed"){
		unit_cost = 400;
	} else if (category == "Goats Takeaway"){
		unit_cost = 100;
	} else if (category == "Pots of Pomo"){
		unit_cost = 250;
	} else {
		unit_cost = 0;
	}

	var quantity  = isNaN(parseFloat(document.getElementById('quantity').value))? 0 : parseFloat(document.getElementById('quantity').value);

	var total_ticket_amount = (quantity * unit_cost);
	document.getElementById('amount_paid').value = total_ticket_amount;
	}
</script>

<?php
//Loading/Offloading
	} elseif ($income_line == "loading") {
		$alias = "loading";
		$income_line_desc = "Loading/Offloading";
?>
<script type="text/javascript">
	function loadCalc() {
		
	var category = document.getElementById('category').value;
	
	if (document.getElementById('no_of_days').value == 0 || document.getElementById('no_of_days').value == "") {
		no_of_days = 1;
		document.getElementById('no_of_days').value = 1;
	} else {
		var no_of_days = isNaN(parseFloat(document.getElementById('no_of_days').value))? 0 : parseFloat(document.getElementById('no_of_days').value);
	}
	
	var unit_cost = 0;
	
	
	if (category == "Goods (Offloading) - N7000"){
		unit_cost = 7000;
	} else if (category == "Goods (Offloading) - N15000"){
		unit_cost = 15000;
	} else if (category == "Goods (Offloading) - N20000"){
		unit_cost = 20000;
	} else if (category == "Goods (Offloading) - N30000"){
		unit_cost = 30000;
	} else if (category == "Goods (Loading) - N20000"){
		unit_cost = 20000;
	} else if (category == "Fruits (Offloading) - N2500"){
		unit_cost = 2500;
	} else if (category == "Fruits (Offloading) - N3500"){
		unit_cost = 3500;
	} else if (category == "Fruits (Offloading) - N7000"){
		unit_cost = 7000;
	} else if (category == "Fruits (Offloading) - N15000"){
		unit_cost = 15000;
	} else if (category == "Apple Bus (Loading) - N3500"){
		unit_cost = 3500;
	} else if (category == "Cargo Truck (Loading) - N7000"){
		unit_cost = 7000;
	} else if (category == "Cargo Truck 1 (Offloading) - N15000"){
		unit_cost = 15000;
	} else if (category == "Cargo Truck 2 (Offloading) - N20000"){
		unit_cost = 20000;
	} else if (category == "OK Truck (Offloading) - N20000"){
		unit_cost = 20000;
	} else if (category == "20 feet container - (Loading) - N15000"){
		unit_cost = 15000;
	} else if (category == "20 feet container - (Offloading) - N15000"){
		unit_cost = 15000;
	} else if (category == "40 feet container - (Offloading) N30000"){
		unit_cost = 30000;
	} else if (category == "40 feet container - (Abassa Offloading - Weekend) - N30000"){
		unit_cost = 30000;
	} else if (category == "40 feet container - (Shoe Offloading - Weekend) - N60000"){
		unit_cost = 60000;
	} else if (category == "40 feet container - (Apple Offloading) - N30000"){
		unit_cost = 30000;
	} else if (category == "40 feet container - (Apple Offloading - Sunday) - N60000"){
		unit_cost = 60000;
	} else if (category == "40 feet container - (Ok, Curtain Offloading) - N30000"){
		unit_cost = 30000;
	} else if (category == "LT Buses (Offloading) - N4000"){
		unit_cost = 4000;
	} else if (category == "LT Buses (Offloading - Sunday) - N7000"){
		unit_cost = 7000;
	} else if (category == "LT Buses (Loading) - N4000"){
		unit_cost = 4000;
	} else if (category == "Mini LT Buses (Loading) - N3000"){
		unit_cost = 3000;
	} else if (category == "Mini LT Buses (Offloading) - N3000"){
		unit_cost = 3000;
	} else if (category == "LT Buses Army Staff (Loading) - N1000"){
		unit_cost = 1000;
	} else if (category == "LT Buses Army Staff (Loading) - N2000"){
		unit_cost = 2000;
	} else if (category == "OK Mini Van (Loading) - N6000"){
		unit_cost = 6000;
	} else if (category == "OK Mini Van (Offloading) - N6000"){
		unit_cost = 6000;
	} else if (category == "Mini Van (Loading) - N5000"){
		unit_cost = 5000;
	} else if (category == "Mini Van (Offloading) - N5000"){
		unit_cost = 5000;
	} else if (category == "Sienna Buses (Loading) - N2000"){
		unit_cost = 2000;
	} else if (category == "Oil Tanker (Offloading) - N30000"){
		unit_cost = 30000;
	} else {
		unit_cost = 0;
	}
	
	
	document.getElementById('amount_paid').value = (unit_cost * no_of_days);
	
	
	}
</script>
<?php
//Toilet Collection 
	} elseif ($income_line == "toilet_collection") {
		$alias = "toilet_collection";
		$income_line_desc = "Toilet Collection";
		$transaction_descr = $income_line_desc;


//Overnight Parking
	} elseif ($income_line == "overnight_parking") {
		$alias = "overnight_parking";
		$income_line_desc = "Overnight Parking";
?>
<script type="text/javascript">
function loadCalc() {
	type_category = document.getElementById("type_category").value;
	
	var unit_cost = 0;
	
	if (document.getElementById('no_of_nights').value == 0 || document.getElementById('no_of_nights').value == "") {
		no_of_nights = 1;
		document.getElementById('no_of_nights').value = 1;
	} else {
		var no_of_nights = isNaN(parseFloat(document.getElementById('no_of_nights').value))? 0 : parseFloat(document.getElementById('no_of_nights').value);
	}
	
	if (type_category == "Vehicle"){
		document.getElementById("vehicle_div").style.display="block";
		document.getElementById("plate_no_div").style.display="block";
		document.getElementById("trans_desc_div").style.display="none";
		document.getElementById("artisan_div").style.display="none";
		
		var vehicle_category = document.getElementById('vehicle_category').value;
		if (vehicle_category == "Overnight Parking - 40 feet - N5000"){
			unit_cost = 5000;
		} else if (vehicle_category == "Overnight Parking - OK Trucks - N2000"){
			unit_cost = 2000;
		} else if (vehicle_category == "Overnight Parking - LT Buses - N1500"){
			unit_cost = 1500;
		} else if (vehicle_category == "Overnight Parking - Sienna - N1000"){
			unit_cost = 1000;
		} else if (vehicle_category == "Overnight Parking - Cars - N1000"){
			unit_cost = 1000;
		} else {
			unit_cost = 0;
		}
		
	} else if (type_category == "Forklift Operator"){
		document.getElementById("vehicle_div").style.display="none";
		document.getElementById("plate_no_div").style.display="none";
		document.getElementById("artisan_div").style.display="none";
		document.getElementById("trans_desc_div").style.display="block";
		
		unit_cost = 500;
		
	} else if (type_category == "Artisan"){
		document.getElementById("vehicle_div").style.display="none";
		document.getElementById("plate_no_div").style.display="none";
		document.getElementById("artisan_div").style.display="block";
		document.getElementById("trans_desc_div").style.display="block";
		
		var artisan_category = document.getElementById('artisan_category').value;
		if (artisan_category == "Welder/Welding Equipment"){
			unit_cost = 500;
		} else if (artisan_category == "Carpenter"){
			unit_cost = 500;
		} else if (artisan_category == "Bricklayer"){
			unit_cost = 500;
		} else if (artisan_category == "Others"){
			unit_cost = 500;
		} else {
			unit_cost = 0;
		} 
	} else {
		document.getElementById("vehicle_div").style.display="none";
		document.getElementById("plate_no_div").style.display="none";
		document.getElementById("artisan_div").style.display="none";
		document.getElementById("trans_desc_div").style.display="none";
	}

	document.getElementById('amount_paid').value = (unit_cost * no_of_nights);
	
	var amount = document.getElementById('amount_paid').value;
	if(amount == 0){
		document.getElementById("btn_post_overnight_parking").disabled = true;
	}
}
</script>
<?php		

	} else {
		
	}
}

?>
<style>
#hidden_div {
    display: none;
}
</style>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>Welcome - <?php echo $staff['full_name']; ?> | Wealth Creation ERP</title>
		<meta http-equiv="Content-Type" name="description" content="Wealth Creation ERP Management System; text/html; charset=utf-8" />
		<meta name="author" content="Woobs Resources Ltd">
		
		<meta name="viewport" content="width=device-width, initial-scale=1.0">

		<link rel="stylesheet" type="text/css" href="../../css/bootstrap.min.css">
		<link rel="stylesheet" type="text/css" href="../../css/formValidation.min.css">
		
		<link rel="stylesheet" type="text/css" href="../../css/datepicker.min.css">
		<link rel="stylesheet" type="text/css" href="../../css/datepicker3.min.css">
		
		
		<link rel="stylesheet" type="text/css" href="../../css/bootstrap-theme.min.css">
		<link rel="stylesheet" type="text/css" href="../../css/bootstrapValidator.min.css">
		<!--<script type="text/javascript" src="../../js/jquery.min.js"></script>-->
		
		
		
		<link rel="stylesheet" href="../../css/sub_menu.css">
	</head>
<body>
<?php 
include ('include/staff_navbar.php');
			
	$vp_user_id = $menu["user_id"];
	$vp_staff_name = $menu["full_name"];
	$sessionID = session_id();
	
	$url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	$url = htmlspecialchars(strip_tags($url));
	
	date_default_timezone_set('Africa/Lagos');
	$now = date('Y-m-d H:i:s');
	
	$vp_query = "INSERT IGNORE INTO visited_pages (id, user_id, staff_name, session_id, uri, time) VALUES ('', '$vp_user_id', '$vp_staff_name', '$sessionID', '$url', '$now')";
	$vp_result = mysqli_query($dbcon,$vp_query); 
?>

<?php
	if(isset($_SESSION['staff']) ) {
		$staffquery = "SELECT * FROM staffs WHERE user_id=".$_SESSION['staff'];
	}
	if(isset($_SESSION['admin']) ) {
		$staffquery = "SELECT * FROM staffs WHERE user_id=".$_SESSION['admin'];
	}
	$staffresult = mysqli_query($dbcon, $staffquery);
	$session_staff = mysqli_fetch_array($staffresult, MYSQLI_ASSOC);
	$session_id = $session_staff['user_id'];
	
	$dcount_query = "SELECT COUNT(id) FROM account_general_transaction_new ";
	$dcount_query .= "WHERE posting_officer_id = '$session_id' ";
	$dcount_query .= "AND (approval_status = 'Declined' OR verification_status = 'Declined')";
	$result = mysqli_query($dbcon, $dcount_query);
	$acct_office_post = mysqli_fetch_array($result);
	$no_of_declined_post = $acct_office_post[0];
?>

<div class="well"></div>
<div class="container-fluid">
	<div class="col-md-2">
		<div class="row">
			<h3><strong>Lines of Income</strong></h3>
		</div>
		
		<div class="row">
			<div class="table-responsive">
				<table class="table table-hover">
					<tr>
						<td><a href="payments.php?income_line=general" oncontextmenu="return false;">General</a></td>
					</tr>
					<tr>
						<td><a href="payments.php?income_line=abattoir" oncontextmenu="return false;">Abattoir</a></td>
					</tr>
					<tr>
						<td><a href="payments.php?income_line=car_loading" oncontextmenu="return false;">Car Loading Ticket</a></td>
					</tr>
					<tr>
						<td><a href="payments.php?income_line=car_park" oncontextmenu="return false;">Car Park Ticket</a></td>
					</tr>
					<tr>
						<td><a href="payments.php?income_line=hawkers" oncontextmenu="return false;">Hawkers Ticket</a></td>
					</tr>
					<tr>
						<td><a href="payments.php?income_line=wheelbarrow" oncontextmenu="return false;">WheelBarrow Ticket</a></td>
					</tr>
					<tr>
						<td><a href="payments.php?income_line=daily_trade" oncontextmenu="return false;">Daily Trade</a></td>
					</tr>
					<tr>
						<td><a href="payments.php?income_line=toilet_collection" oncontextmenu="return false;">Toilet Collection</a></td>
					</tr>
					<tr>
						<td><a href="payments.php?income_line=scroll_board" oncontextmenu="return false;">Scroll Board</a></td>
					</tr>
					<tr>
						<td><a href="payments.php?income_line=other_pos" oncontextmenu="return false;">Other POS Ticket</a></td>
					</tr>
					<tr>
						<td><a href="payments.php?income_line=daily_trade_arrears" oncontextmenu="return false;">Daily Trade Arrears</a></td>
					</tr>
				</table>
			</div>
		</div>		
	</div>

	<div class="col-md-8">
		<div class="row">
				<div class="container-fluid">
					<div class="col-md-12">
						<h4>
						<?php
							if(isset($_SESSION['staff']) ) {
								$staffquery = "SELECT * FROM staffs WHERE user_id=".$_SESSION['staff'];
							}
							if(isset($_SESSION['admin']) ) {
								$staffquery = "SELECT * FROM staffs WHERE user_id=".$_SESSION['admin'];
							}
								$staffresult = mysqli_query($dbcon, $staffquery);
								$session_staff = mysqli_fetch_array($staffresult, MYSQLI_ASSOC);
								$session_id = $session_staff['user_id'];
								$session_department = $session_staff['department'];
								
								if($session_department == "Wealth Creation") {
									$ca_query = "SELECT posting_officer_id, date_of_payment, payment_category, SUM(amount_paid) as amount_posted ";
									$ca_query .= "FROM account_general_transaction_new ";
									$ca_query .= "WHERE (posting_officer_id = '$session_id' AND payment_category='Other Collection' AND date_of_payment='$current_date') ";
									$ca_sum = @mysqli_query($dbcon,$ca_query);
									$ca_total = @mysqli_fetch_array($ca_sum, MYSQLI_ASSOC);
									
									$amount_posted = $ca_total['amount_posted'];
								
									$rm_query = "SELECT *, SUM(amount_paid) as amount_remitted ";
									$rm_query .= "FROM cash_remittance ";
									$rm_query .= "WHERE (remitting_officer_id = '$session_id' AND category='Other Collection' AND date='$current_date') ";
									$rm_sum = @mysqli_query($dbcon,$rm_query);
									$rm_total = @mysqli_fetch_array($rm_sum, MYSQLI_ASSOC);
									
									$date = $rm_total["date"];
									$remit_id = $rm_total["remit_id"];
									$category = $rm_total["category"];
									
									$amount_remitted = $rm_total['amount_remitted'];
									
									$unposted = $amount_remitted - $amount_posted;
									
									if ($menu["department"] == "Wealth Creation") {
									//remittance amount left
									$amt_remitted = $unposted;
									}

									
									echo 'Remitted: <span style="color:#ec7063; font-weight:bold;">&#8358 '.$amount_remitted.'</span> | Posted: <span style="color:#ec7063; font-weight:bold;">&#8358 '.$amount_posted.'</span> | Unposted: <span style="color:#ec7063; font-weight:bold;">&#8358 '.$unposted.'</span> ';
								}
								if ($current_time >= $wc_begin_time && $current_time <= $wc_end_time){
									echo '<a href="log_unposted_trans_others.php" class="btn btn-sm btn-primary">Log Unposted Collection</a> ';
								} 
								
								echo '<a href="payments_past.php" class="btn btn-sm btn-danger">Post Past Payments</a> ';
								
								if(date('D') == 'Mon' && $session_department == "Wealth Creation") { 
									echo '<a href="payments_sunday.php" class="btn btn-sm btn-success">Post Sunday Market</a>';
								}
						?>
						</h4>
						
						<h4>
						<?php							
							$till_query = "SELECT SUM(amount_paid) as amount_posted ";
							$till_query .= "FROM account_general_transaction_new ";
							$till_query .= "WHERE posting_officer_id = '$session_id' ";
							
							if($menu["department"] == "Wealth Creation") {
								$till_query .= "AND leasing_post_status = 'Pending'";
							} else {
								$till_query .= "AND approval_status = 'Pending'";
							}
							
							$sum = @mysqli_query($dbcon,$till_query);
							$total = @mysqli_fetch_array($sum, MYSQLI_ASSOC);
							
							$till = $total['amount_posted'];
														
							$declined_query = "SELECT SUM(amount_paid) as amount_posted ";
							$declined_query .= "FROM account_general_transaction_new ";
							$declined_query .= "WHERE posting_officer_id = '$session_id' ";
							
							if($menu["department"] == "Wealth Creation") {
								$declined_query .= "AND leasing_post_status = 'Declined'";
							} else {
								$declined_query .= "AND approval_status = 'Declined'";
							}
							
							$dsum = @mysqli_query($dbcon,$declined_query);
							$dtotal = @mysqli_fetch_array($dsum, MYSQLI_ASSOC);
							
							$till_declined = $dtotal['amount_posted'];
							
							$total_till = ($till + $till_declined);
							$total_till = number_format((float)$total_till, 2);
							
							if ($menu["department"] == "Accounts") {
								echo '<a href="acct_view_trans.php"><span style="color:#ec7063; font-weight:bold;">&#8358 '.$total_till.'</span> Till Balance | ';
							} else {
								echo '<a href="../leasing/view_trans.php"><span style="color:#ec7063; font-weight:bold;">&#8358 '.$total_till.'</span> Till Balance | ';
							}
						?>
						
						<?php
							$no_of_declined_post = 0;
							
							$ldcount_query = "SELECT COUNT(id) FROM account_general_transaction_new ";
							$ldcount_query .= "WHERE posting_officer_id = '$session_id' ";
							$ldcount_query .= "AND leasing_post_status = 'Declined'";
							$lresult = mysqli_query($dbcon, $ldcount_query);
							$leasing_post = mysqli_fetch_array($lresult);
							$no_of_declined_post_leasing = $leasing_post[0];
							
							$dcount_query = "SELECT COUNT(id) FROM account_general_transaction_new ";
							$dcount_query .= "WHERE posting_officer_id = '$session_id' ";
							$dcount_query .= "AND approval_status = 'Declined'";
							$result = mysqli_query($dbcon, $dcount_query);
							$account_post = mysqli_fetch_array($result);
							$no_of_declined_post_account = $account_post[0];
							
							if($menu["department"] == "Wealth Creation") {
								$no_of_declined_post = $no_of_declined_post_leasing;
							} else {
								$no_of_declined_post = $no_of_declined_post_account;
							}
							
							$icount_query = "SELECT COUNT(id) FROM account_general_transaction_new ";
							$icount_query .= "WHERE posting_officer_id = '$session_id' ";
							$icount_query .= "AND it_status != '' ";
							$iresult = @mysqli_query($dbcon, $icount_query);
							$it_status_post = @mysqli_fetch_array($iresult);
							$it_status = $it_status_post[0];
							
							$pcount_query = "SELECT COUNT(id) FROM account_general_transaction_new ";
							$pcount_query .= "WHERE posting_officer_id = '$session_id' ";
							
							if($menu["department"] == "Wealth Creation") {
								$pcount_query .= "AND leasing_post_status = 'Pending'";
							} else {
								$pcount_query .= "AND approval_status = 'Pending'";
							}
							
							$result = mysqli_query($dbcon, $pcount_query);
							$leasing_post = mysqli_fetch_array($result);
							$no_of_pending_post = $leasing_post[0];
							
							if($menu["department"] == "Accounts") {
								echo '<span style="color:#ec7063; font-weight:bold;">'.$no_of_declined_post.'</span> Declined | <span style="color:#ec7063; font-weight:bold;">'.$no_of_pending_post.'</span> Pending</a> | <a href="view_trans.php"><span style="color:#ec7063; font-weight:bold;">'.$it_status.'</span> Wrong entries</a>';
							} else {
								echo '<span style="color:#ec7063; font-weight:bold;">'.$no_of_declined_post.'</span> Declined | <span style="color:#ec7063; font-weight:bold;">'.$no_of_pending_post.'</span> Pending</a> | <a href="../leasing/view_trans.php"><span style="color:#ec7063; font-weight:bold;">'.$it_status.'</span> Wrong entries</a>';
							}
						?>
						</h4>
						
						<?php 
							if ($menu["department"] == "Wealth Creation" || $menu["level"] == "ce") {
								include ('../leasing/include/countdown_script.php'); 
							}
						?>
					</div>
				</div>
		</div>
	
		<div class="row">
			<div class="col-md-11">
				<ul class="nav nav-tabs">
					<li class="active"><a href="#t1" data-toggle="tab">
					<?php
					//Remittance Portal
						if(isset($_GET["income_line"])) {
							echo '<strong>'.@$income_line_desc.'</strong>';
						}
					?>
					</a></li>
				</ul>
				
				<div class="tab-content">
					<div class="tab-pane fade in active" id="t1">
						<h3>
							<?php 
								

								echo '<h5>';

								$gquery = "SELECT * FROM accounts";
								//$gquery .= "WHERE page_visibility = 'General' ";
								//$gquery .= "ORDER BY acct_desc ASC";
								$gaccount_set = @mysqli_query($dbcon, $gquery); 
								
								while ($gaccount = mysqli_fetch_array($gaccount_set, MYSQLI_ASSOC)) {
									if(@$income_line == "general") {
										echo ucwords(strtolower($gaccount['acct_desc'])).' | '; 
									}
								} 
								echo '</h5>';
							?>
						</h3>
						<?php
							if(isset($_GET["income_line"])) {
								if ($income_line == "general") {
									include 'payments/general_form_inc.php';
								} elseif ($income_line == "car_sticker") {
									include 'payments/car_sticker_inc.php';
								} elseif ($income_line == "abattoir") {
									include 'payments/abattoir_form_inc.php';
								} elseif ($income_line == "car_loading") {
									include 'payments/car_loading_form_inc.php';
								} elseif ($income_line == "car_park") {
									include 'payments/car_park_form_inc.php';
								} elseif ($income_line == "hawkers") {
									include 'payments/hawkers_form_inc.php';
								} elseif ($income_line == "wheelbarrow") {
									include 'payments/wheelbarrow_form_inc.php';
								} elseif ($income_line == "daily_trade") {
									include 'payments/daily_trade_form_inc.php';
								} elseif ($income_line == "toilet_collection") {
									include 'payments/toilet_collection_form_inc.php';
								} elseif ($income_line == "loading") {
									include 'payments/loading_form_inc.php';
								} elseif ($income_line == "overnight_parking") {
									include 'payments/overnight_parking_form_inc.php';
								} elseif ($income_line == "scroll_board") {
									include 'payments/scroll_board_form_inc.php';
								} elseif ($income_line == "daily_trade_arrears") {
									include 'payments/daily_trade_arrears_form_inc.php';
								} elseif ($income_line == "other_pos") {
									include 'payments/other_pos_form_inc.php';
								}  
								else {
									echo "<h3><strong>Please select and income line</strong></h3>";
								}
							} else {
								echo "<h3><strong>Please select an income line</strong></h3>";
							}
						?>

						<?php  ?>
						
						<span><?php include ('controllers/error_messages_inc.php'); ?></span>
					</div>
				</div>
			</div>
		</div>
	</div><!-- col-md-8 -->
	
	
	<div class="col-md-2">
		<div class="row">
			<h3 class="text-right"><strong>Lines of Income</strong></h3>
		</div>
		
		<div class="row">
			<div class="table-responsive">
				<table class="table table-hover">
					<tr class="text-right">
						<td><a href="payments.php?income_line=car_sticker" oncontextmenu="return false;">Car Sticker</a></td>
					</tr>
					<tr class="text-right">
						<td><a href="payments.php?income_line=loading" oncontextmenu="return false;">Loading & Offloading</a></td>
					</tr>
					<tr class="text-right">
						<td><a href="payments.php?income_line=overnight_parking" oncontextmenu="return false;">Overnight Parking</a></td>
					</tr>
				</table>
			</div>
		</div>		
	</div>
</div> <!--container-fluid -->


<script type="text/javascript" src="../../js/jquery-3.1.1.js"></script>
<script type="text/javascript" src="../../js/formValidation.min.js"></script>
<script type="text/javascript" src="../../js/framework/bootstrap.min.js"></script>
<script type='text/javascript' src="../../js/bootstrap-datepicker.min.js"></script>
<script type='text/javascript' src="../../js/fv.js"></script>
<script type="text/javascript" src="../../js/bootstrapValidator.min.js"></script>
<script type="text/javascript" src="../../js/bootstrap.min.js"></script>

<script src="../../js/sub_menu.js"></script>
<script type="text/javascript">
$(document).ready(function() {
	document.getElementById("trans_desc_div").style.display="none";
	document.getElementById("vehicle_div").style.display="none";
	document.getElementById("plate_no_div").style.display="none";	
	document.getElementById("artisan_div").style.display="none";
});
</script>
<script>
  $(document).ready(function(){
	$('#board_name').change(function(){
		var boardName = $(this).val();
		//console.log(boardName);
		$.ajax({
			url:'fetch_scrollboard.php',
			method: 'POST',
			data: { board_name: boardName },
			success: function(response) {
				//console.log(response);
				var data = JSON.parse(response);
				
				if (data.status === "success") {
					$('#expected_rent_monthly').val(data.expected_rent_monthly);
					$('#expected_rent_yearly').val(data.expected_rent_yearly);
					$('#allocated_to').val(data.allocated_to);
				}
			} 
		});
	});
  });
</script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    let startDateInput = document.getElementById("start_date");
    let endDateInput = document.getElementById("end_date");

    startDateInput.addEventListener("change", function() {
        let startDate = new Date(startDateInput.value);
        if (!isNaN(startDate)) {
            // Set min attribute for end date to prevent selection of past dates
            let minEndDate = new Date(startDate);
            minEndDate.setDate(minEndDate.getDate() + 1); // Ensure at least one day difference
            endDateInput.min = minEndDate.toISOString().split("T")[0];
        }
    });

    endDateInput.addEventListener("change", function() {
        let startDate = new Date(startDateInput.value);
        let endDate = new Date(endDateInput.value);
        if (endDate <= startDate) {
            alert("End date must be after start date!");
            endDateInput.value = ""; // Reset invalid input
        }
    });
});
</script>
</body>
</html>