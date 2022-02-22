<?php

namespace Cyberion\Mongodb\Permission\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Cyberion\Mongodb\Permission\Exceptions\UnauthorizedException;
use Cyberion\Mongodb\Permission\Exceptions\UnauthorizedPermission;
use Cyberion\Mongodb\Permission\Exceptions\UserNotLoggedIn;
use Cyberion\Mongodb\Permission\Helpers;

/**
 * Class PermissionMiddleware
 * @package Cyberion\Mongodb\Permission\Middlewares
 */
class PermissionMiddleware {
    /**
     * @param Request $request
     * @param Closure $next
     * @param array|string $permission
     *
     * @throws UnauthorizedException
     * @return mixed
     */
    public function handle(Request $request, Closure $next, array|string $permission): mixed {
        if (app('auth')->guest()) {
            $helpers = new Helpers();
            throw new UserNotLoggedIn(403, $helpers->getUserNotLoggedINMessage());
        }

        $permissions = \is_array($permission) ? $permission : \explode('|', $permission);

        if (! app('auth')->user()->hasAnyPermission($permissions)) {
            $helpers = new Helpers();
            throw new UnauthorizedPermission(
                403,
                $helpers->getUnauthorizedPermissionMessage(implode(', ', $permissions)),
                $permissions
            );
        }

        return $next($request);
    }
}
