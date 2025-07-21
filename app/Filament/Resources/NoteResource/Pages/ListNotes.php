<?php

namespace App\Filament\Resources\NoteResource\Pages;

use Filament\Actions;
use Filament\Resources\Components\Tab;
use App\Filament\Resources\NoteResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListNotes extends ListRecords
{
    protected static string $resource = NoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('notes.tabs.all_notes')),
            'privateShare' => Tab::make(__('notes.tabs.sharing_to_me'))
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->where('is_public', false)
                    ->whereHas('users', function ($q) {
                        $q->where('user_id', auth()->id())
                            ->where('is_owner', false);
                    })
                ),
            'public' => Tab::make(__('notes.tabs.public'))
                ->modifyQueryUsing(fn(Builder $query) => $query->where('is_public', true)),
        ];
    }
}
