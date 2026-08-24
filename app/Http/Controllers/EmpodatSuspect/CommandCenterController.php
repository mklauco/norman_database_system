<?php

declare(strict_types=1);

namespace App\Http\Controllers\EmpodatSuspect;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class CommandCenterController extends Controller
{
    /**
     * Display the EMPODAT Suspect command center.
     *
     * Route-level `permission:empodat-suspect.refresh` middleware already
     * restricts access; the Livewire component re-checks it on mount and on
     * every action as defense in depth.
     */
    public function index(): View
    {
        return view('empodat_suspect.commands.index');
    }
}
