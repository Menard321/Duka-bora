<?php
/**
 * footer.php
 * Application Footer
 *
 * Closes <main> and <body>/<html>.
 * Includes application JavaScript.
 *
 * @package DukaBora
 */
?>
</main><!-- /.page-wrapper -->

<footer class="app-footer" role="contentinfo">
    <div class="footer-container">
        <p>
            &copy; <?= date('Y') ?>
            <strong>Duka Bora</strong> Inventory Management System.
            All rights reserved.
        </p>
        <p class="footer-tagline">
            <i class="fas fa-store"></i> Built with &hearts; for retail excellence.
        </p>
    </div>
</footer>

<!-- Application JavaScript -->
<script src="<?= basePath('js/script.js') ?>"></script>
</body>
</html>
