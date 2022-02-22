<?php

namespace Cyberion\Mongodb\Permission\Traits;

use Cyberion\Mongodb\Permission\Contracts\PermissionInterface as Permission;
use Cyberion\Mongodb\Permission\Exceptions\GuardDoesNotMatch;
use Cyberion\Mongodb\Permission\Guard;
use Cyberion\Mongodb\Permission\Helpers;
use Cyberion\Mongodb\Permission\Models\Role;
use Cyberion\Mongodb\Permission\PermissionRegistrar;
use Illuminate\Support\Collection;
use Jenssegers\Mongodb\Eloquent\Builder;
use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Relations\BelongsToMany;
use ReflectionException;

/**
 * Trait HasPermissions
 * @package Cyberion\Mongodb\Permission\Traits
 */
trait HasPermissions
{
    private $permissionClass;

    public static function bootHasPermissions()
    {
        static::deleting(function (Model $model) {
            if (isset($model->forceDeleting) && !$model->forceDeleting) {
                return;
            }

            $model->permissions()->sync([]);
        });
    }

    public function getPermissionClass()
    {
        if ($this->permissionClass === null) {
            $this->permissionClass = app(PermissionRegistrar::class)->getPermissionClass();
        }
        return $this->permissionClass;
    }

    /**
     * A role may be given various permissions.
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(config('permission.models.permission'));
    }

    /**
     * Grant the given permission(s) to a role.
     *
     * @param string|array|Permission|\Illuminate\Support\Collection $permissions
     *
     * @throws GuardDoesNotMatch
     * @return $this
     */
    public function givePermissionTo(...$permissions): self
    {
        $permissions = collect($permissions)
            ->flatten()
            ->map(function ($permission) {
                return $this->getStoredPermission($permission);
            })
            ->each(function ($permission) {
                $this->ensureModelSharesGuard($permission);
            })
            ->all();

        $this->permissions()->saveMany($permissions);

        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * Remove all current permissions and set the given ones.
     *
     * @param string|array|Permission|\Illuminate\Support\Collection $permissions
     *
     * @throws GuardDoesNotMatch
     * @return $this
     */
    public function syncPermissions(...$permissions): self
    {
        $this->permissions()->sync([]);

        return $this->givePermissionTo($permissions);
    }

    /**
     * Revoke the given permission.
     *
     * @param string|array|Permission|\Illuminate\Support\Collection $permissions
     *
     * @throws \Cyberion\Mongodb\Permission\Exceptions\GuardDoesNotMatch
     * @return $this
     */
    public function revokePermissionTo(...$permissions): self
    {
        collect($permissions)
            ->flatten()
            ->map(function ($permission) {
                $permission = $this->getStoredPermission($permission);
                $this->permissions()->detach($permission);

                return $permission;
            });

        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * @param string|Permission $permission
     *
     * @throws ReflectionException
     * @return Permission
     */
    protected function getStoredPermission(string|Permission $permission): Permission
    {
        if (\is_string($permission)) {
            return $this->getPermissionClass()->findByName($permission, $this->getDefaultGuardName());
        }

        return $permission;
    }

    /**
     * @param Model $roleOrPermission
     *
     * @throws GuardDoesNotMatch
     * @throws ReflectionException
     */
    protected function ensureModelSharesGuard(Model $roleOrPermission): void
    {
        if (! $this->getGuardNames()->contains($roleOrPermission->guard_name)) {
            $expected = $this->getGuardNames();
            $given    = $roleOrPermission->guard_name;
            $helpers  = new Helpers();

            throw new GuardDoesNotMatch($helpers->getGuardDoesNotMatchMessage($expected, $given));
        }
    }

    /**
     * @throws ReflectionException
     * @return Collection
     */
    protected function getGuardNames(): Collection
    {
        return (new Guard())->getNames($this);
    }

    /**
     * @throws ReflectionException
     * @return string
     */
    protected function getDefaultGuardName(): string
    {
        return (new Guard())->getDefaultName($this);
    }

    /**
     * Forget the cached permissions.
     */
    public function forgetCachedPermissions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Convert to Permission Models
     *
     * @param mixed $permissions
     *
     * @return Collection
     */
    private function convertToPermissionModels(mixed $permissions): Collection
    {
        if (\is_array($permissions)) {
            $permissions = collect($permissions);
        }

        if (! $permissions instanceof Collection) {
            $permissions = collect([$permissions]);
        }

        return $permissions->map(function ($permission) {
            return $this->getStoredPermission($permission);
        });
    }

    /**
     * Return a collection of permission names associated with this user.
     *
     * @return Collection
     */
    public function getPermissionNames(): Collection
    {
        return $this->getAllPermissions()->pluck('name');
    }

    /**
     * Return all the permissions the model has via roles.
     */
    public function getPermissionsViaRoles(): Collection
    {
        return $this->load('roles', 'roles.permissions')
            ->roles->flatMap(function (Role $role) {
                return $role->permissions;
            })->sort()->values();
    }

    /**
     * Return all the permissions the model has, both directly and via roles.
     */
    public function getAllPermissions(): Collection
    {
        return $this->permissions
            ->merge($this->getPermissionsViaRoles())
            ->sort()
            ->values();
    }

    /**
     * Determine if the model may perform the given permission.
     *
     * @param string|Permission $permission
     * @param string|null $guardName
     *
     * @throws ReflectionException
     * @return bool
     */
    public function hasPermissionTo(string|Permission $permission, string $guardName = null): bool
    {
        if (\is_string($permission)) {
            $permission = $this->getPermissionClass()->findByName(
                $permission,
                $guardName ?? $this->getDefaultGuardName()
            );
        }

        return $this->hasDirectPermission($permission) || $this->hasPermissionViaRole($permission);
    }

    /**
     * Determine if the model has any of the given permissions.
     *
     * @param array ...$permissions
     *
     * @throws ReflectionException
     * @return bool
     */
    public function hasAnyPermission(...$permissions): bool
    {
        if (\is_array($permissions[0])) {
            $permissions = $permissions[0];
        }

        foreach ($permissions as $permission) {
            if ($this->hasPermissionTo($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the model has all of the given permissions(s).
     *
     * @param $permissions
     *
     * @throws ReflectionException
     * @return bool
     */
    public function hasAllPermissions(...$permissions): bool
    {
        $helpers = new Helpers();
        $permissions = $helpers->flattenArray($permissions);

        foreach ($permissions as $permission) {
            if (!$this->hasPermissionTo($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Determine if the model has, via roles, the given permission.
     *
     * @param Permission $permission
     *
     * @return bool
     */
    protected function hasPermissionViaRole(Permission $permission): bool
    {
        return $this->hasRole($permission->roles);
    }

    /**
     * Determine if the model has the given permission.
     *
     * @param string|Permission $permission
     *
     * @throws ReflectionException
     * @return bool
     */
    public function hasDirectPermission(string|Permission $permission): bool
    {
        if (\is_string($permission)) {
            $permission = $this->getPermissionClass()->findByName($permission, $this->getDefaultGuardName());
        }

        return $this->permissions->contains('id', $permission->id);
    }

    /**
     * Return all permissions the directory coupled to the model.
     */
    public function getDirectPermissions(): Collection
    {
        return $this->permissions;
    }

    /**
     * Scope the model query to certain permissions only.
     *
     * @param Builder $query
     * @param array|string|\Cyberion\Mongodb\Permission\Contracts\PermissionInterface|Collection $permissions
     *
     * @return Builder
     */
    public function scopePermission(Builder $query, array|string|Collection|Permission $permissions): Builder
    {
        $permissions = $this->convertToPermissionModels($permissions);

        $roles = \collect([]);

        foreach ($permissions as $permission) {
            $roles = $roles->merge($permission->roles);
        }
        $roles = $roles->unique();

        return $query->orWhereIn('permission_ids', $permissions->pluck('_id'))
            ->orWhereIn('role_ids', $roles->pluck('_id'));
    }
}
