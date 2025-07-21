<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Note extends Model
{
    //
    use HasFactory;
    protected $fillable = [
        'title',
        'content',
        'is_public',
    ];


    public function users()
    {
        return $this->belongsToMany(User::class, NoteAccess::class, 'note_id', 'user_id')
            ->withPivot('is_owner');
    }

    public function comments()
    {
        return $this->hasMany(NoteComment::class);
    }

    public function owner()
    {
        return $this->hasOneThrough(
            User::class,
            NoteAccess::class,
            'note_id',
            'id',
            'id',
            'user_id'
        )->where('note_accesses.is_owner', true);
    }
    public function getUsernameOwnerAttribute()
    {
        $name = ucwords($this->owner->name);
        $username = $this->owner->username;
        return "{$name}@{$username}";
    }
}
