<?php
session_start();

$conn = new mysqli("localhost", "root", "", "book_library");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Determine viewer and requested profile
$viewer_id = $_SESSION['user_id'] ?? null;
$requestedUser = $_GET['user'] ?? '';
$profileUser = null;
if (!empty($requestedUser)) {
  if (ctype_digit($requestedUser)) {
    $stmt = $conn->prepare("SELECT id, username, profile_picture FROM users WHERE id = ?");
    $stmt->bind_param("i", $requestedUser);
    $stmt->execute();
    $profileUser = $stmt->get_result()->fetch_assoc();
    $stmt->close();
  } else {
    $stmt = $conn->prepare("SELECT id, username, profile_picture FROM users WHERE username = ?");
    $stmt->bind_param("s", $requestedUser);
    $stmt->execute();
    $profileUser = $stmt->get_result()->fetch_assoc();
    $stmt->close();
  }
  if (!$profileUser) { header("Location: home.php"); exit; }
  $user_id = (int)$profileUser['id'];
  $is_owner = ($viewer_id && $viewer_id == $user_id);
} else {
  if (empty($viewer_id)) { header("Location: home.php?return_url=".urlencode($_SERVER['REQUEST_URI'])); exit; }
  $user_id = $viewer_id;
  $stmt = $conn->prepare("SELECT username, profile_picture FROM users WHERE id = ?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $profileUser = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  $is_owner = true;
}

$search = $_GET['search'] ?? '';
$genreFilter = $_GET['genre'] ?? '';
$limit = 3; $page = $_GET['page'] ?? 1; $offset = ($page-1)*$limit;

// Count reading list items
$listCount = $conn->query("SELECT COUNT(*) AS total FROM reading_list WHERE user_id=$user_id")->fetch_assoc()['total'] ?? 0;

// Build filters
$where = ["user_id=$user_id"];
if ($search) $where[] = "(title LIKE '%".$conn->real_escape_string($search)."%' OR author LIKE '%".$conn->real_escape_string($search)."%')";
if ($genreFilter) $where[] = "genre='".$conn->real_escape_string($genreFilter)."'";
$whereSQL = "WHERE ".implode(" AND ", $where);

// Pagination
$totalBooks = $conn->query("SELECT COUNT(*) AS total FROM books $whereSQL")->fetch_assoc()['total'] ?? 0;
$totalPages = ceil($totalBooks/$limit);


// Books
$result = $conn->query("SELECT * FROM books $whereSQL ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$booksDisplayed = [];
if ($result) {
  while ($r = $result->fetch_assoc()) $booksDisplayed[] = $r;
}

// If a viewer is logged in and is not the owner, determine which of these books the viewer already has
$viewerOwnedMap = [];
if (!empty($viewer_id) && !$is_owner && !empty($booksDisplayed)) {
  $conds = [];
  foreach ($booksDisplayed as $bd) {
    $t = $conn->real_escape_string($bd['title']);
    $a = $conn->real_escape_string($bd['author'] ?? '');
    $conds[] = "(title='" . $t . "' AND author='" . $a . "')";
  }
  if (!empty($conds)) {
    $sql = "SELECT title, author FROM books WHERE user_id = " . (int)$viewer_id . " AND (" . implode(' OR ', $conds) . ")";
    $r2 = $conn->query($sql);
    if ($r2) {
      while ($row = $r2->fetch_assoc()) {
        $key = $row['title'] . '||' . ($row['author'] ?? '');
        $viewerOwnedMap[$key] = true;
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($profileUser['username'] ?? ($_SESSION['username'] ?? 'Gallery')); ?>'s Gallery</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Modern Typography and Base Styles */
    body {
      font-family: 'Inter', sans-serif;
      line-height: 1.6;
      color: #2d3748;
      background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
    }

    /* Hero Section Enhancements */
    .carousel-item {
      min-height: 70vh;
      display: flex;
      align-items: center;
    }

    .carousel-item h1 {
      font-weight: 700;
      text-shadow: 0 2px 4px rgba(0,0,0,0.1);
      letter-spacing: -0.02em;
    }

    .carousel-item .lead {
      font-weight: 400;
      opacity: 0.9;
    }

    .btn-outline-light {
      border-width: 2px;
      font-weight: 600;
      transition: all 0.3s ease;
      border-radius: 50px;
      padding: 12px 30px;
    }

    .btn-outline-light:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(255,255,255,0.3);
    }

    /* Section Headers */
    h2, .display-6 {
      font-weight: 700;
      letter-spacing: -0.02em;
      color: #1a202c;
    }

    /* Card Enhancements */
    .card {
      border: none;
      border-radius: 16px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      transition: all 0.3s ease;
      overflow: hidden;
    }

    .card:hover {
      transform: translateY(-8px);
      box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }

    .card-body {
      padding: 2rem;
    }

    /* Button Styles */
    .btn {
      border-radius: 50px;
      font-weight: 600;
      padding: 12px 24px;
      transition: all 0.3s ease;
    }

    .btn-primary {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border: none;
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
    }

    .btn-outline-primary {
      border-width: 2px;
      color: #667eea;
      border-color: #667eea;
    }

    .btn-outline-primary:hover {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-color: transparent;
      color: white;
    }

    .btn-light {
      background: rgba(255,255,255,0.9);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255,255,255,0.2);
    }

    /* Genre Cards */
    .genre-card {
      transition: all 0.4s ease;
      cursor: pointer;
    }

    .genre-card:hover {
      transform: translateY(-12px) scale(1.02);
      box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    }

    .genre-overlay::before {
      transition: all 0.3s ease;
    }

    .genre-card:hover .genre-overlay::before {
      background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.5));
    }

    /* Testimonial Cards */
    .testimonial-card {
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255,255,255,0.2);
    }

    .testimonial-avatar {
      border-color: #667eea;
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    /* Social Icons */
    .social-icon-box {
      transition: all 0.4s ease;
      position: relative;
      overflow: hidden;
    }

    .social-icon-box::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: left 0.5s;
    }

    .social-icon-box:hover::before {
      left: 100%;
    }

    /* Footer */
    footer {
      background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
      border-top: 1px solid rgba(255,255,255,0.1);
    }

    footer a {
      transition: all 0.3s ease;
    }

    footer a:hover {
      color: #667eea !important;
      transform: translateX(4px);
    }

    /* Animations */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .section-fade-in {
      animation: fadeInUp 0.8s ease-out;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
      .carousel-item h1 {
        font-size: 2.5rem;
      }

      .card-body {
        padding: 1.5rem;
      }

      .btn {
        padding: 10px 20px;
        font-size: 0.9rem;
      }
    }

    /* Loading States */
    .loading {
      opacity: 0.6;
      pointer-events: none;
    }

    /* Custom Scrollbar */
    ::-webkit-scrollbar {
      width: 8px;
    }

    ::-webkit-scrollbar-track {
      background: #f1f5f9;
    }

    ::-webkit-scrollbar-thumb {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
      background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
    }
  </style>
</head>
<body class="bg-light">
<?php require_once __DIR__ . '/inc/header.php'; ?>
<div class="container mt-5">

  <!-- Success/Error Messages -->
  <?php
    $msg = $_GET['msg'] ?? '';
    if ($msg === 'added') echo '<div class="alert alert-success alert-dismissible fade show">✅ Book added to your gallery!</div>';
    elseif ($msg === 'exists') echo '<div class="alert alert-warning alert-dismissible fade show">⚠️ This book is already in your gallery.</div>';
    elseif ($msg === 'error') echo '<div class="alert alert-danger alert-dismissible fade show">❌ Error adding book to gallery.</div>';
  ?>

  <!-- Search & Filter -->
  <form method="GET" class="row mb-4">
    <div class="col-md-6">
  <div class="input-group">
    <input type="text" name="search" id="searchInput" class="form-control" placeholder="🔍 Search" value="<?php echo htmlspecialchars($search); ?>">
    <button type="button" class="btn btn-outline-secondary" onclick="clearSearch()">❌</button>
  </div>
</div>
    <div class="col-md-4">
      <select name="genre" class="form-select">
        <option value="">Filter by genre</option>
        <?php
          $genres = $conn->query("SELECT DISTINCT genre FROM books WHERE user_id=$user_id ORDER BY genre ASC");
          while ($g = $genres->fetch_assoc()):
            $genreVal = htmlspecialchars($g['genre']);
            $selected = ($genreFilter === $g['genre']) ? 'selected' : '';
        ?>
          <option value="<?php echo $genreVal; ?>" <?php echo $selected; ?>><?php echo $genreVal; ?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="col-md-2">
      <button type="submit" class="btn btn-primary w-100">Apply</button>
    </div>
  </form>

  <!-- Book Cards -->
  <div class="row">
    <?php foreach ($booksDisplayed as $row): ?>
      <?php
        $bookId    = (int)$row['id'];
        $title     = htmlspecialchars($row['title']);
        $author    = htmlspecialchars($row['author']);
        $genre     = htmlspecialchars($row['genre']);
        $synopsis  = nl2br(htmlspecialchars($row['synopsis']));
        $cover     = htmlspecialchars($row['cover_image']);
        $attachment= htmlspecialchars($row['attachment']);
        $progress  = $conn->query("SELECT progress FROM reading_list WHERE user_id=$user_id AND book_id=$bookId")->fetch_assoc()['progress'] ?? null;
      ?>
      <div class="col-md-4 mb-4">
        <div class="card h-100 shadow-sm">
          <!-- Cover triggers modal -->
          <img
            src="<?php echo $cover; ?>"
            class="card-img-top"
            alt="Book Cover"
            style="cursor:pointer;"
            data-bs-toggle="modal"
            data-bs-target="#readModal<?php echo $bookId; ?>"
          >
          <div class="card-body">
            <h5 class="card-title"><?php echo $title; ?></h5>
            <h6 class="card-subtitle mb-2 text-muted"><?php echo $author; ?></h6>
            <?php
  // Limit synopsis preview to 150 characters
  $fullSynopsis = htmlspecialchars($row['synopsis']);
  $shortSynopsis = mb_strimwidth($fullSynopsis, 0, 150, "...");
?>
<p class="card-text">
  <span id="short-<?php echo $bookId; ?>"><?php echo nl2br($shortSynopsis); ?></span>
  <span id="full-<?php echo $bookId; ?>" style="display:none;"><?php echo nl2br($fullSynopsis); ?></span>
  <a href="javascript:void(0);" 
     onclick="toggleSynopsis(<?php echo $bookId; ?>)" 
     id="toggle-<?php echo $bookId; ?>" 
     class="text-primary">See more</a>
</p>

            <p><strong>Genre:</strong> <span class="badge bg-secondary"><?php echo $genre; ?></span></p>

            <!-- Progress (if exists) -->
            <?php if ($progress !== null): ?>
              <div class="progress mb-2">
                <div class="progress-bar" style="width:<?php echo (int)$progress; ?>%"><?php echo (int)$progress; ?>%</div>
              </div>
            <?php endif; ?>

            <!-- Actions -->
            <button type="button" class="btn btn-outline-secondary btn-sm add-reading-list-btn" data-book-id="<?php echo $bookId; ?>">💾 Add to Reading List</button>
            <?php if ($is_owner): ?>
              <a href="edit.php?id=<?php echo $bookId; ?>" class="btn btn-warning btn-sm">✏️ Edit</a>
              <a href="delete.php?id=<?php echo $bookId; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this book?')">🗑️ Delete</a>
            <?php else: ?>
              <?php if (!empty($viewer_id)): ?>
                <?php $sig = $row['title'] . '||' . ($row['author'] ?? ''); ?>
                <?php if (!empty($viewerOwnedMap[$sig])): ?>
                  <button type="button" class="btn btn-success btn-sm" disabled>✓ In My Gallery</button>
                <?php else: ?>
                  <button type="button" class="btn btn-outline-success btn-sm add-gallery-btn" data-book-id="<?php echo $bookId; ?>">➕ Add to My Gallery</button>
                <?php endif; ?>
              <?php endif; ?>
            <?php endif; ?>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#readModal<?php echo $bookId; ?>">📖 Read Now</button>
          </div>
        </div>
      </div>

      <!-- Read Now Modal -->
      <div class="modal fade" id="readModal<?php echo $bookId; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><?php echo $title; ?></h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <iframe src="<?php echo $attachment; ?>" style="width:100%;height:80vh;" frameborder="0"></iframe>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
