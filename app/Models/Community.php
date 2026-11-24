<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Community extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'creator_id'];

    public function creator() { return $this->belongsTo(User::class, 'creator_id'); }
    public function members() { return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps(); }
    public function posts()   { return $this->hasMany(Post::class); }

    public function getRouteKeyName()
{
    return 'slug';
}
}
