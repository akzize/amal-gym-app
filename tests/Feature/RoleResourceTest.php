<?php

namespace Tests\Feature;

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleResourceTest extends TestCase
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

    public function test_creating_a_role_saves_the_arabic_name_without_making_it_a_permission(): void
    {
        $permissionsBefore = Permission::count();

        Livewire::test(CreateRole::class)
            ->fillForm(['name' => 'cashier', 'name_ar' => 'أمين الصندوق', 'guard_name' => 'web'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('أمين الصندوق', Role::findByName('cashier')->name_ar);
        // Shield syncs every other form key as a permission; name_ar must not end up there
        $this->assertSame($permissionsBefore, Permission::count());
        $this->assertFalse(Permission::where('name', 'أمين الصندوق')->exists());
    }

    public function test_editing_the_arabic_name_keeps_the_role_permissions(): void
    {
        $role = Role::create(['name' => 'staff']);
        $role->givePermissionTo(Permission::create(['name' => 'View:User']));

        Livewire::test(EditRole::class, ['record' => $role->id])
            ->fillForm(['name_ar' => 'موظف'])
            ->call('save')
            ->assertHasNoFormErrors();

        $role->refresh();
        $this->assertSame('موظف', $role->name_ar);
        $this->assertSame(['View:User'], $role->getPermissionNames()->all());
    }

    public function test_user_form_lists_roles_by_arabic_name_with_fallback(): void
    {
        Role::create(['name' => 'admin', 'name_ar' => 'مدير']);
        Role::create(['name' => 'night_guard']);

        $this->assertSame('مدير', RoleResource::labelFor('admin'));
        $this->assertSame('Night Guard', RoleResource::labelFor('night_guard'));

        Livewire::test(CreateUser::class)
            ->assertFormFieldExists('role', fn($field): bool => $field->getOptions() === ['admin' => 'مدير', 'night_guard' => 'Night Guard']);
    }
}
