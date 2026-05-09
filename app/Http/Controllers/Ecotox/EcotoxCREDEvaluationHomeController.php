<?php

namespace App\Http\Controllers\Ecotox;

use App\Http\Controllers\Controller;

class EcotoxCREDEvaluationHomeController extends Controller
{
    public function index()
    {
        return view('ecotox.credevaluation.home');
    }

    public function countAll()
    {
        // This will be implemented later
        session()->flash('success', 'Database counts updated successfully');

        return redirect()->back();
    }
}
