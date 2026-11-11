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

    public function test_table_shows_groups_and_sports_and_filters_by_group(): void
    {
        $association = \App\Models\Association::create(['name' => 'Club']);
        $group = fn(string $name, string $sport) => \App\Models\Group::forceCreate([
            'name' => $name, 'association_id' => $association->id,
            'sport_id' => \App\Models\Sport::forceCreate(['name' => $sport, 'name_ar' => $sport])->id,
        ]);
        $judo = $group('G-Judo', 'Judo');
        $karate = $group('G-Karate', 'Karate');

        $trainee = fn(string $name) => Trainee::forceCreate(['full_name' => $name, 'full_arabic_name' => $name, 'gender' => 'male']);
        $both = $trainee('Amine');
        $both->groups()->attach([$judo->id, $karate->id]);
        $karateOnly = $trainee('Badr');
        $karateOnly->groups()->attach($karate->id);
        $none = $trainee('Chama');

        Livewire::test(\App\Filament\Resources\Trainees\Pages\ListTrainees::class)
            ->assertSee(['G-Judo', 'G-Karate', 'Judo', 'Karate'])
            ->assertTableColumnStateSet('sports', ['Judo', 'Karate'], $both)
            ->filterTable('groups', [$judo->id])
            ->assertCanSeeTableRecords([$both])
            ->assertCanNotSeeTableRecords([$karateOnly, $none])
            ->filterTable('groups', [$judo->id, $karate->id])
            ->assertCanSeeTableRecords([$both, $karateOnly])
            ->assertCanNotSeeTableRecords([$none]);
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
