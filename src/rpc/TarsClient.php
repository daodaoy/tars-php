<?php

declare(strict_types=1);

namespace wenbinye\tars\rpc;

use Psr\Log\LoggerAwareInterface;
use wenbinye\tars\rpc\connection\ConnectionFactoryInterface;
use wenbinye\tars\rpc\message\RequestFactoryInterface;
use wenbinye\tars\rpc\message\RequestInterface;
use wenbinye\tars\rpc\message\ResponseFactoryInterface;
use wenbinye\tars\rpc\message\ResponseInterface;
use wenbinye\tars\rpc\message\ReturnValueInterface;

class TarsClient implements TarsClientInterface, LoggerAwareInterface
{
    use MiddlewareSupport;

    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var ConnectionFactoryInterface
     */
    private $connectionFactory;
    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;
    /**
     * @var ErrorHandlerInterface|null
     */
    private $errorHandler;

    /**
     * AbstractClient constructor.
     *
     * @param MiddlewareInterface[] $middlewares
     */
    public function __construct(ConnectionFactoryInterface $connectionFactory,
                                RequestFactoryInterface $requestFactory,
                                ResponseFactoryInterface $responseFactory,
                                ?ErrorHandlerInterface $errorHandler = null,
                                array $middlewares = [])
    {
        $this->requestFactory = $requestFactory;
        $this->connectionFactory = $connectionFactory;
        $this->responseFactory = $responseFactory;
        $this->errorHandler = $errorHandler;
        $this->middlewares = $middlewares;
    }

    /**
     * {@inheritdoc}
     */
    public function call($servant, string $method, ...$args): array
    {
        $request = $this->requestFactory->createRequest($servant, $method, $args);
        $response = $this->buildMiddlewareStack([$this, 'send'])->__invoke($request);

        return array_map(static function (ReturnValueInterface $value) {
            return $value->getData();
        }, $response->getReturnValues());
    }

    public function send(RequestInterface $request): ResponseInterface
    {
        $connection = $this->connectionFactory->create($request->getServantName());
        try {
            $rawContent = $connection->send($request = $request->withAttribute('route', $connection->getRoute()));

            $response = $this->responseFactory->create($rawContent, $request);
            if (isset($this->errorHandler) && !$response->isSuccess()) {
                return $this->errorHandler->handle($response);
            }

            return $response;
        } finally {
            $connection->disconnect();
        }
    }

    public static function builder(): TarsClientBuilder
    {
        return new TarsClientBuilder();
    }
}
