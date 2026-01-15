<?php
session_start();
require_once 'config/connect.php';
require_once 'config/currency.php';

$title = 'Motoshapi - Motorcycle Parts';
$activePage = 'home';
$uiTheme = 'spare';
$bodyClass = 'bg-light sp-landing-idle';
$mainClass = 'flex-grow-1 p-0';
include 'includes/header.php';
?>

<?php
// Categories for tiles
$tile_categories_stmt = $conn->query(
    "SELECT c.*, 
            COUNT(p.id) AS product_count,
            (SELECT p2.image_url FROM products p2 
                WHERE p2.category_id = c.id AND p2.image_url IS NOT NULL AND p2.image_url <> '' 
                ORDER BY p2.created_at DESC 
                LIMIT 1) AS tile_image
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.id
     ORDER BY product_count DESC, c.name ASC
     LIMIT 3"
);
$tileCategories = $tile_categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Featured products (falls back to newest active products)
$featuredProducts = [];
try {
    $featured_stmt = $conn->query(
        "SELECT id, name, price, stock, image_url FROM products WHERE is_active = 1 AND featured = 1 ORDER BY updated_at DESC, created_at DESC LIMIT 4"
    );
    $featuredProducts = $featured_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $featuredProducts = [];
}

if (empty($featuredProducts)) {
    try {
        $fallback_stmt = $conn->query(
            "SELECT id, name, price, stock, image_url FROM products WHERE is_active = 1 ORDER BY (stock > 0) DESC, created_at DESC LIMIT 4"
        );
        $featuredProducts = $fallback_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $featuredProducts = [];
    }
}
?>

<section class="sp-landing sp-landing--fullscreen" aria-label="Hero">
    <div class="sp-landing-video" aria-hidden="true">
        <video autoplay muted loop playsinline preload="metadata">
            <source src="uploads/hero/hero.mp4" type="video/mp4">
        </video>
    </div>
    <div class="sp-landing-overlay" aria-hidden="true"></div>
    <div class="sp-container">
        <div class="sp-landing-content">
            <div class="sp-landing-kicker">motoshapi</div>
            <h1 class="sp-landing-title">Ride ready. Parts that perform.</h1>
            <div class="sp-landing-sub">Quality motorcycle parts and accessories—picked for riders who care about performance and reliability.</div>
            <div class="sp-landing-actions">
                <a href="products.php" class="sp-hero-cta">Shop now <i class="bi bi-chevron-right"></i></a>
            </div>
        </div>
    </div>
</section>

