<?php
session_start();
// Contact Us page
$conn = new mysqli("localhost", "root", "", "book_library");
if ($conn->connect_error) {
  $conn = null;
}

$contactCurrentUser = null;
if (!empty($_SESSION['user_id']) && $conn instanceof mysqli) {
  $uid = (int)$_SESSION['user_id'];
  $contactCurrentUser = $conn->query("SELECT id, username, email, profile_picture FROM users WHERE id=$uid")->fetch_assoc() ?: null;
}

// Handle form submission
$successMessage = '';
$errorMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $subject = trim($_POST['subject'] ?? '');
  $message = trim($_POST['message'] ?? '');
  
  if (empty($name) || empty($email) || empty($subject) || empty($message)) {
    $errorMessage = 'All fields are required.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errorMessage = 'Please enter a valid email address.';
  } else {
    // In a real application, you would save this to a database or send an email
    // For now, we'll just show a success message
    $successMessage = 'Thank you for contacting us! We will get back to you within 24-48 hours.';
    
    // Optional: Save to database (you would need to create a contacts table)
    // $stmt = $conn->prepare("INSERT INTO contacts (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())");
    // $stmt->bind_param("ssss", $name, $email, $subject, $message);
    // $stmt->execute();
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Contact Us - My Book Gallery</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <style>
    body { display: flex; flex-direction: column; min-height: 100vh; }
    footer { margin-top: auto; }
    .hero-section { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 80px 0; }
    .contact-card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
    .contact-card:hover { transform: translateY(-5px); box-shadow: 0 12px 28px rgba(0,0,0,0.15); }
    .contact-icon { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 1rem; }
    .faq-item { border-left: 4px solid #667eea; transition: all 0.3s ease; }
    .faq-item:hover { border-left-color: #764ba2; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .social-icon { width: 50px; height: 50px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 1.25rem; transition: all 0.3s ease; text-decoration: none; }
    .social-icon:hover { transform: translateY(-3px); box-shadow: 0 6px 16px rgba(0,0,0,0.2); }
    .form-control:focus, .form-select:focus { border-color: #667eea; box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25); }
    .text-hover:hover { color:#fff !important; transition:color .3s ease; }
  </style>
</head>
<body class="bg-light">

<!-- Shared header include -->
<?php require_once __DIR__ . '/inc/header.php'; ?>

<!-- Hero Section -->
<section class="hero-section">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-8 mx-auto text-center">
        <h1 class="display-4 fw-bold mb-4">Get In Touch</h1>
        <p class="lead mb-4">Have a question, suggestion, or just want to say hello? We'd love to hear from you! Our team is here to help and typically responds within 24-48 hours.</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
          <a href="#contact-form" class="btn btn-light btn-lg">Send a Message</a>
          <a href="#faq-section" class="btn btn-outline-light btn-lg">View FAQ</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Contact Information Cards -->
<section class="py-5 bg-white">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card contact-card h-100 border-0 shadow-sm">
          <div class="card-body p-4 text-center">
            <div class="contact-icon bg-primary bg-opacity-10 text-primary">
              <i class="bi bi-envelope-fill"></i>
            </div>
            <h5 class="fw-bold mb-3">Email Us</h5>
            <p class="text-muted mb-2">For general inquiries and support</p>
            <a href="mailto:support@mybookgallery.com" class="text-decoration-none">support@mybookgallery.com</a>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card contact-card h-100 border-0 shadow-sm">
          <div class="card-body p-4 text-center">
            <div class="contact-icon bg-success bg-opacity-10 text-success">
              <i class="bi bi-chat-dots-fill"></i>
            </div>
            <h5 class="fw-bold mb-3">Live Chat</h5>
            <p class="text-muted mb-2">Available Mon-Fri, 9AM-5PM EST</p>
            <a href="#" class="text-decoration-none" onclick="alert('Live chat feature coming soon!'); return false;">Start Chat</a>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card contact-card h-100 border-0 shadow-sm">
          <div class="card-body p-4 text-center">
            <div class="contact-icon bg-warning bg-opacity-10 text-warning">
              <i class="bi bi-geo-alt-fill"></i>
            </div>
            <h5 class="fw-bold mb-3">Visit Us</h5>
            <p class="text-muted mb-2">123 Library Street<br>Book City, BC 12345</p>
            <a href="#" class="text-decoration-none" onclick="alert('Opening Google Maps...'); return false;">Get Directions</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Contact Form Section -->
<section class="py-5 bg-light" id="contact-form">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="card border-0 shadow-lg">
          <div class="card-body p-5">
            <div class="text-center mb-4">
              <h2 class="fw-bold mb-3">Send Us a Message</h2>
              <p class="text-muted">Fill out the form below and we'll get back to you as soon as possible.</p>
            </div>

            <?php if ($successMessage): ?>
              <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($successMessage); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($errorMessage); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            <?php endif; ?>

            <form method="POST" action="contact.php">
              <div class="row g-3">
                <div class="col-md-6">
                  <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="name" name="name" 
                         value="<?php echo isset($contactCurrentUser) ? htmlspecialchars($contactCurrentUser['username']) : ''; ?>" 
                         required placeholder="John Doe">
                </div>
                <div class="col-md-6">
                  <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                  <input type="email" class="form-control" id="email" name="email" 
                         value="<?php echo isset($contactCurrentUser) ? htmlspecialchars($contactCurrentUser['email']) : ''; ?>" 
                         required placeholder="john@example.com">
                </div>
                <div class="col-12">
                  <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                  <select class="form-select" id="subject" name="subject" required>
                    <option value="">Choose a subject...</option>
                    <option value="General Inquiry">General Inquiry</option>
                    <option value="Technical Support">Technical Support</option>
                    <option value="Book Request">Book Request</option>
                    <option value="Report a Problem">Report a Problem</option>
                    <option value="Partnership Opportunity">Partnership Opportunity</option>
                    <option value="Feedback">Feedback</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
                <div class="col-12">
                  <label for="message" class="form-label">Message <span class="text-danger">*</span></label>
                  <textarea class="form-control" id="message" name="message" rows="6" required 
                            placeholder="Tell us how we can help you..."></textarea>
                  <div class="form-text">Please provide as much detail as possible.</div>
                </div>
                <div class="col-12">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter">
                    <label class="form-check-label" for="newsletter">
                      Subscribe to our newsletter for updates and new book releases
                    </label>
                  </div>
                </div>
                <div class="col-12">
                  <button type="submit" name="submit_contact" class="btn btn-primary btn-lg w-100">
                    <i class="bi bi-send-fill me-2"></i>Send Message
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FAQ Section -->
<section class="py-5 bg-white" id="faq-section">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="display-5 fw-bold mb-3">Frequently Asked Questions</h2>
      <p class="lead text-muted">Quick answers to common questions</p>
    </div>
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="accordion" id="faqAccordion">
          <div class="accordion-item border-0 mb-3 shadow-sm faq-item">
            <h2 class="accordion-header">
              <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                <i class="bi bi-question-circle-fill me-2 text-primary"></i>
                How do I create an account?
              </button>
            </h2>
            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
              <div class="accordion-body">
                Creating an account is easy! Click the "Read Now" button in the navigation bar, then select "Don't have an account? Register" at the bottom of the login modal. Fill in your details, and you'll be ready to start building your personal library.
              </div>
            </div>
          </div>

          <div class="accordion-item border-0 mb-3 shadow-sm faq-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                <i class="bi bi-question-circle-fill me-2 text-primary"></i>
                Are all books really free?
              </button>
            </h2>
            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body">
                Yes! All books in our library are 100% free to read and download. We believe in making literature accessible to everyone. There are no hidden fees, subscriptions, or premium tiers.
              </div>
            </div>
          </div>

          <div class="accordion-item border-0 mb-3 shadow-sm faq-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                <i class="bi bi-question-circle-fill me-2 text-primary"></i>
                Can I download books to read offline?
              </button>
            </h2>
            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body">
                Absolutely! Once you're logged in, you can download any book in PDF format to read offline on any device. Just click the download button on the book's detail page.
              </div>
            </div>
          </div>

          <div class="accordion-item border-0 mb-3 shadow-sm faq-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                <i class="bi bi-question-circle-fill me-2 text-primary"></i>
                How do I add books to my reading list?
              </button>
            </h2>
            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body">
                When browsing books, simply click the "Add to Reading List" button on any book card. You can access your reading list anytime from the user menu in the top right corner.
              </div>
            </div>
          </div>

          <div class="accordion-item border-0 mb-3 shadow-sm faq-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                <i class="bi bi-question-circle-fill me-2 text-primary"></i>
                Can I request a specific book?
              </button>
            </h2>
            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body">
                Yes! Use the contact form above and select "Book Request" as the subject. Let us know which book you're looking for, and we'll do our best to add it to our collection if it's available in the public domain.
              </div>
            </div>
          </div>

          <div class="accordion-item border-0 mb-3 shadow-sm faq-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                <i class="bi bi-question-circle-fill me-2 text-primary"></i>
                How do I reset my password?
              </button>
            </h2>
            <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body">
                Click the "Forgot Password?" link on the login modal. Enter your email address, and we'll send you instructions to reset your password.
              </div>
            </div>
          </div>

          <div class="accordion-item border-0 mb-3 shadow-sm faq-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">
                <i class="bi bi-question-circle-fill me-2 text-primary"></i>
                Is my personal information secure?
              </button>
            </h2>
            <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body">
                Yes, we take your privacy seriously. We use industry-standard security measures to protect your data. We never share your personal information with third parties without your consent.
              </div>
            </div>
          </div>

          <div class="accordion-item border-0 mb-3 shadow-sm faq-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq8">
                <i class="bi bi-question-circle-fill me-2 text-primary"></i>
                Can I share my gallery with others?
              </button>
            </h2>
            <div id="faq8" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body">
                Yes! Your gallery has a unique URL that you can share with friends and family. They can view your collection and discover new books through your recommendations.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Social Media & Additional Contact -->
<section class="py-5 bg-light">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="display-5 fw-bold mb-3">Connect With Us</h2>
      <p class="lead text-muted">Follow us on social media for updates, book recommendations, and more</p>
    </div>
    <div class="row justify-content-center">
      <div class="col-auto">
        <div class="d-flex gap-3 flex-wrap justify-content-center">
          <a href="#" class="social-icon bg-primary text-white" title="Facebook" onclick="alert('Facebook page coming soon!'); return false;">
            <i class="bi bi-facebook"></i>
          </a>
          <a href="#" class="social-icon bg-info text-white" title="Twitter" onclick="alert('Twitter page coming soon!'); return false;">
            <i class="bi bi-twitter"></i>
          </a>
          <a href="#" class="social-icon bg-danger text-white" title="Instagram" onclick="alert('Instagram page coming soon!'); return false;">
            <i class="bi bi-instagram"></i>
          </a>
          <a href="#" class="social-icon bg-dark text-white" title="GitHub" onclick="alert('GitHub repository coming soon!'); return false;">
            <i class="bi bi-github"></i>
          </a>
          <a href="#" class="social-icon bg-success text-white" title="WhatsApp" onclick="alert('WhatsApp contact coming soon!'); return false;">
            <i class="bi bi-whatsapp"></i>
          </a>
          <a href="mailto:support@mybookgallery.com" class="social-icon bg-warning text-white" title="Email">
            <i class="bi bi-envelope-fill"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Office Hours Section -->
<section class="py-5 bg-white">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="card border-0 shadow-lg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
          <div class="card-body p-5 text-white">
            <div class="row align-items-center">
              <div class="col-md-8">
                <h3 class="fw-bold mb-3">Our Support Hours</h3>
                <div class="mb-2">
                  <i class="bi bi-clock-fill me-2"></i>
                  <strong>Monday - Friday:</strong> 9:00 AM - 6:00 PM EST
                </div>
                <div class="mb-2">
                  <i class="bi bi-clock-fill me-2"></i>
                  <strong>Saturday:</strong> 10:00 AM - 4:00 PM EST
                </div>
                <div class="mb-2">
                  <i class="bi bi-clock-fill me-2"></i>
                  <strong>Sunday:</strong> Closed
                </div>
                <p class="mt-3 mb-0 opacity-75">
                  <small>We typically respond to all inquiries within 24-48 hours during business days.</small>
                </p>
              </div>
              <div class="col-md-4 text-center">
                <div class="display-1 mb-3">
                  <i class="bi bi-headset"></i>
                </div>
                <p class="mb-0 fw-bold">24/7 Email Support</p>
              </div>
            </div>
          </div>
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
            <i class="bi bi-geo-alt"></i> 123 Library Street, Book City, BC 12345<br>
            <i class="bi bi-envelope"></i> support@mybookgallery.com<br>
            <i class="bi bi-telephone"></i> +1 (555) 123-4567
          </small>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="col-md-4 mb-4 mb-md-0">
        <h5 class="fw-bold mb-3">Quick Links</h5>
        <ul class="list-unstyled">
          <li class="mb-2"><a href="home.php" class="text-decoration-none text-white-50 text-hover">Home</a></li>
          <li class="mb-2"><a href="genre.php" class="text-decoration-none text-white-50 text-hover">Browse Genres</a></li>
          <li class="mb-2"><a href="<?php echo !empty($contactCurrentUser) ? 'gallery.php' : '#'; ?>" class="text-decoration-none text-white-50 text-hover" <?php echo empty($contactCurrentUser) ? 'onclick="event.preventDefault(); new bootstrap.Modal(document.getElementById(\'loginModal\')).show();"' : ''; ?>>My Gallery</a></li>
          <li class="mb-2"><a href="<?php echo !empty($contactCurrentUser) ? 'my_reading_list.php' : '#'; ?>" class="text-decoration-none text-white-50 text-hover" <?php echo empty($contactCurrentUser) ? 'onclick="event.preventDefault(); new bootstrap.Modal(document.getElementById(\'loginModal\')).show();"' : ''; ?>>Reading List</a></li>
          <li class="mb-2"><a href="<?php echo !empty($contactCurrentUser) ? 'profile.php' : '#'; ?>" class="text-decoration-none text-white-50 text-hover" <?php echo empty($contactCurrentUser) ? 'onclick="event.preventDefault(); new bootstrap.Modal(document.getElementById(\'loginModal\')).show();"' : ''; ?>>Profile</a></li>
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
          <li class="mb-2"><a href="#faq-section" class="text-decoration-none text-white-50 text-hover">FAQ</a></li>
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

<?php if (isset($conn) && $conn instanceof mysqli) $conn->close(); ?>

</body>
</html>
