
<?php
// Daily Income Analysis Styles Component
function renderStyles() {
?>
<style>
    .sunday-header {
        background-color: #ef4444 !important;
        color: white !important;
    }
    .sunday-cell {
        background-color: #fef2f2 !important;
    }
    .day-header {
        writing-mode: vertical-lr;
        text-orientation: mixed;
        min-width: 40px;
        font-size: 11px;
    }
    .income-line-cell {
        min-width: 150px;
        max-width: 200px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .amount-cell {
        text-align: right;
        font-family: 'Courier New', monospace;
        font-size: 12px;
    }
    .table-container {
        max-height: 70vh;
        overflow: auto;
    }
    .sticky-header {
        position: sticky;
        top: 0;
        z-index: 10;
        background: white;
    }
    .sticky-column {
        position: sticky;
        left: 0;
        background: white;
        z-index: 5;
        border-right: 2px solid #e5e7eb;
    }
    .total-row {
        background-color: #f3f4f6 !important;
        font-weight: bold;
    }
    .total-column {
        background-color: #f9fafb !important;
        font-weight: bold;
    }
</style>
<?php
}
?>
