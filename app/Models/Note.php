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
        'is_pinned',
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

}
