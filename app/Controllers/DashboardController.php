<?php

namespace App\Controllers;

use Core\Session\Session;

class DashboardController
{
    public function __construct(private Session $session) {}

    public function index()
    {
        $this->session->put('user', 'Ori');
        return "Sesión guardada. Nombre: " . $this->session->get('user');
    }
}
