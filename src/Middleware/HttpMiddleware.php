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
namespace MMCore\Middleware;

use MMCore\Exception\MMCoreHttpApiException;
use Hyperf\HttpMessage\Stream\SwooleFileStream;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Utils\Codec\Json;
use Hyperf\Utils\Context;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class HttpMiddleware implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $msg = 'SUCCESS';
            $data = (object)[];
            $code = 200;
            $response = $handler->handle($request);
            $body = $response->getBody();
            if($body instanceof SwooleFileStream) {
                return $response;
            }
            $c = $body->getContents();
            if ($c != '') {
                $data = \json_decode($c, true);
                if (is_null($data)) {
                    $data = $c;
                }
            } else {
                $data = $c;
            }
        } catch (MMCoreHttpApiException $e) {
            $code = $e->getCode();
            $msg = $e->getMessage();
        }
        $content = [
            'code' => $code,
            'data' => $data ?? ((object) []),
            'msg' => $msg,
            'time' => time(),
        ];
        $response1 = Context::get(PsrResponseInterface::class);
        $response1 = $response1->withAddedHeader('content-type', 'application/json')->withBody(new SwooleStream(Json::encode($content)))->withStatus(200);
        return \Hyperf\Utils\Context::set(ResponseInterface::class, $response1);
    }
}
