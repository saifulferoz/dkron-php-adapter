# Dkron PHP Adapter & Symfony Bundle

[![CI](https://github.com/saifulferoz/dkron-php-adapter/actions/workflows/ci.yml/badge.svg)](https://github.com/saifulferoz/dkron-php-adapter/actions/workflows/ci.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/gromo/dkron-php-adapter.svg)](https://packagist.org/packages/gromo/dkron-php-adapter)
[![PHP Version](https://img.shields.io/badge/PHP-%5E8.2-777bb4.svg)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/Symfony-%5E7.4%20%7C%7C%20%5E8.0-black.svg)](https://symfony.com/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE.md)

A modern PHP SDK and **Symfony 7.4+ / 8.x Bundle** for interacting with [Dkron](https://dkron.io), the distributed, fault-tolerant job scheduling service.

---

## Features

- ⚡ **Symfony 7.4 & 8.x Ready**: First-class Symfony Bundle (`DkronBundle`) with autowiring, dependency injection, and YAML/PHP configuration.
- 🐘 **Modern PHP 8.2+**: Strict types, typed properties, constructor promotions, and PHPUnit 11 test suite.
- 🎯 **Full Dkron API Compatibility**: Supports all latest Dkron job attributes (including `concurrency: replace`, `displayname`, `metadata`, `tags`, `processors`, `ephemeral`, `timezone`, etc.).
- 🔄 **Cluster Failover & Load Balancing**: Automatic multi-node failover, endpoint health tracking, and round-robin request dispatching.
- 🛠️ **Fluent Job Builder**: Intuitive methods to build, validate, toggle, run, and inspect cron jobs and executions.

---

## Requirements

- PHP `^8.2` (PHP 8.2, 8.3, 8.4, 8.5)
- Guzzle HTTP `^7.8.1`
- Symfony `^7.4 || ^8.0` (optional, for Bundle integration)

---

## Installation

Install via [Composer](https://getcomposer.org/):

```bash
composer require gromo/dkron-php-adapter
```

---

## Symfony Integration

### 1. Enable the Bundle

If you are using Symfony Flex, the bundle is enabled automatically. Otherwise, register it in `config/bundles.php`:

```php
return [
    // ...
    Dkron\Bundle\DkronBundle::class => ['all' => true],
];
```

### 2. Configure the Bundle

Create `config/packages/dkron.yaml`:

```yaml
dkron:
  # One or more Dkron cluster endpoints (supports environment variables)
  endpoints:
    - '%env(DKRON_URL)%'
    # - 'http://node2.dkron.internal:8080'

  # Request timeout in seconds (default: 10.0)
  timeout: 10.0

  # Default headers for every request (e.g., authentication)
  headers:
    Authorization: 'Bearer %env(DKRON_API_TOKEN)%'

  # Optional: custom PSR-18 / Guzzle ClientInterface service ID
  # http_client: 'my_custom_guzzle_client'
```

### 3. Inject and Use

Inject `Dkron\Api` or `Dkron\Endpoints` directly into your services or controllers:

```php
namespace App\Service;

use Dkron\Api;
use Dkron\Models\Job;

class SchedulerService
{
    public function __construct(
        private readonly Api $dkronApi
    ) {}

    public function scheduleBackupTask(): void
    {
        $job = (new Job('database-backup', '0 2 * * *'))
            ->setDisplayname('Nightly DB Backup')
            ->setTimezone('UTC')
            ->setExecutor('shell')
            ->setExecutorConfig([
                'command' => 'php bin/console app:backup-database',
            ])
            ->setRetries(3)
            ->setTags(['role' => 'worker'])
            ->setMetadata(['env' => 'production']);

        $this->dkronApi->saveJob($job);
    }
}
```

---

## Standalone PHP Usage

### Initializing the Client

```php
use Dkron\Api;

// Single endpoint
$api = new Api('http://127.0.0.1:8080');

// Multiple endpoints with automatic failover and load balancing
$api = new Api([
    'http://192.168.1.10:8080',
    'http://192.168.1.11:8080',
    'http://192.168.1.12:8080',
], timeout: 15.0);
```

### Job Operations

#### Creating & Saving a Job

```php
use Dkron\Models\Job;

$job = new Job(name: 'invoice-generator', schedule: '@daily');

$job->setDisplayname('Generate Daily Invoices')
    ->setTimezone('America/New_York')
    ->setOwner('Billing Team')
    ->setOwnerEmail('billing@example.com')
    ->setExecutor('http')
    ->setExecutorConfig([
        'method' => 'POST',
        'url' => 'https://api.internal/invoices/generate',
        'headers' => '["Content-Type: application/json"]',
    ])
    ->setConcurrency(Job::CONCURRENCY_FORBID) // 'allow', 'forbid', or 'replace'
    ->setRetries(2)
    ->setDependentJobs(['notify-billing-slack'])
    ->setTags(['region' => 'us-east-1'])
    ->setMetadata(['tier' => 'critical']);

$savedJob = $api->saveJob($job);
```

#### Creating from an Array / Payload

```php
$job = Job::createFromArray([
    'name' => 'cache-warmup',
    'schedule' => '@every 15m',
    'executor' => 'shell',
    'executor_config' => [
        'command' => '/usr/local/bin/warmup.sh',
    ],
    'concurrency' => Job::CONCURRENCY_ALLOW,
    'disabled' => false,
]);

$api->saveJob($job);
```

#### Querying Jobs

```php
// Get all jobs
$jobs = $api->getJobs();

// Filter jobs by metadata or tags (Dkron query parameters)
$prodJobs = $api->getJobs([
    'metadata' => ['env' => 'production'],
]);

// Get a single job by name
$job = $api->getJob('invoice-generator');
echo $job->getSchedule(); // @daily
echo $job->getNext();     // Next execution timestamp
```

#### Running & Toggling Jobs

```php
// Trigger manual execution immediately
$execution = $api->runJob('invoice-generator');

// Enable or disable a job
$api->toggleJob('invoice-generator');

// Delete a job
$api->deleteJob('invoice-generator');
```

### Execution History

```php
// Get execution history for a specific job
$executions = $api->getJobExecutions('invoice-generator');
foreach ($executions as $exec) {
    echo sprintf(
        "Job: %s | Node: %s | Success: %s | Output: %s\n",
        $exec->getJobName(),
        $exec->getNodeName(),
        $exec->isSuccess() ? 'YES' : 'NO',
        $exec->getOutput()
    );
}

// Get all executions cluster-wide
$allExecutions = $api->getAllExecutions();

// Clear execution history for a job
$api->deleteJobExecutions('invoice-generator');
```

### Cluster & Node Management

```php
// Get system and serf status
$status = $api->getStatus();
print_r($status->getAgent());

// Check if cluster/node is currently busy
if ($api->isBusy()) {
    echo "Cluster is currently busy executing jobs.\n";
}

// Get current leader node
$leader = $api->getLeader();
echo "Leader node: " . $leader->getName() . " (" . $leader->getAddr() . ")\n";

// List all cluster members
$members = $api->getMembers();
foreach ($members as $member) {
    echo $member->getName() . " -> " . $member->getAddr() . "\n";
}

// Force a node to leave the cluster
$api->leave();
```

---

## Dkron Job Properties Reference

| Property | Type | Description |
| :--- | :--- | :--- |
| `name` | `string` | Unique identifier / name of the job *(Required)*. |
| `displayname` | `string` | Human-readable display name. |
| `schedule` | `string` | Cron expression or interval (`@every 5m`, `@daily`, `0 0 * * *`). |
| `timezone` | `string` | Timezone to evaluate the schedule in (e.g. `UTC`, `Europe/Paris`). |
| `owner` | `string` | Job owner or team name. |
| `owner_email` | `string` | Notification / contact email. |
| `disabled` | `bool` | Whether the job is temporarily disabled. |
| `concurrency` | `string` | Overlapping policy: `allow`, `forbid`, or `replace`. |
| `executor` | `string` | Executor plugin: `shell`, `http`, `grpc`, etc. |
| `executor_config`| `array` | Key-value options for the chosen executor plugin. |
| `processors` | `array` | Processor plugins (e.g. `log`, `files`, `webhook`). |
| `retries` | `int` | Number of retry attempts on job failure. |
| `parent_job` | `string` | Parent job name if this job is a child. |
| `dependent_jobs`| `array` | List of jobs to trigger upon successful run. |
| `tags` | `array` | Target worker tags (e.g. `{"role": "web"}`). |
| `metadata` | `array` | Arbitrary key-value metadata for filtering and grouping. |
| `ephemeral` | `bool` | If `true`, the job is automatically removed after execution. |
| `status` | `string` | Read-only job status (`running`, `success`, `error`, etc.). |
| `next` | `string` | Read-only timestamp of next scheduled execution. |
| `error_count` | `int` | Read-only cumulative error count. |
| `success_count`| `int` | Read-only cumulative success count. |
| `last_error` | `?string`| Read-only timestamp of last error. |
| `last_success` | `?string`| Read-only timestamp of last successful run. |

---

## Testing

Run unit and integration tests with PHPUnit:

```bash
composer test
# or
./vendor/bin/phpunit
```

---

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on submitting pull requests and running tests.

---

## License

This package is licensed under the [MIT License](LICENSE.md).
