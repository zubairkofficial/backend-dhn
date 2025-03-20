<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/users', function() {
    return User::with('customerUsers')->where('is_user_customer', 1)->get();
});

