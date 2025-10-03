 <script>
        // Initialize DataTable
        $(document).ready(function() {
            $('#transactionsTable').DataTable({
                pageLength: 25,
                responsive: true,
                order: [[1, 'desc']],
                columnDefs: [
                    { orderable: false, targets: [7] }
                ]
            });
        });

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

        // Refresh page
        function refreshPage() {
            window.location.reload();
        }

        // Auto-hide flash messages after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('[class*="bg-green-50"], [class*="bg-red-50"]');
            alerts.forEach(alert => {
                if (alert.textContent.includes('successfully') || alert.textContent.includes('Error')) {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }
            });
        }, 5000);
    </script>

    <script>
        function toggleDropdown(id) {
            document.querySelectorAll('.absolute').forEach(el => {
                if (el.id !== id) el.classList.add('hidden');
            });
            const dropdown = document.getElementById(id);
            if (dropdown) dropdown.classList.toggle('hidden');
        }

        document.addEventListener('click', function (e) {
            const buttons = ['transactionDropdown', 'reportDropdown', 'userDropdown'];
            const clickedInsideDropdown = buttons.some(id => {
                const dropdown = document.getElementById(id);
                return dropdown && dropdown.contains(e.target);
            });

            const clickedOnButton = e.target.closest('button');
            if (!clickedInsideDropdown && !clickedOnButton) {
                buttons.forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.classList.add('hidden');
                });
            }
        });
    </script>