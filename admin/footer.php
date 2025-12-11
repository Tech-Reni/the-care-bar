        </div> <!-- page-content -->
    </div> <!-- main -->

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }
 
        // Close sidebar on link click
        document.querySelectorAll('.sb-menu a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 768) {
                    document.getElementById('sidebar').classList.remove('open');
                }
            });
        });
    </script>

</body>
</html>