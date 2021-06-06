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
namespace Mmcore\Exception\Handler;

use Dotenv\Exception\ValidationException;
use Hyperf\ExceptionHandler\Handler\WhoopsExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Utils\Codec\Json;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class MMCoreValidationExceptionHandler extends WhoopsExceptionHandler
{
    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        // 格式化输出
        $data = Json::encode([
            'code' => $throwable->getCode(),
            'msg' => $throwable->getMessage(),
            'time' => time(),
            'data' => 'Validator',
        ], \JSON_UNESCAPED_UNICODE);

        $this->stopPropagation();
        // 阻止异常冒泡
        return $response->withStatus(500)->withHeader('content-type', 'application/json')->withBody(new SwooleStream($data));
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof ValidationException;
    }
}
