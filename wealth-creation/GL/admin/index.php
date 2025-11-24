<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Wealth-Creation | Accounting System</title>
<script src="https://cdn.tailwindcss.com"></script>

<style>
    /* Smooth subtle shadows */
    .soft-shadow { box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
</style>
</head>

<body class="bg-gray-100">

<!-- MAIN WRAPPER -->
<div class="flex h-screen">

    <!-- SIDEBAR (Smaller, Professional, SAGE-Style) -->
    <aside class="w-56 bg-[#F4F7FA] border-r border-gray-300 text-gray-700 flex flex-col soft-shadow">

        <div class="p-5 text-xl font-semibold tracking-wide border-b border-gray-300 text-gray-800">
            WC-GL
        </div>

        <nav class="flex-1 p-4 space-y-1 text-sm">

            <!-- Reusable link classes -->
            <?php 
                function active($key){
                    return (isset($_GET['action']) && $_GET['action']==$key)
                        ? "bg-[#D8E7FF] text-[#004AAD] font-semibold"
                        : "hover:bg-gray-200";
                }
            ?>

            <a href="?action=gl" 
               class="block px-3 py-2 rounded-md transition <?= active('gl') ?>">
                General Ledger
            </a>

            <a href="?action=trial-balance" 
               class="block px-3 py-2 rounded-md transition <?= active('trial-balance') ?>">
                Trial Balance
            </a>

            <a href="?action=ifrs" 
               class="block px-3 py-2 rounded-md transition <?= active('ifrs') ?>">
                IFRS Financials
            </a>

            <a href="?action=closing" 
               class="block px-3 py-2 rounded-md transition <?= active('closing') ?>">
                Closing & Opening
            </a>

            <a href="?action=posting-demo" 
               class="block px-3 py-2 rounded-md transition <?= active('posting-demo') ?>">
                Transaction Posting
            </a>

        </nav>
    </aside>

    <!-- CONTENT AREA -->
    <main class="flex-1 p-2 overflow-y-auto">
        <!-- <div class="bg-white rounded-xl soft-shadow p-6 min-h-[85vh]"> -->
            <!-- YOUR PAGE CONTENT -->
            <?= $content ?>
        <!-- </div> -->
    </main>

</div>

</body>
</html>
