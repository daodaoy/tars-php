<?php

declare(strict_types=1);

namespace wenbinye\tars\rpc;

use InvalidArgumentException;
use kuiper\annotations\AnnotationReader;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\SimpleCache\CacheInterface;
use wenbinye\tars\client\QueryFServant;
use wenbinye\tars\protocol\Packer;
use wenbinye\tars\protocol\PackerInterface;
use wenbinye\tars\rpc\connection\ConnectionFactory;
use wenbinye\tars\rpc\connection\ConnectionFactoryInterface;
use wenbinye\tars\rpc\message\MethodMetadataFactory;
use wenbinye\tars\rpc\message\MethodMetadataFactoryInterface;
use wenbinye\tars\rpc\message\RequestFactory;
use wenbinye\tars\rpc\message\RequestFactoryInterface;
use wenbinye\tars\rpc\message\RequestIdGenerator;
use wenbinye\tars\rpc\message\RequestIdGeneratorInterface;
use wenbinye\tars\rpc\message\ResponseFactory;
use wenbinye\tars\rpc\message\ResponseFactoryInterface;
use wenbinye\tars\rpc\route\InMemoryRouteResolver;
use wenbinye\tars\rpc\route\RegistryRouteResolver;
use wenbinye\tars\rpc\route\Route;
use wenbinye\tars\rpc\route\RouteHolderFactory;
use wenbinye\tars\rpc\route\RouteHolderFactoryInterface;
use wenbinye\tars\rpc\route\RouteResolverInterface;
use wenbinye\tars\rpc\route\SwooleTableRegistryCache;

