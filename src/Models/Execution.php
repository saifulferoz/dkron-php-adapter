<?php

declare(strict_types=1);

namespace Dkron\Models;

use JsonSerializable;

class Execution implements JsonSerializable
{
    private ?string $id;
    private ?string $jobName;
    private ?string $startedAt;
    private ?string $finishedAt;
    private ?bool $success;
    private ?string $output;
    private ?string $nodeName;
    private ?int $group;
    private ?int $attempt;

    public function __construct(
        ?string $jobName = null,
        ?string $startedAt = null,
        ?string $finishedAt = null,
        ?bool $success = null,
        ?string $output = null,
        ?string $nodeName = null,
        ?string $id = null,
        ?int $group = null,
        ?int $attempt = null
    ) {
        $this->jobName = $jobName;
        $this->startedAt = $startedAt;
        $this->finishedAt = $finishedAt;
        $this->success = $success;
        $this->output = $output;
        $this->nodeName = $nodeName;
        $this->id = $id;
        $this->group = $group;
        $this->attempt = $attempt;
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            isset($data['job_name']) ? (string)$data['job_name'] : null,
            isset($data['started_at']) ? (string)$data['started_at'] : null,
            isset($data['finished_at']) ? (string)$data['finished_at'] : null,
            isset($data['success']) ? (bool)$data['success'] : null,
            isset($data['output']) ? (string)$data['output'] : null,
            isset($data['node_name']) ? (string)$data['node_name'] : null,
            isset($data['id']) ? (string)$data['id'] : null,
            isset($data['group']) ? (int)$data['group'] : null,
            isset($data['attempt']) ? (int)$data['attempt'] : null
        );
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getJobName(): ?string
    {
        return $this->jobName;
    }

    public function getStartedAt(): ?string
    {
        return $this->startedAt;
    }

    public function getFinishedAt(): ?string
    {
        return $this->finishedAt;
    }

    public function isSuccess(): ?bool
    {
        return $this->success;
    }

    public function getOutput(): ?string
    {
        return $this->output;
    }

    public function getNodeName(): ?string
    {
        return $this->nodeName;
    }

    public function getGroup(): ?int
    {
        return $this->group;
    }

    public function getAttempt(): ?int
    {
        return $this->attempt;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'job_name' => $this->jobName,
            'started_at' => $this->startedAt,
            'finished_at' => $this->finishedAt,
            'success' => $this->success,
            'output' => $this->output,
            'node_name' => $this->nodeName,
            'group' => $this->group,
            'attempt' => $this->attempt,
        ];
    }
}
