        </main>
        <?php
        $renderGlobalFooter = $renderGlobalFooter ?? true;
        if ($renderGlobalFooter):
        ?>
        <footer class="bg-dark text-white mt-auto py-3">
            <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
                <div>&copy; <?php echo date('Y'); ?> Motoshapi. All rights reserved.</div>
                <div class="d-flex gap-3">
                    <a class="link-light text-decoration-none" href="products.php">Shop</a>
                    <a class="link-light text-decoration-none" href="profile.php">Account</a>
                </div>
            </div>
        </footer>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dark-mode.js"></script>
</body>
</html>