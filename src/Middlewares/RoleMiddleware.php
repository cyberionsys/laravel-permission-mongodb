<?php

namespace Cyberion\Mongodb\Permission\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Cyberion\Mongodb\Permission\Exceptions\UnauthorizedRole;
use Cyberion\Mongodb\Permission\Exceptions\UserNotLoggedIn;
use Cyberion\Mongodb\Permission\Helpers;

/**
 * Class RoleMiddleware
 * @package Cyberion\Mongodb\Permission\Middlewares
 */
class RoleMiddleware {
    /**
     * @param Request $request
     * @param Closure $next
     * @param array|string $role
     *
     * @throws \Cyberion\Mongodb\Permission\Exceptions\UnauthorizedException
     * @return mixed
     */
    public function handle(Request $request, Closure $next, array|string $role): mixed {
        if (app('auth')->guest()) {
            $helpers = new Helpers();
            throw new UserNotLoggedIn(403, $helpers->getUserNotLoggedINMessage());
        }

        $roles = \is_array($role) ? $role : \explode('|', $role);

        if (! app('auth')->user()->hasAnyRole($roles)) {
            $helpers = new Helpers();
            throw new UnauthorizedRole(403, $helpers->getUnauthorizedRoleMessage(implode(', ', $roles)), $roles);
        }

        return $next($request);
    }
}
