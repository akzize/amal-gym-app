<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    public function test_404_page_renders_with_gym_branding(): void
    {
        $response = $this->get('/non-existent-route-for-testing-gym-error');

        $response->assertStatus(404);
        $response->assertSee('404');
        $response->assertSee('نادي أمل للرياضة');
        $response->assertSee('amal-gym-logo.png');
    }

    public function test_error_page_shows_custom_message(): void
    {
        \Illuminate\Support\Facades\Route::get('/test-abort-custom-msg', function () {
            abort(404, 'Member subscription has expired at Amal Gym');
        });

        $response = $this->get('/test-abort-custom-msg');

        $response->assertStatus(404);
        $response->assertSee('Member subscription has expired at Amal Gym');
    }

    public function test_403_page_renders_properly(): void
    {
        \Illuminate\Support\Facades\Route::get('/test-abort-403', function () {
            abort(403, 'VIP locker room access denied');
        });

        $response = $this->get('/test-abort-403');

        $response->assertStatus(403);
        $response->assertSee('403');
        $response->assertSee('VIP locker room access denied');
    }

    public function test_500_page_renders_properly(): void
    {
        \Illuminate\Support\Facades\Route::get('/test-abort-500', function () {
            abort(500, 'Weight rack database connection failure');
        });

        $response = $this->get('/test-abort-500');

        $response->assertStatus(500);
        $response->assertSee('500');
        $response->assertSee('Weight rack database connection failure');
    }

    public function test_503_page_renders_properly(): void
    {
        \Illuminate\Support\Facades\Route::get('/test-abort-503', function () {
            abort(503, 'Gym undergoing annual maintenance');
        });

        $response = $this->get('/test-abort-503');

        $response->assertStatus(503);
        $response->assertSee('503');
        $response->assertSee('Gym undergoing annual maintenance');
    }

    public function test_419_page_renders_properly(): void
    {
        \Illuminate\Support\Facades\Route::get('/test-abort-419', function () {
            abort(419, 'Workout session expired');
        });

        $response = $this->get('/test-abort-419');

        $response->assertStatus(419);
        $response->assertSee('419');
        $response->assertSee('Workout session expired');
    }
}
