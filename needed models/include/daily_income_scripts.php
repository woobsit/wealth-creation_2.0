
<?php
// Daily Income Analysis JavaScript Component
function renderScripts($selected_month, $selected_year) {
?>
<script>
    // Toggle dropdown
    function toggleDropdown() {
        const dropdown = document.getElementById('userDropdown');
        dropdown.classList.toggle('hidden');
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('userDropdown');
        const button = event.target.closest('button');
        
        if (!button || !button.onclick) {
            dropdown.classList.add('hidden');
        }
    });

    $(document).ready(function() {
        // Initialize DataTable with export functionality
        var table = $('#dailyAnalysisTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'copyHtml5',
                    footer: true,
                    text: 'Copy Data',
                    className: 'hidden-button'
                },
                {
                    extend: 'excelHtml5',
                    footer: true,
                    text: 'Export to Excel',
                    title: 'Daily Income Analysis - <?= $selected_month . " " . $selected_year ?>',
                    className: 'hidden-button'
                }
            ],
            paging: false,
            searching: true,
            ordering: true,
            info: false,
            scrollX: true,
            scrollY: '60vh',
            scrollCollapse: true,
            fixedColumns: {
                leftColumns: 1
            }
        });
        
        // Connect custom buttons to DataTables buttons
        $('#copyBtn').on('click', function() {
            $('.buttons-copy').click();
        });
        
        $('#excelBtn').on('click', function() {
            $('.buttons-excel').click();
        });
        
        // Hide DataTables buttons
        $('.hidden-button').hide();
    });
</script>
<?php
}
?>
