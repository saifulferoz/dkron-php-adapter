<?php

declare(strict_types=1);

namespace Dkron\Tests\Models;

use Dkron\Models\Job;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

class JobTest extends TestCase
{
    public function testCreateFromArray(): void
    {
        $mockData = [
            'name' => 'test:name',
            'displayname' => 'Test Display Name',
            'schedule' => '0 0 * * *',
            'timezone' => 'UTC',
            'owner' => 'DevOps',
            'owner_email' => 'devops@example.com',
            'disabled' => true,
            'concurrency' => Job::CONCURRENCY_REPLACE,
            'executor' => 'shell',
            'executor_config' => ['command' => 'echo 1'],
            'processors' => ['log' => ['forward' => true]],
            'retries' => 3,
            'parent_job' => 'parent:job',
            'dependent_jobs' => ['child:job1', 'child:job2'],
            'tags' => ['role' => 'worker'],
            'metadata' => ['env' => 'production'],
            'ephemeral' => true,
            'status' => 'running',
            'next' => '2026-08-25T00:00:00Z',
            'error_count' => 5,
            'success_count' => 10,
            'last_error' => '2026-08-24T12:00:00Z',
            'last_success' => '2026-08-24T10:00:00Z',
        ];

        $job = Job::createFromArray($mockData);

        $this->assertInstanceOf(Job::class, $job);
        $this->assertEquals($mockData['name'], $job->getName());
        $this->assertEquals($mockData['displayname'], $job->getDisplayname());
        $this->assertEquals($mockData['schedule'], $job->getSchedule());
        $this->assertEquals($mockData['timezone'], $job->getTimezone());
        $this->assertEquals($mockData['owner'], $job->getOwner());
        $this->assertEquals($mockData['owner_email'], $job->getOwnerEmail());
        $this->assertTrue($job->isDisabled());
        $this->assertTrue($job->getDisabled());
        $this->assertEquals(Job::CONCURRENCY_REPLACE, $job->getConcurrency());
        $this->assertEquals($mockData['executor'], $job->getExecutor());
        $this->assertEquals($mockData['executor_config'], $job->getExecutorConfig());
        $this->assertEquals($mockData['processors'], $job->getProcessors());
        $this->assertEquals(3, $job->getRetries());
        $this->assertEquals('parent:job', $job->getParentJob());
        $this->assertEquals(['child:job1', 'child:job2'], $job->getDependentJobs());
        $this->assertEquals(['role' => 'worker'], $job->getTags());
        $this->assertEquals(['env' => 'production'], $job->getMetadata());
        $this->assertTrue($job->isEphemeral());
        $this->assertEquals('running', $job->getStatus());
        $this->assertEquals('2026-08-25T00:00:00Z', $job->getNext());
        $this->assertEquals(5, $job->getErrorCount());
        $this->assertEquals(10, $job->getSuccessCount());
        $this->assertEquals('2026-08-24T12:00:00Z', $job->getLastError());
        $this->assertEquals('2026-08-24T10:00:00Z', $job->getLastSuccess());
    }

