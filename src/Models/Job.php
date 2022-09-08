<?php

namespace Dkron\Models;

use InvalidArgumentException;

class Job implements \JsonSerializable
{

    const CONCURRENCY_ALLOW = 'allow';
    const CONCURRENCY_FORBID = 'forbid';

    /** @var string */
    private string $concurrency = self::CONCURRENCY_ALLOW;

    /** @var string[] */
    private array $dependentJobs = [];

    /** @var bool */
    private bool $disabled = false;

    /** @var string */
    private string $executor = "";

    /** @var array[string]string */
    private array $executorConfig = [];

    /** @var int */
    private int $errorCount = 0;

    /** @var string */
    private string $lastError = "";

    /** @var string */
    private string $lastSuccess = "";

    /** @var string */
    private string $name = "";

    /** @var string */
    private string $displayname = "";

    /** @var string */
    private string $owner = "";

    /** @var string */
    private string $ownerEmail = "";

    /** @var string */
    private string $parentJob = "";

    /** @var array[string]string */
    private array $processors = [];

    /** @var int */
    private int $retries = 0;

    /** @var string */
    private string $schedule = "* * * * * *";

    /** @var int */
    private int $successCount = 0;

    /** @var array[string]string */
    private array $tags = [];

    /** @var array[string]string */
    private array $metadata = [];

    /** @var string */
    private string $timezone = "";

    /** @var string */
    private string $status = "";

    /** @var string */
    private string $next = "";

    /**
     * Job constructor.
     * @param string $name
     * @param string $schedule
     * @param int|null $errorCount
     * @param string|null $lastError
     * @param string|null $lastSuccess
     * @param int|null $successCount
     */
    public function __construct(
        string $name,
        string $schedule,
        int $errorCount = null,
        string $lastError = null,
        string $lastSuccess = null,
        int $successCount = null
    ) {
        $this->name = $name;
        $this->setSchedule($schedule);

        // read-only parameters
        $this->errorCount = is_int($errorCount) ? $errorCount : 0;
        $this->lastError = is_string($lastError) ? $lastError : '';
        $this->lastSuccess = is_string($lastSuccess) ? $lastSuccess : '';
        $this->successCount = is_int($successCount) ? $successCount : 0;
    }

    public function disableConcurrency(): self
    {
        return $this->setConcurrency(self::CONCURRENCY_FORBID);
    }

    public function enableConcurrency(): self
    {
        return $this->setConcurrency(self::CONCURRENCY_ALLOW);
    }

    /**
     * @return string
     */
    public function getConcurrency(): string
    {
        return $this->concurrency;
    }

    /**
     * Get data to be serialized as json for API
     *  All fields must be sent in order to overwrite values
     *
     * @return array[string]string
     */
    public function getDataToSubmit(): array
    {
        return [
            'name' => $this->name,
            'displayname' => $this->displayname,
            'schedule' => $this->schedule,
            'concurrency' => $this->concurrency,
            'dependent_jobs' => $this->dependentJobs,
            'disabled' => $this->disabled,
            'executor' => $this->executor,
            'executor_config' => (object)$this->executorConfig,
            'owner' => $this->owner,
            'owner_email' => $this->ownerEmail,
            'parent_job' => $this->parentJob,
            'processors' => (object)$this->processors,
            'retries' => $this->retries,
            'tags' => (object)$this->tags,
            'metadata' => (object)$this->metadata,
            'timezone' => $this->timezone,
            'next' => $this->next,
            'status' => $this->status,
        ];
    }

    /**
     * @return string[]
     */
    public function getDependentJobs(): array
    {
        return $this->dependentJobs;
    }

    /**
     * @return bool
     */
    public function getDisabled(): bool
    {
        return $this->disabled;
    }

    /**
     * @return int
     */
    public function getErrorCount(): int
    {
        return $this->errorCount;
    }

    /**
     * @return string
     */
    public function getExecutor(): string
    {
        return $this->executor;
    }

    /**
     * @return array[string]string
     */
    public function getExecutorConfig(): array
    {
        return $this->executorConfig;
    }

