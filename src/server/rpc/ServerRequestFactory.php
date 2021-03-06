<?php

declare(strict_types=1);

namespace wenbinye\tars\server\rpc;

use Psr\Container\ContainerInterface;
use wenbinye\tars\protocol\PackerInterface;
use wenbinye\tars\rpc\message\MethodMetadata;
use wenbinye\tars\rpc\message\MethodMetadataFactoryInterface;
use wenbinye\tars\rpc\TarsRpcPacker;

class ServerRequestFactory implements ServerRequestFactoryInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var MethodMetadataFactoryInterface
     */
    private $methodMetadataFactory;
    /**
     * @var TarsRpcPacker
     */
    private $packer;
    /**
     * @var array
     */
    private $servants;

    /**
     * ServerRequestFactory constructor.
     */
    public function __construct(ContainerInterface $container, PackerInterface $packer, MethodMetadataFactoryInterface $methodMetadataFactory, array $servants = [])
    {
        $this->container = $container;
        $this->packer = new TarsRpcPacker($packer);
        $this->methodMetadataFactory = $methodMetadataFactory;
        $this->servants = $servants;
    }

    public function register(string $servantName, string $servantInterface): void
    {
        $this->servants[$servantName] = $servantInterface;
    }

    public function create(string $requestBody): ServerRequestInterface
    {
        $unpackResult = \TUPAPI::decodeReqPacket($requestBody);
        $servantInterface = $this->servants[$unpackResult['sServantName']] ?? null;
        $version = $unpackResult['iVersion'];
        $requestId = $unpackResult['iRequestId'];
        if (!isset($servantInterface) || !$this->container->has($servantInterface)) {
            return new ServerRequest(null, MethodMetadata::dummy(), $requestBody, [], $version, $requestId);
        }
        $servant = $this->container->get($servantInterface);
        if (!method_exists($servant, $unpackResult['sFuncName'])) {
            return new ServerRequest($servant, MethodMetadata::dummy(), $requestBody, [], $version, $requestId);
        }
        $methodMetadata = $this->methodMetadataFactory->create($servant, $unpackResult['sFuncName']);
        $parameters = $this->packer->unpackRequest($methodMetadata, $unpackResult['sBuffer'], $version);

        return new ServerRequest($servant, $methodMetadata, $requestBody, $parameters, $version, $requestId);
    }
}
