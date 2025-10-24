
<?php
// Daily Income Period Selection Component
function renderPeriodSelector($selected_month, $selected_year) {
?>
<div class="mb-6 bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900">Period Selection</h2>
        <div class="flex gap-4">
            <button id="copyBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-copy mr-2"></i> Copy
            </button>
            <button id="excelBtn" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                <i class="fas fa-file-excel mr-2"></i> Excel
            </button>
        </div>
    </div>
    
    <form method="GET" class="flex items-center gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Month</label>
            <select name="month" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <?php
                $months = ['January', 'February', 'March', 'April', 'May', 'June', 
                          'July', 'August', 'September', 'October', 'November', 'December'];
                foreach ($months as $month) {
                    $selected = ($selected_month == $month) ? 'selected' : '';
                    echo "<option value=\"{$month}\" {$selected}>{$month}</option>";
                }
                ?>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
            <select name="year" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <?php
                $current_year = date('Y');
                for ($year = $current_year - 2; $year <= $current_year + 1; $year++) {
                    $selected = ($selected_year == $year) ? 'selected' : '';
                    echo "<option value=\"{$year}\" {$selected}>{$year}</option>";
                }
                ?>
            </select>
        </div>
        
        <div class="flex items-end">
            <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                <i class="fas fa-search mr-2"></i> Load
            </button>
        </div>
    </form>
</div>
<?php
}
?>
