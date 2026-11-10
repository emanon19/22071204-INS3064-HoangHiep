<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ContactModel;
use InvalidArgumentException;
use Throwable;

class ContactController
{
    private ContactModel $contactModel;

    private const CONTACT_GROUPS = [
        'Family',
        'Friends',
        'Work',
        'Other',
        'General',
    ];

    public function __construct()
    {
        $this->contactModel = new ContactModel();
    }

    /**
     * Display all contacts.
     */
    public function index(): void
    {
        $search = trim((string) ($_GET['q'] ?? ''));

        $contacts = [];
        $totalContacts = 0;
        $pageError = null;

        try {
            $contacts = $this->contactModel->getAll($search);
            $totalContacts = $this->contactModel->count();
        } catch (Throwable $exception) {
            error_log($exception->getMessage());

            http_response_code(500);

            $pageError = 'Unable to retrieve contacts. Please try again.';
        }

        $this->view('contacts/index', [
            'pageTitle' => 'All Contacts',
            'contacts' => $contacts,
            'totalContacts' => $totalContacts,
            'search' => $search,
            'pageError' => $pageError,
        ]);
    }

    /**
     * Display the create contact form.
     */
    public function create(): void
    {
        $this->view('contacts/create', [
            'pageTitle' => 'Add Contact',
            'contact' => $this->emptyContact(),
            'errors' => [],
            'groups' => self::CONTACT_GROUPS,
        ]);
    }

    /**
     * Process the create contact form.
     */
    public function store(): void
    {
        $this->requirePost();
        $this->verifyCsrf();

        $data = $this->normalizeInput($_POST);
        $errors = $this->validateInput($data);

        if ($errors !== []) {
            $this->view('contacts/create', [
                'pageTitle' => 'Add Contact',
                'contact' => $data,
                'errors' => $errors,
                'groups' => self::CONTACT_GROUPS,
            ]);

            return;
        }

        try {
            $this->contactModel->create($data);

            $this->setFlash(
                'success',
                'Contact created successfully.'
            );

            $this->redirect($this->routeUrl());
        } catch (Throwable $exception) {
            error_log($exception->getMessage());

            $errors[] = 'Unable to create the contact. Please try again.';

            $this->view('contacts/create', [
                'pageTitle' => 'Add Contact',
                'contact' => $data,
                'errors' => $errors,
                'groups' => self::CONTACT_GROUPS,
            ]);
        }
    }

    /**
     * Display the edit contact form.
     */
    public function edit(): void
    {
        $id = $this->getContactId();

        if ($id === null) {
            $this->redirectWithError('Invalid contact ID.');
        }

        try {
            $contact = $this->contactModel->findById($id);

            if ($contact === false) {
                $this->redirectWithError('Contact not found.');
            }

            $this->view('contacts/edit', [
                'pageTitle' => 'Edit Contact',
                'contact' => $contact,
                'errors' => [],
                'groups' => self::CONTACT_GROUPS,
            ]);
        } catch (Throwable $exception) {
            error_log($exception->getMessage());

            $this->redirectWithError(
                'Unable to retrieve the contact.'
            );
        }
    }

    /**
     * Process the update contact form.
     */
    public function update(): void
    {
        $this->requirePost();
        $this->verifyCsrf();

        $id = $this->getContactId();

        if ($id === null) {
            $this->redirectWithError('Invalid contact ID.');
        }

        $contact = $this->contactModel->findById($id);

        if ($contact === false) {
            $this->redirectWithError('Contact not found.');
        }

        $data = $this->normalizeInput($_POST);
        $errors = $this->validateInput($data, $id);

        if ($errors !== []) {
            $this->view('contacts/edit', [
                'pageTitle' => 'Edit Contact',
                'contact' => array_merge($contact, $data),
                'errors' => $errors,
                'groups' => self::CONTACT_GROUPS,
            ]);

            return;
        }

        try {
            $this->contactModel->update($id, $data);

            $this->setFlash(
                'success',
                'Contact updated successfully.'
            );

            $this->redirect($this->routeUrl());
        } catch (Throwable $exception) {
            error_log($exception->getMessage());

            $errors[] = 'Unable to update the contact. Please try again.';

            $this->view('contacts/edit', [
                'pageTitle' => 'Edit Contact',
                'contact' => array_merge($contact, $data),
                'errors' => $errors,
                'groups' => self::CONTACT_GROUPS,
            ]);
        }
    }

