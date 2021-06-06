<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Mmcore\Utils;

class EnvUtil
{
    public static function getAppEnv()
    {
        return env('APP_ENV', 'prod');
    }

    public static function isProd(): bool
    {
        return self::is('prod');
    }

    public static function isDev(): bool
    {
        return self::is('dev');
    }

    public static function is($env): bool
    {
        return self::getAppEnv() == $env;
    }
}
