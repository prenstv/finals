<?php
// Shared header include: expects session already started in calling file.
// It will use existing $conn if present; otherwise it will open a short-lived connection.

if (!isset($conn) || !($conn instanceof mysqli)) {
  $localConn = new mysqli("localhost", "root", "", "book_library");
  if ($localConn->connect_error) {
    $conn = null;
  } else {
    $conn = $localConn;
  }
} else {
  $localConn = null;
}

$currentUser = null;
$listCount = 0;
if (!empty($_SESSION['user_id']) && $conn instanceof mysqli) {
  $uid = (int)$_SESSION['user_id'];
  $currentUser = $conn->query("SELECT id, username, profile_picture FROM users WHERE id=$uid")->fetch_assoc() ?: null;
  $lc = $conn->query("SELECT COUNT(*) AS total FROM reading_list WHERE user_id=$uid");
  $listCount = $lc ? ($lc->fetch_assoc()['total'] ?? 0) : 0;
}
?>

<!-- Shared Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-danger">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="home.php">
      <img src="uploads/logo.png" alt="Logo" style="height:40px; width:auto;" class="me-2">
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="home.php">🏠 Home</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="genreDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">📚 Genre</a>
          <ul class="dropdown-menu" aria-labelledby="genreDropdown">
            <?php
            $genres = [];
            if (isset($conn) && $conn instanceof mysqli) {
              $gRes = $conn->query("SELECT DISTINCT genre FROM books ORDER BY genre ASC");
              if ($gRes) {
                while ($g = $gRes->fetch_assoc()) {
                  $genres[] = $g['genre'];
                }
              }
            }
            foreach ($genres as $g) {
              echo '<li><a class="dropdown-item genre-link" href="genre.php?genre=' . urlencode($g) . '" data-genre="' . htmlspecialchars($g) . '">' . htmlspecialchars($g) . '</a></li>';
            }
            ?>
          </ul>
        </li>
        <li class="nav-item"><a class="nav-link" href="about.php">ℹ️ About Us</a></li>
        <li class="nav-item"><a class="nav-link" href="contact.php">✉️ Contact Us</a></li>
      </ul>

      <?php if (!empty($currentUser)): ?>
        <div class="nav-item dropdown">
          <button class="nav-link dropdown-toggle d-flex align-items-center text-white border-0 bg-transparent" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="<?php echo !empty($currentUser['profile_picture']) ? htmlspecialchars($currentUser['profile_picture']) : 'uploads/profiles/default.png'; ?>" alt="Profile" class="rounded-circle" style="width:36px;height:36px;object-fit:cover;">
            <span class="ms-2 d-none d-md-inline"><?php echo htmlspecialchars($currentUser['username']); ?></span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
            <li class="dropdown-header px-3"><strong><?php echo htmlspecialchars($currentUser['username']); ?></strong></li>
            <li><a class="dropdown-item" href="gallery.php?user=<?php echo urlencode($currentUser['username']); ?>"><?php echo htmlspecialchars($currentUser['username']); ?> Gallery</a></li>
            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
            <li><a class="dropdown-item d-flex justify-content-between align-items-center" href="my_reading_list.php">My Reading List <span class="badge bg-dark"><?php echo (int)$listCount; ?></span></a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger logout-link" href="logout.php">Logout</a></li>
          </ul>
        </div>
      <?php else: ?>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#loginModal">📖 Read Now</button>
      <?php endif; ?>

    </div>
  </div>
</nav>

<!-- Login Modal -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="loginModalLabel">Login</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" action="login.php" id="sharedLoginForm">
          <?php $initialReturn = $_GET['return_url'] ?? $_SERVER['REQUEST_URI']; ?>
          <input type="hidden" name="return_url" id="sharedReturnUrl" value="<?php echo htmlspecialchars($initialReturn); ?>">
          <div class="mb-3"><label class="form-label">Username</label><input type="text" name="username" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
          <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
      </div>
      <div class="modal-footer d-flex justify-content-between">
        <a href="register.php" id="sharedRegisterLink" class="btn btn-link">Don’t have an account? Register</a>
        <a href="forgot_password.php" class="btn btn-link">Forgot Password?</a>
      </div>
    </div>
  </div>
</div>

<!-- Logout confirmation modal -->
<div class="modal fade" id="logoutConfirmModal" tabindex="-1" aria-labelledby="logoutConfirmLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="logoutConfirmLabel">Confirm Logout</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">Are you sure you want to log out?</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmLogoutBtn">Logout</button>
      </div>
    </div>
  </div>
</div>

