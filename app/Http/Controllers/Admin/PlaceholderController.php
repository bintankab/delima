<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PlaceholderController extends Controller
{
    public function show(string $title): Response
    {
        return Inertia::render('Admin/ComingSoon', [
            'meta' => ['title' => $title],
            'title' => $title,
        ]);
    }
}
