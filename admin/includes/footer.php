        </main>
        <?php
        $renderAdminFooter = $renderAdminFooter ?? true;
        if ($renderAdminFooter):
        ?>
        <footer class="bg-dark text-white mt-auto py-3">
            <div class="container-fluid d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
                <span>&copy; <?php echo date('Y'); ?> Motoshapi Admin Panel</span>
                <span class="text-white-50 small">Performance dashboard</span>
            </div>
        </footer>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dark-mode.js"></script>
</body>
</html>
