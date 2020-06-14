<?php

declare(strict_types=1);

namespace wenbinye\tars\server;

use Dotenv\Dotenv;
use kuiper\annotations\AnnotationReader;
use kuiper\helper\Properties;
use kuiper\swoole\listener\ManagerStartEventListener;
use kuiper\swoole\listener\StartEventListener;
use kuiper\swoole\listener\TaskEventListener;
use kuiper\swoole\listener\WorkerStartEventListener;
use kuiper\swoole\monolog\CoroutineIdProcessor;
use kuiper\web\middleware\AccessLog;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Validator\Validation;
use wenbinye\tars\client\ConfigServant;
use wenbinye\tars\rpc\middleware\RequestLog;
use wenbinye\tars\rpc\middleware\SendStat;
use wenbinye\tars\rpc\middleware\ServerRequestLog;
use wenbinye\tars\rpc\route\Route;
use wenbinye\tars\rpc\TarsClient;
use wenbinye\tars\server\listener\WorkerKeepAlive;
use wenbinye\tars\stat\collector\SystemCpuCollector;

class ConfigLoader implements ConfigLoaderInterface
{
    private const REGEXP_PLACEHOLDER = '#\{([^\{\}]+)\}#';

    /**
     * @var PropertyLoader
     */
    private $propertyLoader;

    /**
     * ConfigLoader constructor.
     *
     * @param PropertyLoader $propertyLoader
     */
    public function __construct(?PropertyLoader $propertyLoader = null)
    {
        $this->propertyLoader = $propertyLoader ?? new PropertyLoader(AnnotationReader::getInstance(),
                Validation::createValidatorBuilder()->getValidator());
    }

    public function load(InputInterface $input): void
    {
        $configFile = $input->getOption('config');
        if (!$configFile) {
            throw new \InvalidArgumentException('config file is required');
        }
        if (!is_readable($configFile)) {
            throw new \InvalidArgumentException("config file '$configFile' is not readable");
        }
        Config::parseFile($configFile);
        $config = Config::getInstance();
        $serverProperties = $this->propertyLoader->loadServerProperties($config);
        $this->addDefaultConfig($config, $input, $serverProperties);
        $this->loadEnvFile($config, $serverProperties);
        $configFile = $serverProperties->getSourcePath().'/config.php';
        if (file_exists($configFile)) {
            /* @noinspection PhpIncludeInspection */
            $config->merge(require $configFile);
        }
        $this->expandConfig($config, static function (array $matches) use ($config) {
            $name = $matches[1];
            if (!$config->has($name)) {
                throw new \RuntimeException("Unknown config entry: '$name'");
            }

            return $config->get($name);
        });
        $this->addDefaultLoggers($config, $serverProperties);
    }

    private function addDefaultConfig(Properties $config, InputInterface $input, ServerProperties $serverProperties): void
    {
        $config->merge([
            'application' => [
                'name' => $serverProperties->getServerName(),
                'monitor' => [
                    'collectors' => [
                        SystemCpuCollector::class,
                    ],
                ],
                'middleware' => [
                    'client' => [
                        SendStat::class,
                        RequestLog::class,
                    ],
                    'web' => [
                        AccessLog::class,
                    ],
                    'servant' => [
                        ServerRequestLog::class,
                    ],
                ],
                'listeners' => [
                    StartEventListener::class,
                    ManagerStartEventListener::class,
                    WorkerStartEventListener::class,
                    TaskEventListener::class,
                    WorkerKeepAlive::class,
                ],
                'logging' => [
                    'path' => $serverProperties->getAppLogPath(),
                    'config' => [
                        ServerRequestLog::class => 'AccessLogger',
                        AccessLog::class => 'AccessLogger',
                    ],
                    'level' => [
                        'wenbinye\\tars' => 'info',
                        'kuiper' => 'info',
                    ],
                ],
            ],
        ]);
        foreach ($input->getOption('define') as $item) {
            $pair = explode('=', $item, 2);
            $config->set($pair[0], $pair[1] ?? null);
        }
    }

