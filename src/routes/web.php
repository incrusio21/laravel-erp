<?php

use Illuminate\Support\Facades\Route;

Route::view('{path?}', 'erp::react')->where('path', '.*');