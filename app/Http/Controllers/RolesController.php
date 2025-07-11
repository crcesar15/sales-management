<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class RolesController extends Controller
{
    public function index()
    {
        return Inertia::render('roles/index');
    }

    public function create()
    {
        return Inertia::render('roles/create/index');
    }

    public function edit()
    {
        return Inertia::render('roles/edit/index');
    }
}
