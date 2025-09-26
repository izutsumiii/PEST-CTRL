    </main>
    
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>PEST-CTRL</h3>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            
            
            <div class="footer-section">
                <h4>Contact Info</h4>
                <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
                <p><i class="fas fa-envelope"></i> info@pestctrl.com</p>
                <p><i class="fas fa-map-marker-alt"></i> 123 Pest Control St, City, State 12345</p>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> PEST-CTRL. All rights reserved.</p>
            <button onclick="scrollToTop()" class="scroll-top-btn">
                <i class="fas fa-arrow-up"></i> Back to Top
            </button>
        </div>
    </footer>
</body>
</html>
<script>
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}
</script>