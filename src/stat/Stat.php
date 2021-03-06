<?php

declare(strict_types=1);

namespace wenbinye\tars\stat;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use wenbinye\tars\client\StatFServant;
use wenbinye\tars\protocol\type\StructMap;
use wenbinye\tars\rpc\message\ResponseInterface;
use wenbinye\tars\server\ClientProperties;
use wenbinye\tars\server\ServerProperties;

class Stat implements StatInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var StatStoreAdapter
     */
    private $store;

    /**
     * @var StatFServant
     */
    private $statClient;

    /**
     * @var int
     */
    private $reportInterval;
    /**
     * @var ServerProperties
     */
    private $serverProperties;

    /**
     * Stat constructor.
     */
    public function __construct(StatFServant $statClient, StatStoreAdapter $store, ClientProperties $clientProperties, ServerProperties $serverProperties)
    {
        $this->store = $store;
        $this->statClient = $statClient;
        $this->reportInterval = $clientProperties->getReportInterval();
        $this->serverProperties = $serverProperties;
    }

    public function success(ResponseInterface $response, int $responseTime): void
    {
        $timeSlice = $this->getRequestTimeSlice($response);
        $this->store->save(StatEntry::success($timeSlice, $this->serverProperties, $response, $responseTime));
    }

    public function fail(ResponseInterface $response, int $responseTime): void
    {
        $this->store->save(StatEntry::fail($this->getRequestTimeSlice($response), $this->serverProperties, $response, $responseTime));
    }

    public function timedOut(ResponseInterface $response, int $responseTime): void
    {
        $this->store->save(StatEntry::timedOut($this->getRequestTimeSlice($response), $this->serverProperties, $response, $responseTime));
    }

    public function send(): void
    {
        $msg = new StructMap();
        $entries = [];
        $currentSlice = $this->getTimeSlice(time());
        foreach ($this->store->getEntries($currentSlice) as $entry) {
            $msg->put($entry->getHead(), $entry->getBody());
            $entries[] = $entry;
        }
        if ($msg->count() > 0) {
            $this->logger->debug('[Stat] send stat', ['msg' => $msg]);
            $ret = $this->statClient->reportMicMsg($msg, true);
            foreach ($entries as $entry) {
                $this->store->delete($entry);
            }
        }
    }

    private function getTimeSlice(int $time): int
    {
        return (int) ($time / ($this->reportInterval / 1000));
    }

    private function getRequestTimeSlice(ResponseInterface $response): int
    {
        $time = $response->getRequest()->getAttribute('startTime') ?? time();

        return $this->getTimeSlice($time);
    }
}
