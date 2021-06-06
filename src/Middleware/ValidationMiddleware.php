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
namespace Mmcore\Middleware;

use MMCore\Exception\MmcoreHttpApiException;
use Hyperf\Validation\Middleware\ValidationMiddleware as HyperfValidationMiddleware;
use Hyperf\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ValidationMiddleware extends HyperfValidationMiddleware
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return parent::process($request, $handler);
        } catch (ValidationException $e) {
            throw new MmcoreHttpApiException($e->validator->getMessageBag()->first(), 400);
        }
    }
}
