<?php
session_start();
// About Us page
$conn = new mysqli("localhost", "root", "", "book_library");
if ($conn->connect_error) {
  $conn = null;
}

$aboutCurrentUser = null;
if (!empty($_SESSION['user_id']) && $conn instanceof mysqli) {
  $uid = (int)$_SESSION['user_id'];
  $aboutCurrentUser = $conn->query("SELECT id, username, profile_picture FROM users WHERE id=$uid")->fetch_assoc() ?: null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>About Us - My Book Gallery</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { display: flex; flex-direction: column; min-height: 100vh; }
    footer { margin-top: auto; }
    .hero-section { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 80px 0; }
    .feature-icon { font-size: 3rem; margin-bottom: 1rem; }
    .stat-card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
    .stat-card:hover { transform: translateY(-10px); box-shadow: 0 12px 28px rgba(0,0,0,0.15); }
    .team-member-card { transition: transform 0.3s ease; }
    .team-member-card:hover { transform: translateY(-8px); }
    .team-avatar { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid #667eea; }
    .timeline-item { position: relative; padding-left: 40px; margin-bottom: 30px; }
    .timeline-item::before { content: ''; position: absolute; left: 0; top: 5px; width: 20px; height: 20px; border-radius: 50%; background: #667eea; border: 4px solid #fff; box-shadow: 0 0 0 2px #667eea; }
    .timeline-item::after { content: ''; position: absolute; left: 9px; top: 25px; width: 2px; height: calc(100% + 10px); background: #e0e0e0; }
    .timeline-item:last-child::after { display: none; }
    .value-card { border-left: 4px solid #667eea; transition: all 0.3s ease; }
    .value-card:hover { border-left-color: #764ba2; box-shadow: 0 8px 20px rgba(0,0,0,0.1); transform: translateX(5px); }
  </style>
</head>
<body class="bg-light">

<!-- Shared header include -->
<?php require_once __DIR__ . '/inc/header.php'; ?>

<!-- Hero Section -->
<section class="hero-section">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-6 mb-4 mb-lg-0">
        <h1 class="display-4 fw-bold mb-4">About My Book Gallery</h1>
        <p class="lead mb-4">We're on a mission to make reading accessible to everyone, everywhere. Join our community of passionate book lovers and discover your next favorite read.</p>
        <div class="d-flex gap-3">
          <a href="<?php echo !empty($aboutCurrentUser) ? 'gallery.php?user=' . urlencode($aboutCurrentUser['username']) : '#'; ?>" class="btn btn-light btn-lg" <?php echo empty($aboutCurrentUser) ? 'data-bs-toggle="modal" data-bs-target="#loginModal"' : ''; ?>>
            <?php echo !empty($aboutCurrentUser) ? 'View Your Gallery' : 'Get Started'; ?>
          </a>
          <a href="contact.php" class="btn btn-outline-light btn-lg">Contact Us</a>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card shadow-lg border-0">
          <div class="card-body p-5 text-center">
            <div class="row">
              <div class="col-6 mb-4">
                <h2 class="display-4 fw-bold text-primary">50K+</h2>
                <p class="text-muted mb-0">Free eBooks</p>
              </div>
              <div class="col-6 mb-4">
                <h2 class="display-4 fw-bold text-success">10K+</h2>
                <p class="text-muted mb-0">Active Users</p>
              </div>
              <div class="col-6">
                <h2 class="display-4 fw-bold text-warning">100+</h2>
                <p class="text-muted mb-0">Genres</p>
              </div>
              <div class="col-6">
                <h2 class="display-4 fw-bold text-danger">24/7</h2>
                <p class="text-muted mb-0">Access</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Mission & Vision Section -->
<section class="py-5 bg-white">
  <div class="container">
    <div class="row">
      <div class="col-lg-6 mb-4 mb-lg-0">
        <div class="card h-100 border-0 shadow-sm">
          <div class="card-body p-5">
            <div class="feature-icon text-primary">🎯</div>
            <h3 class="fw-bold mb-3">Our Mission</h3>
            <p class="text-muted mb-0">To democratize access to knowledge and literature by providing a free, user-friendly platform where book lovers can discover, share, and enjoy an extensive collection of eBooks. We believe that everyone deserves access to quality reading material, regardless of their location or financial situation.</p>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card h-100 border-0 shadow-sm">
          <div class="card-body p-5">
            <div class="feature-icon text-success">🌟</div>
            <h3 class="fw-bold mb-3">Our Vision</h3>
            <p class="text-muted mb-0">To become the world's most beloved digital library, fostering a global community of readers who share a passion for learning and storytelling. We envision a future where every person has the tools and resources to explore new worlds through books.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Our Story Section -->
<section class="py-5 bg-light">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="display-5 fw-bold mb-3">Our Story</h2>
      <p class="lead text-muted">From a simple idea to a thriving community</p>
    </div>
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="timeline-item">
          <h5 class="fw-bold">2020 - The Beginning</h5>
          <p class="text-muted">My Book Gallery was founded with a simple vision: make books accessible to everyone. What started as a small collection of public domain books quickly grew into something much bigger.</p>
        </div>
        <div class="timeline-item">
          <h5 class="fw-bold">2021 - Community Growth</h5>
          <p class="text-muted">We reached our first 1,000 users and expanded our collection to over 10,000 books. The community feature was introduced, allowing users to share their favorite reads.</p>
        </div>
        <div class="timeline-item">
          <h5 class="fw-bold">2022 - Platform Enhancement</h5>
          <p class="text-muted">Major platform upgrades including the reading list feature, improved search functionality, and mobile optimization. Our user base grew to 5,000+ active members.</p>
        </div>
        <div class="timeline-item">
          <h5 class="fw-bold">2023 - Global Expansion</h5>
          <p class="text-muted">Reached 10,000+ users worldwide and expanded our library to 50,000+ books across 100+ genres. Introduced personalized recommendations and user galleries.</p>
        </div>
        <div class="timeline-item">
          <h5 class="fw-bold">2024 - Today</h5>
          <p class="text-muted">Continuing to grow and improve, with new features being added regularly based on community feedback. Our mission remains the same: making reading accessible to all.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Core Values Section -->
<section class="py-5 bg-white">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="display-5 fw-bold mb-3">Our Core Values</h2>
      <p class="lead text-muted">The principles that guide everything we do</p>
    </div>
    <div class="row g-4">
      <div class="col-md-6 col-lg-3">
        <div class="card value-card h-100 border-0 shadow-sm">
          <div class="card-body p-4">
            <div class="feature-icon text-primary">📚</div>
            <h5 class="fw-bold mb-3">Accessibility</h5>
            <p class="text-muted small mb-0">We believe knowledge should be free and accessible to everyone, everywhere.</p>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="card value-card h-100 border-0 shadow-sm">
          <div class="card-body p-4">
            <div class="feature-icon text-success">🤝</div>
            <h5 class="fw-bold mb-3">Community</h5>
            <p class="text-muted small mb-0">Building a supportive community of readers who share and discover together.</p>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="card value-card h-100 border-0 shadow-sm">
          <div class="card-body p-4">
            <div class="feature-icon text-warning">💡</div>
            <h5 class="fw-bold mb-3">Innovation</h5>
            <p class="text-muted small mb-0">Constantly improving our platform with new features and better user experience.</p>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="card value-card h-100 border-0 shadow-sm">
          <div class="card-body p-4">
            <div class="feature-icon text-danger">❤️</div>
            <h5 class="fw-bold mb-3">Passion</h5>
            <p class="text-muted small mb-0">Driven by our love for books and commitment to spreading the joy of reading.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Features Section -->
<section class="py-5 bg-light">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="display-5 fw-bold mb-3">Why Choose My Book Gallery?</h2>
      <p class="lead text-muted">Everything you need for an amazing reading experience</p>
    </div>
    <div class="row g-4">
      <div class="col-md-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm">
          <div class="card-body p-4 text-center">
            <div class="feature-icon">📖</div>
            <h5 class="fw-bold mb-3">Vast Collection</h5>
            <p class="text-muted mb-0">Access over 50,000 free eBooks across 100+ genres, from classics to contemporary works.</p>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm">
          <div class="card-body p-4 text-center">
            <div class="feature-icon">🔍</div>
            <h5 class="fw-bold mb-3">Smart Search</h5>
            <p class="text-muted mb-0">Find exactly what you're looking for with our advanced search and filtering options.</p>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm">
          <div class="card-body p-4 text-center">
            <div class="feature-icon">📱</div>
            <h5 class="fw-bold mb-3">Read Anywhere</h5>
            <p class="text-muted mb-0">Seamless reading experience across all your devices - desktop, tablet, or mobile.</p>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm">
          <div class="card-body p-4 text-center">
            <div class="feature-icon">💾</div>
            <h5 class="fw-bold mb-3">Personal Library</h5>
            <p class="text-muted mb-0">Build and organize your own collection with custom galleries and reading lists.</p>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm">
          <div class="card-body p-4 text-center">
            <div class="feature-icon">👥</div>
            <h5 class="fw-bold mb-3">Community Driven</h5>
            <p class="text-muted mb-0">Discover books through community recommendations and shared collections.</p>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm">
          <div class="card-body p-4 text-center">
            <div class="feature-icon">🆓</div>
            <h5 class="fw-bold mb-3">100% Free</h5>
            <p class="text-muted mb-0">No subscriptions, no hidden fees. All features are completely free forever.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Statistics Section -->
<section class="py-5 bg-white">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="display-5 fw-bold mb-3">By The Numbers</h2>
      <p class="lead text-muted">Our impact in the reading community</p>
    </div>
    <div class="row g-4">
      <div class="col-md-3 col-sm-6">
        <div class="card stat-card h-100 border-0 shadow-sm">
          <div class="card-body p-4 text-center">
            <h2 class="display-4 fw-bold text-primary mb-2">50,000+</h2>
            <p class="text-muted mb-0">Free eBooks Available</p>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="card stat-card h-100 border-0 shadow-sm">
          <div class="card-body p-4 text-center">
            <h2 class="display-4 fw-bold text-success mb-2">10,000+</h2>
            <p class="text-muted mb-0">Active Members</p>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="card stat-card h-100 border-0 shadow-sm">
          <div class="card-body p-4 text-center">
            <h2 class="display-4 fw-bold text-warning mb-2">1M+</h2>
            <p class="text-muted mb-0">Books Read</p>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="card stat-card h-100 border-0 shadow-sm">
          <div class="card-body p-4 text-center">
            <h2 class="display-4 fw-bold text-danger mb-2">100+</h2>
            <p class="text-muted mb-0">Book Genres</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Team Section -->
<section class="py-5 bg-light">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="display-5 fw-bold mb-3">Meet Our Team</h2>
      <p class="lead text-muted">The passionate people behind My Book Gallery</p>
    </div>
    <div class="row g-4 justify-content-center">
      <div class="col-md-6 col-lg-3">
        <div class="card team-member-card h-100 border-0 shadow-sm">
          <div class="card-body p-4 text-center">
            <img src="https://i.pravatar.cc/120?img=12" alt="Team Member" class="team-avatar mb-3">
            <h5 class="fw-bold mb-1">Sarah Johnson</h5>
            <p class="text-muted small mb-2">Founder & CEO</p>
            <p class="text-muted small">Passionate about making books accessible to everyone.</p>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="card team-member-card h-100 border-0 shadow-sm">
          <div class="card-body p-4 text-center">
            <img src="https://i.pravatar.cc/120?img=13" alt="Team Member" class="team-avatar mb-3">
            <h5 class="fw-bold mb-1">Michael Chen</h5>
            <p class="text-muted small mb-2">Lead Developer</p>
            <p class="text-muted small">Building the best reading experience possible.</p>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="card team-member-card h-100 border-0 shadow-sm">
          <div class="card-body p-4 text-center">
            <img src="https://i.pravatar.cc/120?img=14" alt="Team Member" class="team-avatar mb-3">
            <h5 class="fw-bold mb-1">Emily Rodriguez</h5>
            <p class="text-muted small mb-2">Community Manager</p>
            <p class="text-muted small">Connecting readers and fostering community.</p>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="card team-member-card h-100 border-0 shadow-sm">
          <div class="card-body p-4 text-center">
            <img src="https://i.pravatar.cc/120?img=15" alt="Team Member" class="team-avatar mb-3">
            <h5 class="fw-bold mb-1">David Kim</h5>
            <p class="text-muted small mb-2">Content Curator</p>
            <p class="text-muted small">Ensuring quality and diversity in our collection.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Call to Action Section -->
<section class="py-5 bg-white">
  <div class="container">
    <div class="card border-0 shadow-lg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
      <div class="card-body p-5 text-center text-white">
        <h2 class="display-5 fw-bold mb-3">Ready to Start Your Reading Journey?</h2>
        <p class="lead mb-4">Join thousands of book lovers and discover your next favorite read today.</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
          <a href="<?php echo !empty($aboutCurrentUser) ? 'gallery.php?user=' . urlencode($aboutCurrentUser['username']) : '#'; ?>" class="btn btn-light btn-lg" <?php echo empty($aboutCurrentUser) ? 'data-bs-toggle="modal" data-bs-target="#loginModal"' : ''; ?>>
            <?php echo !empty($aboutCurrentUser) ? 'Browse Your Gallery' : 'Get Started Free'; ?>
          </a>
          <a href="home.php" class="btn btn-outline-light btn-lg">Explore Books</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="bg-dark text-white py-5 mt-3">
  <div class="container">
    <div class="row">
      <!-- About Footer -->
      <div class="col-md-4 mb-4 mb-md-0">
        <h5 class="fw-bold mb-3">My Book Gallery</h5>
        <p class="text-muted small">Your gateway to thousands of free eBooks. Discover, share, and build your personal library with our vibrant community of book lovers.</p>
        <div class="mt-3">
          <small class="text-muted">
            <i class="bi bi-geo-alt"></i> Global Library<br>
            <i class="bi bi-envelope"></i> support@mybookgallery.com
          </small>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="col-md-4 mb-4 mb-md-0">
        <h5 class="fw-bold mb-3">Quick Links</h5>
        <ul class="list-unstyled">
          <li class="mb-2"><a href="home.php" class="text-decoration-none text-white-50 text-hover">Home</a></li>
          <li class="mb-2"><a href="genre.php" class="text-decoration-none text-white-50 text-hover">Browse Genres</a></li>
          <li class="mb-2"><a href="<?php echo !empty($aboutCurrentUser) ? 'gallery.php' : '#'; ?>" class="text-decoration-none text-white-50 text-hover" <?php echo empty($aboutCurrentUser) ? 'onclick="event.preventDefault(); new bootstrap.Modal(document.getElementById(\'loginModal\')).show();"' : ''; ?>>My Gallery</a></li>
          <li class="mb-2"><a href="<?php echo !empty($aboutCurrentUser) ? 'my_reading_list.php' : '#'; ?>" class="text-decoration-none text-white-50 text-hover" <?php echo empty($aboutCurrentUser) ? 'onclick="event.preventDefault(); new bootstrap.Modal(document.getElementById(\'loginModal\')).show();"' : ''; ?>>Reading List</a></li>
          <li class="mb-2"><a href="<?php echo !empty($aboutCurrentUser) ? 'profile.php' : '#'; ?>" class="text-decoration-none text-white-50 text-hover" <?php echo empty($aboutCurrentUser) ? 'onclick="event.preventDefault(); new bootstrap.Modal(document.getElementById(\'loginModal\')).show();"' : ''; ?>>Profile</a></li>
        </ul>
      </div>

      <!-- Support & Legal -->
      <div class="col-md-4">
        <h5 class="fw-bold mb-3">Support & Legal</h5>
        <ul class="list-unstyled">
          <li class="mb-2"><a href="about.php" class="text-decoration-none text-white-50 text-hover">About Us</a></li>
          <li class="mb-2"><a href="#" class="text-decoration-none text-white-50 text-hover">Privacy Policy</a></li>
          <li class="mb-2"><a href="#" class="text-decoration-none text-white-50 text-hover">Terms of Service</a></li>
          <li class="mb-2"><a href="contact.php" class="text-decoration-none text-white-50 text-hover">Contact Us</a></li>
          <li class="mb-2"><a href="#" class="text-decoration-none text-white-50 text-hover">FAQ</a></li>
        </ul>
      </div>
    </div>

    <hr class="bg-secondary my-4">

    <!-- Copyright Section -->
    <div class="row align-items-center">
      <div class="col-md-6">
        <p class="text-muted small mb-0">&copy; 2024 My Book Gallery. All rights reserved.</p>
      </div>
      <div class="col-md-6 text-md-end">
        <p class="text-muted small mb-0">Made with <span style="color:#e74c3c;">❤</span> for book lovers everywhere</p>
      </div>
    </div>
  </div>
</footer>

<style>
  .text-hover:hover { color:#fff !important; transition:color .3s ease; }
</style>

<?php if (isset($conn) && $conn instanceof mysqli) $conn->close(); ?>

</body>
</html>
