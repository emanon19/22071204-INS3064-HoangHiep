<?php

declare(strict_types=1);

?>

<div class="page-heading">
    <div>
        <p class="eyebrow">YOUR ADDRESS BOOK</p>
        <h1>All Contacts</h1>
        <p class="muted">Manage and organize your personal connections.</p>
    </div>

    <a class="button button-primary" href="<?= e(app_url('create')) ?>">
        + Add contact
    </a>
</div>

<?php if (!empty($pageError)): ?>
    <div class="flash flash-error" role="alert">
        <?= e($pageError) ?>
    </div>
<?php endif; ?>

<div class="summary-row">
    <div class="summary-card">
        <span class="summary-label">Total contacts</span>
        <strong class="summary-number">
            <?= e($totalContacts ?? 0) ?>
        </strong>
    </div>

    <form
        class="search-form"
        method="GET"
        action="<?= e(app_url()) ?>"
    >
        <input
            type="hidden"
            name="route"
            value=""
        >

        <label class="sr-only" for="search">
            Search contacts
        </label>

        <input
            type="search"
            id="search"
            name="q"
            placeholder="Search name, email, phone..."
            value="<?= e($search ?? '') ?>"
        >

        <button class="button button-secondary" type="submit">
            Search
        </button>

        <?php if (($search ?? '') !== ''): ?>
            <a class="clear-link" href="<?= e(app_url()) ?>">
                Clear
            </a>
        <?php endif; ?>
    </form>
</div>

<section class="panel">
    <div class="panel-heading">
        <h2>Contact list</h2>

        <span class="result-count">
            <?= count($contacts) ?>
            <?= count($contacts) === 1 ? 'result' : 'results' ?>
        </span>
    </div>

    <?php if (empty($contacts)): ?>

        <div class="empty-state">
            <div class="empty-icon">+</div>

            <h3>
                <?= ($search ?? '') !== ''
                    ? 'No matching contacts'
                    : 'No contacts yet' ?>
            </h3>

            <p>
                <?= ($search ?? '') !== ''
                    ? 'Try a different search keyword.'
                    : 'Start building your address book by adding a contact.' ?>
            </p>

            <?php if (($search ?? '') === ''): ?>
                <a
                    class="button button-primary"
                    href="<?= e(app_url('create')) ?>"
                >
                    + Add your first contact
                </a>
            <?php endif; ?>
        </div>

    <?php else: ?>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Group</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($contacts as $contact): ?>

                        <?php
                        $groupClass = strtolower(
                            preg_replace(
                                '/[^a-zA-Z0-9_-]/',
                                '',
                                $contact['group_name']
                            )
                        );
                        ?>

                        <tr>
                            <td data-label="Name">
                                <span class="contact-name">
                                    <?= e($contact['name']) ?>
                                </span>
                            </td>

                            <td data-label="Email">
                                <?php if (!empty($contact['email'])): ?>
                                    <a
                                        class="email-link"
                                        href="mailto:<?= e($contact['email']) ?>"
                                    >
                                        <?= e($contact['email']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="muted">—</span>
                                <?php endif; ?>
                            </td>

                            <td data-label="Phone">
                                <?= e($contact['phone'] ?: '—') ?>
                            </td>

                            <td data-label="Group">
                                <span class="group-pill group-<?= e($groupClass) ?>">
                                    <?= e($contact['group_name']) ?>
                                </span>
                            </td>

                            <td data-label="Created" class="date-cell">
                                <?= e(date(
                                    'd M Y',
                                    strtotime($contact['created_at'])
                                )) ?>
                            </td>

                            <td data-label="Actions">
                                <div class="actions">

                                    <a
                                        class="action-link"
                                        href="<?= e(app_url('edit', [
                                            'id' => $contact['id'],
                                        ])) ?>"
                                    >
                                        Edit
                                    </a>

                                    <span class="action-divider">|</span>

                                    <form
                                        method="POST"
                                        action="<?= e(app_url('delete', [
                                            'id' => $contact['id'],
                                        ])) ?>"
                                        onsubmit="return confirm('Are you sure you want to delete this contact?');"
                                    >
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e(csrf_token()) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= e($contact['id']) ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="action-link action-delete"
                                            style="border:0;background:none;padding:0;cursor:pointer;font:inherit;"
                                        >
                                            Delete
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>

                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>
</section>