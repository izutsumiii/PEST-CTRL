<?php 
require_once 'includes/header.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
// this is index.php
// Get featured products
$stmt = $pdo->prepare("SELECT 
                          p.id,
                          p.name,
                          p.price,
                          p.image_url,
                          p.stock_quantity,
                          COALESCE(AVG(r.rating), 0) as rating,
                          COUNT(DISTINCT r.id) as review_count,
                          COALESCE(SUM(oi.quantity), 0) as total_sold
                      FROM products p
                      LEFT JOIN reviews r ON p.id = r.product_id
                      LEFT JOIN order_items oi ON p.id = oi.product_id
                      LEFT JOIN orders o ON oi.order_id = o.id
                      WHERE p.status = 'active' 
                      AND (o.status NOT IN ('cancelled', 'refunded') OR o.status IS NULL)
                      GROUP BY p.id, p.name, p.price, p.image_url, p.stock_quantity
                      ORDER BY total_sold DESC, p.created_at DESC
                      LIMIT 5");
$stmt->execute();
$featuredProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Load categories for shop-by sections
$stmtCats = $pdo->prepare("SELECT id, name, parent_id FROM categories");
$stmtCats->execute();
$allCategories = $stmtCats->fetchAll(PDO::FETCH_ASSOC);
// Build lookup map by cleaned lowercase name
$categoryNameToId = [];
// Also build helpers for hierarchy
$idToCategory = [];
$childrenByParent = [];
foreach ($allCategories as $cat) {
    $name = (string)$cat['name'];
    $clean = preg_replace('/^[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}\x{200D}\s]+/u', '', $name);
    $clean = html_entity_decode($clean, ENT_QUOTES, 'UTF-8');
    $key = strtolower(trim($clean));
    if ($key !== '') $categoryNameToId[$key] = (int)$cat['id'];
    $cat['clean_name'] = $clean;
    $idToCategory[(int)$cat['id']] = $cat;
    $parentId = isset($cat['parent_id']) && $cat['parent_id'] ? (int)$cat['parent_id'] : 0;
    if (!isset($childrenByParent[$parentId])) $childrenByParent[$parentId] = [];
    $childrenByParent[$parentId][] = $cat;
}
function categoryLinkFor($labels, $fallbackSearch = '') {
    global $categoryNameToId;
    $labels = (array)$labels;
    $matchedIds = [];
    foreach ($labels as $label) {
        $key = strtolower(trim($label));
        if (isset($categoryNameToId[$key])) {
            $matchedIds[] = (int)$categoryNameToId[$key];
        }
    }
    if (!empty($matchedIds)) {
        $query = http_build_query(['categories' => $matchedIds]);
        return 'products.php?' . $query;
    }
    if ($fallbackSearch !== '') {
        return 'products.php?search=' . urlencode($fallbackSearch);
    }
    return 'products.php';
}
// Helpers to fetch children by clean parent name
function getCategoryIdByName($name) {
    global $categoryNameToId;
    $key = strtolower(trim($name));
    return isset($categoryNameToId[$key]) ? (int)$categoryNameToId[$key] : 0;
}
function getChildrenByParentId($parentId) {
    global $childrenByParent;
    return isset($childrenByParent[$parentId]) ? $childrenByParent[$parentId] : [];
}
// Icon mapping for pest subcategories
function iconForCategory($name) {
    $n = strtolower(trim($name));
    $map = [
        'ants' => 'fa-ants',
        'bed' => 'fa-bed',
        'roach' => 'fa-bug',
        'cockroach' => 'fa-bug',
        'mosquito' => 'fa-mosquito',
        'fly' => 'fa-wind',
        'tick' => 'fa-shield-virus',
        'mite' => 'fa-shield-virus',
        'termite' => 'fa-house-chimney-crack',
        'mouse' => 'fa-shield-mouse-pointer',
        'mice' => 'fa-shield-mouse-pointer',
        'rat' => 'fa-shield-mouse-pointer',
        'rodent' => 'fa-shield-mouse-pointer',
        'bee' => 'fa-hammer',
        'wasp' => 'fa-shield-halved',
        'hornet' => 'fa-shield-halved',
        'spider' => 'fa-spider',
        'flea' => 'fa-shield-cat',
        'lizard' => 'fa-hand',
        'snake' => 'fa-staff-snake',
        'bird' => 'fa-dove'
    ];
    foreach ($map as $key => $icon) {
        if (strpos($n, $key) !== false) return $icon;
    }
    return 'fa-shield-alt';
}
?>

<?php
// Helper to resolve product image path robustly
function resolveProductImageUrl($url) {
    $url = trim((string)$url);
    if ($url === '') return '';
    if (preg_match('#^https?://#i', $url)) return $url;
    if (strpos($url, 'assets/') === 0) return str_replace(' ', '%20', $url);
    // Assume bare filename stored, serve from uploads
    return 'assets/uploads/' . str_replace(' ', '%20', $url);
}
?>

<!-- Hero Slideshow Section -->
<section class="hero-slideshow">
    <div class="container">
        <div class="slideshow-container">
            <!-- Slide 1 -->
            <div class="slide active">
                <img src="assets/uploads/1759552181_Imidart.jpg" alt="Pest Control Products">
                <div class="slide-content">
                    <a href="products.php" class="shop-now-btn">Shop Now</a>
                </div>
            </div>
            
            <!-- Slide 2 -->
            <div class="slide">
                <img src="assets/uploads/1759501037_INSECTICIDES-Mahishmati.jpg" alt="Insecticides">
                <div class="slide-content">
                    <a href="products.php" class="shop-now-btn">Shop Now</a>
                </div>
            </div>
            
            <!-- Slide 3 -->
            <div class="slide">
                <img src="assets/uploads/1759552123_mouse trap.jpg" alt="Mouse Traps">
                <div class="slide-content">
                    <a href="products.php" class="shop-now-btn">Shop Now</a>
                </div>
            </div>
            
            <!-- Slide 4 -->
            <div class="slide">
                <img src="assets/uploads/1759552310_304-Advion-Roach-Bait-Gel-Syngenta.jpg.thumb_450x450.jpg" alt="Roach Control">
                <div class="slide-content">
                    <a href="products.php" class="shop-now-btn">Shop Now</a>
                </div>
            </div>
            
            <!-- Slide 5 -->
            <div class="slide">
                <img src="assets/uploads/1759552519_391-Tempo-1pc-Dust-envu.jpg.thumb_450x450.jpg" alt="Pest Control Dust">
                <div class="slide-content">
                    <a href="products.php" class="shop-now-btn">Shop Now</a>
                </div>
            </div>
            
            <!-- Navigation arrows -->
            <button class="prev" onclick="changeSlide(-1)">❮</button>
            <button class="next" onclick="changeSlide(1)">❯</button>
            
            <!-- Dots indicator -->
            <div class="dots-container">
                <span class="dot active" onclick="currentSlide(1)"></span>
                <span class="dot" onclick="currentSlide(2)"></span>
                <span class="dot" onclick="currentSlide(3)"></span>
                <span class="dot" onclick="currentSlide(4)"></span>
                <span class="dot" onclick="currentSlide(5)"></span>
            </div>
        </div>
    </div>
</section>

<!-- Key Benefits Section -->
<style>
.benefits-section { padding: 30px 0; }
.benefits-section .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.benefits-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; align-items: stretch; }
.benefit { background: #ffffff; border: 1px solid #eeeeee; border-radius: 10px; padding: 20px; text-align: center; }
.benefit .icon { font-size: 36px; color: #000000; filter: grayscale(100%); margin-bottom: 10px; }
.benefit h3 { text-transform: uppercase; color: #130325; font-weight: 800; font-size: 16px; margin: 10px 0 6px; }
.benefit p { color: #130325; font-size: 14px; margin: 0; }
@media (max-width: 768px) { .benefits-section { padding: 20px 0; } .benefit { padding: 16px; } }
</style>

<section class="benefits-section">
    <div class="container">
        <div class="benefits-grid">
            <div class="benefit">
                <div class="icon"><i class="fas fa-shield-alt" aria-hidden="true"></i></div>
                <h3>Complete Pest Solutions</h3>
                <p>Products for every pest, every time.</p>
            </div>
            <div class="benefit">
                <div class="icon"><i class="fas fa-book-open" aria-hidden="true"></i></div>
                <h3>Expert Advice</h3>
                <p>Trusted guides written by professionals.</p>
            </div>
            <div class="benefit">
                <div class="icon"><i class="fas fa-toolbox" aria-hidden="true"></i></div>
                <h3>Pro-Grade Products</h3>
                <p>Professional quality, delivered to your door.</p>
            </div>
            <div class="benefit">
                <div class="icon"><i class="fas fa-rotate-left" aria-hidden="true"></i></div>
                <h3>Hassle-Free Returns</h3>
                <p>Easy, worry-free shopping guaranteed.</p>
            </div>
        </div>
    </div>
    
</section>

<section class="featured-products">
    <div class="container">
        <h2 style="margin-top: 60px; margin-bottom: 40px; color: #F9F9F9; text-transform: uppercase;">Featured Products</h2>
    <div class="products-grid">
        <?php 
        $isLoggedIn = isset($_SESSION['user_id']);
        foreach ($featuredProducts as $product): 
            $featImg = resolveProductImageUrl($product['image_url'] ?? '');
        ?>
            <div class="product-card">
                <div class="product-checkbox">
                    <input type="checkbox"
                           class="compare-checkbox"
                           data-product-id="<?php echo $product['id']; ?>"
                           data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                           data-product-price="<?php echo $product['price']; ?>"
                           data-product-image="<?php echo htmlspecialchars($featImg); ?>"
                           data-product-rating="<?php echo $product['rating']; ?>"
                           data-product-reviews="<?php echo $product['review_count']; ?>"
                           onchange="toggleCompare(this)">
                    <label>Compare</label>
                </div>
                <img loading="lazy" src="<?php echo htmlspecialchars($featImg); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                <p class="price">₱<?php echo number_format($product['price'], 2); ?></p>
                <div class="rating">
                    <?php
                        $rating = $product['rating'] ?? 0;
                        $fullStars = floor($rating);
                        $hasHalfStar = ($rating - $fullStars) >= 0.5;
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $fullStars) {
                                echo '★';
                            } elseif ($i == $fullStars + 1 && $hasHalfStar) {
                                echo '☆';
                            } else {
                                echo '☆';
                            }
                        }
                    ?>
                    <span class="rating-text">(<?php echo number_format($product['rating'] ?? 0, 1); ?>) - <?php echo $product['review_count'] ?? 0; ?> reviews</span>
                </div>
                <div class="stock"><?php echo $product['stock_quantity'] ?? 0; ?> in stock</div>
                <div class="product-actions">
                    <a href="product-detail.php?id=<?php echo $product['id']; ?>" 
                       class="btn btn-details">View Details</a>
                    <button onclick="handleAddToCart(<?php echo $product['id']; ?>, 1)" 
                            class="btn btn-cart" 
                            data-product-id="<?php echo $product['id']; ?>">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                    
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    </div>
</section>

<!-- About PEST-CTRL Section -->
<style>
.about-pestctrl { padding: 50px 0 70px; }
.about-pestctrl .container { max-width: 1100px; margin: 0 auto; padding: 0 20px; }
.about-pestctrl h2 { text-transform: uppercase; color: #FFD736; font-weight: 800; margin-bottom: 12px; font-size: 20px; }
.about-pestctrl h3 { color: #FFD736; font-weight: 700; margin: 16px 0 8px; font-size: 16px; }
.about-pestctrl p { color: #ffffff; line-height: 1.7; margin: 0 0 12px 0; font-size: 13px; }
.about-toggle { color: #ffffff; font-weight: 700; cursor: pointer; display: inline-block; margin-top: 8px; }
.about-spacer { height: 30px; }
</style>

<section class="about-pestctrl">
  <div class="container">
    <h2>Pest Control & Lawn Care</h2>
    <p><strong>PEST-CTRL</strong> helps homeowners and businesses solve pest, lawn, and garden problems with pro‑grade products, clear instructions, and expert guidance. We carry curated solutions that work the first time, so you can skip costly service calls.</p>

    <h3>Affordable Pest Control Solutions</h3>
    <p>Our customers routinely save up to 70% versus hiring an exterminator. We stock professional traps, baits, sprays, insecticides, rodenticides, herbicides, and organic options for every budget and use case.</p>

    <div id="about-long" style="display:none">
      <p>We’re constantly updating our catalog and guides with the latest in DIY pest management. Whether it’s termite, ant, bed bug, roach, rodent, or mosquito control, we provide the same active ingredients the pros use—plus safer, low‑toxicity choices like Insect Growth Regulators. From capture and humane relocation to full eradication, we have the tools and know‑how to help you protect your home inside and out.</p>
      <p>Why pay brand premiums? Many products share the same proven active ingredients. For example, a typical exterior general spray service can cost ~$70 per visit, while a DIY application using comparable concentrates can be as little as a few dollars. With step‑by‑step instructions and support, spraying, baiting, dusting, spreading, fogging, or misting becomes simple and effective.</p>
      <p>Need help choosing? Call our product specialists for free advice. We’ll help identify your pest, recommend the right chemistry and equipment, and walk you through application and prevention—so you get results safely and confidently.</p>
      <h3>FREE Expert Advice</h3>
      <p>PEST-CTRL is headquartered in the Philippines and ships nationwide. Our team provides free, friendly support on DIY pest control, lawn care, gardening, and animal care—so you always have a pro in your corner.</p>
    </div>

    <a id="about-toggle" class="about-toggle" href="#">Read more</a>
  </div>
</section>

<div class="about-spacer"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const toggle = document.getElementById('about-toggle');
  const long = document.getElementById('about-long');
  if (!toggle || !long) return;
  toggle.addEventListener('click', function(e) {
    e.preventDefault();
    const isHidden = long.style.display === 'none' || long.style.display === '';
    long.style.display = isHidden ? 'block' : 'none';
    toggle.textContent = isHidden ? 'Read less' : 'Read more';
  });
});
 </script>

<!-- Compare Products Bar -->
<div id="compare-bar" class="compare-bar" style="display: none;">
    <div class="compare-content">
        <h4>Compare Products (<span id="compare-count">0</span>/4)</h4>
        <div id="compare-items"></div>
        <div class="compare-actions">
            <button id="compare-btn" class="btn btn-compare" disabled>Compare Selected</button>
            <button id="clear-compare" class="btn btn-clear">Clear All</button>
        </div>
    </div>
</div>


<!-- Buy Now notification -->
<div id="buy-now-notification" class="buy-now-notification" style="display: none;">
    <span id="buy-now-message"></span>
</div>

<script>
function handleAddToCart(productId, quantity = 1) {
    var isLoggedIn = <?php echo json_encode($isLoggedIn); ?>;
    if (!isLoggedIn) {
        window.location.href = 'login.php';
        return;
    }
    addToCart(productId, quantity);
}

function handleBuyNow(productId, quantity = 1) {
    var isLoggedIn = <?php echo json_encode($isLoggedIn); ?>;
    if (!isLoggedIn) {
        window.location.href = 'login.php';
        return;
    }
    buyNow(productId, quantity);
}

// Slideshow functionality
let currentSlideIndex = 0;
const slides = document.querySelectorAll('.slide');
const dots = document.querySelectorAll('.dot');

function showSlide(index) {
    // Hide all slides
    slides.forEach(slide => slide.classList.remove('active'));
    dots.forEach(dot => dot.classList.remove('active'));
    
    // Show current slide
    if (slides[index]) {
        slides[index].classList.add('active');
    }
    if (dots[index]) {
        dots[index].classList.add('active');
    }
}

function changeSlide(direction) {
    currentSlideIndex += direction;
    
    if (currentSlideIndex >= slides.length) {
        currentSlideIndex = 0;
    } else if (currentSlideIndex < 0) {
        currentSlideIndex = slides.length - 1;
    }
    
    showSlide(currentSlideIndex);
}

function currentSlide(index) {
    currentSlideIndex = index - 1;
    showSlide(currentSlideIndex);
}

// Auto-slide functionality
function autoSlide() {
    changeSlide(1);
}

// Start auto-slide every 5 seconds
let slideInterval = setInterval(autoSlide, 5000);

// Pause auto-slide on hover
const slideshowContainer = document.querySelector('.slideshow-container');
if (slideshowContainer) {
    slideshowContainer.addEventListener('mouseenter', () => {
        clearInterval(slideInterval);
    });
    
    slideshowContainer.addEventListener('mouseleave', () => {
        slideInterval = setInterval(autoSlide, 5000);
    });
}

// Initialize slideshow
document.addEventListener('DOMContentLoaded', function() {
    showSlide(0);
});

// Compare functionality
let compareProducts = [];
const maxCompare = 4;
function toggleCompare(checkbox) {
    const productId = checkbox.dataset.productId;
    const productName = checkbox.dataset.productName;
    const productPrice = checkbox.dataset.productPrice;
    const productImage = checkbox.dataset.productImage;
    const productRating = checkbox.dataset.productRating || '0';
    const productReviews = checkbox.dataset.productReviews || '0';
    if (checkbox.checked) {
        if (compareProducts.length >= maxCompare) {
            checkbox.checked = false;
            alert(`You can only compare up to ${maxCompare} products at a time.`);
            return;
        }
        if (compareProducts.some(p => p.id === productId)) {
            checkbox.checked = false;
            return;
        }
        compareProducts.push({ id: productId, name: productName, price: productPrice, image: productImage, rating: productRating, reviews: productReviews });
    } else {
        compareProducts = compareProducts.filter(p => p.id !== productId);
    }
    updateCompareBar();
}

function updateCompareBar() {
    const compareBar = document.getElementById('compare-bar');
    const compareCount = document.getElementById('compare-count');
    const compareItems = document.getElementById('compare-items');
    const compareBtn = document.getElementById('compare-btn');
    if (!compareBar || !compareCount || !compareItems || !compareBtn) return;
    compareCount.textContent = compareProducts.length;
    if (compareProducts.length > 0) {
        compareBar.style.display = 'block';
        compareBtn.disabled = compareProducts.length < 2;
        compareItems.innerHTML = compareProducts.map(product => `
            <div class="compare-item">
                <img src="${product.image || 'default-image.jpg'}" alt="${product.name}" onerror="this.src='default-image.jpg'">
                <span title="${product.name}">${product.name}</span>
                <button onclick="removeFromCompare('${product.id}')" class="remove-compare" title="Remove">×</button>
            </div>
        `).join('');
    } else {
        compareBar.style.display = 'none';
    }
}

function removeFromCompare(productId) {
    compareProducts = compareProducts.filter(p => p.id !== productId);
    const checkbox = document.querySelector(`.compare-checkbox[data-product-id="${productId}"]`);
    if (checkbox) checkbox.checked = false;
    updateCompareBar();
}

function clearCompare() {
    compareProducts = [];
    document.querySelectorAll('.compare-checkbox').forEach(cb => cb.checked = false);
    updateCompareBar();
}

function compareSelected() {
    if (compareProducts.length < 2) {
        alert('Please select at least 2 products to compare.');
        return;
    }
    const productIds = compareProducts.map(p => p.id).join(',');
    window.location.href = `compare.php?products=${productIds}`;
}

// Add to cart function - use the one from assets/script.js

// Buy Now function
function buyNow(productId, quantity = 1) {
    console.log('Buy Now clicked:', productId, quantity);
    
    if (!productId || productId <= 0) {
        console.error('Invalid product ID:', productId);
        showBuyNowNotification('Invalid product selected', 'error');
        return;
    }
    
    if (!quantity || quantity <= 0) {
        console.error('Invalid quantity:', quantity);
        showBuyNowNotification('Invalid quantity specified', 'error');
        return;
    }
    
    const button = document.querySelector(`button[data-buy-product-id="${productId}"]`);
    if (!button) {
        console.error('Buy Now button not found for product:', productId);
        showBuyNowNotification('Button not found', 'error');
        return;
    }
    
    const originalText = button.textContent;
    button.textContent = 'Processing...';
    button.disabled = true;
    
    console.log('Sending buy now request...');
    
    fetch('ajax/buy-now.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: parseInt(productId),
            quantity: parseInt(quantity)
        })
    })
    .then(response => {
        console.log('Buy Now response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return response.text().then(text => {
            console.log('Raw response:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', e, 'Raw text:', text);
                throw new Error('Invalid JSON response');
            }
        });
    })
    .then(data => {
        console.log('Buy Now response data:', data);
        
        if (data.success) {
            showBuyNowNotification('Redirecting to checkout...', 'success');
            
            setTimeout(() => {
                console.log('Redirecting to:', data.redirect_url);
                window.location.href = data.redirect_url;
            }, 1500);
        } else {
            const errorMessage = data.message || 'Error processing buy now request';
            console.error('Buy Now failed:', errorMessage);
            showBuyNowNotification(errorMessage, 'error');
            button.textContent = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Buy Now Error:', error);
        showBuyNowNotification('Error processing request: ' + error.message, 'error');
        button.textContent = originalText;
        button.disabled = false;
    });
}

