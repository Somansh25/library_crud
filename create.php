<?php
require_once 'config.php';

$errors = [];
$title = $author = $isbn = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. CSRF Guard Validation Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Security Exception: CSRF token validation signature mismatch.");
    }

    $title  = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $isbn   = trim($_POST['isbn'] ?? '');
    $cover_image_name = null;

    if (empty($title) || empty($author) || empty($isbn)) {
        $errors[] = "All text field logging parameters are required.";
    }
    if (!empty($isbn) && !preg_match('/^[0-9]{13}$/', $isbn)) {
        $errors[] = "The ISBN identifier must be exactly 13 numeric digits.";
    }

    // 2. Cryptographically Protected Secure File Upload Management Loop
    if (isset($_FILES['cover']) && $_FILES['cover']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['cover']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "File upload processing system encountered an error code: " . $_FILES['cover']['error'];
        } else {
            $file_tmp   = $_FILES['cover']['tmp_name'];
            $file_name  = $_FILES['cover']['name'];
            $file_size  = $_FILES['cover']['size'];

            if ($file_size > 2 * 1024 * 1024) {
                $errors[] = "Asset constraint boundary validation error: Image size must be under 2MB.";
            }

            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png'];

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file_tmp);
            finfo_close($finfo);
            $allowed_mimes = ['image/jpeg', 'image/png', 'image/pjpeg', 'image/x-png'];

            if (!in_array($ext, $allowed_exts) || !in_array($mime_type, $allowed_mimes)) {
                $errors[] = "Security Type Violation: Only valid JPG, JPEG, and PNG graphic formats are permitted.";
            }

            if (empty($errors)) {
                if (!is_dir('uploads')) {
                    mkdir('uploads', 0755, true);
                }
                // Obfuscate the filename entirely to mitigate directory execution or scanning strategies
                $cover_image_name = bin2hex(random_bytes(16)) . '.' . $ext;
                if (!move_uploaded_file($file_tmp, 'uploads/' . $cover_image_name)) {
                    $errors[] = "Internal server IO error finalizing file writing routine.";
                    $cover_image_name = null;
                }
            }
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO books (title, author, isbn, cover_image) VALUES (:title, :author, :isbn, :cover)");
            $stmt->execute([
                'title'  => $title,
                'author' => $author,
                'isbn'   => $isbn,
                'cover'  => $cover_image_name
            ]);

            $_SESSION['flash_message'] = "New entry configuration successfully appended to systemic catalog registries.";
            $_SESSION['flash_type']    = "success";
            header("Location: index.php");
            exit;
        } catch (\PDOException $e) {
            if ($cover_image_name && file_exists('uploads/' . $cover_image_name)) {
                unlink('uploads/' . $cover_image_name); // Revert disk footprint on transactional rollback failures
            }
            if ($e->getCode() == 23000) {
                $errors[] = "Operational conflict: This identical ISBN marker is already registered inside inventory maps.";
            } else {
                $errors[] = "Database ingestion error: " . $e->getMessage();
            }
        }
    }
}

include_once 'templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow border-0">
            <div class="card-header bg-primary text-white py-3 border-0 rounded-top">
                <h5 class="card-title fw-bold m-0"><i class="bi bi-journal-plus me-2"></i>Catalog System Records</h5>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger py-2 small" role="alert">
                        <ul class="mb-0 ps-3"><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="create.php" enctype="multipart/form-data" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Book Volume Title</label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($title) ?>" placeholder="The Alchemist" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Author / Principal Writer</label>
                        <input type="text" name="author" class="form-control" value="<?= htmlspecialchars($author) ?>" placeholder="Paulo Coelho" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">ISBN Code Serial (13 Digits)</label>
                        <input type="text" name="isbn" class="form-control" value="<?= htmlspecialchars($isbn) ?>" placeholder="9780140154078" maxlength="13" pattern="[0-9]{13}" title="Please enter exactly 13 digits." required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold text-secondary small">Book Cover Graphic Asset</label>
                        <input type="file" name="cover" class="form-control" accept=".jpg,.jpeg,.png">
                        <div class="form-text text-muted small">Allowed extensions: JPG, JPEG, PNG. Max File Size: 2MB.</div>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary py-2 fw-semibold"><i class="bi bi-cloud-arrow-up-fill me-1"></i>Commit Inventory Data</button>
                        <a href="index.php" class="btn btn-light border py-2 text-muted small">Return to Catalog</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once 'templates/footer.php'; ?>