    /**
     * Delete a contact.
     */
    public function delete(): void
    {
        $this->requirePost();
        $this->verifyCsrf();

        $id = $this->getContactId();

        if ($id === null) {
            $this->redirectWithError('Invalid contact ID.');
        }

        try {
            $deleted = $this->contactModel->delete($id);

            if (!$deleted) {
                $this->setFlash('warning', 'Contact not found.');
            } else {
                $this->setFlash(
                    'success',
                    'Contact deleted successfully.'
                );
            }

            $this->redirect($this->routeUrl());
        } catch (Throwable $exception) {
            error_log($exception->getMessage());

            $this->redirectWithError(
                'Unable to delete the contact. Please try again.'
            );
        }
    }

    /**
     * Normalize submitted contact values.
     *
     * @return array<string, string>
     */
    private function normalizeInput(array $input): array
    {
        return [
            'name' => trim((string) ($input['name'] ?? '')),
            'email' => trim((string) ($input['email'] ?? '')),
            'phone' => trim((string) ($input['phone'] ?? '')),
            'group_name' => trim(
                (string) ($input['group_name'] ?? 'General')
            ),
            'notes' => trim((string) ($input['notes'] ?? '')),
        ];
    }

    /**
     * Validate contact information.
     *
     * @return array<int, string>
     */
    private function validateInput(
        array $data,
        ?int $currentId = null
    ): array {
        $errors = [];

        if ($data['name'] === '') {
            $errors[] = 'Name is required.';
        } elseif (mb_strlen($data['name']) > 100) {
            $errors[] = 'Name must be 100 characters or fewer.';
        }

        if ($data['email'] !== '') {
            if (
                mb_strlen($data['email']) > 150
                || filter_var(
                    $data['email'],
                    FILTER_VALIDATE_EMAIL
                ) === false
            ) {
                $errors[] = 'Enter a valid email address (maximum 150 characters).';
            } elseif (
                $this->contactModel->emailExists(
                    $data['email'],
                    $currentId
                )
            ) {
                $errors[] = 'That email address is already used by another contact.';
            }
        }

        if (mb_strlen($data['phone']) > 20) {
            $errors[] = 'Phone must be 20 characters or fewer.';
        }

        if (
            !in_array(
                $data['group_name'],
                self::CONTACT_GROUPS,
                true
            )
        ) {
            $errors[] = 'Choose a valid contact group.';
        }

        return $errors;
    }

    /**
     * Return empty contact form values.
     *
     * @return array<string, string>
     */
    private function emptyContact(): array
    {
        return [
            'name' => '',
            'email' => '',
            'phone' => '',
            'group_name' => 'General',
            'notes' => '',
        ];
    }

    /**
     * Render a view inside the shared layout.
     */
    private function view(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        $viewFile = dirname(__DIR__)
            . '/views/'
            . $view
            . '.php';

        if (!is_file($viewFile)) {
            throw new \RuntimeException(
                'View not found: ' . $view
            );
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        require dirname(__DIR__) . '/views/layout.php';
    }

    private function requirePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Allow: POST');

            exit('Method Not Allowed');
        }
    }

    private function verifyCsrf(): void
    {
        $submitted = $_POST['csrf_token'] ?? '';

        if (
            !is_string($submitted)
            || !isset($_SESSION['csrf_token'])
            || !hash_equals($_SESSION['csrf_token'], $submitted)
        ) {
            http_response_code(403);
            exit('Invalid security token. Please refresh the page and try again.');
        }
    }

    private function getContactId(): ?int
    {
        $id = filter_var(
            $_POST['id'] ?? $_GET['id'] ?? null,
            FILTER_VALIDATE_INT
        );

        return $id !== false && $id !== null && $id > 0
            ? $id
            : null;
    }

    private function setFlash(string $type, string $message): void
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    private function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    private function redirectWithError(string $message): never
    {
        $this->setFlash('error', $message);
        $this->redirect($this->routeUrl());
    }

    private function routeUrl(string $route = ''): string
    {
        $baseUrl = rtrim(
            dirname($_SERVER['SCRIPT_NAME']),
            '/\\'
        );

        if ($baseUrl === '.' || $baseUrl === '/') {
            $baseUrl = '';
        }

        return $baseUrl
            . '/index.php?route='
            . urlencode($route);
    }
}