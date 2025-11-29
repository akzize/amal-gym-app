<?php

namespace App\Providers;

use Filament\Forms\Components\DatePicker;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // prevent fron using the native date picker globally
        DatePicker::configureUsing(function(DatePicker $datePicker){
            $datePicker->native(false);
        });
    }
}
