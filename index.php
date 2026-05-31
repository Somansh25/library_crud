<?php
require_once 'config.php';

try {
    $stmt = $pdo->query("SELECT * FROM books ORDER BY id DESC");
    $books = $stmt->fetchAll();
} catch (\PDOException $e) {
    die("Error fetching catalog records: " . $e->getMessage());
}

include_once 'templates/header.php';
?>

<div class="row align-items-center mb-4">
    <div class="col-md-6">
        <h2 class="text-secondary fw-bold m-0">Book Inventory Registry</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="create.php" class="btn btn-primary shadow-sm px-4">
            <i class="bi bi-journal-plus me-2"></i>Add New Book
        </a>
    </div>
</div>

<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="alert alert-<?= htmlspecialchars($_SESSION['flash_type']) ?> alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-info-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['flash_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php 
    unset($_SESSION['flash_message']); 
    unset($_SESSION['flash_type']); 
    ?>
<?php endif; ?>

<div class="card shadow-sm border-0 overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-dark text-uppercase small">
                    <tr>
                        <th class="ps-4 py-3">Sr. No</th>
                        <th class="py-3">Cover</th>
                        <th class="py-3">Book Title</th>
                        <th class="py-3">Author / Writer</th>
                        <th class="py-3">ISBN Identifier</th>
                        <th class="py-3">Cataloging Date</th>
                        <th class="text-center pe-4 py-3">Management Controls</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($books) > 0): ?>
                        <?php $serialNumber = 1; ?>
                        <?php foreach ($books as $book): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-muted">#<?= $serialNumber++ ?></td>
                                <td>
                                    <?php if (!empty($book['cover_image']) && file_exists('uploads/' . $book['cover_image'])): ?>
                                        <img src="uploads/<?= htmlspecialchars($book['cover_image']) ?>" class="thumbnail-cover border shadow-sm" alt="Cover">
                                    <?php else: ?>
                                        <div class="placeholder-cover border"><i class="bi bi-image small"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td><strong class="text-dark"><?= htmlspecialchars($book['title']) ?></strong></td>
                                <td class="text-muted"><?= htmlspecialchars($book['author']) ?></td>
                                <td><span class="badge bg-secondary-subtle text-secondary-emphasis border font-monospace"><?= htmlspecialchars($book['isbn']) ?></span></td>
                                <td class="text-muted small"><?= date('M d, Y | h:i A', strtotime($book['created_at'])) ?></td>
                                <td class="text-center pe-4">
                                    <div class="btn-group" role="group">
                                        <a href="update.php?id=<?= urlencode($book['id']) ?>" class="btn btn-sm btn-outline-primary px-3">
                                            <i class="bi bi-pencil-square me-1"></i>Edit
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger px-3" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteConfirmationModal" 
                                                data-id="<?= htmlspecialchars($book['id']) ?>" 
                                                data-title="<?= htmlspecialchars($book['title']) ?>">
                                            <i class="bi bi-trash3-fill me-1"></i>Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="bi bi-book display-4 text-muted opacity-50 d-block mb-3"></i>
                                <h5 class="text-secondary fw-semibold">No catalog entries discovered</h5>
                                <a href="create.php" class="btn btn-sm btn-success px-4 mt-2 shadow-sm">Catalog First Book</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form id="deleteRecordForm" method="POST" action="delete.php">
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Remove Asset Confirmation</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <p class="m-0 text-muted">Are you certain you want to purge this record and its cover asset from the system?</p>
                    <strong id="targetAssetTitle" class="text-dark d-block mt-2 bg-light p-2 rounded border border-danger-subtle"></strong>
                </div>
                <input type="hidden" name="id" id="deleteRecordId" value="">
                <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <div class="modal-footer bg-light border-top-0">
                    <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4">Delete Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var deleteModal = document.getElementById('deleteConfirmationModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var title = button.getAttribute('data-title');
            deleteModal.querySelector('#targetAssetTitle').textContent = title;
            deleteModal.querySelector('#deleteRecordId').value = id;
        });
    }
});
</script>

<?php include_once 'templates/footer.php'; ?>