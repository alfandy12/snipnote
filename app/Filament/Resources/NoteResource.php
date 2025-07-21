<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Note;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Resources\Components\Tab;
use Filament\Support\Enums\FontWeight;
use Filament\Forms\Components\Fieldset;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use App\Jobs\NotificationFromSharingNote;
use Filament\Forms\Components\RichEditor;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Pages\CreateRecord;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\NoteResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\Layout\Grid as GridTable;
use App\Filament\Resources\NoteResource\RelationManagers;
use Filament\Forms\Components\Actions\Action as ActionForm;
use Filament\Infolists\Components\Section as SectionInfolist;
use Filament\Notifications\Actions\Action as ActionNotification;
use App\Filament\Resources\CommentNoteRelationManagerResource\RelationManagers\CommentsRelationManager;

class NoteResource extends Resource
{
    protected static ?string $model = Note::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->columns(3)
                    ->schema([
                        Placeholder::make('owner')
                            ->content(fn(Note $record): string => $record->usernameOwner),
                        Placeholder::make('created')
                            ->content(fn(Note $record): string => $record->created_at->since()),
                        Placeholder::make('updated')
                            ->content(fn(Note $record): string => $record->updated_at->since())
                    ])->visibleOn('view'),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                RichEditor::make('content')
                    ->required()
                    ->columnSpanFull()
                    ->disableToolbarButtons([
                        'attachFiles',
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    GridTable::make()
                        ->schema([
                            Tables\Columns\TextColumn::make('title')
                                ->weight(FontWeight::Bold)
                                ->limit(40)
                                ->searchable(),
                            Tables\Columns\TextColumn::make('content')
                                ->searchable()
                                ->formatStateUsing(fn(string $state): string => strip_tags($state))
                                ->limit(100),
                            Tables\Columns\TextColumn::make('comments_count')
                                ->visible(function ($record) {
                                    if (is_null($record)) {
                                        return false;
                                    }
                                    return $record->comments_count > 0 ? true : false;
                                })
                                ->formatStateUsing(fn(string $state): string => __('notes.comments.has_comment', ['count' => $state]))
                                ->badge()
                                ->color('primary'),
                            Tables\Columns\TextColumn::make('is_public')
                                ->visible(function ($record) {
                                    return $record ? $record->is_public : false;
                                })
                                ->formatStateUsing(fn(string $state): string => __('notes.tabs.public'))
                                ->badge()
                                ->color('success'),

                        ])
                        ->columns(1),
                ]),
            ])
            ->contentGrid([
                'default' => 2,
                'md' => 3,
                'lg' => 4,
                'xl' => 5,
            ])

            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton(),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->requiresConfirmation(),

                Action::make(__('notes.action.sharing'))
                    //cant sharing spesific user if not owner
                    ->hidden(fn(Model $record) => ! $record->users->contains(fn($user) => $user->id === auth()->id() && $user->pivot->is_owner))
                    ->iconButton()
                    ->color(Color::Cyan)
                    ->icon('heroicon-m-share')
                    ->action(function (array $data, Note $record): void {
                        //for make low query, update if is_public changed
                        if ($record->is_public !== $data['is_public']) {
                            $record->is_public = $data['is_public'];
                            $record->save();
                        }

                        if (isset($data['user_id'])) {
                            // array sync
                            $usersToSync = [];

                            // another ser
                            $usersToSync = collect($data['user_id'])
                                ->mapWithKeys(fn($id) => [$id => ['is_owner' => false]])
                                ->all();

                            // owner
                            $ownerId = $record->users->firstWhere('pivot.is_owner', true)?->id;

                            // sync owner
                            if ($ownerId) {
                                $usersToSync[$ownerId] = ['is_owner' => true];
                            }

                            //sync note
                            $record->users()->sync($usersToSync);

                            //notification to my self
                            if (!empty($data['user_id'])) {
                                //my username
                                $username =  auth()->user()->username;

                                // Notifikasi builder
                                $notification = Notification::make()
                                    ->title(__('notes.action.sharing_notification.another_user', ['username' => $username]))
                                    ->actions([
                                        ActionNotification::make(__('notes.column.view'))
                                            ->url(NoteResource::getUrl('view', ['record' => $record]))
                                            ->button()
                                            ->color('primary')->markAsRead(),
                                    ])
                                    ->toDatabase();

                                // Kirim job ke queue
                                NotificationFromSharingNote::dispatch($data['user_id'], $notification);
                            }

                            Notification::make()
                                ->title(__('notes.action.sharing_notification.my_notif_title'))
                                ->success()
                                ->send();
                        }
                    })
                    ->form([
                        Forms\Components\Toggle::make('is_public')
                            ->onIcon('heroicon-m-lock-open')
                            ->offIcon('heroicon-m-lock-closed')
                            ->onColor(Color::Green)
                            ->offColor(Color::Red)
                            ->live(onBlur: true)
                            ->label('Public Note')
                            ->helperText(new HtmlString('This note will be visible to all users.'))
                            ->default(function (Note $record) {
                                return $record->is_public;
                            })
                            ->required(),
                        Forms\Components\Select::make('user_id')
                            ->multiple()
                            ->label(__('notes.action.modal.sharing.title'))
                            ->helperText(__('notes.action.modal.sharing.description'))
                            ->live(onBlur: true)
                            ->hidden(fn(callable $get) => $get('is_public'))
                            ->default(function (Note $record): array {
                                return $record->users()
                                    ->wherePivot('is_owner', false)
                                    ->pluck('users.id')
                                    ->toArray();
                            })
                            ->getOptionLabelsUsing(function (array $values): array {
                                return User::whereIn('id', $values)->pluck('username', 'id')->toArray();
                            })

                            ->getSearchResultsUsing(function (string $search): array {
                                return User::where('username', 'like', "%{$search}%")
                                    ->where('id', '!=', auth()->id())
                                    ->limit(10)
                                    ->pluck('username', 'id')
                                    ->toArray();
                            })
                            ->searchable(),
                    ]),
            ])
            ->recordUrl(function ($record) {
                //if owner, click to edit
                $owner = $record->users->firstWhere('pivot.is_owner', true)?->id == auth()->id();
                $url = static::getUrl($owner ? 'edit' : 'view', ['record' => $record]);
                return $url;
            })
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
            CommentsRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotes::route('/'),
            'create' => Pages\CreateNote::route('/create'),
            'view' => Pages\ViewNote::route('/{record}'),
            'edit' => Pages\EditNote::route('/{record}/edit'),
        ];
    }

    //eloquent query
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('users', 'comments.user')
            ->withCount('comments')
            ->where(function ($query) {
                $query->where('is_public', true)
                    ->orWhereHas('users', function ($q) {
                        $q->where('users.id', auth()->id());
                    });
            })->orderByDesc('updated_at');
    }
}
