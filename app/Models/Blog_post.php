<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Blog_post extends Model
{
    protected $table = 'blog_posts';

    protected $fillable = [
        'user_id',
        'post_title',
        'summary',
        'author',
        'category',
        'main_content'
    ];

    public function user():BelongsTo{
        return $this->belongsTo(User::class);
    }

    public function post_comment():HasMany{
        return $this->hasMany(Post_comment::class, 'post_id');
    }
}
