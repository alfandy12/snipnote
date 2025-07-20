<?php

namespace App\Livewire;

use Filament\Forms;
use Livewire\Component;
use Filament\Forms\Form;
use Illuminate\Contracts\View\View;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Joaopaulolndev\FilamentEditProfile\Concerns\HasSort;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Joaopaulolndev\FilamentEditProfile\Concerns\HasUser;

class CustomProfileComponent extends Component implements HasForms
{
    use InteractsWithForms;
    use HasSort;
    use HasUser;

    public $userClass;

    public ?array $data = [];

    protected static int $sort = 0;

    public function mount(): void
    {

        $this->user = $this->getUser();

        $this->userClass = get_class($this->user);

        $fields = ['name', 'email', 'username'];

        $this->form->fill($this->user->only($fields));
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('filament-edit-profile::default.profile_information'))
                    ->aside()
                    ->description(__('filament-edit-profile::default.profile_information_description'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('filament-edit-profile::default.name'))
                            ->required(),
                        TextInput::make('username')
                            ->required()
                            ->unique($this->userClass, ignorable: $this->user),
                        TextInput::make('email')
                            ->label(__('filament-edit-profile::default.email'))
                            ->email()
                            ->required()
                            ->hidden(! filament('filament-edit-profile')->getShouldShowEmailForm())
                            ->unique($this->userClass, ignorable: $this->user),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            $this->user->update($data);
        } catch (Halt $exception) {
            return;
        }

        Notification::make()
            ->success()
            ->title(__('filament-edit-profile::default.saved_successfully'))
            ->send();
    }

    public function render(): View
    {
        return view('livewire.custom-profile-component');
    }
}