class TarsClientBuilder implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var RouteResolverInterface
     */
    private $routeResolver;

    /**
     * @var Route
     */
    private $locator;

    /**
     * @var QueryFServant
     */
    private $queryFClient;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var PackerInterface
     */
    private $packer;

    /**
     * @var MethodMetadataFactoryInterface
     */
    private $methodMetadataFactory;

    /**
     * @var RequestIdGeneratorInterface
     */
    private $requestIdGenerator;

    /**
     * @var RouteHolderFactoryInterface
     */
    private $routeHolderFactory;

    /**
     * @var ConnectionFactoryInterface
     */
    private $connectionFactory;

    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var ErrorHandlerInterface
     */
    private $errorHandler;

    /**
     * @var MiddlewareInterface[]
     */
    private $middlewares = [];

    /**
     * @var ServantProxyGeneratorInterface
     */
    private $servantProxyGenerator;

    public function getLocator(): Route
    {
        return $this->locator;
    }

    public function setLocator(Route $locator): TarsClientBuilder
    {
        $this->locator = $locator;

        return $this;
    }

    public function getQueryFClient(): QueryFServant
    {
        if (!$this->queryFClient) {
            if (!$this->locator) {
                throw new InvalidArgumentException('locator is required');
            }
            $routeResolver = new InMemoryRouteResolver();
            $routeResolver->addRoute($this->locator);
            $routeHolderFactory = new RouteHolderFactory($routeResolver);
            $connectionFactory = new ConnectionFactory($routeHolderFactory);
            $tarsClient = new TarsClient(
                $connectionFactory,
                $this->getRequestFactory(),
                $this->getResponseFactory(),
                $this->getErrorHandler(),
                $this->middlewares
            );
            $proxyClass = $this->getServantProxyGenerator()->generate(QueryFServant::class);
            $this->queryFClient = new $proxyClass($tarsClient);
        }

        return $this->queryFClient;
    }

    public function setQueryFClient(QueryFServant $queryFClient): TarsClientBuilder
    {
        $this->queryFClient = $queryFClient;

        return $this;
    }

    public function getCache(): CacheInterface
    {
        if (!$this->cache) {
            $this->cache = new SwooleTableRegistryCache();
        }

        return $this->cache;
    }

    public function setCache(CacheInterface $cache): TarsClientBuilder
    {
        $this->cache = $cache;

        return $this;
    }

    public function getRouteResolver(): RouteResolverInterface
    {
        if (!$this->routeResolver) {
            $this->routeResolver = new RegistryRouteResolver(
                $this->getQueryFClient(),
                $this->getCache()
            );
        }

        return $this->routeResolver;
    }

    /**
     * @param mixed $routeResolver
     *
     * @return TarsClientBuilder
     */
    public function setRouteResolver($routeResolver)
    {
        $this->routeResolver = $routeResolver;

        return $this;
    }

    public function getPacker(): PackerInterface
    {
        if (!$this->packer) {
            $this->packer = new Packer(AnnotationReader::getInstance());
        }

        return $this->packer;
    }

    public function setPacker(PackerInterface $packer): TarsClientBuilder
    {
        $this->packer = $packer;

        return $this;
    }

    public function getMethodMetadataFactory(): MethodMetadataFactoryInterface
    {
        if (!$this->methodMetadataFactory) {
            $this->methodMetadataFactory = new MethodMetadataFactory(AnnotationReader::getInstance());
        }

        return $this->methodMetadataFactory;
    }

    public function setMethodMetadataFactory(MethodMetadataFactoryInterface $methodMetadataFactory): TarsClientBuilder
    {
        $this->methodMetadataFactory = $methodMetadataFactory;

        return $this;
    }

    public function getRequestIdGenerator(): RequestIdGeneratorInterface
    {
        if (!$this->requestIdGenerator) {
            $this->requestIdGenerator = new RequestIdGenerator();
        }

        return $this->requestIdGenerator;
    }

    public function setRequestIdGenerator(RequestIdGeneratorInterface $requestIdGenerator): TarsClientBuilder
    {
        $this->requestIdGenerator = $requestIdGenerator;

        return $this;
    }

    public function getRouteHolderFactory(): RouteHolderFactoryInterface
    {
        if (!$this->routeHolderFactory) {
            $this->routeHolderFactory = new RouteHolderFactory($this->getRouteResolver());
            if ($this->logger) {
                $this->routeHolderFactory->setLogger($this->logger);
            }
        }

        return $this->routeHolderFactory;
    }

    public function setRouteHolderFactory(RouteHolderFactoryInterface $routeHolderFactory): TarsClientBuilder
    {
        $this->routeHolderFactory = $routeHolderFactory;

        return $this;
    }

    public function getServantProxyGenerator(): ServantProxyGeneratorInterface
    {
        if (!$this->servantProxyGenerator) {
            $this->servantProxyGenerator = new ServantProxyGenerator(AnnotationReader::getInstance());
        }

        return $this->servantProxyGenerator;
    }

    public function setServantProxyGenerator(ServantProxyGeneratorInterface $servantProxyGenerator): TarsClientBuilder
    {
        $this->servantProxyGenerator = $servantProxyGenerator;

        return $this;
    }

    public function getConnectionFactory(): ConnectionFactoryInterface
    {
        if (!$this->connectionFactory) {
            $this->connectionFactory = new ConnectionFactory($this->getRouteHolderFactory());
            if ($this->logger) {
                $this->connectionFactory->setLogger($this->logger);
            }
        }

        return $this->connectionFactory;
    }

    public function setConnectionFactory(ConnectionFactoryInterface $connectionFactory): TarsClientBuilder
    {
        $this->connectionFactory = $connectionFactory;

        return $this;
    }

    public function getRequestFactory(): RequestFactoryInterface
    {
        if (!$this->requestFactory) {
            $this->requestFactory = new RequestFactory(
                $this->getMethodMetadataFactory(),
                $this->getPacker(),
                $this->getRequestIdGenerator()
            );
        }

        return $this->requestFactory;
    }

    public function setRequestFactory(RequestFactoryInterface $requestFactory): TarsClientBuilder
    {
        $this->requestFactory = $requestFactory;

        return $this;
    }

    public function getResponseFactory(): ResponseFactoryInterface
    {
        if (!$this->responseFactory) {
            $this->responseFactory = new ResponseFactory($this->getPacker());
        }

        return $this->responseFactory;
    }

    public function setResponseFactory(ResponseFactoryInterface $responseFactory): TarsClientBuilder
    {
        $this->responseFactory = $responseFactory;

        return $this;
    }

    public function getErrorHandler(): ErrorHandlerInterface
    {
        if (!$this->errorHandler) {
            $this->errorHandler = new DefaultErrorHandler();
        }

        return $this->errorHandler;
    }

    public function setErrorHandler(ErrorHandlerInterface $errorHandler): TarsClientBuilder
    {
        $this->errorHandler = $errorHandler;

        return $this;
    }

    public function addMiddleware(MiddlewareInterface $middleware): TarsClientBuilder
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    public function setMiddlewares(array $middlewares): TarsClientBuilder
    {
        $this->middlewares = $middlewares;

        return $this;
    }

    public function build(): TarsClientInterface
    {
        return new TarsClient(
            $this->getConnectionFactory(),
            $this->getRequestFactory(),
            $this->getResponseFactory(),
            $this->getErrorHandler(),
            $this->middlewares
        );
    }

    public function createProxy(string $clientClassName)
    {
        $proxyClass = $this->getServantProxyGenerator()->generate($clientClassName);

        return new $proxyClass($this->build());
    }
}
