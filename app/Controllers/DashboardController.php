<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Requests\StoreUserRequest;

class DashboardController
{
    public function index(): string
    {
        session('user', 'guest');

        return view('index', ['name' => (string) session('user')]);
    }

    public function store(StoreUserRequest $request): string
    {
        $request->validate();

        $name = $request->post('name') ?? $request->query('name') ?? '';

        return "User created successfully: {$name}";
    }
}
