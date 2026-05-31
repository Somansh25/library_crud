<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$id    = $_POST['id'] ?? null;
$token = $_POST['token'] ?? null;

if (!$id || !ctype_digit((string) $id) || !$token || $token !== $_SESSION['csrf_token']) {
    die("Security Exception Validation Layer Block: Insecure request configuration signature parameters.");
}

$id = (int) $id;

try {
    // 1. Fetch record parameters to handle physical disk file unlinking operations
    $stmt = $pdo->prepare("SELECT cover_image FROM books WHERE id = ?");
    $stmt->execute([$id]);
    $book = $stmt->fetch();

    if ($book) {
        if (!empty($book['cover_image']) && file_exists('uploads/' . $book['cover_image'])) {
            unlink('uploads/' . $book['cover_image']);
        }

        // 2. Clear out relational row indexes from memory records
        $deleteStmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
        $deleteStmt->execute([$id]);

        $_SESSION['flash_message'] = "Selected record context asset dropped from inventory indexes successfully.";
        $_SESSION['flash_type']    = "warning";
    }
} catch (\PDOException $e) {
    $_SESSION['flash_message'] = "Internal system blockages halted execution requests: " . $e->getMessage();
    $_SESSION['flash_type']    = "danger";
}

header("Location: index.php");
exit;
?>