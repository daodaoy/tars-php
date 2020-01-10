<?php

declare(strict_types=1);

namespace wenbinye\tars\server\rpc;

use kuiper\annotations\AnnotationReader;
use Monolog\Test\TestCase;
use Psr\Container\ContainerInterface;
use wenbinye\tars\protocol\annotation\TarsParameter;
use wenbinye\tars\protocol\annotation\TarsReturnType;
use wenbinye\tars\protocol\annotation\TarsServant;
use wenbinye\tars\protocol\Packer;
use wenbinye\tars\protocol\TarsTypeFactory;
use wenbinye\tars\rpc\message\MethodMetadataFactory;
use wenbinye\tars\rpc\message\RequestFactory;
use wenbinye\tars\rpc\message\RequestIdGenerator;
use wenbinye\tars\rpc\message\ResponseFactory;
use wenbinye\tars\server\Config;

class TarsRequestHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        Config::parseFile(__DIR__.'/../fixtures/PHPTest.PHPHttpServer.config.conf');
    }

    public function testName()
    {
        $servant = new HelloService();
        $container = \Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('has')
            ->andReturn(true);
        $container->shouldReceive('get')
            ->andReturn($servant);
        TarsServant::register('PHPTest.PHPTcpServer.obj', HelloServiceServant::class);

        $annotationReader = AnnotationReader::getInstance();
        $packer = new Packer(new TarsTypeFactory($annotationReader));
        $methodMetadataFactory = new MethodMetadataFactory($annotationReader);
        $requestFactory = new RequestFactory($methodMetadataFactory, $packer, new RequestIdGenerator());
        $serverRequestFactory = new ServerRequestFactory($container, $packer, $methodMetadataFactory);
        $responseFactory = new ResponseFactory($packer);
        $tarsRequestHandler = new TarsRequestHandler($packer);

        $message = 'world';
        $request = $requestFactory->createRequest($servant, 'hello', [$message]);

        $response = $tarsRequestHandler->handle($serverRequestFactory->create($request->getBody()));
        // var_export($response);
        $this->assertTrue($response->isSuccess());

        $clientResponse = $responseFactory->create($response->getBody(), $request);
        // var_export($clientResponse);
        $this->assertEquals('hello '.$message, $clientResponse->getReturnValues()[0]->getData());
    }
}

/**
 * @TarsServant("PHPTest.PHPTcpServer.obj")
 */
interface HelloServiceServant
{
    /**
     * @TarsParameter(name = "message", type = "string")
     * @TarsReturnType(type = "string")
     *
     * @param string $message
     *
     * @return string
     */
    public function hello($message);
}

class HelloService implements HelloServiceServant
{
    /**
     * {@inheritdoc}
     */
    public function hello($message)
    {
        return 'hello '.$message;
    }
}
