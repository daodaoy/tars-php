<?php

declare(strict_types=1);

namespace wenbinye\tars\rpc\message\tup;

use wenbinye\tars\rpc\ErrorCode;

/**
 * Class ResponsePacket.
 */
class ResponsePacket
{
    private const RESULT_CODE = '__CODE';
    private const RESULT_DESC = '__DESC';
    /**
     * @var int
     */
    private $version;
    /**
     * @var int
     */
    private $packetType;
    /**
     * @var int
     */
    private $requestId;
    /**
     * @var int
     */
    private $messageType;
    /**
     * @var int
     */
    private $resultCode;
    /**
     * @var string
     */
    private $buffer;
    /**
     * @var string
     */
    private $resultDesc;
    /**
     * @var array
     */
    private $context;
    /**
     * @var array
     */
    private $status;

    /**
     * ResponsePacket constructor.
     */
    public function __construct(
        int $version,
        int $packetType,
        int $requestId,
        int $messageType,
        int $returnCode,
        string $buffer,
        string $resultDesc,
        array $context,
        array $status)
    {
        $this->version = $version;
        $this->packetType = $packetType;
        $this->requestId = $requestId;
        $this->messageType = $messageType;
        $this->resultCode = $returnCode;
        $this->buffer = $buffer;
        $this->resultDesc = $resultDesc;
        $this->context = $context;
        $this->status = $status;
    }

    public static function builder(): ResponsePacketBuilder
    {
        return new ResponsePacketBuilder();
    }

    public static function parse(string $response, int $version): ResponsePacket
    {
        if (Tup::VERSION === $version) {
            $parsedBody = \TUPAPI::decodeReqPacket($response);
            $status = isset($parsedBody['status']) && is_array($parsedBody['status']) ? $parsedBody['status'] : [];
            $returnCode = (int) ($status[self::RESULT_CODE] ?? ErrorCode::SERVER_SUCCESS);
            $resultDesc = $status[self::RESULT_DESC] ?? '';
            unset($status[self::RESULT_DESC], $status[self::RESULT_CODE]);

            return new self(
                $parsedBody['iVersion'] ?? Tup::VERSION,
                $parsedBody['iPacketType'] ?? Tup::PACKET_TYPE,
                $parsedBody['iRequestId'] ?? -1,
                $parsedBody['cMessageType'] ?? Tup::MESSAGE_TYPE,
                $returnCode,
                $parsedBody['sBuffer'] ?? '',
                $resultDesc,
                $parsedBody['context'] ?? [],
                $status
            );
        } else {
            $parsedBody = \TUPAPI::decode($response, $version);

            return new self(
                $parsedBody['iVersion'] ?? Tup::VERSION,
                $parsedBody['iPacketType'] ?? Tup::PACKET_TYPE,
                $parsedBody['iRequestId'] ?? -1,
                $parsedBody['cMessageType'] ?? Tup::MESSAGE_TYPE,
                $parsedBody['iRet'] ?? ErrorCode::UNKNOWN,
                $parsedBody['sBuffer'] ?? '',
                $parsedBody['sResultDesc'] ?? '',
                $parsedBody['context'] ?? [],
                isset($parsedBody['status']) && is_array($parsedBody['status']) ? $parsedBody['status'] : []
            );
        }
    }

    public function pack(string $servantName, string $funcName, array $buffers): string
    {
        if (Tup::VERSION === $this->getVersion()) {
            return \TUPAPI::encode(
                $this->getVersion(),
                $this->getRequestId(),
                $servantName,
                $funcName,
                $this->getPacketType(),
                $this->getMessageType(),
                0,
                $this->context,
                array_merge($this->status, [
                    self::RESULT_CODE => $this->getResultCode(),
                    self::RESULT_DESC => $this->getResultDesc(),
                ]),
                $buffers);
        } else {
            return \TUPAPI::encodeRspPacket(
                $this->getVersion(),
                $this->getPacketType(),
                $this->getMessageType(),
                $this->getRequestId(),
                $this->getResultCode(),
                $this->getResultDesc(),
                $buffers,
                $this->status);
        }
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getPacketType(): int
    {
        return $this->packetType;
    }

    public function getMessageType(): int
    {
        return $this->messageType;
    }

    public function getRequestId(): int
    {
        return $this->requestId;
    }

    public function getResultCode(): int
    {
        return $this->resultCode;
    }

    public function getBuffer(): string
    {
        return $this->buffer;
    }

    public function getResultDesc(): string
    {
        return $this->resultDesc;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getStatus(): array
    {
        return $this->status;
    }
}