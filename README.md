```shell
# how to start
 composer create-project hyperf/hyperf-skeleton your_project_name
 cd your_project_name
 composer require mmfei/hyperf-mmcore
 php ./bin/hyperf.php vendor:publish mmfei/hyperf-mmcore
 php ./bin/hyperf.php migrate
 php ./bin/hyperf.php gen:model user_auth
 php ./bin/hyperf.php gen:model user

 
```

> 加入中间件
```php
# config/autoload/middlewares.php

return [
    'http' => [
        \MmCore\Middleware\CorsMiddleware::class,
        \MmCore\Middleware\ValidationMiddleware::class,
        \MmCore\Middleware\HttpMiddleware::class,
    ],
];

```
> 修改异常处理
```php
# config/autoload/exceptions.php
return [
    'handler' => [
        'http' => [
            \MmCore\Exception\Handler\AppExceptionHandler::class,
        ],
    ],
];
```


```shell
composer require 96qbhy/hyperf-auth
php bin/hyperf.php vendor:publish 96qbhy/hyperf-auth
```

```php
<?php

declare(strict_types=1);
/**
 * This file is part of qbhy/hyperf-auth.
 *
 * @link     https://github.com/qbhy/hyperf-auth
 * @document https://github.com/qbhy/hyperf-auth/blob/master/README.md
 * @contact  qbhy0715@qq.com
 * @license  https://github.com/qbhy/hyperf-auth/blob/master/LICENSE
 */
use Qbhy\SimpleJwt\Encoders;
use Qbhy\SimpleJwt\EncryptAdapters as Encrypter;


return [
    'default' => [
        'guard' => 'jwt',
        'provider' => 'users',
    ],
    'guards' => [
        'jwt' => [
            'driver' => Qbhy\HyperfAuth\Guard\JwtGuard::class,
            'provider' => 'users',

            /*
             * 以下是 simple-jwt 配置
            * 必填
            * jwt 服务端身份标识
            */
            'secret' => env('SIMPLE_JWT_SECRET' , '1239084'),

            /*
             * 可选配置
             * jwt 默认头部token使用的字段
             */
            'header_name' => env('JWT_HEADER_NAME', 'Authorization'),

            /*
             * 可选配置
             * jwt 生命周期，单位分钟
             */
            'ttl' => (int) env('SIMPLE_JWT_TTL', 60 * 60),

            /*
             * 可选配置
             * 允许过期多久以内的 token 进行刷新
             */
            'refresh_ttl' => (int) env('SIMPLE_JWT_REFRESH_TTL', 60 * 60 * 24 * 7),

            /*
             * 可选配置
             * 默认使用的加密类
             */
            'default' => Encrypter\PasswordHashEncrypter::class,

            /*
             * 可选配置
             * 加密类必须实现 Qbhy\SimpleJwt\Interfaces\Encrypter 接口
             */
            'drivers' => [
                Encrypter\PasswordHashEncrypter::alg() => Encrypter\PasswordHashEncrypter::class,
                Encrypter\CryptEncrypter::alg() => Encrypter\CryptEncrypter::class,
                Encrypter\SHA1Encrypter::alg() => Encrypter\SHA1Encrypter::class,
                Encrypter\Md5Encrypter::alg() => Encrypter\Md5Encrypter::class,
            ],

            /*
             * 可选配置
             * 编码类
             */
            'encoder' => new Encoders\Base64UrlSafeEncoder(),
            //            'encoder' => new Encoders\Base64Encoder(),

            /*
             * 可选配置
             * 缓存类
             */
//            'cache' => new \Doctrine\Common\Cache\FilesystemCache(sys_get_temp_dir()),
            // 如果需要分布式部署，请选择 redis 或者其他支持分布式的缓存驱动
            'cache' => function () {
                return make(\Qbhy\HyperfAuth\HyperfRedisCache::class);
            },

            /*
             * 可选配置
             * 缓存前缀
             */
            'prefix' => env('SIMPLE_JWT_PREFIX', 'default'),
        ],
//        'session' => [
//            'driver' => Qbhy\HyperfAuth\Guard\SessionGuard::class,
//            'provider' => 'users',
//        ],
    ],
    'providers' => [
        'users' => [
            'driver' => \Qbhy\HyperfAuth\Provider\EloquentProvider::class,
            'model' => App\Model\UserAuth::class, //  需要实现 Qbhy\HyperfAuth\Authenticatable 接口
        ],
    ],
];
```


> 一些建议

```shell
# 开发环境建议用以下方式
## 安装热加载 @see https://hyperf.wiki/2.0/#/zh-cn/watcher?id=%e5%90%af%e5%8a%a8
composer require hyperf/watcher --dev
## 修改代码后立刻生效(注解无效,需要手动删除runtime文件夹) or `composer dump-autoload -o`
touch 'SCAN_CACHEABLE=false' >> .env
php ./bin/hyperf.php server:watch
```
