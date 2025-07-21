<?php

namespace App\Filament\Resources\CommentNoteRelationManagerResource\RelationManagers;

use Filament\Forms;
use App\Models\Note;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\NoteComment;
use Carbon\Carbon;
use Filament\Tables\Columns\Layout\Split;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Support\HtmlString;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('comment')
                    ->required()
                    ->columnSpanFull()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('comment')
            ->columns([
                Split::make([
                    Tables\Columns\TextColumn::make('userCommentName')
                        ->formatStateUsing(function (NoteComment $record, string $state): HtmlString {
                            $state = ucwords($state);
                            $date = Carbon::parse($record->created_at)->since();
                            return new HtmlString("<b>{$state}</b> | <small><i>{$date}</i></small>");
                        })
                        ->description(fn(NoteComment $record): string => $record->comment)
                ])
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->action(function (array $data) {
                        $recordOwner = $this->getOwnerRecord();
                        $data['user_id'] = auth()->id();
                        $data['note_id'] = $recordOwner->id;
                        NoteComment::create($data);
                    })
                    ->createAnother(false),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(function (NoteComment $comment) {
                        $recordOwner = $this->getOwnerRecord();
                        //owner can delete all coment, and users can only delete their own comments
                        $isOwner = $recordOwner->users->where('pivot.is_owner', true)->first();
                        return auth()->id() == $isOwner->id ||  auth()->id() == $comment->user_id;
                    }),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])->modifyQueryUsing(fn (Builder $query) => $query->orderByDesc('created_at'));
    }
    public function isReadOnly(): bool
    {
        return false;
    }

}