    public function testGetDataToSubmit(): void
    {
        $mockData = [
            'name' => 'test:name',
            'schedule' => 'test:schedule',
        ];

        $job = new Job(
            $mockData['name'],
            $mockData['schedule'],
            777,
            'Last Error Date',
            'Last Success Date',
            999
        );
        $job->setExecutorConfig([]);
        $job->setProcessors([]);
        $job->setTags([]);

        $dataToSubmit = $job->getDataToSubmit();

        $requiredFields = [
            'concurrency',
            'dependent_jobs',
            'disabled',
            'executor',
            'executor_config',
            'name',
            'owner',
            'owner_email',
            'parent_job',
            'processors',
            'retries',
            'schedule',
            'tags',
            'metadata',
            'ephemeral',
            'status',
            'timezone',
        ];

        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $dataToSubmit);
        }

        $readonlyFields = [
            'error_count',
            'last_error',
            'last_success',
            'success_count',
        ];

        foreach ($readonlyFields as $field) {
            $this->assertArrayNotHasKey($field, $dataToSubmit);
        }

        // check values
        foreach ($mockData as $key => $value) {
            $this->assertEquals($value, $dataToSubmit[$key]);
        }

        // check key-value objects
        $this->assertInstanceOf(stdClass::class, $dataToSubmit['executor_config']);
        $this->assertInstanceOf(stdClass::class, $dataToSubmit['processors']);
        $this->assertInstanceOf(stdClass::class, $dataToSubmit['tags']);
        $this->assertInstanceOf(stdClass::class, $dataToSubmit['metadata']);
    }

    public function testFluentMethods(): void
    {
        $job = new Job('job1', '@hourly');
        $job->setName('renamed')
            ->setDisplayname('Display')
            ->setSchedule('@daily')
            ->setTimezone('America/New_York')
            ->setOwner('Team')
            ->setOwnerEmail('team@corp.com')
            ->disable()
            ->setExecutor('http')
            ->setExecutorConfig(['url' => 'https://api.example.com'])
            ->setProcessors(['files' => []])
            ->setRetries(2)
            ->setParentJob('parent')
            ->setDependentJobs(['dep1'])
            ->setTags(['tier' => '1'])
            ->setMetadata(['env' => 'test'])
            ->setEphemeral(true)
            ->setStatus('idle')
            ->setNext('2026-08-25T01:00:00Z');

        $this->assertEquals('renamed', $job->getName());
        $this->assertEquals('Display', $job->getDisplayname());
        $this->assertEquals('@daily', $job->getSchedule());
        $this->assertEquals('America/New_York', $job->getTimezone());
        $this->assertEquals('Team', $job->getOwner());
        $this->assertEquals('team@corp.com', $job->getOwnerEmail());
        $this->assertTrue($job->isDisabled());
        $this->assertEquals('http', $job->getExecutor());
        $this->assertEquals(['url' => 'https://api.example.com'], $job->getExecutorConfig());
        $this->assertEquals(['files' => []], $job->getProcessors());
        $this->assertEquals(2, $job->getRetries());
        $this->assertEquals('parent', $job->getParentJob());
        $this->assertEquals(['dep1'], $job->getDependentJobs());
        $this->assertEquals(['tier' => '1'], $job->getTags());
        $this->assertEquals(['env' => 'test'], $job->getMetadata());
        $this->assertTrue($job->isEphemeral());
        $this->assertEquals('idle', $job->getStatus());
        $this->assertEquals('2026-08-25T01:00:00Z', $job->getNext());

        $job->enable();
        $this->assertFalse($job->isDisabled());

        $job->replaceConcurrency();
        $this->assertEquals(Job::CONCURRENCY_REPLACE, $job->getConcurrency());

        $job->disableConcurrency();
        $this->assertEquals(Job::CONCURRENCY_FORBID, $job->getConcurrency());

        $job->enableConcurrency();
        $this->assertEquals(Job::CONCURRENCY_ALLOW, $job->getConcurrency());
    }

    public function testJsonSerialize(): void
    {
        $job = new Job('job1', '@hourly');
        $json = $job->jsonSerialize();

        $this->assertIsArray($json);
        $this->assertEquals('job1', $json['name']);
        $this->assertEquals('@hourly', $json['schedule']);
    }

    #[DataProvider('setConcurrencyDataProvider')]
    public function testSetConcurrency(string $value, ?string $exception = null): void
    {
        $job = new Job('name', 'schedule');

        if ($exception) {
            $this->expectException($exception);
        }

        $job->setConcurrency($value);
        $this->assertEquals($value, $job->getConcurrency());
    }

    public static function setConcurrencyDataProvider(): array
    {
        return [
            'success:allow' => [
                'value' => Job::CONCURRENCY_ALLOW,
                'exception' => null,
            ],
            'success:forbid' => [
                'value' => Job::CONCURRENCY_FORBID,
                'exception' => null,
            ],
            'success:replace' => [
                'value' => Job::CONCURRENCY_REPLACE,
                'exception' => null,
            ],
            'error:empty' => [
                'value' => '',
                'exception' => InvalidArgumentException::class,
            ],
            'error:invalid' => [
                'value' => 'invalid',
                'exception' => InvalidArgumentException::class,
            ],
        ];
    }
}
