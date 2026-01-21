        </main>
        
        <?php 
        // Check if we're on the main index page
        $isIndexPage = (basename($_SERVER['PHP_SELF']) === 'index.php');
        ?>
        
        <?php if ($isIndexPage): ?>
        <footer class="text-center mt-3 mb-2" style="font-size: 12px; color: #aaa;">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($shopName); ?></p>
            <p><a href="pages/admin/login.php">Admin Login</a></p>
        </footer>
        <?php endif; ?>
    </div>

    <!-- Scripts -->
    <script src="assets/js/main.js"></script>
</body>
</html>
