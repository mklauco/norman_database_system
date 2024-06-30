<?php

namespace App\Http\Controllers\Api\Susdat;

use Illuminate\Http\Request;
use App\Models\Susdat\Substance;
use App\Http\Controllers\Controller;

class SubstanceAPI extends Controller
{
    //

    public function show($id){
        $substance = Substance::findOrFail($id);
        return response()->json($substance);
    }
}
