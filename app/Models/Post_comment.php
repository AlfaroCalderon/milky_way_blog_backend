<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post_comment extends Model
{
    protected $table = 'post_comments';

    protected $fillable = [
        'user_id',
        'post_id',
        'comment'
    ];

    public function user():BelongsTo{
        return $this->belongsTo(User::class);
    }

    public function blog_post():BelongsTo{
        return $this->belongsTo(Blog_post::class);
    }
}
