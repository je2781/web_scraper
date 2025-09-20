<?php

use Illuminate\Support\Facades\Route;
use Laravel\Horizon\Horizon;

Route::get('/', function () {
    return view('welcome');
});

if (app()->environment('local')) {
    Horizon::routes();
}