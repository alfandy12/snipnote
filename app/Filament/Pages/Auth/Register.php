<?php

namespace App\Filament\Pages\Auth;

use App\Models\Team;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Pages\Auth\Register as BaseRegister;
use Closure;
use Filament\Forms\Get;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class Register extends BaseRegister
{

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getUsernameFormcomponent(),
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                        // $this->getPhoneFormcomponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }
    // protected function getPhoneFormcomponent()
    // {
    //     return PhoneInput::make('phone')->defaultCountry('ID');
    // }
    protected function getUsernameFormcomponent()
    {
        return \Filament\Forms\Components\TextInput::make('username')
            ->label('Username')
            ->unique('users', 'username')->regex('/^(?!.*[\s])(?!.*[^\w\.\-_])([\w\.\-_]+)$/')->validationMessages([
                'regex' => __('auth.username_regex')
            ])
            ->required();
    }
    protected function handleRegistration(array $data): User
    {
        //$data['phone'] = preg_replace('/\D/', '', $data['phone']);
        $user = User::create($data);
        return $user;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'User registered';
    }
}
