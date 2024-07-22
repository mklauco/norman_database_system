<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use App\Models\Susdat\Substance;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\SubstanceResource;

class SubstanceController extends Controller
{
    //

    public function index(){
        $substances = Substance::where('id' , '<', 10)->get();
        return SubstanceResource::collection($substances);
    }

    public function show(Substance $substance){
        // $substance = Substance::findOrFail($id);
        return new SubstanceResource($substance);
    }
}
