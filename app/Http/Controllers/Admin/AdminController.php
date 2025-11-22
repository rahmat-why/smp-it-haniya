<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class AdminController extends Controller
{
    public function index()
    {
        if (!session()->has('role')) {
            return redirect('/login');
        }

        return view('admin.dashboard');
    }
}
