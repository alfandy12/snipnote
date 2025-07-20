<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Note;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Support\Enums\FontWeight;
use Filament\Forms\Components\Fieldset;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use App\Jobs\NotificationFromSharingNote;
use Filament\Forms\Components\RichEditor;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\NoteResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\Layout\Grid as GridTable;
use App\Filament\Resources\NoteResource\RelationManagers;
use Filament\Forms\Components\Actions\Action as ActionForm;
use Filament\Notifications\Actions\Action as ActionNotification;

class NoteResource extends Resource
{
    protected static ?string $model = Note::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
                Forms\Components\Toggle::make('is_pinned')
                    ->required(),
                Section::make('comments')
                    ->schema([
                        Forms\Components\Textarea::make('comment'),
                    ])
                    ->footerActions([
                        ActionForm::make('submit')
                            ->action(function () {
                                // ...
                            }),
                    ])
                    ->collapsed()
                        ->visible(fn ($livewire) => !($livewire instanceof CreateRecord))
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
                                // ->visible(fn(Model $record)  => dd($record))
                                ->formatStateUsing(fn(string $state): string => "{$state} comments")
                                ->badge()
                                ->color('success'),

                        ])
                        ->columns(1),
                ]),
            ])
            //->modifyQueryUsing(fn(Builder $query) => $query->when($this->activeTab != 'all', fn($q) => $q->whereHas('category', fn(Builder $query) => $query->where('slug', $this->activeTab))))
            ->contentGrid([
                'default' => 2,
                'md' => 3,
                'lg' => 4,
                'xl' => 5,
            ])

            // Tables\Columns\IconColumn::make('is_public')
            //     ->boolean(),
            // Tables\Columns\IconColumn::make('is_pinned')
            //     ->boolean(),
            // Tables\Columns\TextColumn::make('title')
            //     ->searchable(),
            // Tables\Columns\TextColumn::make('created_at')
            //     ->dateTime()
            //     ->sortable()
            //     ->toggleable(isToggledHiddenByDefault: true),
            // Tables\Columns\TextColumn::make('updated_at')
            //     ->dateTime()
            //     ->sortable()
            //     ->toggleable(isToggledHiddenByDefault: true),

            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\ViewAction::make()
                //     ->iconButton(),
                Tables\Actions\EditAction::make()
                    ->iconButton(),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->requiresConfirmation(),
                Action::make('sharing')
                    //cant sharing spesific user if not owner
                    ->hidden(fn(Model $record) => ! $record->users->contains(fn($user) => $user->id === auth()->id() && $user->pivot->is_owner))
                    ->iconButton()
                    ->color(Color::Cyan)
                    ->icon('heroicon-m-share')
                    ->action(function (array $data, Note $record): void {
                        dd($record);
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
                                    ->title("{$username} telah membagikan sebuah catatan kepada kamu.")
                                    ->actions([
                                        ActionNotification::make('Lihat')
                                            ->url(NoteResource::getUrl('view', ['record' => $record]))
                                            ->button()
                                            ->color('primary'),
                                    ])
                                    ->toDatabase();

                                // Kirim job ke queue
                                NotificationFromSharingNote::dispatch($data['user_id'], $notification);
                            }

                            Notification::make()
                                ->title('You have successfully updated sharing on this note.')
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
                            ->label('Bagikan dengan Pengguna')
                            ->helperText('Hapus atau tambah pengguna untuk berbagi catatan ini.')
                            ->live(onBlur: true)
                            ->hidden(fn(callable $get) => $get('is_public'))

                            // 1. Mengatur NILAI DEFAULT yang terpilih (berupa array ID)
                            ->default(function (Note $record): array {
                                return $record->users()
                                    ->wherePivot('is_owner', false)
                                    ->pluck('users.id')
                                    ->toArray();
                            })

                            // 2. Mengambil LABEL untuk nilai yang SUDAH TERPILIH (SANGAT PENTING!)
                            // Fungsi ini akan mengubah array ID [3, 6] menjadi [3 => 'nama_user_3', 6 => 'nama_user_6']
                            ->getOptionLabelsUsing(function (array $values): array {
                                return User::whereIn('id', $values)->pluck('username', 'id')->toArray();
                            })

                            // 3. Mengatur fungsi PENCARIAN
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
            ->recordUrl(
                fn($record) => static::getUrl('view', ['record' => $record])
            )
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
            ->with('users', 'comments')
            ->withCount('comments')
            ->where('is_public', true)
            ->orWhereHas('users', function ($query) {
                $query->where('users.id', auth()->id());
            });
    }
}
