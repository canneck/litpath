<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecommendationFeedback extends Model
{
    protected $fillable = ['user_id', 'stars'];
}
