<?php

namespace App\Providers;

use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\Number;
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

        // the app locale is Arabic, but numbers must always use western digits (0-9)
        Number::useLocale('en');
        Table::configureUsing(fn (Table $table) => $table->defaultNumberLocale('en'));
        Schema::configureUsing(fn (Schema $schema) => $schema->defaultNumberLocale('en'));
    }
}
