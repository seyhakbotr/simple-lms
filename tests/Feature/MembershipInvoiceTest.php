<?php

use App\Filament\Admin\Resources\UserResource as AdminUserResource;
use App\Filament\Staff\Resources\UserResource as StaffUserResource;
use App\Models\Invoice;
use App\Models\MembershipType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('admin can create user with paid membership and invoice is generated', function () {
    $adminRole = Role::factory()->create(['name' => 'admin']);
    $borrowerRole = Role::factory()->create(['name' => 'borrower']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);
    $paidMembership = MembershipType::factory()->create([
        'name' => 'Gold',
        'membership_fee' => 50,
        'is_active' => true,
    ]);
    $this->actingAs($admin);

    $newUser = User::factory()->make();

    Livewire::test(AdminUserResource\Pages\CreateUser::class)
        ->fillForm([
            'name' => $newUser->name,
            'email' => $newUser->email,
            'password' => 'password',
            'passwordConfirmation' => 'password',
            'role_id' => $borrowerRole->id,
            'membership_type_id' => $paidMembership->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('users', [
        'email' => $newUser->email,
        'membership_type_id' => $paidMembership->id,
    ]);

    $this->assertDatabaseHas('invoices', [
        'user_id' => User::whereEmail($newUser->email)->first()->id,
        'total_amount' => $paidMembership->membership_fee,
    ]);
});

test('admin can create user with free membership and no invoice is generated', function () {
    $adminRole = Role::factory()->create(['name' => 'admin']);
    $borrowerRole = Role::factory()->create(['name' => 'borrower']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);
    $freeMembership = MembershipType::factory()->create([
        'name' => 'Bronze',
        'membership_fee' => 0,
        'is_active' => true,
    ]);
    $this->actingAs($admin);

    $newUser = User::factory()->make();

    Livewire::test(AdminUserResource\Pages\CreateUser::class)
        ->fillForm([
            'name' => $newUser->name,
            'email' => $newUser->email,
            'password' => 'password',
            'passwordConfirmation' => 'password',
            'role_id' => $borrowerRole->id,
            'membership_type_id' => $freeMembership->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('users', [
        'email' => $newUser->email,
        'membership_type_id' => $freeMembership->id,
    ]);

    $this->assertDatabaseMissing('invoices', [
        'user_id' => User::whereEmail($newUser->email)->first()->id,
    ]);
});

test('staff can create user with default membership and invoice is generated if fee is not zero', function () {
    $staffRole = Role::factory()->create(['name' => 'staff']);
    $borrowerRole = Role::factory()->create(['name' => 'borrower']);
    $staff = User::factory()->create(['role_id' => $staffRole->id]);
    $paidMembership = MembershipType::factory()->create([
        'name' => 'Gold',
        'membership_fee' => 50,
        'is_active' => true,
    ]);
    MembershipType::factory()->create([
        'name' => 'Bronze',
        'membership_fee' => 0,
        'is_active' => false,
    ]);
    $this->actingAs($staff);

    $newUser = User::factory()->make();

    Livewire::test(StaffUserResource\Pages\CreateUser::class)
        ->fillForm([
            'name' => $newUser->name,
            'email' => $newUser->email,
            'password' => 'password',
            'passwordConfirmation' => 'password',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('users', [
        'email' => $newUser->email,
        'membership_type_id' => $paidMembership->id,
    ]);

    $this->assertDatabaseHas('invoices', [
        'user_id' => User::whereEmail($newUser->email)->first()->id,
        'total_amount' => $paidMembership->membership_fee,
    ]);
});

test('admin can edit user and change membership to paid and invoice is generated', function () {
    $adminRole = Role::factory()->create(['name' => 'admin']);
    $borrowerRole = Role::factory()->create(['name' => 'borrower']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);
    $paidMembership = MembershipType::factory()->create([
        'name' => 'Gold',
        'membership_fee' => 50,
        'is_active' => true,
    ]);
    $freeMembership = MembershipType::factory()->create([
        'name' => 'Bronze',
        'membership_fee' => 0,
        'is_active' => true,
    ]);
    $user = User::factory()->create([
        'role_id' => $borrowerRole->id,
        'membership_type_id' => $freeMembership->id,
    ]);
    $this->actingAs($admin);


    Livewire::test(AdminUserResource\Pages\EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm([
            'membership_type_id' => $paidMembership->id,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'membership_type_id' => $paidMembership->id,
    ]);

    $this->assertDatabaseHas('invoices', [
        'user_id' => $user->id,
        'total_amount' => $paidMembership->membership_fee,
    ]);
});
