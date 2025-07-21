<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NoteComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'note_id',
        'comment',
    ];

    public function note()
    {
        return $this->belongsTo(Note::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getUserCommentNameAttribute()
    {
        $name = ucwords($this->user->name);
        $username = $this->user->username;
        return "{$name}@{$username}";
    }
}
