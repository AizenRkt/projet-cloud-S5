<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenApi\Annotations as OA;


class SwaggerTestController extends Controller
{
    /**
     */
    public function test()
    {
        return response()->json(['ok' => true]);
    }
}
