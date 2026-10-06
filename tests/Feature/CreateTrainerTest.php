<?php

namespace Tests\Feature;

use App\Filament\Resources\Trainers\Pages\CreateTrainer;
use App\Models\Trainer;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class CreateTrainerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // User doesn't implement FilamentUser, so Filament only lets it into the panel in "local"
        config(['app.env' => 'local']);
        Gate::before(fn () => true);
        Filament::setCurrentPanel('admin');

        $this->actingAs(User::factory()->create());
    }

    private function fillForm(): array
    {
        return [
            'name' => 'Coach Test',
            'user.email' => 'coach@example.com',
            'user.password' => 'secret-password',
            'salary_type' => 'fixed',
            'salary_amount' => 2000,
            'status' => 'active',
        ];
    }

    public function test_it_creates_the_trainer_with_its_login_user(): void
    {
        Livewire::test(CreateTrainer::class)
            ->fillForm($this->fillForm())
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'coach@example.com')->firstOrFail();
        $this->assertDatabaseHas('trainers', ['name' => 'Coach Test', 'user_id' => $user->id]);
    }

    public function test_login_user_is_rolled_back_when_trainer_creation_fails(): void
    {
        Trainer::creating(fn () => throw new RuntimeException('Trainer insert failed'));

        try {
            Livewire::test(CreateTrainer::class)
                ->fillForm($this->fillForm())
                ->call('create');
        } catch (RuntimeException) {
            // expected
        }

        $this->assertDatabaseMissing('users', ['email' => 'coach@example.com']);
        $this->assertDatabaseCount('trainers', 0);
    }
}
