<?php
namespace App\Controllers;

class BaseController
{
    protected function render(string $view, array $data = []): void
    {
        extract($data);
        include __DIR__ . '/../views/partials/header.php';
        include __DIR__ . '/../views/' . $view . '.php';
        include __DIR__ . '/../views/partials/footer.php';
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . BASE_URL . $path);
        exit;
    }
}