<?php
/**
 * Copyright 2016 - 2019, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2019, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Middleware\UnauthorizedHandler;

use Authorization\Exception\Exception;
use Authorization\Exception\ForbiddenException;
use Authorization\Middleware\UnauthorizedHandler\HandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * This handler rethrows an exception caught by the middleware.
 */
class ApiExceptionHandler implements HandlerInterface
{
    /**
     * {@inheritDoc}
     */
    public function handle(Exception $exception, ServerRequestInterface $request, array $options = [])
    {
        $service = $request->getAttribute('service');
        if ($service !== null) {
            if ($exception instanceof ForbiddenException) {
                $service->getResult()->setCode(403);
                $service->getResult()->setException(new Exception(__('Forbidden authorization request')));
            } else {
                $service->getResult()->setCode(401);
                $service->getResult()->setException($exception);
            }

            return $service->respond();
        }

        throw $exception;
    }
}
