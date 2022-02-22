<?php

namespace Cyberion\Mongodb\Permission\Traits;

/**
 * Trait RefreshesPermissionCache
 * @package Cyberion\Mongodb\Permission\Traits
 */
trait RefreshesPermissionCache
{
    public static function bootRefreshesPermissionCache()
    {
        static::saved(function () {
            \app(\config('permission.models.permission'))->forgetCachedPermissions();
        });

        static::deleted(function () {
            \app(\config('permission.models.permission'))->forgetCachedPermissions();
        });
    }
}