    /**
     * @return string
     */
    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * @return string
     */
    public function getLastSuccess(): string
    {
        return $this->lastSuccess;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getOwner(): string
    {
        return $this->owner;
    }

    /**
     * @return string
     */
    public function getOwnerEmail(): string
    {
        return $this->ownerEmail;
    }

    /**
     * @return string
     */
    public function getParentJob(): string
    {
        return $this->parentJob;
    }

    /**
     * @return array[string]string
     */
    public function getProcessors(): array
    {
        return $this->processors;
    }

    /**
     * @return int
     */
    public function getRetries(): int
    {
        return $this->retries;
    }

    /**
     * @return string
     */
    public function getSchedule(): string
    {
        return $this->schedule;
    }

    /**
     * @return int
     */
    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    /**
     * @return array[string]string
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @return string
     */
    public function getTimezone(): string
    {
        return $this->timezone;
    }

    /**
     * @return bool
     */
    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    /**
     * @return array[string]string
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'displayName' => $this->displayname,
            'schedule' => $this->schedule,
            'concurrency' => $this->concurrency,
            'dependent_jobs' => $this->dependentJobs,
            'disabled' => $this->disabled,
            'error_count' => $this->errorCount,
            'executor' => $this->executor,
            'executor_config' => (object)$this->executorConfig,
            'last_error' => $this->lastError,
            'last_success' => $this->lastSuccess,
            'owner' => $this->owner,
            'owner_email' => $this->ownerEmail,
            'parent_job' => $this->parentJob,
            'processors' => (object)$this->processors,
            'retries' => $this->retries,
            'success_count' => $this->successCount,
            'tags' => (object)$this->tags,
            'metadata' => (object)$this->metadata,
            'timezone' => $this->timezone,
            'status' => $this->status,
            'next' => $this->next,
        ];
    }

    /**
     * @param string $concurrency
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setConcurrency(string $concurrency): self
    {
        if (!in_array($concurrency, [self::CONCURRENCY_ALLOW, self::CONCURRENCY_FORBID], true)) {
            throw new InvalidArgumentException(
                'Concurrency value is incorrect. Allowed values are '
                .self::CONCURRENCY_ALLOW.' or '.self::CONCURRENCY_FORBID
            );
        }
        $this->concurrency = $concurrency;

        return $this;
    }

    /**
     * @param string[] $dependentJobs
     * @return $this
     */
    public function setDependentJobs(array $dependentJobs): self
    {
        $this->dependentJobs = $dependentJobs;

        return $this;
    }

    /**
     * @param bool $disabled
     * @return $this
     */
    public function setDisabled(bool $disabled): self
    {
        $this->disabled = (bool)$disabled;

        return $this;
    }

    /**
     * @param string $executor
     * @return $this
     */
    public function setExecutor(string $executor)
    {
        $this->executor = $executor;

        return $this;
    }

    /**
     * @param array[string]string $executorConfig
     * @return $this
     */
    public function setExecutorConfig(array $executorConfig)
    {
        $this->executorConfig = $executorConfig;

        return $this;
    }

    /**
     * @param string $owner
     * @return $this
     */
    public function setOwner(string $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @param string $ownerEmail
     * @return $this
     */
    public function setOwnerEmail(string $ownerEmail): self
    {
        $this->ownerEmail = $ownerEmail;

        return $this;
    }

    /**
     * @param string $parentJob
     * @return $this
     */
    public function setParentJob(string $parentJob): self
    {
        $this->parentJob = $parentJob;

        return $this;
    }

    /**
     * @param array[string]string $processors
     * @return $this
     */
    public function setProcessors(array $processors): self
    {
        $this->processors = $processors;

        return $this;
    }

    /**
     * @param int $retries
     * @return $this
     */
    public function setRetries(int $retries): self
    {
        $this->retries = $retries;

        return $this;
    }

    /**
     * @param string $schedule
     * @return $this
     */
    public function setSchedule($schedule): self
    {
        $this->schedule = $schedule;

        return $this;
    }

    /**
     * @param array[string]string $tags
     * @return $this
     */
    public function setTags(array $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * @param string $timezone
     * @return $this
     */
    public function setTimezone(string $timezone)
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * @param array[string]string $data
     * @return Job
     */
    public static function createFromArray(array $data): self
    {
        // create job with required and read-only data
        $job = new self(
            $data['name'],
            $data['schedule'],
            $data['error_count'] ?? null,
            $data['last_error'] ?? null,
            $data['last_success'] ?? null,
            $data['success_count'] ?? null
        );
        if (isset($data['concurrency'])) {
            $job->setConcurrency($data['concurrency']);
        }
        if (isset($data['disabled'])) {
            $job->setDisabled($data['disabled']);
        }
        if (isset($data['executor'])) {
            $job->setExecutor($data['executor']);
        }
        if (isset($data['executor_config'])) {
            $job->setExecutorConfig($data['executor_config']);
        }
        if (isset($data['owner'])) {
            $job->setOwner($data['owner']);
        }
        if (isset($data['owner_email'])) {
            $job->setOwnerEmail($data['owner_email']);
        }
        if (isset($data['parent_job'])) {
            $job->setParentJob($data['parent_job']);
        }
        if (isset($data['retries'])) {
            $job->setRetries($data['retries']);
        }
        if (isset($data['timezone'])) {
            $job->setTimezone($data['timezone']);
        }

        // nullable values
        if (!empty($data['dependent_jobs'])) {
            $job->setDependentJobs($data['dependent_jobs']);
        }
        if (!empty($data['processors'])) {
            $job->setProcessors($data['processors']);
        }
        if (!empty($data['tags'])) {
            $job->setTags($data['tags']);
        }
        if (isset($data['displayName'])) {
            $job->setDisplayname($data['displayName']);
        }
        if (isset($data['metadata'])) {
            $job->setMetadata($data['metadata']);
        }

        return $job;
    }

    /**
     * @return string
     */
    public function getDisplayname(): string
    {
        return $this->displayname;
    }

    /**
     * @param string $displayname
     */
    public function setDisplayname(string $displayname): void
    {
        $this->displayname = $displayname;
    }

    /**
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @param array $metadata
     */
    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getNext(): string
    {
        return $this->next;
    }

    /**
     * @param string $next
     */
    public function setNext(string $next): void
    {
        $this->next = $next;
    }
}
