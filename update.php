<?php
require_once 'config.php';

$id = $_GET['id'] ?? null;
if (!$id || !ctype_digit((string) $id)) {
    header("Location: index.php");
    exit;
}

$id = (int) $id;

try {
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$id]);
    $book = $stmt->fetch();
    if (!$book) {
        header("Location: index.php");
        exit;
    }
} catch (\PDOException $e) {
    die("Database transaction failure: " . $e->getMessage());
}

$errors = [];
$title = $book['title'];
$author = $book['author'];
$isbn = $book['isbn'];
$cover_image_name = $book['cover_image'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Security Exception: CSRF token validation signature mismatch.");
    }

    $title  = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $isbn   = trim($_POST['isbn'] ?? '');

    if (empty($title) || empty($author) || empty($isbn)) {
        $errors[] = "All parameter log fields are required.";
    }
    if (!empty($isbn) && !preg_match('/^[0-9]{13}$/', $isbn)) {
        $errors[] = "The ISBN identifier must be exactly 13 numeric digits.";
    }

    if (isset($_FILES['cover']) && $_FILES['cover']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['cover']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "File error code discovered on parsing execution blocks: " . $_FILES['cover']['error'];
        } else {
            $file_tmp   = $_FILES['cover']['tmp_name'];
            $file_name  = $_FILES['cover']['name'];
            $file_size  = $_FILES['cover']['size'];

            if ($file_size > 2 * 1024 * 1024) {
                $errors[] = "Image asset constraints boundary violation: Must be under 2MB.";
            }

            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png'];

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file_tmp);
            finfo_close($finfo);
            $allowed_mimes = ['image/jpeg', 'image/png', 'image/pjpeg', 'image/x-png'];

            if (!in_array($ext, $allowed_exts) || !in_array($mime_type, $allowed_mimes)) {
                $errors[] = "Security Violation: Only valid JPG, JPEG, and PNG formats are permitted.";
            }

            if (empty($errors)) {
                $new_filename = bin2hex(random_bytes(16)) . '.' . $ext;
                if (move_uploaded_file($file_tmp, 'uploads/' . $new_filename)) {
                    // Unlink old asset from disk to prevent unreferenced data buildup
                    if (!empty($book['cover_image']) && file_exists('uploads/' . $book['cover_image'])) {
                        unlink('uploads/' . $book['cover_image']);
                    }
                    $cover_image_name = $new_filename;
                } else {
                    $errors[] = "Internal server IO error writing file to location.";
                }
            }
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE books SET title = :title, author = :author, isbn = :isbn, cover_image = :cover WHERE id = :id");
            $stmt->execute([
                'title'  => $title,
                'author' => $author,
                'isbn'   => $isbn,
                'cover'  => $cover_image_name,
                'id'     => $id
            ]);

            $_SESSION['flash_message'] = "Catalog specifications modified successfully.";
            $_SESSION['flash_type']    = "success";
            header("Location: index.php");
            exit;
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                $errors[] = "Operational conflict: This identical ISBN marker is already registered inside inventory maps.";
            } else {
                $errors[] = "Modification processing exception error occurred: " . $e->getMessage();
            }
        }
    }
}

include_once 'templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow border-0">
            <div class="card-header bg-dark text-white py-3 border-0 rounded-top bg-dark-indigo">
                <h5 class="card-title fw-bold m-0"><i class="bi bi-pencil-square me-2"></i>Modify Asset Specifications</h5>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger py-2 small" role="alert">
                        <ul class="mb-0 ps-3"><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="update.php?id=<?= urlencode($id) ?>" enctype="multipart/form-data" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Volume Title</label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($title) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Author / Writer</label>
                        <input type="text" name="author" class="form-control" value="<?= htmlspecialchars($author) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">ISBN Mapping Code (13 Digits)</label>
                        <input type="text" name="isbn" class="form-control" value="<?= htmlspecialchars($isbn) ?>" maxlength="13" pattern="[0-9]{13}" title="Please enter exactly 13 digits." required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold text-secondary small">Replace Cover Image (Optional)</label>
                        <input type="file" name="cover" class="form-control" accept=".jpg,.jpeg,.png">
                        <?php if (!empty($book['cover_image'])): ?>
                            <div class="form-text text-primary small">An active cover graphic configuration is currently saved for this item.</div>
                        <?php endif; ?>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-warning text-dark py-2 fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Save Book Alterations</button>
                        <a href="index.php" class="btn btn-light border py-2 text-muted small">Abort Update Process</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once 'templates/footer.php'; ?>