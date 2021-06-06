<?php

namespace MmCore;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            // 合并到  config/autoload/dependencies.php 文件
            'dependencies' => [],
            // 合并到  config/autoload/annotations.php 文件
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            // 默认 Command 的定义，合并到 HyperfContractConfigInterface 内，换个方式理解也就是与 config/autoload/commands.php 对应
            'commands' => [],
            // 与 commands 类似
            'listeners' => [],
            // 组件默认配置文件，即执行命令后会把 source 的对应的文件复制为 destination 对应的的文件
            'publish' => [
                [
                    'id' => 'config auth',
                    'description' => 'description of this config file.', // 描述
                    // 建议默认配置放在 publish 文件夹中，文件命名和组件名称相同
                    'source' => __DIR__ . '/publish/config/autoload/auth.php',  // 对应的配置文件路径
                    'destination' => BASE_PATH . '/config/autoload/auth.php', // 复制为这个路径下的该文件
                ],
                [
                    'id' => 'config cache',
                    'description' => 'description of this config file.', // 描述
                    // 建议默认配置放在 publish 文件夹中，文件命名和组件名称相同
                    'source' => __DIR__ . '/publish/config/autoload/cache.php',  // 对应的配置文件路径
                    'destination' => BASE_PATH . '/config/autoload/cache.php', // 复制为这个路径下的该文件
                ],
                [
                    'id' => 'config cors',
                    'description' => 'description of this config file.', // 描述
                    // 建议默认配置放在 publish 文件夹中，文件命名和组件名称相同
                    'source' => __DIR__ . '/publish/config/autoload/cors_config.php',  // 对应的配置文件路径
                    'destination' => BASE_PATH . '/config/autoload/cors_config.php', // 复制为这个路径下的该文件
                ],
                [
                    'id' => 'config translation',
                    'description' => 'description of this config file.', // 描述
                    // 建议默认配置放在 publish 文件夹中，文件命名和组件名称相同
                    'source' => __DIR__ . '/publish/config/autoload/translation.php',  // 对应的配置文件路径
                    'destination' => BASE_PATH . '/config/autoload/translation.php', // 复制为这个路径下的该文件
                ],
                [
                    'id' => 'migrate user',
                    'description' => 'user auth.', // 描述
                    // 建议默认配置放在 publish 文件夹中，文件命名和组件名称相同
                    'source' => __DIR__ . '/publish/migrations/2021_06_06_134804_create_user_auth.php',  // 对应的配置文件路径
                    'destination' => BASE_PATH . '/migrations/2021_06_06_134804_create_user_auth.php', // 复制为这个路径下的该文件
                ],
                [
                    'id' => 'model user',
                    'description' => 'model user auth.', // 描述
                    // 建议默认配置放在 publish 文件夹中，文件命名和组件名称相同
                    'source' => __DIR__ . '/publish/Model/UserAuth',  // 对应的配置文件路径
                    'destination' => BASE_PATH . '/App/Model/UserAuth.php', // 复制为这个路径下的该文件
                ],
                [
                    'id' => 'storage',
                    'description' => 'storage.', // 描述
                    // 建议默认配置放在 publish 文件夹中，文件命名和组件名称相同
                    'source' => __DIR__ . '/publish/storage',  // 对应的配置文件路径
                    'destination' => BASE_PATH . '/storage', // 复制为这个路径下的该文件
                ],
            ],
            // 亦可继续定义其它配置，最终都会合并到与 ConfigInterface 对应的配置储存器中
        ];
    }
}
