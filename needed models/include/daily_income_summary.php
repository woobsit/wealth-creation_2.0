
<?php
// Daily Income Summary Statistics Component
function renderSummaryStats($selected_month, $selected_year, $daily_analysis, $grand_total, $days_in_month) {
?>
<div class="mb-6 bg-white rounded-lg shadow p-6">
    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
        <h3 class="text-lg font-semibold text-red-800">
            <i class="fas fa-calendar-alt mr-2"></i>
            This Month: <strong><?= $selected_month . ' ' . $selected_year ?></strong> Collection Summary as at <?= date('Y-m-d') ?>
        </h3>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-blue-50 p-4 rounded-lg">
            <div class="text-sm text-blue-600 font-medium">Total Income Lines</div>
            <div class="text-2xl font-bold text-blue-900"><?= count($daily_analysis) ?></div>
        </div>
        <div class="bg-green-50 p-4 rounded-lg">
            <div class="text-sm text-green-600 font-medium">Grand Total</div>
            <div class="text-2xl font-bold text-green-900"><?= formatCurrency($grand_total) ?></div>
        </div>
        <div class="bg-purple-50 p-4 rounded-lg">
            <div class="text-sm text-purple-600 font-medium">Average Daily</div>
            <div class="text-2xl font-bold text-purple-900"><?= formatCurrency($grand_total / $days_in_month) ?></div>
        </div>
        <div class="bg-orange-50 p-4 rounded-lg">
            <div class="text-sm text-orange-600 font-medium">Days in Month</div>
            <div class="text-2xl font-bold text-orange-900"><?= $days_in_month ?></div>
        </div>
    </div>
</div>
<?php
}
?>
