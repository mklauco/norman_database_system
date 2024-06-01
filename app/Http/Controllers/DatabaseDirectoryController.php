<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DatabaseDirectoryController extends Controller
{
    //

    public function index()
    {
        return view('databases.index');
    }
}
