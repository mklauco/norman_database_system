<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

abstract class ListGeneratorController
{
  public function getEqualitySigns($id = null)
  {
    $list = [
      0 => '=',
      1 => '>',
      2 => '>=',
      3 => '<',
      4 => '<=',
      5 => '!=',
    ];
    if (is_null($id)) {
      return $list;
    } else {
      return $list[$id];
    }
  }
}