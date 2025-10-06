<?php
ob_start();
session_start();
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'models/User.php';
require_once 'models/Transaction.php';
require_once 'models/Remittance.php'; 
require_once 'models/UnpostedTransaction.php'; 
require_once 'models/Account.php';
require_once 'helpers/session_helper.php';

// Check if user is logged in
requireLogin();

$userId = getLoggedInUserId();
// Initialize objects
$db = new Database();
$user = new User();
$transaction = new Transaction();
$remittance = new Remittance();
$account = new Account();
$otherTransactions = new UnpostedTransaction();

// Get current user information
$currentUser = $user->getUserById($userId);
$department = $user->getDepartmentByUserIdstring($userId);
$currentUserStaffInfo = $user->getUserStaffDetail($userId);

$totalRemitted = $otherTransactions->getRemittanceSummaryForToday($userId);
// print_r($totalRemitted);
// exit;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wealth Creation - Income ERP System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .stats-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95); 
        }
    </style>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    function loadForm(line, department) {
        let extraFields = '';
        let calcScript = '';

        // Load remitting staff dynamically
        fetch(`get_remitters.php?department=${encodeURIComponent(department)}`)
        .then(res => res.json())
        .then(remitters => {
            const select = document.getElementById('remitting_staff');
            select.innerHTML = '<option value="">Select...</option>';
            remitters.forEach(person => {
            const opt = document.createElement('option');
            opt.value = person.value;
            opt.textContent = person.full_name;
            select.appendChild(opt);
            });
        })
        .catch(err => {
            console.error('Failed to load remitters:', err);
            const select = document.getElementById('remitting_staff');
            select.innerHTML = '<option value="">Error loading options</option>';
        });


        const formArea = document.getElementById('formArea');

        //Injected from the functions
        const sessionDept = '<?= $department; ?>';
        const remitId = '<?= $totalRemitted['remit_id']; ?>';
        const unposted = '<?= $totalRemitted['unposted'];  ?>';
        const remittanceDate = '<?= date('Y-m-d') ?>';
        const amtRemitted = '<?= $totalRemitted['remitted'] ?>';

        const remittanceDropdown = `
        <div class="mb-3">
            <label class="block font-medium">Remittances</label>
            <select name="remit_id" id="remit_id" class="w-full p-2 border rounded" required>
            <option value="">Select...</option>
            <option value="${remitId}">${remittanceDate}: Remittance - ₦${unposted}</option>
            </select>
            <input type="hidden" name="amt_remitted" id="amt_remitted" value="${amtRemitted}" />
        </div>
        `;

        if (line === 'Car Loading Ticket') {
            extraFields = `
                <div class="mb-3">
                <label class="block font-medium">Receipt No</label>
                <input type="text" name="receipt_no" placeholder="Receipt No" class="w-full p-2 border rounded" maxlength="7" required />
                </div>
                
                <div class="mb-3">
                <label class="block font-medium">No of Tickets</label>
                <input type="number" name="no_of_tickets" placeholder="No of Ticket" class="w-full p-2 border rounded" onBlur="loadCalc()" required />
                </div>

                <div class="mb-3">
                <label class="block font-medium">Amount Remitted</label>
                <input type="text" id="amount_paid" name="amount_paid" class="w-full p-2 border rounded bg-gray-100" onBlur="loadCalc()" readonly />
                </div>
            `;

            formArea.innerHTML = `
                <div class="bg-white p-6 rounded shadow mt-4 text-sm">
                <h3 class="text-lg font-semibold mb-4 text-blue-700">Post Payment for <span class="text-indigo-600">${line}</span></h3>

                <form method="post" action="post_payment_handler.php">
                    <input type="hidden" name="income_line" value="${line}">
                    <input type="hidden" name="department" value="${department}">

                    ${sessionDept === 'Wealth Creation' ? remittanceDropdown : ''}

                    <!-- Custom fields for Car Loading -->
                    <div class="mb-3">
                    <label class="block font-medium">Transaction Description</label>
                    <input type="text" name="transaction_descr" class="w-full p-2 border rounded bg-gray-100" value="Car Loading Fee Payment" readonly />
                    </div>
                    
                    ${extraFields}
                    
                    <div class="mb-3">
                    <label class="block font-medium">Remitter's Name</label>
                    <select name="remitting_staff" id="remitting_staff" class="w-full p-2 border rounded" required>
                        <option value="">Loading...</option>
                    </select>
                    </div>

                    ${department == 'Accounts' ? `
                    <div>
                    <label class="block font-medium">Debit Account</label>
                    <select name="debit_account" class="w-full p-2 border rounded">
                        <option value="till">Account Till</option>
                        <option value="others">Other</option>
                    </select>
                    </div>
                    <div>
                    <label class="block font-medium">Credit Account (Income Line)</label>
                    <input type="text" name="credit_account" value="${line}" readonly class="w-full p-2 border rounded bg-gray-100">
                    </div>
                    ` : ''}

                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Submit Car Loading Payment
                    </button>
                </form>
                </div>
            `;
            } else {
            // Default generic form
            formArea.innerHTML = `
                <div class="bg-white p-6 rounded shadow mt-4">
                <h3 class="text-lg font-semibold mb-4">Post Payment for <span class="text-blue-600">${line}</span></h3>

                <form action="post_payment_handler.php" method="POST" class="space-y-4">
                    <input type="hidden" name="income_line" value="${line}">

                    <div>
                    <label class="block font-medium">Amount Paid</label>
                    <input type="text" name="amount_paid" required class="w-full p-2 border rounded" placeholder="e.g. 2000.00">
                    </div>

                    ${department == 'Accounts' ? `
                    <div>
                        <label class="block font-medium">Debit Account</label>
                        <select name="debit_account" class="w-full p-2 border rounded">
                        <option value="till">Account Till</option>
                        <option value="others">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-medium">Credit Account (Income Line)</label>
                        <input type="text" name="credit_account" value="${line}" readonly class="w-full p-2 border rounded bg-gray-100">
                    </div>
                    ` : ''}

                    <div>
                    <label class="block font-medium">Date of Payment</label>
                    <input type="date" name="date_of_payment" required class="w-full p-2 border rounded">
                    </div>

                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Submit Payment
                    </button>
                </form>
                </div>
            `;
            }
        }
