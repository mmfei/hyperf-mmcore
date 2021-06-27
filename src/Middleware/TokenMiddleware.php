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
namespace MmCore\Middleware;

use MMCore\Exception\MmCoreHttpApiException;
use MMCore\Helper\Common;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TokenMiddleware implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var HttpResponse
     */
    protected $response;

    public function __construct(ContainerInterface $container, HttpResponse $response, RequestInterface $request)
    {
        $this->container = $container;
        $this->response = $response;
        $this->request = $request;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $this->request->input('token', '');
        if (empty($token)) {
            throw new MmCoreHttpApiException('token缺失', 403);
        }

        //从token里解析出uid等账号信息
        $user_info = Common::decrypt($token);
        if (empty($user_info['uid'])) {
            throw new MmCoreHttpApiException('token不合法或已失效', 499);
        }

        //token是否已失效
        $redis = ApplicationContext::getContainer()->get(\Hyperf\Redis\Redis::class);
        $cache_key = "suju_token_{$user_info['uid']}";
        $is_exists = $redis->sIsMember($cache_key, $token);
        if (empty($is_exists)) {
            throw new MmCoreHttpApiException('token不合法或已失效', 499);
        }

        //token放到$request里
        $this->request->user_info = $user_info;

        return $handler->handle($request);
    }
}
