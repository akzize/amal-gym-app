<?php

namespace Tests\Feature;

use App\Filament\Resources\Trainees\Pages\CreateTrainee;
use App\Models\Trainee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class CreateTraineeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // User doesn't implement FilamentUser, so Filament only lets it into the panel in "local"
        config(['app.env' => 'local']);
        Gate::before(fn() => true);
        $this->actingAs(User::factory()->create());
    }

    public function test_names_and_gender_are_enough(): void
    {
        Livewire::test(CreateTrainee::class)
            ->fillForm(['full_name' => 'Amine Alaoui', 'full_arabic_name' => 'أمين العلوي', 'gender' => 'male'])
            ->call('create')
            ->assertHasNoFormErrors();

        $trainee = Trainee::sole();
        $this->assertNull($trainee->dob);
        $this->assertNull($trainee->phone);
        $this->assertNull($trainee->address);
    }

    public function test_both_names_and_gender_are_required(): void
    {
        Livewire::test(CreateTrainee::class)
            ->fillForm(['full_name' => '', 'full_arabic_name' => '', 'gender' => null])
            ->call('create')
            ->assertHasFormErrors(['full_name' => 'required', 'full_arabic_name' => 'required', 'gender' => 'required']);

        $this->assertSame(0, Trainee::count());
    }
}
