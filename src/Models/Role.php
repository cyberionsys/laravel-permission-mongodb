<?php

namespace Cyberion\Mongodb\Permission\Models;

use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Relations\BelongsToMany;
use Cyberion\Mongodb\Permission\Contracts\PermissionInterface;
use Cyberion\Mongodb\Permission\Contracts\RoleInterface;
use Cyberion\Mongodb\Permission\Exceptions\GuardDoesNotMatch;
use Cyberion\Mongodb\Permission\Exceptions\RoleAlreadyExists;
use Cyberion\Mongodb\Permission\Exceptions\RoleDoesNotExist;
use Cyberion\Mongodb\Permission\Guard;
use Cyberion\Mongodb\Permission\Helpers;
use Cyberion\Mongodb\Permission\Traits\HasPermissions;
use Cyberion\Mongodb\Permission\Traits\RefreshesPermissionCache;
use ReflectionException;

/**
 * Class Role
 * @package Cyberion\Mongodb\Permission\Models
 */
class Role extends Model implements RoleInterface {
    use HasPermissions;
    use RefreshesPermissionCache;
    public $guarded = ['id'];
    protected Helpers $helpers;

    /**
     * Role constructor.
     *
     * @param array $attributes
     *
     * @throws ReflectionException
     */
    public function __construct(array $attributes = []) {
        $attributes['guard_name'] ??= (new Guard())->getDefaultName(static::class);

        parent::__construct($attributes);

        $this->helpers = new Helpers();

        $this->setTable(config('permission.collection_names.roles'));
    }

    /**
     * @param array $attributes
     *
     * @throws RoleAlreadyExists
     * @throws ReflectionException
     * @return $this|mixed
     * @internal param array $attributes§
     *
     */
    public static function create(array $attributes = []) {
        $attributes['guard_name'] ??= (new Guard())->getDefaultName(static::class);
        $helpers = new Helpers();

        if (static::where('name', $attributes['name'])->where('guard_name', $attributes['guard_name'])->first()) {
            $name = (string) $attributes['name'];
            $guardName = (string) $attributes['guard_name'];
            throw new RoleAlreadyExists($helpers->getRoleAlreadyExistsMessage($name, $guardName));
        }

        return $helpers->checkVersion() ? parent::create($attributes) : static::query()->create($attributes);
    }

    /**
     * Find or create role by its name (and optionally guardName).
     *
     * @param string $name
     * @param string|null $guardName
     *
     * @throws \Cyberion\Mongodb\Permission\Exceptions\RoleAlreadyExists
     * @throws ReflectionException
     * @return RoleInterface
     */
    public static function findOrCreate(string $name, string $guardName = null): RoleInterface {
        $guardName ??= (new Guard())->getDefaultName(static::class);

        $role = static::where('name', $name)
            ->where('guard_name', $guardName)
            ->first();

        if (!$role) {
            $role = static::create(['name' => $name, 'guard_name' => $guardName]);
        }

        return $role;
    }

    /**
     * Find a role by its name and guard name.
     *
     * @param string $name
     * @param string|null $guardName
     *
     * @throws RoleDoesNotExist
     * @throws ReflectionException
     * @return RoleInterface
     */
    public static function findByName(string $name, string $guardName = null): RoleInterface {
        $guardName ??= (new Guard())->getDefaultName(static::class);

        $role = static::where('name', $name)
            ->where('guard_name', $guardName)
            ->first();

        if (!$role) {
            $helpers = new Helpers();
            throw new RoleDoesNotExist($helpers->getRoleDoesNotExistMessage($name, $guardName));
        }

        return $role;
    }

    /**
     * A role belongs to some users of the model associated with its guard.
     * @return BelongsToMany
     */
    public function users(): BelongsToMany {
        return $this->belongsToMany($this->helpers->getModelForGuard($this->attributes['guard_name']));
    }

    /**
     * Determine if the user may perform the given permission.
     *
     * @param string|Permission $permission
     *
     * @throws GuardDoesNotMatch
     * @throws ReflectionException
     * @return bool
     *
     */
    public function hasPermissionTo(string|PermissionInterface $permission): bool {
        if (\is_string($permission)) {
            $permission = $this->getPermissionClass()->findByName($permission, $this->getDefaultGuardName());
        }

        if (!$this->getGuardNames()->contains($permission->guard_name)) {
            $expected = $this->getGuardNames();
            $given = $permission->guard_name;

            throw new GuardDoesNotMatch($this->helpers->getGuardDoesNotMatchMessage($expected, $given));
        }

        return $this->permissions->contains('id', $permission->id);
    }
}
