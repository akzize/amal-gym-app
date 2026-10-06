<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
	public function run(): void
	{
		$superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
		$admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
		$staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

		// Permissions come from `shield:generate`; super_admin bypasses them via Gate.
		$admin->syncPermissions(Permission::all());
		$staff->syncPermissions(Permission::where('name', 'like', 'View%')->get());

		$users = [
			['Super Admin', 'superadmin@amal-gym.test', $superAdmin],
			['Admin', 'admin@amal-gym.test', $admin],
			['Staff', 'staff@amal-gym.test', $staff],
		];

		foreach ($users as [$name, $email, $role]) {
			$user = User::updateOrCreate(
				['email' => $email],
				['name' => $name, 'password' => 'password'],
			);
			$user->syncRoles([$role]);
		}
	}
}
