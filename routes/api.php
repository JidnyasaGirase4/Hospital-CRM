<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All API routes are versioned. Add new versions as routes/v1.php,
| routes/v2.php, etc. and mount them below under their own prefix so
| multiple versions can be served side by side during a migration.
|
*/

Route::prefix('v1')->name('api.v1.')->group(base_path('routes/v1.php'));
