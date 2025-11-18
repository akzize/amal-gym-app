<?php

namespace App\Filament\Resources\Trainees\Tables;

use App\Models\Trainee;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\IconSize;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Browsershot\Browsershot;
use Spatie\LaravelPdf\Facades\Pdf;
use Torgodly\Html2Media\Actions\Html2MediaAction;

use function Livewire\after;

class TraineesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo_url')
                    ->circular()
                    ->defaultImageUrl(url('storage/trainees/default.png')),
                TextColumn::make('full_name')
                    ->searchable(),
                TextColumn::make('full_arabic_name')
                    ->searchable(),
                TextColumn::make('gender')
                    ->badge(),
                TextColumn::make('dob')
                    ->date()
                    ->sortable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->label('')
                    ->iconSize(IconSize::Medium),
                DeleteAction::make()
                    ->label('')
                    ->iconSize(IconSize::Medium),
                Html2MediaAction::make('print')
                    ->content(fn ($record) => view('cards.trainee', ['trainees' => collect([$record])]))
                    ->savePdf()
                    // ->print()
                    ->format('a4', 'mm') // A4 format with mm units
                    ->enableLinks() // Enable links in PDF
                    ->filename(filename: 'my-custom-document')
                    ->icon('heroicon-o-identification')
                    ->label('')
                    ->iconSize(IconSize::Medium),

            ])
            ->toolbarActions([
                // BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    Html2MediaAction::make('export')
                        ->accessSelectedRecords()
                        ->content(fn (Collection $records) => view('cards.trainee', ['trainees' => $records]))
                        // ->scale(2)
                        // ->print()
                        ->savePdf()
                        ->format('a4', 'mm') // A4 format with mm units
                        ->margins(0,0,0,0)
                        ->filename(filename: 'trainees-export')
                        ->icon('heroicon-o-identification')
                        ->label('Export Selected')
                        ->iconSize(IconSize::Medium),
                // ]),
            ]);
    }
}
