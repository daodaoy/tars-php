<?php

declare(strict_types=1);

namespace wenbinye\tars\log;

use wenbinye\tars\protocol\annotation\TarsProperty;

final class LogInfo
{
    /**
     * @TarsProperty(order = 0, required = true, type = "string")
     *
     * @var string
     */
    public $appname;

    /**
     * @TarsProperty(order = 1, required = true, type = "string")
     *
     * @var string
     */
    public $servername;

    /**
     * @TarsProperty(order = 2, required = true, type = "string")
     *
     * @var string
     */
    public $sFilename;

    /**
     * @TarsProperty(order = 3, required = true, type = "string")
     *
     * @var string
     */
    public $sFormat;

    /**
     * @TarsProperty(order = 4, required = false, type = "string")
     *
     * @var string
     */
    public $setdivision;

    /**
     * @TarsProperty(order = 5, required = false, type = "bool")
     *
     * @var bool
     */
    public $bHasSufix = true;

    /**
     * @TarsProperty(order = 6, required = false, type = "bool")
     *
     * @var bool
     */
    public $bHasAppNamePrefix = true;

    /**
     * @TarsProperty(order = 7, required = false, type = "bool")
     *
     * @var bool
     */
    public $bHasSquareBracket = false;

    /**
     * @TarsProperty(order = 8, required = false, type = "string")
     *
     * @var string
     */
    public $sConcatStr = '_';

    /**
     * @TarsProperty(order = 9, required = false, type = "string")
     *
     * @var string
     */
    public $sSepar = '|';

    /**
     * @TarsProperty(order = 10, required = false, type = "string")
     *
     * @var string
     */
    public $sLogType = '';
}
