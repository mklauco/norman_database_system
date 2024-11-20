<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DatabaseEntity;

class DatabaseDirectoryController extends Controller
{
    //

    public function index()
    {
        $databases = DatabaseEntity::orderby('id', 'asc')->get();
        return view('landing.index', [
            'databases' => $databases
        ]);
    }
}
