<?php declare(strict_types=1);

namespace MMCore\Middleware;

use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Context;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use MMCore\Helper\Common;

class CorsMiddleware implements MiddlewareInterface
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

    /**
     * @param ContainerInterface $container
     * @param HttpResponse $response
     * @param RequestInterface $request
     */
    public function __construct(ContainerInterface $container, HttpResponse $response, RequestInterface $request)
    {
        $this->container = $container;
        $this->response = $response;
        $this->request = $request;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if( $this->isPass() ) {
            $response = Context::get(ResponseInterface::class);
            $response = $response->withHeader('Access-Control-Allow-Origin', '*')->withHeader('Access-Control-Allow-Credentials', 'true');

            Context::set(ResponseInterface::class, $response);

            if ($request->getMethod() == 'OPTIONS') {
                return $response;
            }
        }

        return $handler->handle($request);
    }
    private function isPass(): bool
    {
        $config = ApplicationContext::getContainer()->get(ConfigInterface::class)->get('cors_config');
        if($config) {
            $env_whitelist = $config['env_whitelist'] ?? [];
            if ($env_whitelist) {
                foreach ($env_whitelist as $env => $matches) {
                    $value = env($env);
                    if ($value && in_array($value, $matches)) return true;
                }
            }
            $domains_whitelist = $config['domains_whitelist'] ?? [];
            $host = $_SERVER['SERVER_NAME'] ?? '';
            if($domains_whitelist && in_array($host , $domains_whitelist)) return true;
        }
        return false;
    }
}
