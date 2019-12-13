<?php

declare(strict_types=1);

namespace wenbinye\tars\server;

use Symfony\Component\Validator\Constraints as Assert;
use wenbinye\tars\rpc\Route;
use wenbinye\tars\server\annotation\ConfigItem;

class ServerProperties
{
    /**
     * @ConfigItem()
     * @Assert\NotBlank()
     *
     * @var string
     */
    private $app;

    /**
     * @ConfigItem()
     * @Assert\NotBlank()
     *
     * @var string
     */
    private $server;

    /**
     * @var array
     */
    private $swooleServerProperties;

    /**
     * @ConfigItem()
     * @Assert\NotBlank()
     *
     * @var string
     */
    private $basePath;
    /**
     * @ConfigItem()
     *
     * @var string
     */
    private $dataPath;
    /**
     * @ConfigItem()
     *
     * @var string
     */
    private $logPath;
    /**
     * @ConfigItem()
     *
     * @var string
     */
    private $logLevel;
    /**
     * @ConfigItem()
     *
     * @var int
     */
    private $logSize;

    /**
     * @ConfigItem(factory="fromString")
     *
     * @var Route
     */
    private $local;

    /**
     * @ConfigItem()
     *
     * @var string
     */
    private $logServantName;
    /**
     * @ConfigItem()
     *
     * @var string
     */
    private $configServantName;
    /**
     * @ConfigItem()
     *
     * @var string
     */
    private $notifyServantName;
    /**
     * @Assert\Count(min=1)
     *
     * @var AdapterProperties[]
     */
    private $adapters = [];

    public function getApp(): string
    {
        return $this->app;
    }

    public function setApp(string $app): void
    {
        $this->app = $app;
    }

    public function getServer(): string
    {
        return $this->server;
    }

    public function setServer(string $server): void
    {
        $this->server = $server;
    }

    public function getServerName(): string
    {
        return $this->app.'.'.$this->server;
    }

    public function getSwooleServerProperties(): array
    {
        return $this->swooleServerProperties;
    }

    public function setSwooleServerProperties(array $swooleServerProperties): void
    {
        $this->swooleServerProperties = $swooleServerProperties;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function setBasePath(string $basePath): void
    {
        $this->basePath = $basePath;
    }

    public function getDataPath(): string
    {
        return $this->dataPath;
    }

    public function setDataPath(string $dataPath): void
    {
        $this->dataPath = $dataPath;
    }

    public function getLogPath(): string
    {
        return $this->logPath;
    }

    public function setLogPath(string $logPath): void
    {
        $this->logPath = $logPath;
    }

    public function getLogLevel(): string
    {
        return $this->logLevel;
    }

    public function setLogLevel(string $logLevel): void
    {
        $this->logLevel = $logLevel;
    }

    public function getLogSize(): int
    {
        return $this->logSize;
    }

    public function setLogSize(int $logSize): void
    {
        $this->logSize = $logSize;
    }

    public function getLocal(): Route
    {
        return $this->local;
    }

    public function setLocal(Route $local): void
    {
        $this->local = $local;
    }

    public function getLogServantName(): string
    {
        return $this->logServantName;
    }

    public function setLogServantName(string $logServantName): void
    {
        $this->logServantName = $logServantName;
    }

    public function getConfigServantName(): string
    {
        return $this->configServantName;
    }

    public function setConfigServantName(string $configServantName): void
    {
        $this->configServantName = $configServantName;
    }

    public function getNotifyServantName(): string
    {
        return $this->notifyServantName;
    }

    public function setNotifyServantName(string $notifyServantName): void
    {
        $this->notifyServantName = $notifyServantName;
    }

    /**
     * @return AdapterProperties[]
     */
    public function getAdapters(): array
    {
        return $this->adapters;
    }

    /**
     * @param AdapterProperties[] $adapters
     */
    public function setAdapters(array $adapters): void
    {
        $this->adapters = $adapters;
    }
}