<!-- Genre Books Modal -->
<div class="modal fade" id="genreBooksModal" tabindex="-1" aria-labelledby="genreBooksModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="genreBooksModalLabel">Books in Genre</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="genreBooksContainer">
        <!-- Books will be loaded here -->
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// When any element wants to set a return URL (example: cover clicks), set the shared input here.
function setSharedReturnUrl(val){
  const el = document.getElementById('sharedReturnUrl'); if(el) el.value = val;
}

// Set a return URL that returns the user to the current page and includes an auto action.
function setSharedReturnForAction(action, bookId){
  try{
    const u = new URL(window.location.href);
    u.searchParams.set('auto_action', action);
    u.searchParams.set('auto_book_id', bookId);
    // set only the path+search so login.php redirect works with relative path
    setSharedReturnUrl(u.pathname + u.search);
  }catch(e){
    // fallback: basic path
    setSharedReturnUrl(window.location.pathname + '?auto_action=' + encodeURIComponent(action) + '&auto_book_id=' + encodeURIComponent(bookId));
  }
}

// Update register link to include return_url when login modal opens
const sharedRegisterLink = document.getElementById('sharedRegisterLink');
const sharedLoginModalEl = document.getElementById('loginModal');
if (sharedLoginModalEl) {
  sharedLoginModalEl.addEventListener('show.bs.modal', function () {
    const returnInput = document.getElementById('sharedReturnUrl');
    if (sharedRegisterLink && returnInput) {
      let url = 'register.php';
      if (returnInput.value) url += '?return_url=' + encodeURIComponent(returnInput.value);
      sharedRegisterLink.href = url;
    }
  });
}

// Intercept logout links and show a Bootstrap confirmation modal
document.addEventListener('click', function(e){
  const link = e.target.closest && e.target.closest('.logout-link');
  if (!link) return;
  e.preventDefault();
  const href = link.getAttribute('href');
  const logoutModalEl = document.getElementById('logoutConfirmModal');
  const logoutModal = new bootstrap.Modal(logoutModalEl);
  const confirmBtn = document.getElementById('confirmLogoutBtn');
  confirmBtn.onclick = function(){ window.location.href = href; };
  logoutModal.show();
});
</script>

<script>
// Auto-action runner: on pages that include this header, detect auto_action params
document.addEventListener('DOMContentLoaded', function(){
  try{
    const params = new URLSearchParams(window.location.search);
    const action = params.get('auto_action');
    const bookId = params.get('auto_book_id');
    if (!action || !bookId) return;

    // After handling, remove params from URL to avoid re-running on refresh
    const cleanUrl = window.location.pathname + (function(){
      const s = new URLSearchParams(window.location.search);
      s.delete('auto_action'); s.delete('auto_book_id');
      const qs = s.toString(); return qs ? ('?' + qs) : '';
    })();

    if (action === 'read_later'){
      fetch('read_later_ajax.php', { method: 'POST', body: new URLSearchParams({ book_id: bookId }), headers: { 'X-Requested-With':'XMLHttpRequest' } })
        .then(r=>r.json()).then(data=>{
          if (data && data.success){
            document.querySelectorAll('.add-reading-list-btn[data-book-id="'+bookId+'"]').forEach(b=>{
              b.disabled=true; b.classList.remove('btn-outline-secondary'); b.classList.add('btn-success'); b.innerHTML='✓ In Reading List';
            });
            // try update badge
            const badge = document.querySelector('.dropdown-menu a[href="my_reading_list.php"] .badge') || document.querySelector('.nav .badge');
            if (badge && !isNaN(parseInt(badge.textContent))) badge.textContent = parseInt(badge.textContent) + 1;
          }
          history.replaceState({}, document.title, cleanUrl);
        }).catch(()=> history.replaceState({}, document.title, cleanUrl));
    } else if (action === 'add_to_gallery'){
      fetch('add_to_gallery_ajax.php', { method: 'POST', body: new URLSearchParams({ book_id: bookId }), headers: { 'X-Requested-With':'XMLHttpRequest' } })
        .then(r=>r.json()).then(data=>{
          if (data && data.success){
            // Update legacy form buttons (if any) and new AJAX buttons
            document.querySelectorAll('form[action="add_to_gallery.php"] input[name="book_id"][value="'+bookId+'"]').forEach(input=>{
              const btn = input.closest('form').querySelector('button');
              if (btn){ btn.disabled=true; btn.classList.remove('btn-outline-success'); btn.classList.add('btn-success'); btn.innerHTML='✓ In Gallery'; }
            });
            document.querySelectorAll('.add-gallery-btn[data-book-id="'+bookId+'"]').forEach(b=>{
              b.disabled = true; b.classList.remove('btn-outline-success'); b.classList.add('btn-success'); b.innerHTML = '✓ In Gallery';
            });
          }
          history.replaceState({}, document.title, cleanUrl);
        }).catch(()=> history.replaceState({}, document.title, cleanUrl));
    } else if (action === 'read_now'){
      // open modal for the book if present on page
      const modalEl = document.getElementById('readNowModal' + bookId) || document.getElementById('readModal' + bookId) || null;
      if (modalEl){
        const m = new bootstrap.Modal(modalEl); m.show();
      }
      history.replaceState({}, document.title, cleanUrl);
    }
  }catch(e){ /* ignore */ }
});
</script>

