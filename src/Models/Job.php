<?php

declare(strict_types=1);

namespace Dkron\Models;

use InvalidArgumentException;
use JsonSerializable;
use stdClass;

class Job implements JsonSerializable
{
    public const CONCURRENCY_ALLOW = 'allow';
    public const CONCURRENCY_FORBID = 'forbid';
    public const CONCURRENCY_REPLACE = 'replace';

    private string $name = '';
    private string $displayname = '';
    private string $schedule = '* * * * * *';
    private string $timezone = '';
    private string $owner = '';
    private string $ownerEmail = '';
    private bool $disabled = false;
    private string $concurrency = self::CONCURRENCY_ALLOW;
    private string $executor = '';
    private array $executorConfig = [];
    private array $processors = [];
    private int $retries = 0;
    private string $parentJob = '';
    /** @var string[] */
    private array $dependentJobs = [];
    private array $tags = [];
    private array $metadata = [];
    private bool $ephemeral = false;
    private string $status = '';
    private string $next = '';

    // Read-only parameters populated by Dkron
    private int $errorCount = 0;
    private int $successCount = 0;
    private ?string $lastError = null;
    private ?string $lastSuccess = null;

    public function __construct(
        string $name,
        string $schedule = '* * * * * *',
        ?int $errorCount = null,
        ?string $lastError = null,
        ?string $lastSuccess = null,
        ?int $successCount = null
    ) {
        $this->name = $name;
        $this->setSchedule($schedule);

        $this->errorCount = $errorCount ?? 0;
        $this->lastError = $lastError;
        $this->lastSuccess = $lastSuccess;
        $this->successCount = $successCount ?? 0;
    }