<div class="sp-main">
    <div class="sp-container">

        <section class="sp-hero">
            <div class="sp-hero-grid">
                <div class="sp-hero-copy">
                    <div class="sp-hero-kicker">featured products</div>
                    <h1 class="sp-hero-title">Featured</h1>
                    <div class="sp-hero-sub">Hand-picked parts and accessories our riders love.</div>
                    <a href="products.php" class="sp-hero-cta">Browse products <i class="bi bi-chevron-right"></i></a>
                </div>

                <div class="sp-hero-media">
                    <div class="sp-hero-featured" aria-label="Featured products">
                        <?php if (empty($featuredProducts)): ?>
                            <div class="sp-hero-featured-empty">
                                <div style="font-weight: 900; letter-spacing: .08em; text-transform: uppercase; font-size: 12px;">Featured products</div>
                                <div style="margin-top: 6px;">No products available right now.</div>
                            </div>
                        <?php else: ?>
                            <div id="featuredProductsCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4500" data-bs-touch="true" data-bs-pause="false">
                                <div class="carousel-indicators">
                                    <?php foreach ($featuredProducts as $i => $product): ?>
                                        <button type="button"
                                                data-bs-target="#featuredProductsCarousel"
                                                data-bs-slide-to="<?php echo (int)$i; ?>"
                                                class="<?php echo $i === 0 ? 'active' : ''; ?>"
                                                <?php if ($i === 0): ?>aria-current="true"<?php endif; ?>
                                                aria-label="Featured product <?php echo (int)($i + 1); ?>"></button>
                                    <?php endforeach; ?>
                                </div>

                                <div class="carousel-inner">
                                    <?php foreach ($featuredProducts as $i => $product): ?>
                                        <div class="carousel-item<?php echo $i === 0 ? ' active' : ''; ?>">
                                            <div class="sp-featured-slide">
                                                <a class="sp-featured-slide-media" href="product.php?id=<?php echo urlencode((string)$product['id']); ?>" aria-label="View <?php echo htmlspecialchars($product['name']); ?>">
                                                    <?php if (!empty($product['image_url'])): ?>
                                                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                                    <?php else: ?>
                                                        <i class="bi bi-image" style="font-size: 3rem; color: rgba(0,0,0,.35);"></i>
                                                    <?php endif; ?>
                                                </a>
                                                <div class="sp-featured-slide-body">
                                                    <div class="sp-featured-slide-title">
                                                        <a href="product.php?id=<?php echo urlencode((string)$product['id']); ?>"><?php echo htmlspecialchars($product['name']); ?></a>
                                                    </div>
                                                    <div class="sp-featured-slide-meta">
                                                        <div class="sp-featured-slide-price"><?php echo format_price($product['price']); ?></div>
                                                        <div class="sp-featured-slide-stock <?php echo ((int)($product['stock'] ?? 0)) > 0 ? 'in' : 'out'; ?>">
                                                            <?php echo ((int)($product['stock'] ?? 0)) > 0 ? 'In stock' : 'Out of stock'; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="sp-tiles" aria-label="Shop categories">
            <?php if (empty($tileCategories)): ?>
                <div class="sp-tile">
                    <div class="sp-tile-body">
                        <h3 class="sp-tile-title">Categories</h3>
                        <a class="sp-tile-link" href="products.php">Shop now <i class="bi bi-chevron-right"></i></a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($tileCategories as $cat): ?>
                    <?php
                        $tileHref = 'products.php?category_id=' . urlencode((string)$cat['id']);
                        $tileImg = !empty($cat['tile_image']) ? $cat['tile_image'] : '';
                    ?>
                    <div class="sp-tile">
                        <div class="sp-tile-media">
                            <?php if ($tileImg !== ''): ?>
                                <img src="<?php echo htmlspecialchars($tileImg); ?>" alt="<?php echo htmlspecialchars($cat['name']); ?>">
                            <?php else: ?>
                                <div class="sp-tile-placeholder" aria-hidden="true">
                                    <i class="bi bi-grid-3x3-gap" style="font-size: 2.2rem;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="sp-tile-body">
                            <h3 class="sp-tile-title"><?php echo htmlspecialchars($cat['name']); ?></h3>
                            <a class="sp-tile-link" href="<?php echo htmlspecialchars($tileHref); ?>">Shop now <i class="bi bi-chevron-right"></i></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <section id="about" class="sp-surface" style="margin-top: 16px;">
            <div class="p-4">
                <div class="sp-section-title">About</div>
                <div style="color: var(--sp-text-muted); max-width: 980px;">
                    <p style="margin: 0 0 10px;">
                        Motoshapi is your trusted online shop for quality motorcycle parts and accessories.
                        We focus on providing reliable products, clear pricing, and a smooth shopping experience—whether you're maintaining a daily ride or upgrading your build.
                    </p>
                    <p style="margin: 0 0 12px;">
                        Browse categories, discover featured picks, and check product availability before you buy.
                        Every item includes details and pricing to help you choose the right part with confidence.
                    </p>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 10px;">
                        <div style="border: 1px solid var(--sp-border); background: #fff; padding: 12px;">
                            <div style="font-weight: 900; letter-spacing: .08em; text-transform: uppercase; font-size: 12px; margin-bottom: 6px;">Quality parts</div>
                            <div>Motor oils, tires, mags, and more—ready for everyday riders and enthusiasts.</div>
                        </div>
                        <div style="border: 1px solid var(--sp-border); background: #fff; padding: 12px;">
                            <div style="font-weight: 900; letter-spacing: .08em; text-transform: uppercase; font-size: 12px; margin-bottom: 6px;">Real stock info</div>
                            <div>See what’s in stock and shop with less guesswork.</div>
                        </div>
                        <div style="border: 1px solid var(--sp-border); background: #fff; padding: 12px;">
                            <div style="font-weight: 900; letter-spacing: .08em; text-transform: uppercase; font-size: 12px; margin-bottom: 6px;">Easy ordering</div>
                            <div>Add to cart, checkout, and track your orders in one place.</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </div>
</div>

<script>
    (function () {
        const landing = document.querySelector('.sp-landing--fullscreen');
        if (!landing) return;

        // Safety: ensure idle state even if server-side class is removed.
        document.body.classList.add('sp-landing-idle');

        let activated = false;
        const activate = () => {
            if (activated) return;
            activated = true;
            document.body.classList.remove('sp-landing-idle');
            document.body.classList.add('sp-landing-active');
            cleanup();
        };

        const onPointer = () => activate();
        const onKey = (e) => {
            if (!e) return activate();
            if (e.key === 'Tab' || e.key === 'Enter' || e.key === ' ' || e.key === 'Escape') activate();
        };
        const onScroll = () => activate();

        const cleanup = () => {};

        window.addEventListener('pointerdown', onPointer, { passive: true, once: true });
        window.addEventListener('touchstart', onPointer, { passive: true, once: true });
        window.addEventListener('keydown', onKey, { once: true });
        window.addEventListener('scroll', onScroll, { passive: true, once: true });
    })();
</script>

<?php include 'includes/footer.php'; ?>