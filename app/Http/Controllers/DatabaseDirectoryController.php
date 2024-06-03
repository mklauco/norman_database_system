<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DatabaseEntity;

class DatabaseDirectoryController extends Controller
{
    //

    public function index()
    {
        $databases = DatabaseEntity::all();
        return view('database_entities.index', [
            'databases' => $databases
        ]);
    }
}