    public static function createFromArray(array $data): self
    {
        $job = new self(
            (string)($data['name'] ?? ''),
            (string)($data['schedule'] ?? '* * * * * *'),
            isset($data['error_count']) ? (int)$data['error_count'] : null,
            isset($data['last_error']) ? (string)$data['last_error'] : null,
            isset($data['last_success']) ? (string)$data['last_success'] : null,
            isset($data['success_count']) ? (int)$data['success_count'] : null
        );

        if (isset($data['displayname'])) {
            $job->setDisplayname((string)$data['displayname']);
        } elseif (isset($data['displayName'])) {
            $job->setDisplayname((string)$data['displayName']);
        }

        if (isset($data['timezone'])) {
            $job->setTimezone((string)$data['timezone']);
        }

        if (isset($data['owner'])) {
            $job->setOwner((string)$data['owner']);
        }

        if (isset($data['owner_email'])) {
            $job->setOwnerEmail((string)$data['owner_email']);
        } elseif (isset($data['ownerEmail'])) {
            $job->setOwnerEmail((string)$data['ownerEmail']);
        }

        if (isset($data['disabled'])) {
            $job->setDisabled((bool)$data['disabled']);
        }

        if (isset($data['concurrency'])) {
            $job->setConcurrency((string)$data['concurrency']);
        }

        if (isset($data['executor'])) {
            $job->setExecutor((string)$data['executor']);
        }

        if (isset($data['executor_config'])) {
            $job->setExecutorConfig((array)$data['executor_config']);
        } elseif (isset($data['executorConfig'])) {
            $job->setExecutorConfig((array)$data['executorConfig']);
        }

        if (isset($data['processors'])) {
            $job->setProcessors((array)$data['processors']);
        }

        if (isset($data['retries'])) {
            $job->setRetries((int)$data['retries']);
        }

        if (isset($data['parent_job'])) {
            $job->setParentJob((string)$data['parent_job']);
        } elseif (isset($data['parentJob'])) {
            $job->setParentJob((string)$data['parentJob']);
        }

        if (!empty($data['dependent_jobs'])) {
            $job->setDependentJobs((array)$data['dependent_jobs']);
        } elseif (!empty($data['dependentJobs'])) {
            $job->setDependentJobs((array)$data['dependentJobs']);
        }

        if (!empty($data['tags'])) {
            $job->setTags((array)$data['tags']);
        }

        if (!empty($data['metadata'])) {
            $job->setMetadata((array)$data['metadata']);
        }

        if (isset($data['ephemeral'])) {
            $job->setEphemeral((bool)$data['ephemeral']);
        }

        if (isset($data['status'])) {
            $job->setStatus((string)$data['status']);
        }

        if (isset($data['next'])) {
            $job->setNext((string)$data['next']);
        }

        return $job;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDisplayname(): string
    {
        return $this->displayname;
    }

    public function setDisplayname(string $displayname): self
    {
        $this->displayname = $displayname;

        return $this;
    }

    public function getSchedule(): string
    {
        return $this->schedule;
    }

    public function setSchedule(string $schedule): self
    {
        $this->schedule = $schedule;

        return $this;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getOwner(): string
    {
        return $this->owner;
    }

    public function setOwner(string $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function getOwnerEmail(): string
    {
        return $this->ownerEmail;
    }

    public function setOwnerEmail(string $ownerEmail): self
    {
        $this->ownerEmail = $ownerEmail;

        return $this;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function getDisabled(): bool
    {
        return $this->disabled;
    }

    public function setDisabled(bool $disabled): self
    {
        $this->disabled = $disabled;

        return $this;
    }

    public function enable(): self
    {
        $this->disabled = false;

        return $this;
    }

    public function disable(): self
    {
        $this->disabled = true;

        return $this;
    }

    public function getConcurrency(): string
    {
        return $this->concurrency;
    }

    public function setConcurrency(string $concurrency): self
    {
        if (!in_array($concurrency, [self::CONCURRENCY_ALLOW, self::CONCURRENCY_FORBID, self::CONCURRENCY_REPLACE], true)) {
            throw new InvalidArgumentException(
                sprintf('Concurrency value is incorrect. Allowed values are %s, %s, or %s.', self::CONCURRENCY_ALLOW, self::CONCURRENCY_FORBID, self::CONCURRENCY_REPLACE)
            );
        }
        $this->concurrency = $concurrency;

        return $this;
    }

    public function enableConcurrency(): self
    {
        return $this->setConcurrency(self::CONCURRENCY_ALLOW);
    }

    public function disableConcurrency(): self
    {
        return $this->setConcurrency(self::CONCURRENCY_FORBID);
    }

    public function replaceConcurrency(): self
    {
        return $this->setConcurrency(self::CONCURRENCY_REPLACE);
    }

    public function getExecutor(): string
    {
        return $this->executor;
    }

    public function setExecutor(string $executor): self
    {
        $this->executor = $executor;

        return $this;
    }

    public function getExecutorConfig(): array
    {
        return $this->executorConfig;
    }

    public function setExecutorConfig(array $executorConfig): self
    {
        $this->executorConfig = $executorConfig;

        return $this;
    }

    public function getProcessors(): array
    {
        return $this->processors;
    }

    public function setProcessors(array $processors): self
    {
        $this->processors = $processors;

        return $this;
    }

    public function getRetries(): int
    {
        return $this->retries;
    }

    public function setRetries(int $retries): self
    {
        $this->retries = $retries;

        return $this;
    }

    public function getParentJob(): string
    {
        return $this->parentJob;
    }

    public function setParentJob(string $parentJob): self
    {
        $this->parentJob = $parentJob;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getDependentJobs(): array
    {
        return $this->dependentJobs;
    }

    /**
     * @param string[] $dependentJobs
     */
    public function setDependentJobs(array $dependentJobs): self
    {
        $this->dependentJobs = $dependentJobs;

        return $this;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(array $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function isEphemeral(): bool
    {
        return $this->ephemeral;
    }

    public function setEphemeral(bool $ephemeral): self
    {
        $this->ephemeral = $ephemeral;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getNext(): string
    {
        return $this->next;
    }

    public function setNext(string $next): self
    {
        $this->next = $next;

        return $this;
    }

    public function getErrorCount(): int
    {
        return $this->errorCount;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function getLastSuccess(): ?string
    {
        return $this->lastSuccess;
    }

    /**
     * Get data to be submitted to Dkron API when creating/updating a job
     */
    public function getDataToSubmit(): array
    {
        return [
            'name' => $this->name,
            'displayname' => $this->displayname,
            'schedule' => $this->schedule,
            'timezone' => $this->timezone,
            'owner' => $this->owner,
            'owner_email' => $this->ownerEmail,
            'disabled' => $this->disabled,
            'concurrency' => $this->concurrency,
            'executor' => $this->executor,
            'executor_config' => (object)$this->executorConfig,
            'processors' => (object)$this->processors,
            'retries' => $this->retries,
            'parent_job' => $this->parentJob,
            'dependent_jobs' => $this->dependentJobs,
            'tags' => (object)$this->tags,
            'metadata' => (object)$this->metadata,
            'ephemeral' => $this->ephemeral,
            'status' => $this->status,
        ];
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'displayname' => $this->displayname,
            'schedule' => $this->schedule,
            'timezone' => $this->timezone,
            'owner' => $this->owner,
            'owner_email' => $this->ownerEmail,
            'disabled' => $this->disabled,
            'concurrency' => $this->concurrency,
            'executor' => $this->executor,
            'executor_config' => (object)$this->executorConfig,
            'processors' => (object)$this->processors,
            'retries' => $this->retries,
            'parent_job' => $this->parentJob,
            'dependent_jobs' => $this->dependentJobs,
            'tags' => (object)$this->tags,
            'metadata' => (object)$this->metadata,
            'ephemeral' => $this->ephemeral,
            'status' => $this->status,
            'next' => $this->next,
            'error_count' => $this->errorCount,
            'success_count' => $this->successCount,
            'last_error' => $this->lastError,
            'last_success' => $this->lastSuccess,
        ];
    }
}
