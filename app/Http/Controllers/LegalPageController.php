<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

final class LegalPageController extends Controller
{
    public function home(): Response
    {
        return $this->page('index');
    }

    public function privacy(): Response
    {
        return $this->page('privacy-policy');
    }

    public function terms(): Response
    {
        return $this->page('terms');
    }

    public function dataDeletion(): Response
    {
        return $this->page('data-deletion');
    }

    private function page(string $page): Response
    {
        return response(
            file_get_contents(resource_path("legal-site/{$page}.html")),
            headers: ['Content-Type' => 'text/html; charset=UTF-8'],
        );
    }
}
