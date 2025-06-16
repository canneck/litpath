<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookRecommendationController;

Route::post('/recommend', [BookRecommendationController::class, 'recommend'])
    ->middleware('api.key');
/*
{
  "favorite_genres": ["fantasy", "science_fiction"],
  "favorite_authors": ["J.K. Rowling", "Isaac Asimov"]
}
  o (si se envía un solo género/autor como string)
{
  "favorite_genres": "fantasy",
  "favorite_authors": "J.K. Rowling"
}
  o (si se envían géneros/autor como null)
{
  "favorite_genres": null,
  "favorite_authors": null
}
  o (si se envían géneros/autor como arrays vacíos)
{
  "favorite_genres": [],
  "favorite_authors": []
}
  o (si se envían géneros/autor como arrays con un solo elemento)
{
  "favorite_genres": ["fantasy"],
  "favorite_authors": ["J.K. Rowling"]
}
*/

Route::post('/feedback', [BookRecommendationController::class, 'feedback'])
    ->middleware('api.key');
/*
{
  "stars": 4
}
  o (ya que user_id es opcional)
{
  "stars": 4,
  "user_id": 1
}
*/

Route::get('/ping', function () {
    return response()->json(['message' => 'pong']);
});
