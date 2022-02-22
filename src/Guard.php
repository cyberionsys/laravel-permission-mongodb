<?php

namespace Cyberion\Mongodb\Permission;

use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionException;

/**
 * Class Guard
 * @package Cyberion\Mongodb\Permission
 */
class Guard
{
    /**
     * return collection of (guard_name) property if exist on class or object
     * otherwise will return collection of guards names that exists in config/auth.php.
     *
     * @param $model
     *
     * @throws ReflectionException
     * @return Collection
     */
    public function getNames($model) : Collection
    {
        $guardName = null;
        $class = null;

        if (\is_object($model)) {
            $guardName = $model->guard_name ?? null;
        }

        if ($guardName === null) {
            $class = \is_object($model) ? \get_class($model) : $model;
            $guardName = (new ReflectionClass($class))->getDefaultProperties()['guard_name'] ?? null;
        }

        if ($guardName) {
            return collect($guardName);
        }

        return collect(config('auth.guards'))
            ->map(function ($guard) {
                if (! isset($guard['provider'])) {
                    return;
                }
                return config("auth.providers.{$guard['provider']}.model");
            })
            ->filter(function ($model) use ($class) {
                return $class === $model;
            })
            ->keys();
    }

    /**
     * Return Default Guard name
     *
     * @param $class
     *
     * @throws ReflectionException
     * @return string
     */
    public function getDefaultName($class): string
    {
        $default = config('auth.defaults.guard');
        return $this->getNames($class)->first() ?: $default;
    }
}
