<?php
/**
 * Confirm and process contact deletion. GET only displays the confirmation.
 */
declare(strict_types=1);
require_once __DIR__ . '/config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    render_header('Contact not found');
    echo '<section class="panel message-panel"><h1>Contact not found</h1><p class="muted">The contact ID is missing or invalid.</p><a class="button button-primary" href="index.php">Back to contacts</a></section>';
    render_footer();
    exit;
}

$statement = $pdo->prepare('SELECT id, name, email, phone, group_name, notes, created_at FROM contacts WHERE id = :id');
$statement->execute([':id' => $id]);
$contact = $statement->fetch();

if (!$contact) {
    render_header('Contact not found');
    echo '<section class="panel message-panel"><h1>Contact not found</h1><p class="muted">This contact may have already been deleted.</p><a class="button button-primary" href="index.php">Back to contacts</a></section>';
    render_footer();
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = 'Your session token is invalid or expired. Please reload this page and try again.';
    } else {
        try {
            $delete = $pdo->prepare('DELETE FROM contacts WHERE id = :id');
            $delete->execute([':id' => $id]);

            if ($delete->rowCount() > 0) {
                set_flash('success', 'Contact deleted successfully.');
            } else {
                set_flash('warning', 'The contact was already deleted.');
            }
            redirect('index.php');
        } catch (PDOException $exception) {
            error_log('Contact delete failed: ' . $exception->getMessage());
            $error = 'The contact could not be deleted. Please try again.';
        }
    }
}

render_header('Delete contact');
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">ADDRESS BOOK</p>
        <h1>Delete contact</h1>
        <p class="muted">Please confirm before removing this person.</p>
    </div>
    <a class="button button-secondary" href="index.php">← Cancel</a>
</section>
<?php if ($error !== null): ?><div class="flash flash-error" role="alert"><?= e($error) ?></div><?php endif; ?>
<section class="panel confirmation-panel">
    <div class="warning-icon">!</div>
    <h2>Are you sure you want to delete this contact?</h2>
    <p class="muted">This action permanently removes the contact from your address book and cannot be undone.</p>
    <dl class="contact-details">
        <div><dt>Name</dt><dd><?= e($contact['name']) ?></dd></div>
        <div><dt>Email</dt><dd><?= e($contact['email'] ?: '—') ?></dd></div>
        <div><dt>Phone</dt><dd><?= e($contact['phone'] ?: '—') ?></dd></div>
        <div><dt>Group</dt><dd><?= e($contact['group_name']) ?></dd></div>
        <?php if (!empty($contact['notes'])): ?><div><dt>Notes</dt><dd><?= nl2br(e($contact['notes'])) ?></dd></div><?php endif; ?>
    </dl>
    <form method="post" action="delete.php?id=<?= (int)$id ?>" class="form-actions">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <button class="button button-danger" type="submit">Yes, delete contact</button>
        <a class="button button-ghost" href="index.php">Keep contact</a>
    </form>
</section>
<?php render_footer(); ?>
