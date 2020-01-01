<?php

declare(strict_types=1);

namespace wenbinye\tars\server;

use wenbinye\tars\server\exception\ConfigException;

class Config extends \ArrayIterator
{
    /**
     * @var Config
     */
    private static $INSTANCE;

    /**
     * @return mixed
     */
    public static function getInstance(): Config
    {
        return self::$INSTANCE;
    }

    /**
     * @param mixed $INSTANCE
     */
    private static function setInstance(Config $instance): void
    {
        self::$INSTANCE = $instance;
    }

    public function __get($name)
    {
        return $this[$name] ?? null;
    }

    public function __set($name, $value)
    {
        throw new \BadMethodCallException('Cannot modify config');
    }

    public function __isset($name)
    {
        return isset($this[$name]);
    }

    public function has(string $key): bool
    {
        return null !== $this->get($key);
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $pos = strpos($key, '.');
        if (false === $pos) {
            return $this[$key] ?? $default;
        }
        $current = substr($key, 0, $pos);
        if (isset($this[$current]) && $this[$current] instanceof self) {
            return $this[$current]->get(substr($key, $pos + 1), $default);
        }

        return $default;
    }

    public function merge(array $configArray): void
    {
        foreach ($configArray as $key => $value) {
            if (isset($this[$key]) && is_array($value) && $this[$key] instanceof self) {
                $this[$key]->merge($value);
                continue;
            }
            $this[$key] = is_array($value) ? static::fromArray($value) : $value;
        }
    }

    public function toArray(): array
    {
        $result = [];
        foreach ($this as $key => $value) {
            if ($value instanceof self) {
                $result[$key] = $value->toArray();
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    public static function fromArray(array $configArray): Config
    {
        $config = new static();
        foreach ($configArray as $key => $value) {
            if (is_array($value)) {
                $config[$key] = static::fromArray($value);
            } else {
                $config[$key] = $value;
            }
        }

        return $config;
    }

    public static function parseFile(string $fileName): Config
    {
        $content = file_get_contents($fileName);
        if (false === $content) {
            throw new ConfigException("cannot read config file '{$fileName}'");
        }

        return static::parse($content);
    }

    public static function parse(string $content): Config
    {
        $stack = [];
        $current = $config = new static();
        foreach (explode("\n", $content) as $lineNum => $line) {
            $line = trim($line);
            if (empty($line) || 0 === strpos($line, '#')) {
                continue;
            }
            if (preg_match("/<(\/?)(\S+)>/", $line, $matches)) {
                if ($matches[1]) {
                    if (empty($stack)) {
                        throw new ConfigException("Unexpect close tag '{$line}' at line {$lineNum}");
                    }
                    $current = array_pop($stack);
                } else {
                    $stack[] = $current;
                    $current = $current[$matches[2]] = new static();
                }
            } else {
                $parts = array_map('trim', explode('=', $line, 2));
                if (1 === count($parts)) {
                    $current[$parts[0]] = true;
                } else {
                    $current[$parts[0]] = $parts[1];
                }
            }
        }
        static::setInstance($config);

        return $config;
    }
}
