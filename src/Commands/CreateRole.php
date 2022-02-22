<?php

namespace Cyberion\Mongodb\Permission\Commands;

use Illuminate\Console\Command;

/**
 * Class CreateRole
 * @package Cyberion\Mongodb\Permission\Commands
 */
class CreateRole extends Command {
    protected $signature = 'permission:create-role
        {name : The name of the role}
        {guard? : The name of the guard}
        {--permission=* : The name of the permission}';
    protected $description = 'Create a role';

    public function handle(): void {
        $roleClass       = \app(\config('permission.models.role'));

        $name        = $this->argument('name');
        $guard       = $this->argument('guard');
        $permissions = $this->option('permission');

        $role = $roleClass::create([
            'name'       => $name,
            'guard_name' => $guard
        ]);

        $this->info("Role `{$role->name}` created");

        $role->givePermissionTo($permissions);
        $permissionsStr = $role->permissions->implode('name', '`, `');
        $this->info("Permissions `{$permissionsStr}` has been given to role `{$role->name}`");
    }
}
