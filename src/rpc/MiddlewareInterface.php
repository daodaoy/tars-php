<?php

declare(strict_types=1);

namespace wenbinye\tars\rpc;

use wenbinye\tars\rpc\message\RequestInterface;
use wenbinye\tars\rpc\message\ResponseInterface;

interface MiddlewareInterface
{
    public function __invoke(RequestInterface $request, callable $next): ResponseInterface;
}