// Use the showNotification function from assets/script.js

function showBuyNowNotification(message, type = 'success') {
    const notification = document.getElementById('buy-now-notification');
    const messageElement = document.getElementById('buy-now-message');
    
    if (!notification || !messageElement) {
        console.error('Buy Now notification elements not found');
        return;
    }
    
    messageElement.textContent = message;
    notification.className = 'buy-now-notification ' + type;
    notification.style.display = 'block';
    
    if (type !== 'success') {
        setTimeout(() => {
            notification.style.display = 'none';
        }, 4000);
    }
}

// Use the cart notification functions from assets/script.js

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing...');
    
    const compareBtn = document.getElementById('compare-btn');
    const clearCompareBtn = document.getElementById('clear-compare');
    if (compareBtn) compareBtn.addEventListener('click', compareSelected);
    if (clearCompareBtn) clearCompareBtn.addEventListener('click', clearCompare);
    
    const buyNowButtons = document.querySelectorAll('[data-buy-product-id]');
    console.log('Found buy now buttons:', buyNowButtons.length);
});
</script>

<!-- Shop By Sections -->
<style>
.shopby { padding: 16px 0 28px; }
.shopby .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.shopby h2 { margin: 0 0 10px 0; }
.benefits-grid { margin-bottom: 10px; }
.shopby .benefit { background: transparent; border: none; padding: 0; }
.shopby-grid { display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap:12px; }
.shopby-item { display:flex; align-items:center; gap:14px; padding:10px 12px; border:1px solid #2b2540; border-radius:8px; text-decoration:none; color:#ffffff; background: rgba(255,255,255,0.04); }
.shopby-item .icon { width:22px; text-align:center; color:#FFD736; filter:none; font-size:18px; }
.shopby-item span { font-size:13px; font-weight:700; color:#ffffff; }
.shopby-item:hover { background: rgba(255, 215, 54, 0.15); border-color:#FFD736; }
@media (max-width:768px){ .shopby-grid{ grid-template-columns:1fr; } }
</style>

<section class="shopby" style="padding-top: 2px;">
  <div class="container">
    <h2 style="text-transform: uppercase; color:#ffffff; font-weight:800; text-align:center; margin: 0 0 16px 0;">Shop by Pest</h2>
    <div class="benefits-grid" style="margin-bottom:24px;">
      <!-- Shop by Pest -->
      <div class="benefit" style="padding:0; background:transparent;">
        <p style="color:#ffffff; font-size:13px; margin:0 0 8px 0; text-align:center;">Find pro-grade solutions for common pest problems</p>
        <div class="shopby-grid">
          <?php
          // Find the top-level category that represents "By Target Pest" (name contains 'pest' or similar)
          $topLevel = getChildrenByParentId(0);
          $targetPestParentId = 0;
          foreach ($topLevel as $parentCat) {
            $name = strtolower($parentCat['clean_name']);
            if (strpos($name, 'pest') !== false) { $targetPestParentId = (int)$parentCat['id']; break; }
          }
          // If not found by name, fallback to first top-level
          if ($targetPestParentId === 0 && !empty($topLevel)) {
            $targetPestParentId = (int)$topLevel[0]['id'];
          }
          // Render only the subcategories under By Target Pest as pills with icons
          $children = getChildrenByParentId($targetPestParentId);
          foreach ($children as $child) {
            $hrefChild = 'products.php?categories[]=' . (int)$child['id'];
            $icon = iconForCategory($child['clean_name']);
            echo '<a class="shopby-item" href="' . htmlspecialchars($hrefChild) . '"><div class="icon"><i class="fas ' . htmlspecialchars($icon) . '"></i></div><span>' . htmlspecialchars($child['clean_name']) . '</span></a>';
          }
          ?>
        </div>
      </div>
    </div>

    <!-- Shop Lawn & Garden removed per request -->

    <h2 style="text-transform: uppercase; color:#ffffff; font-weight:800; text-align:center; margin: 0 0 16px 0;">Shop by Category</h2>
    <div class="benefits-grid">
      <!-- Shop By Category -->
      <div class="benefit" style="padding:0; background:transparent;">
        <p style="color:#ffffff; font-size:13px; margin:0 0 8px 0; text-align:center;">Browse our most popular categories</p>
        <div class="shopby-grid">
          <?php
          $cats = [
            ['Pest Control','fa-shield',['Pest Control']],
            ['Lawn & Garden','fa-seedling',['Garden & Lawn Care']],
            ['Equipment','fa-toolbox',['Accessories & Equipment','Sprayers (manual, battery, knapsack)']],
            ['Animal Care','fa-paw',['Animal Care']],
          ];
          foreach ($cats as $c) {
            $label = $c[0]; $icon = $c[1]; $names = $c[2];
            $href = categoryLinkFor($names, $label);
            echo '<a class="shopby-item" href="' . htmlspecialchars($href) . '"><div class="icon"><i class="fas ' . htmlspecialchars($icon) . '"></i></div><span>' . htmlspecialchars($label) . '</span></a>';
          }
          ?>
        </div>
      </div>
    </div>
  </div>
</section>
<?php require_once 'includes/footer.php'; ?>


<style>
/* Body background */
body {
    background: linear-gradient(135deg, #1a0a2e 0%, #16213e 100%);
    min-height: 100vh;
    color: #F9F9F9;
}

/* Text color overrides for better contrast */
h1, h2, h3, h4, h5, h6 {
    color: #F9F9F9;
}

p, span, div {
    color: #F9F9F9;
}

/* Hero Slideshow Styles */
.hero-slideshow {
    margin-top: 80px;
    margin-bottom: 40px;
}

.slideshow-container {
    position: relative;
    max-width: 1200px;
    margin: 0 auto;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.slide {
    display: none;
    position: relative;
    height: 500px;
    overflow: hidden;
}

.slide.active {
    display: block;
}

.slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
}

.slide-content {
    position: absolute;
    bottom: 60px;
    left: 50%;
    transform: translateX(-50%);
    text-align: center;
}

.shop-now-btn {
    display: inline-block;
    background: #FFD736;
    color: #130325;
    padding: 15px 30px;
    text-decoration: none;
    border-radius: 8px;
    font-weight: bold;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    border: 2px solid #FFD736;
}

.shop-now-btn:hover {
    background: #130325;
    color: #FFD736;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 215, 54, 0.3);
}

/* Navigation arrows */
.prev, .next {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    color: #130325;
    border: none;
    padding: 0;
    font-size: 2rem;
    cursor: pointer;
    transition: all 0.3s ease;
    z-index: 10;
    text-shadow: 2px 2px 4px rgba(255, 255, 255, 0.8);
}

.prev {
    left: 20px;
}

.next {
    right: 20px;
}

.prev:hover, .next:hover {
    color: #2d1b4e;
    transform: translateY(-50%) scale(1.2);
}

/* Dots indicator */
.dots-container {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 10px;
    z-index: 10;
}

.dot {
    width: 15px;
    height: 15px;
    border-radius: 50%;
    background: rgba(19, 3, 37, 0.4);
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
}

.dot.active, .dot:hover {
    background: #130325;
    transform: scale(1.2);
}

/* Responsive design */
@media (max-width: 768px) {
    .slide {
        height: 400px;
    }
    
    .prev, .next {
        font-size: 1.5rem;
    }
    
    .prev {
        left: 10px;
    }
    
    .next {
        right: 10px;
    }
}

/* Product grid styles */
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.product-card {
    position: relative;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
    transition: transform 0.2s, box-shadow 0.2s;
    background: #f8f9fa;
    color: #333;
    display: flex;
    flex-direction: column;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.product-card img {
    width: 100%;
    max-width: 200px;
    height: 200px;
    object-fit: cover;
    margin-bottom: 10px;
}

/* Compare Checkbox - match products.php */
.product-checkbox {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(255, 255, 255, 0.9);
    padding: 6px 10px;
    border-radius: 20px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1px solid #e6e6e6;
    z-index: 2;
}

.product-checkbox:hover {
    background: #ffffff;
    border-color: #007bff;
}

.product-checkbox input[type="checkbox"] {
    accent-color: #007bff;
    transform: scale(1.2);
}

.product-checkbox label {
    margin: 0;
    color: #130325;
    font-size: 12px;
    font-weight: 600;
}

.product-name {
    margin: 10px 0;
    font-size: 1.1em;
    font-weight: bold;
    color: #333;
}

.price {
    font-weight: bold;
    color: #130325;
    font-size: 1.2em;
    margin: 10px 0;
}

.rating {
    color: #130325;
    margin: 10px auto 4px auto;
    font-size: 1.2rem;
    letter-spacing: -2px;
    text-align: center;
}

.rating-text {
    color: #130325;
    font-size: 0.85rem;
    display: block;
    text-align: left;
    margin-top: 4px;
}

.product-card .stock {
    color: #28a745;
    font-size: 0.95rem;
    margin: 15px 0;
    font-weight: 600;
    padding: 5px 12px;
    background: rgba(40, 167, 69, 0.1);
    border-radius: 20px;
    display: inline-block;
}

/* Product action buttons - UNIFIED COLORS */
.product-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: auto;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
    text-align: center;
    font-weight: 600;
}

/* View Details - Dark Purple */
.btn-details {
    background-color: #130325;
    color: #F9F9F9;
}

.btn-details:hover {
    background-color: rgba(19, 3, 37, 0.8);
    transform: translateY(-2px);
    text-decoration: none;
}

/* Add to Cart - Blue */
.btn-cart {
    background-color: #007bff;
    color: #F9F9F9;
}

.btn-cart:hover:not(:disabled) {
    background-color: #0056b3;
    transform: translateY(-2px);
}

.btn-cart:disabled {
    background-color: #6c757d;
    color: #F9F9F9;
    opacity: 0.6;
    cursor: not-allowed;
}

/* Buy Now - Yellow */
.btn-buy {
    background-color: #FFD736;
    color: #130325;
    font-weight: bold;
}

.btn-buy:hover:not(:disabled) {
    background-color: #e6c230;
    transform: translateY(-2px);
}

.btn-buy:disabled {
    background-color: #6c757d;
    color: #F9F9F9;
    opacity: 0.6;
    cursor: not-allowed;
}

/* Compare button styling */
.btn-compare {
    background-color: #130325;
    color: #F9F9F9;
    font-weight: bold;
    border: 2px solid #FFD736;
}

.btn-compare:hover:not(:disabled) {
    background-color: rgba(19, 3, 37, 0.8);
    border-color: #e6c230;
    transform: translateY(-2px);
}

.btn-compare:disabled {
    background-color: #6c757d;
    color: #F9F9F9;
    border-color: #6c757d;
    cursor: not-allowed;
    opacity: 0.6;
}

.btn-clear {
    background: #dc3545;
    color: #F9F9F9;
}

.btn-clear:hover {
    background: #c82333;
    transform: translateY(-2px);
}

/* Compare Bar Styles */
.compare-bar { display: none; position: fixed; bottom: 0; left: 0; right: 0; background: var(--primary-light); box-shadow: 0 -2px 10px var(--shadow-medium); z-index: 1000; padding: 15px; border-top: 2px solid #FFD736; }

@keyframes slideUp { 
    from { transform: translateY(100%); opacity: 0; } 
    to { transform: translateY(0); opacity: 1; } 
}

.compare-content { max-width: 1200px; margin: 0 auto; display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }

.compare-content h4 { 
    margin: 0; 
    color: #ffffff; 
    font-size: 1rem; 
    font-weight: 600; 
}

#compare-items { display: flex; gap: 10px; flex: 1; flex-wrap: wrap; min-width: 200px; }

.compare-item { display: flex; align-items: center; gap: 8px; background: rgba(0, 123, 255, 0.1); padding: 8px 12px; border-radius: 20px; font-size: 14px; border: 2px solid #007bff; transform: translateY(-2px); }
.compare-item img { width: 30px; height: 30px; object-fit: cover; border-radius: 4px; }
.compare-item span { max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.remove-compare { background: none; border: none; color: #dc3545; font-size: 18px; cursor: pointer; padding: 0; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: background-color 0.2s; }
.remove-compare:hover { background: rgba(220, 53, 69, 0.1); }
.compare-actions { display: flex; gap: 10px; }