</script>

</head>
<body class="bg-gray-50 min-h-screen">
<header class="bg-white shadow-sm border-b">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center">
                <div class="flex items-center gap-3">
                    <i class="fas fa-chart-line text-2xl text-blue-600"></i>
                    <span class="text-xl font-bold text-gray-900">WC ERP</span>
                </div>
                <div class="ml-8">
                    <h1 class="text-lg font-semibold text-gray-900">
                        <?php 
                        if (hasDepartment('Accounts')) { echo 'Account Dept. Dashboard'. '<p class="text-sm text-gray-500"> View, Post, Approve & Manage lines of Income</p>';}
                        if (hasDepartment('Wealth Creation')) { echo 'Wealth Creation Dashboard'. '<p class="text-sm text-gray-500"> View, Post, & Manage lines of Income</p>'; }
                        if (hasDepartment('Audit/Inspections')) { echo 'Audit/Inspections Dashboard'. '<p class="text-sm text-gray-500"> View, Approve, & Manage lines of Income</p>'; } 
                        ?>
                    </h1>
                    
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($currentUser['full_name']) ?></div>
                    <div class="text-xs text-gray-500"><?= htmlspecialchars($currentUserStaffInfo['department']) ?></div>
                </div>
                <div class="relative">
                    <button class="flex items-center gap-2 bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-lg transition-colors" onclick="toggleDropdown()">
                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                            <?= strtoupper($currentUser['full_name'][0]) ?>
                        </div>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <div id="userDropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border py-2 hidden z-50">
                        <a href="dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                        </a>
                        <?php if(hasDepartment('Wealth Creation')): ?>
                        <a href="post_collection.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-receipt mr-2"></i> Post Collections
                        </a>
                        <?php endif; ?>
                        <?php if(hasDepartment('Accounts')): ?>
                        <a href="remittance.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-money-bill-wave mr-2"></i> Remittances
                        </a>
                        <?php endif; ?>
                        <?php if(hasDepartment('Audit/Inspections')): ?>
                        <a href="verify_transactions.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-clipboard-check mr-2"></i> Audit Dashboard
                        </a>
                        <?php endif; ?>
                        <div class="border-t my-1"></div>
                        <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
