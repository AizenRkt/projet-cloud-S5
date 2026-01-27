<?php

namespace App\Http\Controllers;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Road-Check API",
 *     version="1.0.0"
 * )
 */
class MinimalSwaggerController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/minimal",
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function index()
    {
        return response()->json(['message' => 'Hello']);
    }
}
