<?php

declare(strict_types=1);

namespace Dkron\Models;

use JsonSerializable;

class Member implements JsonSerializable
{
    private ?string $name;
    private ?string $addr;
    private ?int $port;
    private ?array $tags;
    private ?int $status;
    private ?int $protocolMin;
    private ?int $protocolMax;
    private ?int $protocolCur;
    private ?int $delegateMin;
    private ?int $delegateMax;
    private ?int $delegateCur;

    public function __construct(
        ?string $name = null,
        ?string $addr = null,
        ?int $port = null,
        ?array $tags = null,
        ?int $status = null,
        ?int $protocolMin = null,
        ?int $protocolMax = null,
        ?int $protocolCur = null,
        ?int $delegateMin = null,
        ?int $delegateMax = null,
        ?int $delegateCur = null
    ) {
        $this->name = $name;
        $this->addr = $addr;
        $this->port = $port;
        $this->tags = $tags;
        $this->status = $status;
        $this->protocolMin = $protocolMin;
        $this->protocolMax = $protocolMax;
        $this->protocolCur = $protocolCur;
        $this->delegateMin = $delegateMin;
        $this->delegateMax = $delegateMax;
        $this->delegateCur = $delegateCur;
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            isset($data['Name']) ? (string)$data['Name'] : (isset($data['name']) ? (string)$data['name'] : null),
            isset($data['Addr']) ? (string)$data['Addr'] : (isset($data['addr']) ? (string)$data['addr'] : null),
            isset($data['Port']) ? (int)$data['Port'] : (isset($data['port']) ? (int)$data['port'] : null),
            isset($data['Tags']) ? (array)$data['Tags'] : (isset($data['tags']) ? (array)$data['tags'] : null),
            isset($data['Status']) ? (int)$data['Status'] : (isset($data['status']) ? (int)$data['status'] : null),
            isset($data['ProtocolMin']) ? (int)$data['ProtocolMin'] : (isset($data['protocolMin']) ? (int)$data['protocolMin'] : null),
            isset($data['ProtocolMax']) ? (int)$data['ProtocolMax'] : (isset($data['protocolMax']) ? (int)$data['protocolMax'] : null),
            isset($data['ProtocolCur']) ? (int)$data['ProtocolCur'] : (isset($data['protocolCur']) ? (int)$data['protocolCur'] : null),
            isset($data['DelegateMin']) ? (int)$data['DelegateMin'] : (isset($data['delegateMin']) ? (int)$data['delegateMin'] : null),
            isset($data['DelegateMax']) ? (int)$data['DelegateMax'] : (isset($data['delegateMax']) ? (int)$data['delegateMax'] : null),
            isset($data['DelegateCur']) ? (int)$data['DelegateCur'] : (isset($data['delegateCur']) ? (int)$data['delegateCur'] : null)
        );
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getAddr(): ?string
    {
        return $this->addr;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function getProtocolMin(): ?int
    {
        return $this->protocolMin;
    }

    public function getProtocolMax(): ?int
    {
        return $this->protocolMax;
    }

    public function getProtocolCur(): ?int
    {
        return $this->protocolCur;
    }

    public function getDelegateMin(): ?int
    {
        return $this->delegateMin;
    }

    public function getDelegateMax(): ?int
    {
        return $this->delegateMax;
    }

    public function getDelegateCur(): ?int
    {
        return $this->delegateCur;
    }

    public function jsonSerialize(): array
    {
        return [
            'Name' => $this->name,
            'Addr' => $this->addr,
            'Port' => $this->port,
            'Tags' => $this->tags,
            'Status' => $this->status,
            'ProtocolMin' => $this->protocolMin,
            'ProtocolMax' => $this->protocolMax,
            'ProtocolCur' => $this->protocolCur,
            'DelegateMin' => $this->delegateMin,
            'DelegateMax' => $this->delegateMax,
            'DelegateCur' => $this->delegateCur,
        ];
    }
}
