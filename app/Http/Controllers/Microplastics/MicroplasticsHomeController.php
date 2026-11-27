<?php

declare(strict_types=1);

namespace App\Http\Controllers\Microplastics;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class MicroplasticsHomeController extends Controller
{
    /**
     * Display the Microplastics module home page.
     */
    public function index(): View
    {
        return view('microplastics.home.index');
    }
}
