<?php

declare(strict_types=1);

namespace wenbinye\tars\rpc\route;

use Symfony\Component\Validator\Constraints as Assert;

class Route
{
    /**
     * @Assert\Choice(choices={"tcp", "udp"})
     * @Assert\NotBlank()
     *
     * @var string
     */
    private $protocol;

    /**
     * @Assert\NotBlank()
     *
     * @var string
     */
    private $host;

    /**
     * @Assert\Range(min=1, max=65536)
     *
     * @var int
     */
    private $port;

    /**
     * @var int
     */
    private $timeout;

    /**
     * @var string
     */
    private $servantName;

    /**
     * @var int
     */
    private $weight;

    private static $SHORT_OPTIONS = [
        'host' => 'h',
        'port' => 'p',
        'timeout' => 't',
    ];

    /**
     * Route constructor.
     */
    public function __construct(string $servantName, string $protocol, string $host, int $port, int $timeout, int $weight = 100)
    {
        $this->protocol = $protocol;
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout > 0 ? $timeout : 20000;
        $this->servantName = $servantName;
        $this->weight = $weight;
    }

    public function getProtocol(): string
    {
        return $this->protocol;
    }

    public function withProtocol(string $protocol): self
    {
        $new = clone $this;
        $new->protocol = $protocol;

        return $new;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function withHost(string $host): self
    {
        $new = clone $this;
        $new->host = $host;

        return $new;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function withPort(int $port): self
    {
        $new = clone $this;
        $new->port = $port;

        return $new;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function withTimeout(int $timeout): self
    {
        $new = clone $this;
        $new->timeout = $timeout;

        return $new;
    }

    public function getServantName(): string
    {
        return $this->servantName;
    }

    public function withServantName(string $servantName): self
    {
        $new = clone $this;
        $new->servantName = $servantName;

        return $new;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function withWeight(int $weight): self
    {
        $new = clone $this;
        $new->weight = $weight;

        return $new;
    }

    public function toArray(): array
    {
        return array_filter(get_object_vars($this));
    }

    public function __toString()
    {
        $str = '';
        if ($this->servantName) {
            $str = $this->servantName.'@';
        }

        return $str.$this->protocol.' '.implode(' ', array_filter(array_map(function ($name) {
            return isset($this->{$name}) ? '-'.self::$SHORT_OPTIONS[$name].' '.$this->{$name} : null;
        }, array_keys(self::$SHORT_OPTIONS))));
    }

    public static function fromString(string $str): Route
    {
        $route = [
            'servantName' => '',
            'protocol' => '',
            'host' => '',
            'port' => 0,
            'timeout' => 0,
        ];
        $pos = strpos($str, '@');
        if (false !== $pos) {
            $route['servantName'] = substr($str, 0, $pos);
            $str = substr($str, $pos + 1);
        }
        $parts = preg_split("/\s+/", $str);
        $route['protocol'] = array_shift($parts);
        while (!empty($parts)) {
            $opt = array_shift($parts);
            if (0 === strpos($opt, '-')) {
                $name = array_search(substr($opt, 1), self::$SHORT_OPTIONS, true);
                if (false !== $name) {
                    $value = array_shift($parts);
                    if (in_array($name, ['port', 'timeout'], true)) {
                        $route[$name] = (int) $value;
                    } else {
                        $route[$name] = $value;
                    }
                }
            }
        }

        if (!in_array($route['protocol'], ['tcp', 'udp'], true)) {
            throw new \InvalidArgumentException("invalid route protocol: original text is '$str'");
        }
        if (empty($route['host'])) {
            throw new \InvalidArgumentException("invalid route host: original text is '$str'");
        }
        if ($route['port'] < 1 || $route['port'] > 65536) {
            throw new \InvalidArgumentException("invalid route port: original text is '$str'");
        }

        return new static($route['servantName'], $route['protocol'], $route['host'], $route['port'], $route['timeout']);
    }
}
