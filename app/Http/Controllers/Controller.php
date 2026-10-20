<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    protected function perPage(Request $request, int $default = 20): int
    {
        $value = $request->integer('per_page', $default);

        return in_array($value, [15, 20, 25, 50, 100], true) ? $value : $default;
    }
}
