<?php

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\AccessControl;

function seedAccessControl(): array
{
    $permissions = collect(AccessControl::permissions())
        ->map(fn (array $permission) => Permission::query()->create($permission));

    $roles = collect(AccessControl::defaultRoles())
        ->map(function (array $roleData) use ($permissions): Role {
            $role = Role::query()->create(collect($roleData)->except('permissions')->all());
            $role->permissions()->sync($permissions->whereIn('name', $roleData['permissions'])->pluck('id')->all());

            return $role;
        });

    return [$permissions, $roles];
}

it('blocks staff workspace without the required permission', function () {
    [$permissions] = seedAccessControl();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('staff.index'));

    $response->assertForbidden();
});

it('renders the staff workspace for authorized users', function () {
    [$permissions, $roles] = seedAccessControl();

    $branch = Branch::factory()->create(['name' => 'HQ']);
    $user = User::factory()->create(['branch_id' => $branch->id]);
    $user->roles()->sync([$roles->firstWhere('slug', 'manager')->id]);

    $response = $this->actingAs($user)->get(route('staff.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Staff/Index')
        ->has('staff')
        ->has('roles')
        ->has('branches'));
});

it('updates staff roles, branch, and direct permissions', function () {
    [$permissions, $roles] = seedAccessControl();

    $admin = User::factory()->create();
    $admin->permissions()->sync([$permissions->firstWhere('name', 'staff.manage')->id]);

    $branch = Branch::factory()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($admin)->patch(route('staff.users.update', $user), [
        'name' => 'Updated Staff',
        'email' => 'updated.staff@example.com',
        'job_title' => 'Support Specialist',
        'branch_id' => $branch->id,
        'is_active' => true,
        'role_ids' => [$roles->firstWhere('slug', 'support')->id],
        'permission_ids' => [$permissions->firstWhere('name', 'documents.review')->id],
    ]);

    $response->assertRedirect();

    $user->refresh();

    expect($user->name)->toBe('Updated Staff');
    expect($user->branch_id)->toBe($branch->id);
    expect($user->roles()->pluck('slug')->all())->toBe(['support']);
    expect($user->permissions()->pluck('name')->all())->toBe(['documents.review']);
});

it('updates role permissions', function () {
    [$permissions, $roles] = seedAccessControl();

    $admin = User::factory()->create();
    $admin->permissions()->sync([$permissions->firstWhere('name', 'staff.manage')->id]);
    $role = $roles->firstWhere('slug', 'agent');

    $response = $this->actingAs($admin)->patch(route('staff.roles.update', $role), [
        'description' => 'Updated role',
        'permission_ids' => [
            $permissions->firstWhere('name', 'cases.assign')->id,
            $permissions->firstWhere('name', 'documents.review')->id,
        ],
    ]);

    $response->assertRedirect();

    $role->refresh();

    expect($role->description)->toBe('Updated role');
    expect($role->permissions()->pluck('name')->all())->toBe(['cases.assign', 'documents.review']);
});

it('creates and updates branches', function () {
    [$permissions] = seedAccessControl();

    $admin = User::factory()->create();
    $admin->permissions()->sync([$permissions->firstWhere('name', 'staff.manage')->id]);

    $createResponse = $this->actingAs($admin)->post(route('staff.branches.store'), [
        'name' => 'Tokyo Desk',
        'slug' => 'tokyo-desk',
        'code' => 'TOKY',
        'is_active' => true,
    ]);

    $createResponse->assertRedirect();

    $branch = Branch::query()->where('slug', 'tokyo-desk')->firstOrFail();

    $updateResponse = $this->actingAs($admin)->patch(route('staff.branches.update', $branch), [
        'name' => 'Tokyo Operations',
        'slug' => 'tokyo-operations',
        'code' => 'TOK2',
        'is_active' => false,
    ]);

    $updateResponse->assertRedirect();

    $branch->refresh();

    expect($branch->name)->toBe('Tokyo Operations');
    expect($branch->is_active)->toBeFalse();
});
