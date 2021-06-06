<?php
//跨域配置 , 符合白名单的任何一个条件即可跨域
return [
    'env_whitelist' => [ //配置跨域环境变量白名单
        'APP_ENV' => [
            'dev',
        ],
    ],
    'domains_whitelist' => [ //配置跨域域名白名单
        'localhost',
        '127.0.0.1',
    ],
];
