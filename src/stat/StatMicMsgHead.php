<?php

declare(strict_types=1);

namespace wenbinye\tars\stat;

use wenbinye\tars\protocol\annotation\TarsProperty;

final class StatMicMsgHead
{
    /**
     * @TarsProperty(order = 0, required = true, type = "string")
     *
     * @var string
     */
    public $masterName;

    /**
     * @TarsProperty(order = 1, required = true, type = "string")
     *
     * @var string
     */
    public $slaveName;

    /**
     * @TarsProperty(order = 2, required = true, type = "string")
     *
     * @var string
     */
    public $interfaceName;

    /**
     * @TarsProperty(order = 3, required = true, type = "string")
     *
     * @var string
     */
    public $masterIp;

    /**
     * @TarsProperty(order = 4, required = true, type = "string")
     *
     * @var string
     */
    public $slaveIp;

    /**
     * @TarsProperty(order = 5, required = true, type = "int")
     *
     * @var int
     */
    public $slavePort;

    /**
     * @TarsProperty(order = 6, required = true, type = "int")
     *
     * @var int
     */
    public $returnValue;

    /**
     * @TarsProperty(order = 7, required = false, type = "string")
     *
     * @var string
     */
    public $slaveSetName;

    /**
     * @TarsProperty(order = 8, required = false, type = "string")
     *
     * @var string
     */
    public $slaveSetArea;

    /**
     * @TarsProperty(order = 9, required = false, type = "string")
     *
     * @var string
     */
    public $slaveSetID;

    /**
     * @TarsProperty(order = 10, required = false, type = "string")
     *
     * @var string
     */
    public $tarsVersion;
}
