<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Profile extends Component
{
    public string $name = '';

    public string $email = '';

    public string $language = 'id';

    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPassword_confirmation = '';

    public function mount(): void
    {
        $this->name = auth()->user()->name;
        $this->email = auth()->user()->email;
        $this->language = session('locale', 'id');
    }

    public function updatedLanguage(string $language): void
    {
        if (! in_array($language, ['id', 'en', 'ja'], true)) {
            $this->language = session('locale', 'id');
            return;
        }

        session(['locale' => $language]);
        app()->setLocale($language);
        $this->dispatch('locale-changed', locale: $language);
        $this->redirect(route('profile'), navigate: true);
    }

    public function updateProfile(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore(auth()->id())],
        ]);

        auth()->user()->update(['name' => $this->name, 'email' => $this->email]);
        $this->dispatch('profile-updated');
        $this->dispatch('app-toast', message: __('ui.profile_updated'));
    }

    public function updatePassword(): void
    {
        $this->validate([
            'currentPassword' => ['required', 'current_password'],
            'newPassword' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'currentPassword.current_password' => __('ui.password_incorrect'),
            'newPassword.confirmed' => __('ui.password_confirmation'),
        ]);

        auth()->user()->update(['password' => Hash::make($this->newPassword)]);
        $this->reset('currentPassword', 'newPassword', 'newPassword_confirmation');
        $this->dispatch('password-updated');
        $this->dispatch('app-toast', message: __('ui.password_updated'));
    }

    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return $this->redirect('/', navigate: true);
    }

    public function render()
    {
        return view('livewire.profile', ['user' => auth()->user()]);
    }
}
