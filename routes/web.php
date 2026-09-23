<?php

use Illuminate\Support\Facades\Route;

Route::fallback(function () {
    abort_if(request()->is('api/*'), 404);

    return view('app');
});
