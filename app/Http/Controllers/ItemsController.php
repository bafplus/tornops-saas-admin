<?php

namespace App\Http\Controllers;

class ItemsController extends Controller
{
    public function index()
    {
        return view('items.index');
    }
}