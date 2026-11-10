<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\CategoryModel;
use PDOException;
use Throwable;

final class CategoryController
{
    public function __construct(
        private CategoryModel $categories
    ) {
    }

    public function index(): void
    {
        render('categories/index', [
            'pageTitle' => 'Categories',
            'categories' => $this->categories->all(),
        ]);
    }

    public function create(): void
    {
        $state = consume_form_state();

        render('categories/create', [
            'pageTitle' => 'Add category',
            'formData' => $state['data'],
            'errors' => $state['errors'],
        ]);
    }

    public function edit(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $category = $this->categories->find($id);

        if (!$category) {
            flash('error', 'Category not found.');
            redirect(app_url('categories'));
        }

        $state = consume_form_state();

        render('categories/edit', [
            'pageTitle' => 'Edit category',
            'category' => $category,
            'formData' => $state['data'],
            'errors' => $state['errors'],
        ]);
    }

    private function validate(array $data): array
    {
        $errors = [];

        if ($data['name'] === '') {
            $errors['name'] = 'Category name is required.';
        } elseif (mb_strlen($data['name']) > 150) {
            $errors['name'] = 'Category name must not exceed 150 characters.';
        }

        if (mb_strlen($data['description']) > 5000) {
            $errors['description'] = 'Description is too long.';
        }

        return $errors;
    }

    private function formData(): array
    {
        return [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
        ];
    }

    public function store(): void
    {
        verify_csrf();

        $data = $this->formData();
        $errors = $this->validate($data);

        if ($errors) {
            remember_form($data, $errors);
            redirect(app_url('categories/create'));
        }

        try {
            $this->categories->create([
                'name' => $data['name'],
                'description' => $data['description'] ?: null,
            ]);

            flash('success', 'Category created successfully.');
        } catch (PDOException $e) {
            error_log($e->getMessage());

            $errors = $e->getCode() === '23505'
                ? ['name' => 'A category with this name already exists.']
                : ['general' => 'Unable to create category.'];

            remember_form($data, $errors);
            redirect(app_url('categories/create'));
        } catch (Throwable $e) {
            error_log($e->getMessage());

            remember_form($data, [
                'general' => 'Unable to create category.',
            ]);

            redirect(app_url('categories/create'));
        }

        redirect(app_url('categories'));
    }

    public function update(): void
    {
        verify_csrf();

        $id = (int) ($_POST['id'] ?? 0);
        $category = $this->categories->find($id);

        if (!$category) {
            flash('error', 'Category not found.');
            redirect(app_url('categories'));
        }

        $data = $this->formData();
        $errors = $this->validate($data);

        if ($errors) {
            remember_form($data, $errors);

            redirect(app_url('categories/edit', [
                'id' => $id,
            ]));
        }

        try {
            $this->categories->update($id, [
                'name' => $data['name'],
                'description' => $data['description'] ?: null,
            ]);

            flash('success', 'Category updated successfully.');
        } catch (PDOException $e) {
            error_log($e->getMessage());

            $errors = $e->getCode() === '23505'
                ? ['name' => 'A category with this name already exists.']
                : ['general' => 'Unable to update category.'];

            remember_form($data, $errors);

            redirect(app_url('categories/edit', [
                'id' => $id,
            ]));
        } catch (Throwable $e) {
            error_log($e->getMessage());

            remember_form($data, [
                'general' => 'Unable to update category.',
            ]);

            redirect(app_url('categories/edit', [
                'id' => $id,
            ]));
        }

        redirect(app_url('categories'));
    }

    public function delete(): void
    {
        verify_csrf();

        $id = (int) ($_POST['id'] ?? 0);

        if (!$this->categories->find($id)) {
            flash('error', 'Category not found.');
            redirect(app_url('categories'));
        }

        try {
            $this->categories->delete($id);
            flash('success', 'Category deleted successfully.');
        } catch (Throwable $e) {
            error_log($e->getMessage());
            flash('error', 'Unable to delete category.');
        }

        redirect(app_url('categories'));
    }
}