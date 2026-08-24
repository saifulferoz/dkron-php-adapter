<?php

declare(strict_types=1);

namespace Dkron\Models;

use JsonSerializable;

class Status implements JsonSerializable
{
    private ?array $agent;
    private ?array $serf;
    private ?array $tags;

    public function __construct(
        ?array $agent = null,
        ?array $serf = null,
        ?array $tags = null
    ) {
        $this->agent = $agent;
        $this->serf = $serf;
        $this->tags = $tags;
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            isset($data['agent']) ? (array)$data['agent'] : null,
            isset($data['serf']) ? (array)$data['serf'] : null,
            isset($data['tags']) ? (array)$data['tags'] : null
        );
    }

    public function getAgent(): ?array
    {
        return $this->agent;
    }

    public function getSerf(): ?array
    {
        return $this->serf;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function jsonSerialize(): array
    {
        return [
            'agent' => $this->agent,
            'serf' => $this->serf,
            'tags' => $this->tags,
        ];
    }
}