<!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Flash Messages -->
        <?php if (!empty($success_msg)): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <span><?= htmlspecialchars($success_msg) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_msg)): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <span><?= htmlspecialchars($error_msg) ?></span>
            </div>
        <?php endif; ?>

        <h3 class="text-start"><b>Hello <?php echo $currentUser['full_name']; ?> 👋🏼</b> </h3>
        <p>Welcome to your dashboard! Your dashboard is peculiar to your <?php echo $userDepartment; ?> Department. Please always logout of your account for security reasons. </p>
        <p><?php include ('countdown_script.php'); ?></p>

        <div class="flex items-center justify-between bg-white p-4 rounded shadow mb-6">
          <div class="text-lg font-semibold text-gray-700">
          Remitted: <span class="text-lg text-green-600"><?= formatCurrency($totalRemitted['remitted']) ?> </span>   |   Posted: <span class="text-lg text-red-600"><?= formatCurrency($totalRemitted['posted']) ?></span>  |  Unposted: <span class="text-lg text-red-600"><?= formatCurrency($totalRemitted['unposted']) ?></span>
          
          </div>
          <div class="text-sm text-gray-500">
            <?php 
              $current_time = date('Y-m-d H:i:s');

              // Define daily time window
              $today = date('Y-m-d');
              $wc_begin_time = $today . ' 00:00:00';
              $wc_end_time   = $today . ' 20:30:00';

              if ($current_time >= $wc_begin_time && $current_time <= $wc_end_time) {
                  echo '<a href="log_unposted_trans_others.php" class="inline-block bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-blue-700 transition">Log Unposted Collection</a>';
              }
          ?>
          </div>
        </div>

        <div class="max-w-6xl mx-auto p-6 bg-gray-50 rounded-xl shadow-md">
          <!-- Header -->
          <h2 class="text-2xl font-bold text-gray-800 mb-6">Lines of Income</h2>

          <div class="flex flex-col lg:flex-row gap-6">
            
            <!-- Income Lines (now stacked vertically) -->
            <div class="w-full lg:w-1/3 bg-white rounded-xl shadow p-4">
              <h3 class="text-lg font-semibold text-gray-700 mb-4">Select a Line</h3>

              <div class="flex flex-col space-y-3">
                <?php
                $incomeLines = [
                    ['name' => 'General', 'icon' => 'M4 4h16v4H4z M4 12h16v8H4z'],
                    ['name' => 'Abattoir', 'icon' => 'M3 10h18M3 14h18'], 
                    ['name' => 'Car Loading Ticket', 'icon' => 'M4 6h16M4 12h16M4 18h16'],
                    ['name' => 'Car Park Ticket', 'icon' => 'M4 6h16M4 12h16M4 18h16'],
                    ['name' => 'Hawkers Ticket', 'icon' => 'M4 6h16M4 12h16M4 18h16'],
                    ['name' => 'WheelBarrow Ticket', 'icon' => 'M4 6h16M4 12h16M4 18h16'],
                    ['name' => 'Daily Trade', 'icon' => 'M4 6h16M4 12h16M4 18h16'],
                    ['name' => 'Toilet Collection', 'icon' => 'M4 6h16M4 12h16M4 18h16'],
                    ['name' => 'Scroll Board', 'icon' => 'M4 6h16M4 12h16M4 18h16'],
                    ['name' => 'Other POS Ticket', 'icon' => 'M4 6h16M4 12h16M4 18h16'],
                    ['name' => 'Daily Trade Arrears', 'icon' => 'M4 6h16M4 12h16M4 18h16'],
                ];

                foreach ($incomeLines as $line) {
                    $lineName = $line['name'];
                    $svgPath = $line['icon'];
                    echo "
                    <a type='button' onclick=\"loadForm('$lineName', '$department')\" class='flex items-center bg-indigo-50 border border-indigo-200 rounded-lg px-4 py-2 hover:shadow-sm transition'>
                        <svg xmlns='http://www.w3.org/2000/svg' class='w-5 h-5 text-indigo-600 mr-3' fill='none' viewBox='0 0 24 24' stroke='currentColor'>
                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='$svgPath' />
                        </svg>
                        <span class='text-sm font-medium text-gray-800'>$lineName</span>
                    </a>";
                }
                ?>
              </div>
            </div>
            
            <!-- Dynamic Form Section -->
            <div class="w-full lg:w-2/3" id="formArea">
              <div class="bg-white p-6 rounded-xl shadow text-center text-gray-400">
                <p class="text-lg">Please select an income line to begin</p>
              </div>
            </div>
          </div>
      </div>

    </main>

<script>
function loadCalc() {
    const ticketsInput = document.getElementById('no_of_tickets');
    const incomeLine = document.querySelector("input[name='income_line']");
    if (!ticketsInput || !incomeLine) return;

    const no_of_ticket = parseFloat(document.getElementById(ticketsInput).value) || 0;//isNaN(parseFloat(ticketsInput.value))? 0 : parseFloat(ticketsInput.value);
    
    console.log(no_of_ticket);
    
    // Run only for relevant income lines
    if (incomeLine === 'Car Loading Ticket') {
        unitPrice = 1000;
    } else if (incomeLine === 'Hawker Tickets') {
        unitPrice = 300;
    } else {
        return; // Don't proceed for other income lines
    }

    const total_ticket_amount = no_of_ticket * unitPrice;
    console.log(total_ticket_amount);

    document.getElementById('amount_paid').value = total_ticket_amount;
}
</script>
</body>
</html>