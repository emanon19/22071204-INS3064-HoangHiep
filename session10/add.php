<?php
/**
 * Create a contact.
 */
declare(strict_types=1);
require_once __DIR__ . '/config.php';

$values = ['name' => '', 'email' => '', 'phone' => '', 'group_name' => 'General', 'notes' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $errors[] = 'Your session token is invalid or expired. Please submit the form again.';
        $values = array_merge($values, array_intersect_key($_POST, $values));
    } else {
        [$values, $errors] = validate_contact_input($_POST, $pdo);
        if (!$errors) {
            try {
                $statement = $pdo->prepare(
                    'INSERT INTO contacts (name, email, phone, group_name, notes)
                     VALUES (:name, :email, :phone, :group_name, :notes)'
                );
                $statement->execute([
                    ':name' => $values['name'],
                    ':email' => $values['email'] !== '' ? $values['email'] : null,
                    ':phone' => $values['phone'] !== '' ? $values['phone'] : null,
                    ':group_name' => $values['group_name'],
                    ':notes' => $values['notes'] !== '' ? $values['notes'] : null,
                ]);
                set_flash('success', 'Contact added successfully!');
                redirect('index.php');
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23505') {
                    $errors[] = 'That email address is already in use. Please use another email.';
                } else {
                    error_log('Contact insert failed: ' . $exception->getMessage());
                    $errors[] = 'The contact could not be saved. Please try again.';
                }
            }
        }
    }
}

render_header('Add contact');
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">ADDRESS BOOK</p>
        <h1>Add a contact</h1>
        <p class="muted">Add someone new to your directory.</p>
    </div>
    <a class="button button-secondary" href="index.php">← Back to contacts</a>
</section>
<?php if ($errors): ?>
    <div class="form-errors" role="alert"><strong>Please check the following:</strong><ul>
        <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
    </ul></div>
<?php endif; ?>
<section class="panel form-panel">
    <form method="post" action="add.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <?php require __DIR__ . '/contact-form-fields.php'; ?>
        <div class="form-actions">
            <button class="button button-primary" type="submit">Save contact</button>
            <a class="button button-ghost" href="index.php">Cancel</a>
        </div>
    </form>
</section>
<?php render_footer(); ?>