    protected function loadEnvFile(Properties $config, ServerProperties $serverProperties): void
    {
        if (!class_exists(Dotenv::class)) {
            return;
        }
        $envFiles = ['.env'];
        $env = $config->getString('tars.application.server.env_config_file');
        if ($env) {
            $localFile = $serverProperties->getBasePath().'/'.$env;
            /** @var ConfigServant $configServant */
            $configServant = TarsClient::builder()
                ->setLocator(Route::fromString($config->getString('tars.application.client.locator')))
                ->createProxy(ConfigServant::class);
            $ret = $configServant->loadConfig($serverProperties->getApp(), $serverProperties->getServer(), $env, $content);
            if (0 === $ret) {
                file_put_contents($localFile, $content);
            }
            if (is_readable($localFile)) {
                array_unshift($envFiles, $env);
            }
        }
        Dotenv::createImmutable($serverProperties->getBasePath(), $envFiles, false)
            ->safeLoad();
    }

    protected function expandConfig(Properties $config, callable $replacer): void
    {
        $re = self::REGEXP_PLACEHOLDER;
        foreach ($config as $key => $value) {
            if (is_string($value) && preg_match(self::REGEXP_PLACEHOLDER, $value)) {
                do {
                    $value = preg_replace_callback($re, $replacer, $value);
                } while (preg_match(self::REGEXP_PLACEHOLDER, $value));

                $config[$key] = $value;
            } elseif ($value instanceof Properties) {
                $this->expandConfig($value, $replacer);
            }
        }
    }

    protected function addDefaultLoggers(Properties $config, ServerProperties $serverProperties): void
    {
        if ($config->has('application.logging.loggers')) {
            return;
        }
        $loggerLevelName = strtoupper($serverProperties->getLogLevel());

        $loggerLevel = constant(Logger::class.'::'.$loggerLevelName);
        if (!isset($loggerLevel)) {
            throw new \InvalidArgumentException("Unknown logger level '{$loggerLevelName}'");
        }
        $config->set('application.logging.loggers', [
            'root' => [
                'name' => $serverProperties->getServer(),
                'handlers' => [
                    [
                        'handler' => [
                            'class' => StreamHandler::class,
                            'constructor' => [
                                'stream' => 'php://stderr',
                                'level' => $loggerLevel,
                            ],
                        ],
                        'formatter' => [
                            'class' => LineFormatter::class,
                            'constructor' => [
                                'allowInlineLineBreaks' => true,
                            ],
                        ],
                    ],
                    [
                        'handler' => [
                            'class' => StreamHandler::class,
                            'constructor' => [
                                'stream' => sprintf('%s/log_%s.log', $serverProperties->getAppLogPath(), strtolower($loggerLevelName)),
                                'level' => $loggerLevel,
                            ],
                        ],
                    ],
                    [
                        'handler' => [
                            'class' => StreamHandler::class,
                            'constructor' => [
                                'stream' => sprintf('%s/%s.log', $serverProperties->getAppLogPath(), $serverProperties->getServer()),
                                'level' => $loggerLevel,
                            ],
                        ],
                        'formatter' => [
                            'class' => LineFormatter::class,
                            'constructor' => [
                                'allowInlineLineBreaks' => true,
                            ],
                        ],
                    ],
                ],
                'processors' => [
                    CoroutineIdProcessor::class,
                ],
            ],
            'AccessLogger' => [
                'handlers' => [
                    [
                        'handler' => [
                            'class' => StreamHandler::class,
                            'constructor' => [
                                'stream' => $serverProperties->getAppLogPath().'/access.log',
                            ],
                        ],
                        'formatter' => [
                            'class' => LineFormatter::class,
                            'constructor' => [
                                'format' => "%message% %context% %extra%\n",
                            ],
                        ],
                    ],
                ],
                'processors' => [
                    CoroutineIdProcessor::class,
                ],
            ],
        ]);
    }
}