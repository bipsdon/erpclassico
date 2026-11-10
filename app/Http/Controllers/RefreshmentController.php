<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class RefreshmentController extends Controller
{
    public function index(): View
    {
        return view('refreshment.index');
    }
}
