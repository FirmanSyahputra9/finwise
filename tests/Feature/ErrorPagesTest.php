<?php

use App\Support\EnvironmentMaintenanceSettings;

it('renders the custom branded page for missing routes', function () {
    $this->get('/route-that-does-not-exist')
        ->assertNotFound()
        ->assertSee('404')
        ->assertSee(__('ui.error_404_title'))
        ->assertSee(__('ui.error_back_home'));
});

it('renders a custom page for unsupported methods', function () {
    $this->post(route('transactions'))
        ->assertStatus(405)
        ->assertSee('405')
        ->assertSee(__('ui.error_405_title'));
});

it('renders the custom maintenance page without app data', function () {
    $html = view('errors.maintenance', ['retryAfter' => 60])->render();

    expect($html)
        ->toContain('503')
        ->toContain(__('ui.error_503_title'))
        ->toContain('content="60"');
});

it('reads the environment maintenance flag and custom settings', function () {
    $settings = EnvironmentMaintenanceSettings::fromDotEnv(<<<'ENV'
APP_MAINTENANCE=true
APP_MAINTENANCE_MESSAGE="Kami sedang memperbarui aplikasi"
APP_MAINTENANCE_RETRY=45
ENV);

    expect($settings)->toBe([
        'enabled' => true,
        'message' => 'Kami sedang memperbarui aplikasi',
        'retryAfter' => 45,
    ]);
});

it('treats maintenance as disabled when the environment flag is false', function () {
    $settings = EnvironmentMaintenanceSettings::fromDotEnv("APP_MAINTENANCE=false\n");

    expect($settings['enabled'])->toBeFalse();
});
