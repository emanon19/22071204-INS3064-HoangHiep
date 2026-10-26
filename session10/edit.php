<?php
/**
 * Update an existing contact.
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

$statement = $pdo->prepare('SELECT * FROM contacts WHERE id = :id');
$statement->execute([':id' => $id]);
$contact = $statement->fetch();

if (!$contact) {
    render_header('Contact not found');
    echo '<section class="panel message-panel"><h1>Contact not found</h1><p class="muted">This contact may have been deleted or the link may be incorrect.</p><a class="button button-primary" href="index.php">Back to contacts</a></section>';
    render_footer();
    exit;
}

$values = [
    'name' => $contact['name'],
    'email' => $contact['email'] ?? '',
    'phone' => $contact['phone'] ?? '',
    'group_name' => $contact['group_name'],
    'notes' => $contact['notes'] ?? '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $errors[] = 'Your session token is invalid or expired. Please submit the form again.';
        $values = array_merge($values, array_intersect_key($_POST, $values));
    } else {
        [$values, $errors] = validate_contact_input($_POST, $pdo, (int)$id);
        if (!$errors) {
            try {
                $update = $pdo->prepare(
                    'UPDATE contacts
                     SET name = :name, email = :email, phone = :phone,
                         group_name = :group_name, notes = :notes
                     WHERE id = :id'
                );
                $update->execute([
                    ':name' => $values['name'],
                    ':email' => $values['email'] !== '' ? $values['email'] : null,
                    ':phone' => $values['phone'] !== '' ? $values['phone'] : null,
                    ':group_name' => $values['group_name'],
                    ':notes' => $values['notes'] !== '' ? $values['notes'] : null,
                    ':id' => $id,
                ]);
                set_flash('success', 'Contact updated successfully!');
                redirect('index.php');
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23505') {
                    $errors[] = 'That email address is already in use. Please use another email.';
                } else {
                    error_log('Contact update failed: ' . $exception->getMessage());
                    $errors[] = 'The contact could not be updated. Please try again.';
                }
            }
        }
    }
}

render_header('Edit contact');
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">ADDRESS BOOK</p>
        <h1>Edit contact</h1>
        <p class="muted">Update details for <?= e($contact['name']) ?>.</p>
    </div>
    <a class="button button-secondary" href="index.php">← Back to contacts</a>
</section>
<?php if ($errors): ?>
    <div class="form-errors" role="alert"><strong>Please check the following:</strong><ul>
        <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
    </ul></div>
<?php endif; ?>
<section class="panel form-panel">
    <form method="post" action="edit.php?id=<?= (int)$id ?>" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <?php require __DIR__ . '/contact-form-fields.php'; ?>
        <div class="form-actions">
            <button class="button button-primary" type="submit">Save changes</button>
            <a class="button button-ghost" href="index.php">Cancel</a>
        </div>
    </form>
</section>
<?php render_footer(); ?>
