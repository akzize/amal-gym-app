<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Trainer;
use App\Models\User;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // User doesn't implement FilamentUser, so Filament only lets it into the panel in "local"
        config(['app.env' => 'local']);

        $role = Role::create(['name' => 'admin']);
        Role::create(['name' => 'staff']);
        foreach (['ViewAny', 'View', 'Create', 'Update', 'Delete', 'Restore', 'RestoreAny'] as $ability) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => "{$ability}:User"]));
        }

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->actingAs($this->admin);
    }

    public function test_editing_without_a_password_keeps_the_current_one(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $user->assignRole('staff');

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm(['name' => 'Renamed'])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();
        $this->assertSame('Renamed', $user->name);
        $this->assertTrue(Hash::check('old-password', $user->password));
    }

    public function test_password_change_needs_a_matching_confirmation(): void
    {
        $user = User::factory()->create();
        $user->assignRole('staff');

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm(['password' => 'new-password', 'password_confirmation' => 'other-password'])
            ->call('save')
            ->assertHasFormErrors(['password_confirmation' => 'same']);

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm(['password' => 'new-password', 'password_confirmation' => 'new-password'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_duplicate_email_is_a_validation_error(): void
    {
        $taken = User::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('staff');

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm(['email' => $taken->email])
            ->call('save')
            ->assertHasFormErrors(['email' => 'unique']);
    }

    public function test_create_saves_a_single_role_and_hashes_the_password(): void
    {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'New Staff',
                'email' => 'staff@example.com',
                'role' => 'staff',
                'password' => 'secret-pass',
                'password_confirmation' => 'secret-pass',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'staff@example.com')->sole();
        $this->assertSame(['staff'], $user->getRoleNames()->all());
        $this->assertTrue(Hash::check('secret-pass', $user->password));
    }

    public function test_role_can_be_changed_but_not_your_own(): void
    {
        $user = User::factory()->create();
        $user->assignRole('staff');

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->assertFormSet(['role' => 'staff'])
            ->fillForm(['role' => 'admin'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame(['admin'], $user->refresh()->getRoleNames()->all());

        Livewire::test(EditUser::class, ['record' => $this->admin->id])
            ->assertFormFieldIsDisabled('role')
            ->fillForm(['role' => 'staff'])
            ->call('save');
        $this->assertSame(['admin'], $this->admin->refresh()->getRoleNames()->all());
    }

    public function test_deleting_disables_login_but_keeps_trainer_history(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass']);
        $trainer = Trainer::create(['name' => 'Coach', 'user_id' => $user->id, 'salary_type' => 'fixed', 'salary_amount' => 3000]);
        $trainer->recordPayoutInstallment(now(), 1000);

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->callAction('delete');

        $this->assertSoftDeleted($user);
        $this->assertModelExists($trainer);
        $this->assertSame(1, $trainer->payouts()->count());
        $this->assertTrue($trainer->refresh()->user->is($user));

        Auth::logout();
        $this->assertFalse(Auth::attempt(['email' => $user->email, 'password' => 'secret-pass']));

        // A deleted user still opens in the edit page and can be restored
        $this->actingAs($this->admin);
        Livewire::test(EditUser::class, ['record' => $user->id])
            ->callAction('restore');
        $this->assertNotSoftDeleted($user);
    }

    public function test_you_cannot_delete_yourself(): void
    {
        Livewire::test(EditUser::class, ['record' => $this->admin->id])
            ->assertActionHidden('delete');

        $other = User::factory()->create();

        Livewire::test(ListUsers::class)
            ->callTableBulkAction(DeleteBulkAction::class, [$this->admin, $other]);

        $this->assertNotSoftDeleted($this->admin);
        $this->assertSoftDeleted($other);
    }
}
