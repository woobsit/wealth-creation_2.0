
<?php
// Daily Income Analysis Table Component
function renderAnalysisTable($daily_analysis, $daily_totals, $grand_total, $days_in_month, $sundays) {
?>
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Daily Income Line Analysis</h3>
    </div>
    
    <div class="table-container">
        <table id="dailyAnalysisTable" class="min-w-full">
            <thead class="sticky-header bg-gray-50">
                <tr>
                    <th class="sticky-column px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Income Line
                    </th>
                    <?php for($day = 1; $day <= $days_in_month; $day++): ?>
                        <?php 
                        $is_sunday = isSunday($day, $sundays);
                        $header_class = $is_sunday ? 'sunday-header' : 'bg-gray-50';
                        ?>
                        <th class="day-header px-2 py-3 text-center text-xs font-medium uppercase tracking-wider <?= $header_class ?>">
                            <?= $is_sunday ? 'Sun' : 'Day' ?><br><?= str_pad($day, 2, '0', STR_PAD_LEFT) ?>
                        </th>
                    <?php endfor; ?>
                    <th class="total-column px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-100">
                        Monthly Total
                    </th>
                </tr>
            </thead>
            
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach($daily_analysis as $line): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="sticky-column income-line-cell px-6 py-4 text-sm font-medium text-gray-900" title="<?= htmlspecialchars($line['income_line']) ?>">
                            <?= htmlspecialchars($line['income_line']) ?>
                        </td>
                        
                        <?php for($day = 1; $day <= $days_in_month; $day++): ?>
                            <?php 
                            $is_sunday = isSunday($day, $sundays);
                            $cell_class = $is_sunday ? 'sunday-cell' : '';
                            $amount = isset($line['days'][$day]) ? $line['days'][$day] : 0;
                            ?>
                            <td class="amount-cell px-2 py-4 text-xs text-gray-700 <?= $cell_class ?>">
                                <?= $amount > 0 ? number_format($amount, 0) : '' ?>
                            </td>
                        <?php endfor; ?>
                        
                        <td class="total-column amount-cell px-4 py-4 text-sm font-bold text-green-600 bg-gray-50">
                            <?= number_format($line['total'], 0) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            
            <tfoot class="total-row bg-gray-100 sticky bottom-0">
                <tr>
                    <th class="sticky-column px-6 py-4 text-left text-sm font-bold text-gray-900 bg-gray-100">
                        DAILY TOTAL
                    </th>
                    <?php for($day = 1; $day <= $days_in_month; $day++): ?>
                        <?php 
                        $is_sunday = isSunday($day, $sundays);
                        $cell_class = $is_sunday ? 'bg-red-100 text-red-800' : 'bg-gray-100';
                        ?>
                        <th class="amount-cell px-2 py-4 text-xs font-bold <?= $cell_class ?>">
                            <?= number_format($daily_totals[$day], 0) ?>
                        </th>
                    <?php endfor; ?>
                    <th class="amount-cell px-4 py-4 text-sm font-bold text-green-800 bg-green-100">
                        <?= number_format($grand_total, 0) ?>
                    </th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php
}
?>
