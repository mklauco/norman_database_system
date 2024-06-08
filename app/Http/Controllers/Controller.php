<?php

namespace App\Http\Controllers;

abstract class Controller
{
    //

    public function orderByList($id = null)
    {
        $list = [
            0 => 'asc',
            1 => 'desc',
        ];
        if (is_null($id)) {
            return $list;
        } else {
            return $list[$id];
        }
    }
}
