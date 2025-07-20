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
use Filament\Support\Enums\FontWeight;
use Filament\Forms\Components\Fieldset;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\RichEditor;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\NoteResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\Layout\Grid as GridTable;
use App\Filament\Resources\NoteResource\RelationManagers;

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

                        //for make low query, update if is_public changed
                        if ($record->is_public !== $data['is_public']) {
                            $record->is_public = $data['is_public'];
                            $record->save();
                        }

                        //check is owner
                        $isOwner = $record->users
                            ->firstWhere(fn($user) => $user->pivot->is_owner);

                        $isOwner = $isOwner ? [$isOwner->id, 'is_owner' => false] : [];


                        $newUsers = collect($data['user_id'])->mapWithKeys(fn($id) => [$id, 'is_owner' => false]);

                        dd($isOwner, $newUsers, $data);
                        // sync
                        $record->users()->sync($syncData);
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
                            //can give access to multiple users
                            ->multiple()
                            ->label('username')
                            //search from username
                            ->getSearchResultsUsing(function (string $search): array {
                                return User::where('username', 'like', "%{$search}%")
                                    ->where('id', '!=', auth()->id())
                                    ->limit(10)
                                    ->pluck('username', 'id')
                                    ->toArray();
                            })
                            ->default(function (Note $record) {
                                // default selected for remove sharing to spesific user
                                return $record->users
                                    ->filter(fn($user) => !$user->pivot->is_owner)
                                    ->pluck('username' , 'id')
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(fn($value) => User::find($value)?->username)
                            ->searchable()
                            ->hidden(fn($record, $get) => $get('is_public')),
                        // ...
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
            ->where('is_public', true)
            ->orWhereHas('users', function ($query) {
                $query->where('users.id', auth()->id());
            });
    }
}
