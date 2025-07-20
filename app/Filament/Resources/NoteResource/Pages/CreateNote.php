<?php

namespace App\Filament\Resources\NoteResource\Pages;

use Filament\Actions;
use App\Filament\Resources\NoteResource;
use App\Models\Note;
use Filament\Resources\Pages\CreateRecord;

class CreateNote extends CreateRecord
{
    protected static string $resource = NoteResource::class;


    protected function handleRecordCreation(array $data): Note
    {

        $note = Note::create($data);
        // make access for the user
        $note->users()->attach(auth()->id(), [
            'is_owner' => true,
        ]);

        return $note;
    }


    protected function getRedirectUrl(): string
    {
        return NoteResource::getUrl('index');
    }
}
