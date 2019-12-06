<?php

declare(strict_types=1);

namespace wenbinye\tars\rpc;

interface RequestInterface
{
    const TARS_VERSION = 1;
    const TUP_VERSION = 3;
    const PACKET_TYPE = 0;
    const MESSAGE_TYPE = 0;
    const DEFAULT_TIMEOUT = 2000;

    public function getVersion(): int;

    public function getTimeout(): int;

    public function getMessageType(): int;

    public function getPacketType(): int;

    public function encode(): string;
}
