<?php

declare(strict_types=1);

namespace wenbinye\tars\stat;

use wenbinye\tars\protocol\annotation\TarsProperty;

final class StatMicMsgBody
{
    /**
     * @TarsProperty(order = 0, required = true, type = "int")
     *
     * @var int
     */
    public $count;

    /**
     * @TarsProperty(order = 1, required = true, type = "int")
     *
     * @var int
     */
    public $timeoutCount;

    /**
     * @TarsProperty(order = 2, required = true, type = "int")
     *
     * @var int
     */
    public $execCount;

    /**
     * @TarsProperty(order = 3, required = true, type = "map<int, int>")
     *
     * @var array
     */
    public $intervalCount;

    /**
     * @TarsProperty(order = 4, required = true, type = "long")
     *
     * @var int
     */
    public $totalRspTime;

    /**
     * @TarsProperty(order = 5, required = true, type = "int")
     *
     * @var int
     */
    public $maxRspTime;

    /**
     * @TarsProperty(order = 6, required = true, type = "int")
     *
     * @var int
     */
    public $minRspTime;
}
