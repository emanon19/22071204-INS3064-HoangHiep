<?php
/**
 * Contact list, count, and name/email search.
 */
declare(strict_types=1);
require_once __DIR__ . '/config.php';

$search = trim((string)($_GET['q'] ?? ''));
$sql = 'SELECT id, name, email, phone, group_name, created_at FROM contacts';
$params = [];

if ($search !== '') {
    $sql .= ' WHERE name ILIKE :name OR email LIKE :email';
    $params[':name'] = '%' . $search . '%';
    $params[':email'] = '%' . $search . '%';
}
$sql .= ' ORDER BY name ASC, id ASC';

$statement = $pdo->prepare($sql);
$statement->execute($params);
$contacts = $statement->fetchAll();
$total = count($contacts);

render_header('All contacts');
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">YOUR ADDRESS BOOK</p>
        <h1>All contacts</h1>
        <p class="muted">A simple home for the people you want to stay connected with.</p>
    </div>
    <a class="button button-primary" href="add.php">+ Add new contact</a>
</section>

<section class="summary-row" aria-label="Contact summary">
    <div class="summary-card">
        <span class="summary-label"><?= $search !== '' ? 'Matching contacts' : 'Total contacts' ?></span>
        <strong class="summary-number"><?= $total ?></strong>
    </div>
    <form class="search-form" method="get" action="index.php">
        <label class="sr-only" for="q">Search by name or email</label>
        <input id="q" type="search" name="q" value="<?= e($search) ?>" placeholder="Search name or email…">
        <button class="button button-secondary" type="submit">Search</button>
        <?php if ($search !== ''): ?><a class="clear-link" href="index.php">Clear</a><?php endif; ?>
    </form>
</section>

<section class="panel">
    <div class="panel-heading">
        <h2>Contact directory</h2>
        <span class="result-count"><?= $total ?> result<?= $total === 1 ? '' : 's' ?></span>
    </div>
    <?php if (!$contacts): ?>
        <div class="empty-state">
            <div class="empty-icon">◎</div>
            <h3><?= $search !== '' ? 'No matching contacts' : 'Your address book is empty' ?></h3>
            <p><?= $search !== '' ? 'Try another name or email address.' : 'Add your first contact to get started.' ?></p>
            <?php if ($search === ''): ?><a class="button button-primary" href="add.php">Add a contact</a><?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Phone</th>
                    <th scope="col">Group</th>
                    <th scope="col">Created at</th>
                    <th scope="col">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($contacts as $contact): ?>
                    <tr>
                        <td data-label="Name"><span class="contact-name"><?= e($contact['name']) ?></span></td>
                        <td data-label="Email">
                            <?php if (!empty($contact['email'])): ?>
                                <a class="email-link" href="mailto:<?= e($contact['email']) ?>"><?= e($contact['email']) ?></a>
                            <?php else: ?><span class="muted">—</span><?php endif; ?>
                        </td>
                        <td data-label="Phone"><?= e($contact['phone'] ?: '—') ?></td>
                        <td data-label="Group"><span class="group-pill group-<?= e(strtolower($contact['group_name'])) ?>"><?= e($contact['group_name']) ?></span></td>
                        <td data-label="Created at" class="date-cell"><?= e(date('M j, Y', strtotime($contact['created_at']))) ?></td>
                        <td data-label="Actions">
                            <div class="actions">
                                <a class="action-link" href="edit.php?id=<?= (int)$contact['id'] ?>">Edit</a>
                                <span class="action-divider">·</span>
                                <a class="action-link action-delete" href="delete.php?id=<?= (int)$contact['id'] ?>">Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php render_footer(); ?>
