<?php
session_start();
// Public listing: load books with optional search/genre and pagination
$conn = new mysqli("localhost", "root", "", "book_library");
if ($conn->connect_error) {
  $books = null;
} else {
  $page = max(1, (int)($_GET['page'] ?? 1));
  $limit = 8; // show 8 books per page
  $offset = ($page - 1) * $limit;

  // total for pagination (no filters)
  $totalRow = $conn->query("SELECT COUNT(*) AS total FROM books");
  $totalBooks = 0;
  if ($totalRow) {
    $countRow = $totalRow->fetch_assoc();
    $totalBooks = $countRow && isset($countRow['total']) ? (int)$countRow['total'] : 0;
  }
  $totalPages = $totalBooks ? (int)ceil($totalBooks / $limit) : 1;

  // fetch page
  $books = $conn->query("SELECT * FROM books ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
  
  // Most Read carousel: show user's gallery books if logged in, otherwise show all user-added books (deduplicated)
  $homeCurrentUser = null;
  $carouselItems = [];
  if (!empty($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $homeCurrentUser = $conn->query("SELECT id, username, profile_picture FROM users WHERE id=$uid")->fetch_assoc() ?: null;
    $listCount = $conn->query("SELECT COUNT(*) AS total FROM reading_list WHERE user_id=$uid");
    $listCount = $listCount ? ($listCount->fetch_assoc()['total'] ?? 0) : 0;
    
    // Fetch deduplicated books from the user's gallery (one entry per title+author)
    if (!empty($homeCurrentUser)) {
      $carouselRes = $conn->query("SELECT b.* FROM books b INNER JOIN (SELECT MIN(id) AS id FROM books WHERE user_id=$uid GROUP BY title, author) t ON b.id = t.id ORDER BY b.created_at DESC LIMIT 10");
      if ($carouselRes) {
        while ($r = $carouselRes->fetch_assoc()) $carouselItems[] = $r;
      }
    }
  } else {
    // Not logged in: show deduplicated books added by any user account (where user_id IS NOT NULL)
    $carouselRes = $conn->query("SELECT b.* FROM books b INNER JOIN (SELECT MIN(id) AS id FROM books WHERE user_id IS NOT NULL GROUP BY title, author) t ON b.id = t.id ORDER BY b.created_at DESC LIMIT 10");
    if ($carouselRes) {
      while ($r = $carouselRes->fetch_assoc()) $carouselItems[] = $r;
    }
  }
}

// Fetch testimonials from database with user profile picture
$testimonials = [];
if (isset($conn) && $conn instanceof mysqli) {
  $tRes = $conn->query("SELECT t.*, u.profile_picture FROM testimonials t LEFT JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC");
  if ($tRes) {
    while ($t = $tRes->fetch_assoc()) $testimonials[] = $t;
  }
}

// Prepare testimonials for display
$hardcoded = [
  ['name' => 'Sarah Mitchell', 'role' => 'Book Enthusiast', 'message' => '"My Book Gallery has become my go-to place for discovering new books. The interface is so easy to use, and the community recommendations are spot-on. I\'ve already added over 50 books to my collection!"', 'rating' => 5, 'avatar' => 'https://i.pravatar.cc/60?img=1'],
  ['name' => 'James Chen', 'role' => 'College Student', 'message' => '"As a student, having access to thousands of free books is a game-changer. The reading feature works seamlessly across my phone and laptop. Highly recommend to anyone looking to expand their reading horizon!"', 'rating' => 5, 'avatar' => 'https://i.pravatar.cc/60?img=2'],
  ['name' => 'Emma Rodriguez', 'role' => 'Avid Reader', 'message' => '"I love how organized everything is by genre. The personal library feature helps me keep track of what I\'ve read and what I want to read next. This platform is invaluable for passionate readers!"', 'rating' => 5, 'avatar' => 'https://i.pravatar.cc/60?img=3'],
  ['name' => 'Michael Torres', 'role' => 'Literature Teacher', 'message' => '"The community aspect is wonderful! I\'ve discovered books through other users\' collections that I would never have found otherwise. It\'s like having a book club at my fingertips."', 'rating' => 5, 'avatar' => 'https://i.pravatar.cc/60?img=4'],
  ['name' => 'Olivia Patel', 'role' => 'Digital Nomad', 'message' => '"What really impressed me is the reading list feature. I can save books I want to read and access them anytime. The entire experience is smooth and user-friendly!"', 'rating' => 5, 'avatar' => 'https://i.pravatar.cc/60?img=5'],
  ['name' => 'David Kim', 'role' => 'Content Creator', 'message' => '"Finally, a platform that values accessibility and community. Being able to curate and share my book collections with others has made reading even more enjoyable. Keep up the amazing work!"', 'rating' => 5, 'avatar' => 'https://i.pravatar.cc/60?img=6'],
];
$allTestimonials = !empty($testimonials) ? $testimonials : $hardcoded;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Book Gallery - Home</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<!-- Shared header include -->
<?php require_once __DIR__ . '/inc/header.php'; ?>

<!-- Hero Banner Carousel with Fade + Auto-play -->
<section class="text-white">
  <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
    <div class="carousel-inner">

      <!-- Slide 1 -->
      <div class="carousel-item active" data-bs-interval="5000" style="background: linear-gradient(to right, #2c3e50, #34495e);">
        <div class="container text-center py-5">
          <h1 class="display-5 fw-bold">LOTS OF EBOOKS. 100% FREE</h1>
          <p class="lead mt-3">Welcome to your friendly neighborhood library.<br>We have more than 50,000 free ebooks waiting to be discovered.</p>
          <a href="gallery.php" class="btn btn-outline-light mt-4">Explore Highlights</a>
        </div>
      </div>

      <!-- Slide 2 -->
      <div class="carousel-item" data-bs-interval="7000" style="background: linear-gradient(to right, #1e3c72, #2a5298);">
        <div class="container text-center py-5">
          <h1 class="display-5 fw-bold">📖 Read Anywhere, Anytime</h1>
          <p class="lead mt-3">Enjoy seamless reading across all your devices — no limits, no distractions.</p>
          <a href="<?php echo !empty($homeCurrentUser) ? 'genre.php' : '#'; ?>" class="btn btn-outline-light mt-4" <?php echo empty($homeCurrentUser) ? 'data-bs-toggle="modal" data-bs-target="#loginModal" onclick="setSharedReturnUrl(\'genre.php\');"' : ''; ?>>Browse Featured Books</a>
        </div>
      </div>

      <!-- Slide 3 -->
      <div class="carousel-item" data-bs-interval="6000" style="background: linear-gradient(to right, #42275a, #734b6d);">
        <div class="container text-center py-5">
          <h1 class="display-5 fw-bold">🔍 Smart Search & Filters</h1>
          <p class="lead mt-3">Find exactly what you love with genre tags, keywords, and personalized suggestions.</p>
          <a href="#search" class="btn btn-outline-light mt-4">Try It Now</a>
        </div>
      </div>

    </div>

    <!-- Carousel Controls -->
    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
      <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
      <span class="carousel-control-next-icon"></span>
    </button>
  </div>
</section>


<!-- Most Read Books Section -->
<main class="container mt-0 mb-4 pb-0">
  <h2 class="mb-3 text-center">🔥 Most Read Books</h2>
    <?php if (!empty($carouselItems)): ?>
      <style>
        .deal-cover { height:220px; width:100%; object-fit:cover; box-shadow:0 6px 12px rgba(0,0,0,0.25); border-radius:4px; }
        .deal-slide { padding:12px 8px; }
        /* Hover overlay + fade for carousel covers */
        .deal-slide { position: relative; }
        .deal-slide .deal-cover { transition: filter .18s ease, transform .18s ease; display:block; }
        .deal-slide .deal-overlay { position:absolute; left:0; right:0; top:0; bottom:0; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0); transition:background .18s ease; pointer-events:none; }
        .deal-slide .deal-overlay .read-now-overlay-btn { pointer-events:auto; opacity:0; transform:translateY(6px); transition:all .18s ease; }
        .deal-slide:hover .deal-cover { filter:brightness(.65); }
        .deal-slide:hover .deal-overlay { background:rgba(0,0,0,0.35); }
        .deal-slide:hover .deal-overlay .read-now-overlay-btn { opacity:1; transform:none; }
      </style>

      <div id="mostReadCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3500">
        <div class="carousel-inner">
          <?php
            $perSlide = 6;
            $chunks = array_chunk($carouselItems, $perSlide);
            foreach ($chunks as $i => $chunk):
          ?>
            <div class="carousel-item <?php echo ($i === 0) ? 'active' : ''; ?>">
              <div class="d-flex justify-content-start align-items-center">
                <?php foreach ($chunk as $item):
                  $cid = (int)$item['id'];
                  $ctitle = htmlspecialchars($item['title']);
                  $ccover = !empty($item['cover_image']) ? htmlspecialchars($item['cover_image']) : 'uploads/book_placeholder.jpg';
                  $cattachment = $item['attachment'] ?? '';
                ?>
                  <div class="deal-slide" style="width:16.66%;">
                    <div class="cover-wrap" style="position:relative;">
                      <img src="<?php echo $ccover; ?>" alt="<?php echo $ctitle; ?>" class="deal-cover" loading="lazy" onerror="this.src='uploads/book_placeholder.jpg'">
                      <div class="deal-overlay d-flex align-items-center justify-content-center">
                        <button class="btn btn-sm btn-primary read-now-overlay-btn"
                                data-book-id="<?php echo $cid; ?>"
                                data-has-attachment="<?php echo !empty($cattachment) ? '1' : '0'; ?>">
                          📖 Read Now
                        </button>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#mostReadCarousel" data-bs-slide="prev">
          <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#mostReadCarousel" data-bs-slide="next">
          <span class="carousel-control-next-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Next</span>
        </button>
      </div>
    <?php
      // Generate read modals for carousel items that have an attachment
      foreach ($carouselItems as $ci) {
        if (empty($ci['attachment'])) continue;
        $cid = (int)$ci['id'];
        $ctitle = htmlspecialchars($ci['title']);
        $cattach = htmlspecialchars($ci['attachment']);
    ?>
      <div class="modal fade" id="readNowModal<?php echo $cid; ?>" tabindex="-1" aria-labelledby="readNowLabel<?php echo $cid; ?>" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="readNowLabel<?php echo $cid; ?>"><?php echo $ctitle; ?></h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <iframe src="<?php echo $cattach; ?>" style="width:100%;height:80vh;" frameborder="0"></iframe>
            </div>
          </div>
        </div>
      </div>
    <?php } ?>
    <?php else: ?>
      <p class="text-center text-muted">No featured books yet.</p>
    <?php endif; ?>
</main>
<!-- Login Modal -->
<!-- Browse Genres -->
<?php
// load genres for the browse grid
$genres = [];
if (isset($conn) && $conn instanceof mysqli) {
  $gRes = $conn->query("SELECT genre FROM genres ORDER BY genre ASC");
  if ($gRes) {
    while ($g = $gRes->fetch_assoc()) $genres[] = $g['genre'];
  }
}
?>

<?php if (!empty($genres)): ?>
<section id="browseGenres" class="container mt-1">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="mb-0">BROWSE GENRES <small class="text-muted ms-2"><a href="genre.php">(view all)</a></small></h2>
    </div>

    <style>
      .genre-card { height:140px; background-size:cover; background-position:center; position:relative; border-radius:6px; overflow:hidden; display:block; }
      .genre-overlay { position:absolute; left:0; right:0; top:0; bottom:0; display:flex; align-items:center; justify-content:center; }
      .genre-overlay::before { content:''; position:absolute; left:0; right:0; top:0; bottom:0; background:linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.35)); }
      .genre-title { position:relative; z-index:2; color:#fff; font-size:1.15rem; letter-spacing:1px; font-weight:600; text-align:center; }
      .genre-card:hover { transform:translateY(-4px); transition:transform .18s ease; box-shadow:0 6px 18px rgba(0,0,0,0.18); }
    </style>

    <div class="row gx-3 gy-4">
      <?php foreach ($genres as $gname):
        $safe = htmlspecialchars($gname);
        $escaped = isset($conn) && $conn instanceof mysqli ? $conn->real_escape_string($gname) : $gname;
        // try to get a representative cover from books in this genre
        $cover = 'uploads/book_placeholder.jpg';
        if (isset($conn) && $conn instanceof mysqli) {
          $cRes = $conn->query("SELECT cover_image FROM books WHERE genre='" . $escaped . "' AND cover_image IS NOT NULL AND cover_image != '' LIMIT 1");
          if ($cRes) {
            $crow = $cRes->fetch_assoc();
            if ($crow && !empty($crow['cover_image'])) $cover = $crow['cover_image'];
          }
        }
        $modalId = 'genreModal' . str_replace(' ', '', $gname);
      ?>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
          <a href="#" class="genre-card text-decoration-none text-white genre-trigger" data-genre="<?php echo htmlspecialchars($gname); ?>" data-bs-toggle="modal" data-bs-target="#<?php echo $modalId; ?>" style="background-image:url('<?php echo htmlspecialchars($cover); ?>')" title="<?php echo $safe; ?>">
            <div class="genre-overlay">
              <div class="genre-title"><?php echo $safe; ?></div>
            </div>
          </a>
        </div>

        <!-- Genre Books Modal -->
        <div class="modal fade" id="<?php echo $modalId; ?>" tabindex="-1" aria-labelledby="<?php echo $modalId; ?>Label" aria-hidden="true">
          <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="<?php echo $modalId; ?>Label"><?php echo $safe; ?> Books</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <?php
                  $booksInGenre = [];
                  if (isset($conn) && $conn instanceof mysqli) {
                    // deduplicate by title+author within this genre
                    $sub = "SELECT MIN(id) AS id FROM books WHERE genre='" . $escaped . "' GROUP BY title, author";
                    $bRes = $conn->query("SELECT b.id, b.title, b.author, b.cover_image, b.attachment FROM books b INNER JOIN (" . $sub . ") t ON b.id = t.id ORDER BY b.created_at DESC LIMIT 20");
                    if ($bRes) {
                      while ($b = $bRes->fetch_assoc()) $booksInGenre[] = $b;
                    }
                  }
                  // If logged in, check which of these books the current user already has in their gallery
                  $ownedMap = [];
                  $readingListMap = [];
                  if (!empty($homeCurrentUser) && isset($conn) && $conn instanceof mysqli) {
                    $uid = (int)($_SESSION['user_id'] ?? 0);
                    if ($uid && !empty($booksInGenre)) {
                      $conds = [];
                      foreach ($booksInGenre as $bi) {
                        $t = $conn->real_escape_string($bi['title']);
                        $a = $conn->real_escape_string($bi['author'] ?? '');
                        $conds[] = "(title='" . $t . "' AND author='" . $a . "')";
                      }
                      if (!empty($conds)) {
                        $sql = "SELECT title, author FROM books WHERE user_id = " . $uid . " AND (" . implode(' OR ', $conds) . ")";
                        $r2 = $conn->query($sql);
                        if ($r2) {
                          while ($row = $r2->fetch_assoc()) {
                            $key = $row['title'] . '||' . ($row['author'] ?? '');
                            $ownedMap[$key] = true;
                          }
                        }
                      }

                      // Check which books are already in the user's reading list
                      $bookIds = [];
                      foreach ($booksInGenre as $bi) {
                        $bookIds[] = (int)$bi['id'];
                      }
                      if (!empty($bookIds)) {
                        $sql = "SELECT book_id FROM reading_list WHERE user_id = " . $uid . " AND book_id IN (" . implode(',', $bookIds) . ")";
                        $r3 = $conn->query($sql);
                        if ($r3) {
                          while ($row = $r3->fetch_assoc()) {
                            $readingListMap[$row['book_id']] = true;
                          }
                        }
                      }
                    }
                  }
                  if (!empty($booksInGenre)):
                ?>
                  <div class="row">
                    <?php foreach ($booksInGenre as $book):
                      $bid = (int)$book['id'];
                      $btitle = htmlspecialchars($book['title']);
                      $bauthor = htmlspecialchars($book['author'] ?? '');
                      $bcover = !empty($book['cover_image']) ? htmlspecialchars($book['cover_image']) : 'uploads/book_placeholder.jpg';
                    ?>
                      <div class="col-md-6 mb-3">
                        <div class="card h-100">
                          <img src="<?php echo $bcover; ?>" class="card-img-top" alt="<?php echo $btitle; ?>" style="height:200px;object-fit:cover;" onerror="this.src='uploads/book_placeholder.jpg'">
                          <div class="card-body">
                            <h6 class="card-title"><?php echo $btitle; ?></h6>
                            <?php if (!empty($bauthor)): ?>
                              <p class="card-text text-muted small"><?php echo $bauthor; ?></p>
                            <?php endif; ?>
                            <div class="d-flex flex-column gap-2">
                              <?php if (!empty($homeCurrentUser)): ?>
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#readNowModal<?php echo $bid; ?>">📖 Read Now</button>
                                <?php if (!empty($readingListMap[$bid])): ?>
                                  <button type="button" class="btn btn-secondary btn-sm w-100" disabled>Already on the list</button>
                                <?php else: ?>
                                  <button type="button" class="btn btn-outline-secondary btn-sm w-100 add-reading-list-btn" data-book-id="<?php echo $bid; ?>">💾 Add to Reading List</button>
                                <?php endif; ?>
                                <?php
                                  $sig = $book['title'] . '||' . ($book['author'] ?? '');
                                  if (!empty($ownedMap[$sig])):
                                ?>
                                  <button type="button" class="btn btn-success btn-sm w-100" disabled>✓ In Gallery</button>
                                <?php else: ?>
                                  <button type="button" class="btn btn-outline-success btn-sm w-100 add-gallery-btn" data-book-id="<?php echo $bid; ?>">➕ Add to Gallery</button>
                                <?php endif; ?>
                              <?php else: ?>
                                <button type="button" class="btn btn-primary btn-sm genre-login-btn" data-book-id="<?php echo $bid; ?>" data-action="read_now" data-bs-toggle="modal" data-bs-target="#loginModal">📖 Read Now</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm genre-login-btn" data-book-id="<?php echo $bid; ?>" data-action="read_later" data-bs-toggle="modal" data-bs-target="#loginModal">💾 Read Later</button>
                                <button type="button" class="btn btn-outline-success btn-sm genre-login-btn" data-book-id="<?php echo $bid; ?>" data-action="add_to_gallery" data-bs-toggle="modal" data-bs-target="#loginModal">➕ Add to Gallery</button>
                              <?php endif; ?>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Read Now Modal for Book -->
                      <?php if (!empty($book['attachment'])): ?>
                        <div class="modal fade" id="readNowModal<?php echo $bid; ?>" tabindex="-1" aria-labelledby="readNowLabel<?php echo $bid; ?>" aria-hidden="true">
                          <div class="modal-dialog modal-xl modal-dialog-centered">
                            <div class="modal-content">
                              <div class="modal-header">
                                <h5 class="modal-title" id="readNowLabel<?php echo $bid; ?>"><?php echo $btitle; ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                              </div>
                              <div class="modal-body">
                                <iframe src="<?php echo htmlspecialchars($book['attachment']); ?>" style="width:100%;height:80vh;" frameborder="0"></iframe>
                              </div>
                            </div>
                          </div>
                        </div>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <p class="text-center text-muted">No books found in this genre.</p>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>

<?php if (empty($homeCurrentUser)): ?>
<!-- About Section -->
<section class="bg-white py-4 mt-1" onclick="setSharedReturnUrl('about.php'); new bootstrap.Modal(document.getElementById('loginModal')).show();" style="cursor:pointer;">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-6 mb-4 mb-md-0">
        <h2 class="display-6 fw-bold mb-3">About My Book Gallery</h2>
        <p class="lead text-muted">My Book Gallery is a community-driven digital library dedicated to making books accessible to everyone. Our mission is to create a space where book lovers can discover, share, and enjoy a vast collection of eBooks.</p>
        <div class="mt-4">
          <h5 class="fw-bold mb-3">Why Choose Us?</h5>
          <ul class="list-unstyled">
            <li class="mb-2"><i class="bi bi-check-circle-fill text-success"></i> <strong>100% Free Access</strong> - Explore thousands of eBooks without any subscription costs.</li>
            <li class="mb-2"><i class="bi bi-check-circle-fill text-success"></i> <strong>Community Collections</strong> - Browse and add books curated by our growing user community.</li>
            <li class="mb-2"><i class="bi bi-check-circle-fill text-success"></i> <strong>Genre Organization</strong> - Easily discover books across multiple genres and categories.</li>
            <li class="mb-2"><i class="bi bi-check-circle-fill text-success"></i> <strong>Personal Library</strong> - Build your own collection and manage your reading list.</li>
            <li class="mb-2"><i class="bi bi-check-circle-fill text-success"></i> <strong>Read Anywhere</strong> - Access your books seamlessly across all your devices.</li>
          </ul>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card shadow-lg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
          <div class="card-body text-white text-center p-5">
            <h3 class="mb-3">Join Our Community</h3>
            <p class="mb-4">Connect with thousands of book enthusiasts and expand your reading horizons.</p>
            <div class="row text-center">
              <div class="col-6 mb-3">
                <h4 class="fw-bold">10,000+</h4>
                <p class="small">eBooks Available</p>
              </div>
              <div class="col-6 mb-3">
                <h4 class="fw-bold">5,000+</h4>
                <p class="small">Active Members</p>
              </div>
            </div>
            <a href="<?php echo !empty($homeCurrentUser) ? 'gallery.php?user=' . urlencode($homeCurrentUser['username']) : '#'; ?>" class="btn btn-light mt-3" <?php echo empty($homeCurrentUser) ? 'data-bs-toggle="modal" data-bs-target="#loginModal"' : ''; ?> onclick="setSharedReturnUrl('about.php'); event.stopPropagation();">
              <?php echo !empty($homeCurrentUser) ? 'View Your Gallery' : 'Get Started'; ?>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Testimonials Section -->
<section class="bg-light py-4 mt-1">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="display-6 fw-bold mb-2">What Our Users Say</h2>
      <p class="lead text-muted">Join thousands of satisfied book lovers who are discovering and sharing their favorite reads.</p>
    </div>

    <style>
      .testimonial-carousel-container { max-width:900px; margin:0 auto; }
      .testimonial-card { border:none; background:#fff; transition:all .3s ease; border-radius:12px; }
      .testimonial-card:hover { box-shadow:0 12px 28px rgba(0,0,0,0.15); transform:translateY(-6px); margin-bottom:20px; }
      .testimonial-header { display:flex; align-items:center; gap:1rem; margin-bottom:1.5rem; }
      .testimonial-avatar { width:60px; height:60px; border-radius:50%; object-fit:cover; border:3px solid #667eea; }
      .testimonial-info { text-align:left; }
      .testimonial-author { font-weight:600; color:#333; margin:0; }
      .testimonial-role { font-size:0.85rem; color:#999; margin:0.25rem 0 0 0; }
      .testimonial-stars { color:#ffc107; font-size:1rem; letter-spacing:2px; margin-bottom:1rem; }
      .testimonial-text { font-style:italic; line-height:1.8; color:#555; margin:0; }
      .carousel-item { display:none; }
      .carousel-item.active { display:block; }
      .testimonial-carousel-slide { padding:40px 20px; }
    </style>

    <div class="testimonial-carousel-container">
      <div id="testimonialCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
        <div class="carousel-inner">
          <?php
          $isFirst = true;
          foreach ($allTestimonials as $t):
            $avatar = !empty($t['profile_picture']) ? htmlspecialchars($t['profile_picture']) : (isset($t['avatar']) ? $t['avatar'] : 'https://i.pravatar.cc/60?img=' . rand(1, 10));
            $stars = str_repeat('★', (int)($t['rating'] ?? 5));
          ?>
            <div class="carousel-item <?php echo $isFirst ? 'active' : ''; ?>">
              <div class="card testimonial-card">
                <div class="card-body p-5">
                  <div class="testimonial-header">
                    <img src="<?php echo htmlspecialchars($avatar); ?>" alt="<?php echo htmlspecialchars($t['name']); ?>" class="testimonial-avatar">
                    <div class="testimonial-info">
                      <p class="testimonial-author"><?php echo htmlspecialchars($t['name']); ?></p>
                      <p class="testimonial-role"><?php echo htmlspecialchars($t['role'] ?? 'User'); ?></p>
                    </div>
                  </div>
                  <div class="testimonial-stars mb-3"><?php echo $stars; ?></div>
                  <p class="testimonial-text"><?php echo htmlspecialchars($t['message']); ?></p>
                </div>
              </div>
            </div>
          <?php
            $isFirst = false;
          endforeach;
          ?>
        </div>

        <!-- Carousel Controls -->
        <button class="carousel-control-prev" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="prev" style="top:50%; transform:translateY(-50%);">
          <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="next" style="top:50%; transform:translateY(-50%);">
          <span class="carousel-control-next-icon"></span>
        </button>

        <!-- Carousel Indicators -->
        <div class="carousel-indicators" style="position:static; margin-top:2rem; background:transparent;">
          <button type="button" data-bs-target="#testimonialCarousel" data-bs-slide-to="0" class="active" style="width:12px; height:12px; border-radius:50%; background:#667eea; border:none;" aria-current="true"></button>
          <button type="button" data-bs-target="#testimonialCarousel" data-bs-slide-to="1" style="width:12px; height:12px; border-radius:50%; background:#ccc; border:none;"></button>
          <button type="button" data-bs-target="#testimonialCarousel" data-bs-slide-to="2" style="width:12px; height:12px; border-radius:50%; background:#ccc; border:none;"></button>
          <button type="button" data-bs-target="#testimonialCarousel" data-bs-slide-to="3" style="width:12px; height:12px; border-radius:50%; background:#ccc; border:none;"></button>
          <button type="button" data-bs-target="#testimonialCarousel" data-bs-slide-to="4" style="width:12px; height:12px; border-radius:50%; background:#ccc; border:none;"></button>
          <button type="button" data-bs-target="#testimonialCarousel" data-bs-slide-to="5" style="width:12px; height:12px; border-radius:50%; background:#ccc; border:none;"></button>
        </div>
      </div>
    </div>

    <div class="text-center mt-5">
      <p class="text-muted mb-3">Be part of our growing community of book lovers</p>
      <button class="btn btn-outline-primary btn-lg ms-2" data-bs-toggle="modal" data-bs-target="#loginModal">
        Join Us Today
      </button>
    </div>
  </div>
</section>

<!-- Floating Send Message Button -->
<div class="floating-testimonial-btn">
  <button class="btn btn-primary btn-lg" id="sendMessageBtn" title="Send a Message">
    ✉️ Send Message
  </button>
</div>
<style>
  .floating-testimonial-btn {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1050;
  }
  .floating-testimonial-btn .btn {
    border-radius: 50px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    transition: all 0.3s ease;
  }
  .floating-testimonial-btn .btn:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 16px rgba(0,0,0,0.4);
  }
</style>

<!-- Follow Our Socials Section (Only for Guests) -->
<?php if (empty($homeCurrentUser)): ?>
<section class="bg-white py-4 mt-1">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="display-6 fw-bold mb-2">Follow Our Socials</h2>
      <p class="lead text-muted">Stay connected with our community and get the latest updates on new books and features.</p>
    </div>

    <style>
      .social-icon-box { width:120px; height:120px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:2.5rem; transition:all .3s ease; box-shadow:0 4px 12px rgba(0,0,0,0.1); margin:0 auto; text-decoration:none; color:#fff; }
      .social-icon-box:hover { transform:translateY(-8px); box-shadow:0 12px 28px rgba(0,0,0,0.2); }
      .social-box { text-align:center; }
      .social-label { font-weight:600; color:#333; margin-top:1rem; }
      .social-followers { font-size:0.85rem; color:#999; margin-top:0.25rem; }
    </style>

    <div class="row g-4 justify-content-center">
      <!-- Facebook -->
      <div class="col-6 col-sm-4 col-md-3">
        <div class="social-box">
          <a href="https://facebook.com" target="_blank" class="social-icon-box" style="background:linear-gradient(135deg, #1877F2 0%, #0A66C2 100%);">
            f
          </a>
          <p class="social-label">Facebook</p>
          <p class="social-followers">45.2K followers</p>
        </div>
      </div>

      <!-- Twitter/X -->
      <div class="col-6 col-sm-4 col-md-3">
        <div class="social-box">
          <a href="https://twitter.com" target="_blank" class="social-icon-box" style="background:linear-gradient(135deg, #000 0%, #333 100%);">
            𝕏
          </a>
          <p class="social-label">X (Twitter)</p>
          <p class="social-followers">28.9K followers</p>
        </div>
      </div>

      <!-- Instagram -->
      <div class="col-6 col-sm-4 col-md-3">
        <div class="social-box">
          <a href="https://instagram.com" target="_blank" class="social-icon-box" style="background:linear-gradient(135deg, #E4405F 0%, #FD1D1D 25%, #FCAF45 50%, #F77737 75%, #D92E7F 100%);">
            📷
          </a>
          <p class="social-label">Instagram</p>
          <p class="social-followers">67.5K followers</p>
        </div>
      </div>

      <!-- YouTube -->
      <div class="col-6 col-sm-4 col-md-3">
        <div class="social-box">
          <a href="https://youtube.com" target="_blank" class="social-icon-box" style="background:linear-gradient(135deg, #FF0000 0%, #DC0E0E 100%);">
            ▶
          </a>
          <p class="social-label">YouTube</p>
          <p class="social-followers">12.3K subscribers</p>
        </div>
      </div>
    </div>

    <div class="text-center mt-5">
      <p class="text-muted mb-3">Join our community on social media for book recommendations, events, and exclusive updates</p>
      <a href="#" class="btn btn-outline-primary btn-lg" data-bs-toggle="modal" data-bs-target="#loginModal">
        Create Account to Connect
      </a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- login modal provided by shared header include -->

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
          <li class="mb-2"><a href="<?php echo !empty($homeCurrentUser) ? 'gallery.php' : '#'; ?>" class="text-decoration-none text-white-50 text-hover" <?php echo empty($homeCurrentUser) ? 'onclick="event.preventDefault(); new bootstrap.Modal(document.getElementById(\'loginModal\')).show();"' : ''; ?>>My Gallery</a></li>
          <li class="mb-2"><a href="<?php echo !empty($homeCurrentUser) ? 'my_reading_list.php' : '#'; ?>" class="text-decoration-none text-white-50 text-hover" <?php echo empty($homeCurrentUser) ? 'onclick="event.preventDefault(); new bootstrap.Modal(document.getElementById(\'loginModal\')).show();"' : ''; ?>>Reading List</a></li>
          <li class="mb-2"><a href="<?php echo !empty($homeCurrentUser) ? 'profile.php' : '#'; ?>" class="text-decoration-none text-white-50 text-hover" <?php echo empty($homeCurrentUser) ? 'onclick="event.preventDefault(); new bootstrap.Modal(document.getElementById(\'loginModal\')).show();"' : ''; ?>>Profile</a></li>
        </ul>
      </div>

      <!-- Support & Legal -->
      <div class="col-md-4">
        <h5 class="fw-bold mb-3">Support & Legal</h5>
        <ul class="list-unstyled">
          <li class="mb-2"><a href="#" class="text-decoration-none text-white-50 text-hover">About Us</a></li>
          <li class="mb-2"><a href="#" class="text-decoration-none text-white-50 text-hover">Privacy Policy</a></li>
          <li class="mb-2"><a href="#" class="text-decoration-none text-white-50 text-hover">Terms of Service</a></li>
          <li class="mb-2"><a href="#" class="text-decoration-none text-white-50 text-hover">Contact Us</a></li>
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
  body { display:flex; flex-direction:column; min-height:100vh; }
  footer { margin-top:auto; }
</style>



<!-- Testimonial Modal -->
<div class="modal fade" id="testimonialModal" tabindex="-1" aria-labelledby="testimonialModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="testimonialModalLabel">Share Your Testimonial</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="testimonialForm">
          <div class="mb-3">
            <label for="testimonialName" class="form-label">Your Name</label>
            <input type="text" class="form-control" id="testimonialName" required>
          </div>
          <div class="mb-3">
            <label for="testimonialRole" class="form-label">Your Role (e.g., Book Enthusiast, Student)</label>
            <input type="text" class="form-control" id="testimonialRole" required>
          </div>
          <div class="mb-3">
            <label for="testimonialMessage" class="form-label">Your Testimonial</label>
            <textarea class="form-control" id="testimonialMessage" rows="4" required></textarea>
          </div>
          <div class="mb-3">
            <label for="testimonialRating" class="form-label">Rating</label>
            <select class="form-select" id="testimonialRating" required>
              <option value="5">★★★★★ (5 stars)</option>
              <option value="4">★★★★☆ (4 stars)</option>
              <option value="3">★★★☆☆ (3 stars)</option>
              <option value="2">★★☆☆☆ (2 stars)</option>
              <option value="1">★☆☆☆☆ (1 star)</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary">Submit Testimonial</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php if (isset($conn) && $conn instanceof mysqli) $conn->close(); ?>

</body>
</html>

<script>
// Handle send message button click
document.getElementById('sendMessageBtn').addEventListener('click', function(e) {
  e.preventDefault();
  const isLoggedIn = !!document.getElementById('userDropdown');
  if (!isLoggedIn) {
    const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
    loginModal.show();
  } else {
    const testimonialModal = new bootstrap.Modal(document.getElementById('testimonialModal'));
    testimonialModal.show();
  }
});

// Handle testimonial form submission
document.getElementById('testimonialForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const name = document.getElementById('testimonialName').value;
  const role = document.getElementById('testimonialRole').value;
  const message = document.getElementById('testimonialMessage').value;
  const rating = document.getElementById('testimonialRating').value;

  fetch('submit_testimonial.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ name, role, message, rating })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert('Thank you for your testimonial!');
      bootstrap.Modal.getInstance(document.getElementById('testimonialModal')).hide();
      location.reload(); // Reload to show new testimonial
    } else {
      alert('Error submitting testimonial: ' + data.message);
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('An error occurred. Please try again.');
  });
});

// If user clicks a carousel cover while not logged in, set the shared login form return URL
document.querySelectorAll('.carousel-login-link').forEach(link => {
  link.addEventListener('click', function(e){
    const bookId = this.getAttribute('data-book-id');
    if (bookId) {
      setSharedReturnForAction('read_now', bookId);
    }
  });
});

// Handle genre modal login buttons for guests
document.querySelectorAll('.genre-login-btn').forEach(btn => {
  btn.addEventListener('click', function(e){
    const bookId = this.getAttribute('data-book-id');
    const action = this.getAttribute('data-action');
    if (bookId) {
      let returnPath;
      if (action === 'read_now') {
        returnPath = 'read_now.php?id=' + bookId;
      } else if (action === 'read_later') {
        returnPath = 'read_later.php?book_id=' + bookId;
      } else if (action === 'add_to_gallery') {
        returnPath = 'add_to_gallery.php?book_id=' + bookId;
      }
      if (returnPath) {
        setSharedReturnForAction(action, bookId);
      }
    }
  });
});

// Read Now overlay button behavior for carousel covers
document.addEventListener('click', function (e) {
  const btn = e.target.closest && e.target.closest('.read-now-overlay-btn');
  if (!btn) return;
  e.preventDefault();
  
  const bookId = btn.getAttribute('data-book-id');
  const hasAttach = btn.getAttribute('data-has-attachment') === '1';
  const isLoggedIn = !!document.getElementById('userDropdown'); // header shows this only when logged in

  if (!isLoggedIn) {
    // Guest -> set auto-action and open login modal
    setSharedReturnForAction('read_now', bookId);
    const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
    loginModal.show();
    return;
  }

  // Logged-in
  if (!hasAttach) {
    alert('This book has no online reader available.');
    return;
  }

  // Try to find and open the modal
  const modalId = 'readNowModal' + bookId;
  const modalElement = document.getElementById(modalId);
  
  if (modalElement) {
    const m = new bootstrap.Modal(modalElement);
    m.show();
  } else {
    alert('Reader not available for this book.');
  }
});
</script>
