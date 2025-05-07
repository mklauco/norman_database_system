<?php

namespace App\Http\Controllers\Ecotox;

use Illuminate\Http\Request;
use App\Models\Ecotox\LowestPNEC;
use App\Http\Controllers\Controller;

class LowestPNECController extends Controller
{
    //
    public function index()
    {
        // Logic to retrieve and display the lowest PNEC data
        $lowestPNECs = LowestPNEC::paginate(200);
        return view('ecotox.lowestpnec.index', [
            'lowestPNECs' => $lowestPNECs,
            'displayOption' => 0,
        ]);
    }
}
