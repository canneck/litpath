<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookRecommendationController;

Route::post('/recommend', [BookRecommendationController::class, 'recommend'])
    ->middleware('api.key');
