<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    return response()->json([
        'name' => config('app.name', 'Laravel'),
        'status' => 'ok',
        'environment' => app()->environment(),
        'path' => $request->path(),
    ]);
});
