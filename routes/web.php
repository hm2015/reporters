<?php

use Encore\Admin\Reporters\Http\Controllers\ReportersController;

Route::resource('exceptions', ReportersController::class, ['except' => ['create']]);
