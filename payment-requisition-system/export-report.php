<?php
require_once 'config/config.php';
requireLogin();

// Check if user has access to reports
$allowedDepartments = ['IT', 'Accounts', 'Audit'];
if (!in_array($_SESSION['department'], $allowedDepartments) && $_SESSION['level'] < 4) {
    redirect('index.php');
}

$reports = new Reports();

$export = isset($_GET['export']) ? $_GET['export'] : '';
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$department = isset($_GET['department']) ? $_GET['department'] : '';


if ($export === 'pdf') {
    // Export as PDF
    require_once 'vendor/TCPDF/tcpdf.php';
    
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor(APP_NAME);
    $pdf->SetTitle('Requisition Report');
    $pdf->SetSubject('Monthly Requisition Report');
    
    $pdf->AddPage();
    
    $html = generateReportHTML($reports, $month, $year, $department);
    $pdf->writeHTML($html, true, false, true, false, '');
    
    $filename = "requisition_report_{$year}_{$month}.pdf";
    $pdf->Output($filename, 'D');
    
} elseif ($export === 'excel') {
    // Export as Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="requisition_report_' . $year . '_' . $month . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo generateExcelReport($reports, $month, $year, $department);
} else {
    redirect('reports.php');
}

function generateReportHTML($reports, $month, $year, $department) {
    $monthlyStats = $reports->getMonthlyStats($month, $year, $department);
    $yearlyStats = $reports->getYearlyStats($year, $department);
    $retirementStats = $reports->getRetirementStats($month, $year, $department);
    $departmentBreakdown = $reports->getDepartmentBreakdown($month, $year);
    $detailedReport = $reports->getDetailedReport($month, $year, $department);

    $monthName = date('F', mktime(0, 0, 0, $month, 1));

    $html = "
    <h1>" . APP_NAME . "</h1>
    <h2>Requisition Report - {$monthName} {$year}</h2>

    <h3>Summary Statistics</h3>
    <table border='1' cellpadding='5'>
        <tr>
            <td><strong>Monthly Expenses:</strong></td>
            <td>" . formatCurrency(isset($monthlyStats['total_amount']) ? $monthlyStats['total_amount'] : 0) . "</td>
        </tr>
        <tr>
            <td><strong>Yearly Expenses:</strong></td>
            <td>" . formatCurrency(isset($yearlyStats['total_amount']) ? $yearlyStats['total_amount'] : 0) . "</td>
        </tr>
        <tr>
            <td><strong>Total Retired:</strong></td>
            <td>" . formatCurrency(isset($retirementStats['total_retired']) ? $retirementStats['total_retired'] : 0) . "</td>
        </tr>
        <tr>
            <td><strong>Amount Returned:</strong></td>
            <td>" . formatCurrency(isset($retirementStats['total_returned']) ? $retirementStats['total_returned'] : 0) . "</td>
        </tr>
    </table>

    <h3>Department Breakdown</h3>
    <table border='1' cellpadding='5'>
        <tr>
            <th>Department</th>
            <th>Count</th>
            <th>Amount</th>
        </tr>";

    foreach ($departmentBreakdown as $dept) {
        $html .= "
        <tr>
            <td>{$dept['department']}</td>
            <td>{$dept['count']}</td>
            <td>" . formatCurrency($dept['total_amount']) . "</td>
        </tr>";
    }

    $html .= "
    </table>

    <h3>Detailed Requisitions</h3>
    <table border='1' cellpadding='5'>
        <tr>
            <th>Reference</th>
            <th>Title</th>
            <th>Amount</th>
            <th>Department</th>
            <th>Status</th>
            <th>Retired</th>
            <th>Returned</th>
            <th>Date</th>
        </tr>";

    foreach ($detailedReport as $req) {
        $html .= "
        <tr>
            <td>{$req['reference_number']}</td>
            <td>{$req['title']}</td>
            <td>" . formatCurrency($req['amount'], $req['currency']) . "</td>
            <td>{$req['department']}</td>
            <td>" . ucfirst($req['status']) . "</td>
            <td>" . formatCurrency($req['total_retired']) . "</td>
            <td>" . formatCurrency($req['total_returned']) . "</td>
            <td>" . date('M j, Y', strtotime($req['created_at'])) . "</td>
        </tr>";
    }

    $html .= "</table>";

    return $html;
}


function generateExcelReport($reports, $month, $year, $department) {
    $monthlyStats = $reports->getMonthlyStats($month, $year, $department);
    $yearlyStats = $reports->getYearlyStats($year, $department);
    $retirementStats = $reports->getRetirementStats($month, $year, $department);
    $departmentBreakdown = $reports->getDepartmentBreakdown($month, $year);
    $detailedReport = $reports->getDetailedReport($month, $year, $department);
    
    $monthName = date('F', mktime(0, 0, 0, $month, 1));
    
    $output = APP_NAME . " - Requisition Report\n";
    $output .= "{$monthName} {$year}\n\n";
    
    $output .= "SUMMARY STATISTICS\n";
    $output .= "Monthly Expenses\t" . (isset($monthlyStats['total_amount']) ? $monthlyStats['total_amount'] : 0) . "\n";
    $output .= "Yearly Expenses\t" . (isset($yearlyStats['total_amount']) ? $yearlyStats['total_amount'] : 0) . "\n";
    $output .= "Total Retired\t" . (isset($retirementStats['total_retired']) ? $retirementStats['total_retired'] : 0) . "\n";
    $output .= "Amount Returned\t" . (isset($retirementStats['total_returned']) ? $retirementStats['total_returned'] : 0) . "\n\n";
    
    $output .= "DEPARTMENT BREAKDOWN\n";
    $output .= "Department\tCount\tAmount\n";
    foreach ($departmentBreakdown as $dept) {
        $output .= "{$dept['department']}\t{$dept['count']}\t{$dept['total_amount']}\n";
    }
    
    $output .= "\nDETAILED REQUISITIONS\n";
    $output .= "Reference\tTitle\tAmount\tCurrency\tDepartment\tStatus\tRetired\tReturned\tDate\n";
    foreach ($detailedReport as $req) {
        $output .= "{$req['reference_number']}\t{$req['title']}\t{$req['amount']}\t{$req['currency']}\t{$req['department']}\t{$req['status']}\t{$req['total_retired']}\t{$req['total_returned']}\t" . date('M j, Y', strtotime($req['created_at'])) . "\n";
    }
    
    return $output;
}
?>