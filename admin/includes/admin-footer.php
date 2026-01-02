<?php
// admin/includes/admin-footer.php
?>
            </div> <!-- Close container-fluid -->
        </div> <!-- Close content -->
    </div> <!-- Close wrapper -->
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
    // Initialize DataTables on tables with class 'data-table'
    $(document).ready(function() {
        $('.data-table').DataTable({
            "pageLength": 25,
            "order": [[0, "desc"]],
            "language": {
                "search": "Search:",
                "lengthMenu": "Show _MENU_ entries",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "paginate": {
                    "first": "First",
                    "last": "Last",
                    "next": "Next",
                    "previous": "Previous"
                }
            }
        });
    });
    
    // Toggle sidebar on mobile
    $('#sidebarCollapse').on('click', function() {
        $('#sidebar').toggleClass('active');
    });
    
    // Confirm actions
    $(document).on('click', '.confirm-action', function(e) {
        if (!confirm('Are you sure you want to perform this action?')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Show loading spinner on form submissions
    $(document).on('submit', 'form', function() {
        $(this).find('button[type="submit"]').prop('disabled', true).html('<i class="bi bi-hourglass-split me-2"></i> Processing...');
    });
    </script>
</body>
</html>