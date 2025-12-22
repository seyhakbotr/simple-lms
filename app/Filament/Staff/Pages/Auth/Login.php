<?php

namespace App\Filament\Staff\Pages\Auth;

use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    public function mount(): void
    {
        parent::mount();

        if (auth()->check() && auth()->user()->role?->name === 'admin') {
            $this->redirect('/admin');
        }

        $this->form->fill([
            'email' => 'lina@gmail.com',
            'password' => 'staff002',
        ]);
    }

    public function getRedirectUrl(): string
    {
        if (auth()->check() && auth()->user()->role?->name === 'admin') {
            return url('/admin');
        }

        return parent::getRedirectUrl();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getEmailFormComponent()->label('Email'),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ]);
    }
}
