<?php

namespace App\Controllers;

class DashboardController
{
    public function index()
    {
        session('user', 'ori28');

        return view('index', ['name' => session('user')]);
    }
}
