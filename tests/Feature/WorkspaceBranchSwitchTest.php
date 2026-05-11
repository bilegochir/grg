<?php

use App\Models\Branch;
use App\Models\User;

it('allows a global user to switch the active workspace branch', function () {
    $branch = Branch::factory()->create(['name' => 'Downtown']);
    $user = grantPermissions(User::factory()->create(['branch_id' => null]), ['dashboard.view']);

    $this->actingAs($user)
        ->post(route('workspace.branch.update'), ['branch_id' => $branch->id])
        ->assertRedirect();

    expect(session('workspace_branch_id'))->toBe($branch->id);
});

it('scopes staff page to the selected workspace branch for global users', function () {
    $branchA = Branch::factory()->create(['name' => 'Central']);
    $branchB = Branch::factory()->create(['name' => 'East']);

    $user = grantPermissions(User::factory()->create(['branch_id' => null]), ['staff.manage']);

    User::factory()->create([
        'name' => 'Ariun',
        'branch_id' => $branchA->id,
    ]);

    User::factory()->create([
        'name' => 'Mika',
        'branch_id' => $branchB->id,
    ]);

    $response = $this->actingAs($user)
        ->withSession(['workspace_branch_id' => $branchA->id])
        ->get(route('staff.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Staff/Index')
        ->has('staff', 1)
        ->where('staff.0.name', 'Ariun')
        ->has('branches', 1)
        ->where('branches.0.name', 'Central'));
});
