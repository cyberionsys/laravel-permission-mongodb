<?php

namespace Cyberion\Mongodb\Permission\Contracts;

use Cyberion\Mongodb\Permission\Exceptions\RoleDoesNotExist;
use Jenssegers\Mongodb\Relations\BelongsToMany;

/**
 * Interface RoleInterface
 * @package Cyberion\Mongodb\Permission\Contracts
 */
interface RoleInterface
{
    /**
     * A role may be given various permissions.
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany;

    /**
     * Find a role by its name and guard name.
     *
     * @param string $name
     * @param string|null $guardName
     *
     * @throws RoleDoesNotExist
     * @return RoleInterface
     *
     */
    public static function findByName(string $name, ?string $guardName): RoleInterface;

    /**
     * Determine if the user may perform the given permission.
     *
     * @param string|PermissionInterface $permission
     *
     * @return bool
     */
    public function hasPermissionTo(string|PermissionInterface $permission): bool;
}
