<?php

namespace App\Controllers;

use App\Requests\StoreUserRequest;

class DashboardController
{
    public function index()
    {
        session('user', 'ori28');

        return view('index', ['name' => session('user')]);
    }

    public function store(StoreUserRequest $request)
    {
        $request->validate();

        return "Usuario creado con éxito: " . $request->post('name') ?? $request->query('name');
    }
}
