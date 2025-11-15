<?php
session_start();
$conn = new mysqli("localhost", "root", "", "book_library");
if ($conn->connect_error) die('DB connection error');

$genre = isset($_GET['genre']) ? trim($_GET['genre']) : '';
// Prevent absolute URL open-redirect style values (simple guard)
if (strpos($genre, 'http') !== false) $genre = '';

// If no genre, load list of genres
$genres = [];
if (empty($genre)) {
  $gRes = $conn->query("SELECT genre FROM genres ORDER BY genre ASC");
  if ($gRes) while ($g = $gRes->fetch_assoc()) $genres[] = $g['genre'];
}

// If a genre was provided, fetch books for that genre safely (deduplicated by title+author)
$books = [];
if (!empty($genre)) {
  // Use subquery to deduplicate books by title and author, selecting the first occurrence
  $stmt = $conn->prepare("SELECT b.id, b.title, b.author, b.synopsis, b.cover_image, b.attachment 
                          FROM books b 
                          INNER JOIN (
                            SELECT MIN(id) AS id 
                            FROM books 
                            WHERE genre = ? 
                            GROUP BY title, author
                          ) t ON b.id = t.id 
                          ORDER BY b.created_at DESC");
  $stmt->bind_param('s', $genre);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($b = $res->fetch_assoc()) $books[] = $b;
}

// If logged in, determine which of these books the current user already has in their gallery
$ownedMap = [];
if (!empty($_SESSION['user_id']) && !empty($books)) {
  $uid = (int)$_SESSION['user_id'];
  $conds = [];
  foreach ($books as $bi) {
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
}

// If logged in, get current user info for header
$currentUser = null;
$listCount = 0;
if (!empty($_SESSION['user_id'])) {
  $uid = (int)$_SESSION['user_id'];
  $currentUser = $conn->query("SELECT id, username, profile_picture FROM users WHERE id=$uid")->fetch_assoc() ?: null;
  $lc = $conn->query("SELECT COUNT(*) AS total FROM reading_list WHERE user_id=$uid");
  $listCount = $lc ? ($lc->fetch_assoc()['total'] ?? 0) : 0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Genres - My Book Gallery</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php require_once __DIR__ . '/inc/header.php'; ?>

<main class="container mt-1">
  <?php if (empty($genre)): ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="mb-0">BROWSE GENRES</h2>
    </div>

    <style>
      .genre-card { height:140px; background-size:cover; background-position:center; position:relative; border-radius:6px; overflow:hidden; display:block; }
      .genre-overlay { position:absolute; left:0; right:0; top:0; bottom:0; display:flex; align-items:center; justify-content:center; }
      .genre-overlay::before { content:''; position:absolute; left:0; right:0; top:0; bottom:0; background:linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.35)); }
      .genre-title { position:relative; z-index:2; color:#fff; font-size:1.15rem; letter-spacing:1px; font-weight:600; text-align:center; }
      .genre-card:hover { transform:translateY(-4px); transition:transform .18s ease; box-shadow:0 6px 18px rgba(0,0,0,0.18); }
    </style>

    <div class="row gx-3 gy-4">
      <?php foreach ($genres as $g):
        $cover = 'uploads/book_placeholder.jpg';
        $esc = $conn->real_escape_string($g);
        $cRes = $conn->query("SELECT cover_image FROM books WHERE genre='" . $esc . "' AND cover_image IS NOT NULL AND cover_image != '' LIMIT 1");
        if ($cRes) { $crow = $cRes->fetch_assoc(); if ($crow && !empty($crow['cover_image'])) $cover = $crow['cover_image']; }
      ?>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
          <a href="genre.php?genre=<?php echo rawurlencode($g); ?>" class="genre-card text-decoration-none text-white" style="background-image:url('<?php echo htmlspecialchars($cover); ?>')" title="<?php echo htmlspecialchars($g); ?>">
            <div class="genre-overlay">
              <div class="genre-title"><?php echo htmlspecialchars($g); ?></div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="mb-0">Books in: <?php echo htmlspecialchars($genre); ?></h2>
      <a href="genre.php" class="btn btn-secondary">Go Back to Browse Genres</a>
    </div>

    <?php if (empty($books)): ?>
      <p class="text-muted">No books found in this genre yet.</p>
    <?php else: ?>
      <div class="row gx-3 gy-4">
        <?php foreach ($books as $b):
          $cover = !empty($b['cover_image']) ? $b['cover_image'] : 'uploads/book_placeholder.jpg';
        ?>
          <div class="col-6 col-md-4 col-lg-3">
            <div class="card h-100">
              <img src="<?php echo htmlspecialchars($cover); ?>" class="card-img-top" style="height:200px;object-fit:cover;" onerror="this.src='uploads/book_placeholder.jpg'">
              <div class="card-body">
                <h6 class="card-title mb-1"><?php echo htmlspecialchars($b['title']); ?></h6>
                <p class="card-text text-muted small"><?php echo htmlspecialchars(substr($b['synopsis'],0,100)); ?><?php echo strlen($b['synopsis'])>100 ? '...' : ''; ?></p>
                <div class="d-flex flex-column gap-2">
                  <?php if (!empty($currentUser)): ?>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#readModal<?php echo (int)$b['id']; ?>">📖 Read Now</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100 add-reading-list-btn" data-book-id="<?php echo (int)$b['id']; ?>">💾 Add to Reading List</button>
                    <?php
                      $sig = $b['title'] . '||' . ($b['author'] ?? '');
                      if (!empty($ownedMap[$sig])):
                    ?>
                      <button type="button" class="btn btn-success btn-sm w-100" disabled>✓ In Gallery</button>
                    <?php else: ?>
                      <button type="button" class="btn btn-outline-success btn-sm w-100 add-gallery-btn" data-book-id="<?php echo (int)$b['id']; ?>">➕ Add to Gallery</button>
                    <?php endif; ?>
                  <?php else: ?>
                    <button type="button" class="btn btn-primary btn-sm genre-login-btn" data-book-id="<?php echo (int)$b['id']; ?>" data-action="read_now" data-bs-toggle="modal" data-bs-target="#loginModal">📖 Read Now</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm genre-login-btn" data-book-id="<?php echo (int)$b['id']; ?>" data-action="read_later" data-bs-toggle="modal" data-bs-target="#loginModal">💾 Read Later</button>
                    <button type="button" class="btn btn-outline-success btn-sm genre-login-btn" data-book-id="<?php echo (int)$b['id']; ?>" data-action="add_to_gallery" data-bs-toggle="modal" data-bs-target="#loginModal">➕ Add to Gallery</button>
                  <?php endif; ?>
                </div>
                <!-- Read Now Modal for logged-in users -->
                <?php if (!empty($currentUser) && !empty($b['attachment'])): ?>
                  <div class="modal fade" id="readModal<?php echo (int)$b['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title"><?php echo htmlspecialchars($b['title']); ?></h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <iframe src="<?php echo htmlspecialchars($b['attachment']); ?>" style="width:100%;height:80vh;" frameborder="0"></iframe>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</main>

<!-- login & logout modals are provided by shared header include -->


<script>
// Ensure genre pages set the return URL for guests when they click action buttons
document.querySelectorAll('.genre-login-btn').forEach(btn => {
  btn.addEventListener('click', function(e){
    const bookId = this.getAttribute('data-book-id');
    const action = this.getAttribute('data-action');
    if (!bookId || !action) return;
    setSharedReturnForAction(action, bookId);
  });
});
</script>

</body>
</html>
