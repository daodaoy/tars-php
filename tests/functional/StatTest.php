<?php

declare(strict_types=1);

namespace wenbinye\tars\functional;

use wenbinye\tars\registry\RegistryConnectionFactory;
use wenbinye\tars\rpc\RequestFactoryInterface;
use wenbinye\tars\rpc\Response;
use wenbinye\tars\stat\StatInterface;

class StatTest extends FunctionalTestCase
{
    public function testSendStat()
    {
        $container = $this->getContainer();
        $factory = $container->get(RegistryConnectionFactory::class);
        $servantName = 'tars.tarslog.LogObj';
        $request = $container->get(RequestFactoryInterface::class)->createRequest(
            $servantName, 'logger', []
        );
        $stat = $container->get(StatInterface::class);

        foreach (range(300, 600) as $time) {
            $response = new Response('', $request->withAttribute('route', $factory->create($servantName)->getRoute())
                ->withAttribute('startTime', time() - $time));

            $stat->success($response, 30);
            $stat->fail($response, 3);
            $stat->timedOut($response, 3000);
        }
        $stat->send();
    }
}
