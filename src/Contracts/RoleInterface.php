<?php

namespace Maklad\Permission\Contracts;

use Jenssegers\Mongodb\Relations\BelongsToMany;
use Maklad\Permission\Exceptions\RoleDoesNotExist;

/**
 * Interface RoleInterface
 * @package Maklad\Permission\Contracts
 */
interface RoleInterface {
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
