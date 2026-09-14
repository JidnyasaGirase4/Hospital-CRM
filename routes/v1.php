<?php

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
|
| Mounted at /api/v1 by routes/api.php. Each module registers its routes
| here (or via an included file) as it is built out, phase by phase.
|
*/

require base_path('routes/v1/auth.php');
require base_path('routes/v1/users.php');
require base_path('routes/v1/patients.php');
require base_path('routes/v1/clinical.php');
require base_path('routes/v1/pharmacy.php');
require base_path('routes/v1/laboratory.php');
require base_path('routes/v1/radiology.php');
require base_path('routes/v1/ipd.php');
require base_path('routes/v1/nursing.php');
require base_path('routes/v1/emergency.php');
require base_path('routes/v1/ot.php');
