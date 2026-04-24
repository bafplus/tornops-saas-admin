<?php

namespace App\Http\Controllers;

class ScriptsController extends Controller
{
    public function index()
    {
        return view('scripts.index');
    }
}