<script>
// Handle Add to Reading List buttons via AJAX site-wide
document.addEventListener('click', function(e){
  const btn = e.target.closest && e.target.closest('.add-reading-list-btn');
  if (!btn) return;
  e.preventDefault();
  const bookId = btn.getAttribute('data-book-id');
  if (!bookId) return;

  // optimistic UI: disable while working
  const allBtns = document.querySelectorAll('.add-reading-list-btn[data-book-id="' + bookId + '"]');
  allBtns.forEach(b => { b.disabled = true; b.classList.add('opacity-75'); });

  fetch('read_later_ajax.php', {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: new URLSearchParams({ book_id: bookId })
  }).then(r => r.json()).then(data => {
    if (data && data.success) {
      // mark all matching buttons as added
      allBtns.forEach(b => {
        b.disabled = true;
        b.classList.remove('btn-outline-secondary');
        b.classList.add('btn-success');
        b.innerHTML = '✓ In Reading List';
      });
      // update reading list badge count in header if present
      try {
        const badge = document.querySelector('.dropdown-item.d-flex .badge') || document.querySelector('.nav .badge');
        if (badge && !isNaN(parseInt(badge.textContent))) {
          const v = parseInt(badge.textContent) + 1;
          badge.textContent = v;
        }
      } catch (e) {}
      // redirect to reading list page
      window.location.href = 'my_reading_list.php';
    } else {
      // revert and show minimal feedback
      allBtns.forEach(b => { b.disabled = false; b.classList.remove('opacity-75'); });
      if (data && data.error === 'not_logged_in') {
        // open login modal and set return URL with auto-action so it runs after login
        setSharedReturnForAction('read_later', bookId);
        var loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
        loginModal.show();
      } else {
        alert('Could not add to reading list.');
      }
    }
  }).catch(err => {
    allBtns.forEach(b => { b.disabled = false; b.classList.remove('opacity-75'); });
    alert('Network error. Please try again.');
  });
});
</script>

<script>
// Handle Add to Gallery AJAX button site-wide
document.addEventListener('click', function(e){
  const btn = e.target.closest && e.target.closest('.add-gallery-btn');
  if (!btn) return;
  e.preventDefault();
  const bookId = btn.getAttribute('data-book-id');
  if (!bookId) return;

  const allBtns = document.querySelectorAll('.add-gallery-btn[data-book-id="' + bookId + '"]');
  allBtns.forEach(b => { b.disabled = true; b.classList.add('opacity-75'); });

  fetch('add_to_gallery_ajax.php', {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: new URLSearchParams({ book_id: bookId })
  }).then(r => r.json()).then(data => {
    if (data && data.success) {
      allBtns.forEach(b => {
        b.disabled = true;
        b.classList.remove('btn-outline-success');
        b.classList.add('btn-success');
        b.innerHTML = '✓ In Gallery';
        b.classList.remove('opacity-75');
      });
    } else {
      allBtns.forEach(b => { b.disabled = false; b.classList.remove('opacity-75'); });
      if (data && data.error === 'not_logged_in') {
        setSharedReturnForAction('add_to_gallery', bookId);
        var loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
        loginModal.show();
      } else if (data && data.exists) {
        allBtns.forEach(b => { b.disabled = true; b.classList.remove('btn-outline-success'); b.classList.add('btn-success'); b.innerHTML = '✓ In Gallery'; });
      } else {
        alert('Could not add to gallery.');
      }
    }
  }).catch(err => {
    allBtns.forEach(b => { b.disabled = false; b.classList.remove('opacity-75'); });
    alert('Network error. Please try again.');
  });
});
</script>



<?php
// close transient connection if we opened one
if (isset($localConn) && $localConn instanceof mysqli) {
  $localConn->close();
}
?>
