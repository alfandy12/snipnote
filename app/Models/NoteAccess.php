<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NoteAccess extends Model
{
    use HasFactory;
    protected $fillable = [
        'note_id',
        'user_id',
        'id_owner',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
}
