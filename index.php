<?php
// Test comment for schema sync - $(date)
session_start();
require_once 'config/database.php';
require_once 'config/currency.php';

$title = 'Motoshapi - Motorcycle Parts';
$activePage = 'home';
$mainClass = 'flex-grow-1 p-0';
$renderGlobalFooter = false;
include 'includes/header.php';
?>

    <!-- Hero Section: Slideshow -->
    <div class="position-relative">
        <div class="hero-dark-overlay position-absolute top-0 start-0 w-100 h-100" style="z-index: 5;"></div>
        <div id="heroSlideshow" class="carousel slide mb-5" data-bs-ride="carousel" data-bs-interval="4000">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img src="uploads/slideshow/image1.jpeg" class="d-block w-100" style="max-height: 650px; height: 650px; object-fit: cover;" alt="Slide 1">
                </div>
                <div class="carousel-item">
                    <img src="uploads/slideshow/image2.jpeg" class="d-block w-100" style="max-height: 650px; height: 650px; object-fit: cover;" alt="Slide 2">
                </div>
                <div class="carousel-item">
                    <img src="uploads/slideshow/image3.jpeg" class="d-block w-100" style="max-height: 650px; height: 650px; object-fit: cover;" alt="Slide 3">
                </div>
                <div class="carousel-item">
                    <img src="uploads/slideshow/image4.jpeg" class="d-block w-100" style="max-height: 650px; height: 650px; object-fit: cover;" alt="Slide 4">
                </div>
                <div class="carousel-item">
                    <img src="uploads/slideshow/image5.jpg" class="d-block w-100" style="max-height: 650px; height: 650px; object-fit: cover;" alt="Slide 5">
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#heroSlideshow" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#heroSlideshow" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#heroSlideshow" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                <button type="button" data-bs-target="#heroSlideshow" data-bs-slide-to="1" aria-label="Slide 2"></button>
                <button type="button" data-bs-target="#heroSlideshow" data-bs-slide-to="2" aria-label="Slide 3"></button>
                <button type="button" data-bs-target="#heroSlideshow" data-bs-slide-to="3" aria-label="Slide 4"></button>
                <button type="button" data-bs-target="#heroSlideshow" data-bs-slide-to="4" aria-label="Slide 5"></button>
            </div>
        </div>
        <div class="hero-slogan-overlay position-absolute top-50 start-50 translate-middle text-center w-100" style="z-index: 10;">
            <h1 class="display-4 mb-3 hero-title text-shadow">Welcome to Motoshapi</h1>
            <h2 class="display-5 fw-bold text-white text-shadow">Driven by Parts. Built for Performance.</h2>
            <a class="btn btn-primary btn-lg mt-4" href="products.php" role="button">Shop Now</a>
        </div>
    </div>

    <div class="container mt-4">
        <div class="jumbotron">
            <p class="lead">Your one-stop shop for premium motorcycle parts and accessories.</p>
            <hr class="my-4">
            <p>Browse our extensive collection of high-quality motorcycle parts.</p>
        </div>

        <div class="featured-products-container my-5">
            <h2 class="section-title text-center mb-5">Featured Products</h2>
            <div id="featuredCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3000">
                <div class="carousel-inner">
                    <?php
                    $stmt = $conn->query("SELECT * FROM products WHERE featured = 1 ORDER BY created_at DESC LIMIT 4");
                    $active = true;
                    while($product = $stmt->fetch(PDO::FETCH_ASSOC)):
                    ?>
                    <div class="carousel-item<?php if($active){echo ' active'; $active = false;} ?>">
                        <div class="d-flex justify-content-center">
                            <div class="card product-card" style="width: 22rem;">
                                <a href="product.php?id=<?php echo $product['id']; ?>">
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" class="card-img-top product-img-fit" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                </a>
                                <div class="product-info">
                                    <h5 class="card-title">
                                        <a href="product.php?id=<?php echo $product['id']; ?>" class="text-decoration-none"><?php echo htmlspecialchars($product['name']); ?></a>
                                    </h5>
                                    <p class="card-text"><?php echo format_price($product['price']); ?></p>
                                    <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#featuredCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#featuredCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        </div>

        <footer class="bg-dark text-white mt-5 py-3 w-200" style="left:0;right:0;">
            <div class="d-flex justify-content-center align-items-center flex-column flex-md-row w-100">
                <p class="mb-2 mb-md-0 me-md-4">&copy; 2024 Motoshapi. All rights reserved.</p>
                <button id="aboutUsBtn" class="btn btn-info ms-md-3">About Us</button>
            </div>
        </footer>

        <!-- About Us Modal -->
        <div class="modal fade" id="aboutUsModal" tabindex="-1" aria-labelledby="aboutUsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h2 class="modal-title w-100 text-center" id="aboutUsModalLabel">About Us</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <div class="row justify-content-center gx-5">
                      <?php
                      $about_stmt = $conn->query("SELECT * FROM about_us ORDER BY id ASC");
                      while($member = $about_stmt->fetch(PDO::FETCH_ASSOC)):
                      ?>
                      <div class="col-md-4 col-lg-3 mb-4 d-flex justify-content-center">
                        <div class="card h-100 text-center shadow-sm" style="min-width:280px; padding:25px; margin: 0 15px; border-radius: 15px;">
                          <?php if($member['photo_url']): ?>
                            <img src="<?php echo htmlspecialchars($member['photo_url']); ?>" class="card-img-top mx-auto d-block rounded-circle shadow-sm" alt="<?php echo htmlspecialchars($member['name']); ?>" style="width:200px; height:200px; object-fit:cover; margin-bottom:20px;">
                          <?php else: ?>
                            <div class="bg-secondary text-white d-flex align-items-center justify-content-center mx-auto rounded-circle" style="width:200px; height:200px; margin-bottom:20px;">No Photo</div>
                          <?php endif; ?>
                          <div class="card-body px-3">
                            <h4 class="card-title fw-bold mb-3"><?php echo htmlspecialchars($member['name']); ?></h4>
                            <p class="card-text text-muted mb-2" style="font-size: 0.95rem; line-height: 1.6;"><?php echo htmlspecialchars($member['description']); ?></p>
                          </div>
                        </div>
                      </div>
                      <?php endwhile; ?>
                    </div>
                  </div>
                </div>
            </div>
        </div>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        var aboutUsBtn = document.getElementById('aboutUsBtn');
        var aboutUsModal = new bootstrap.Modal(document.getElementById('aboutUsModal'));
        if (aboutUsBtn) {
            aboutUsBtn.addEventListener('click', function() {
              aboutUsModal.show();
            });
        }
      });
    </script>
    <?php include 'includes/footer.php'; ?>