<?php

declare(strict_types=1);

namespace Dkron\Tests;

use Dkron\Api;
use Dkron\Endpoints;
use Dkron\Exception\DkronNoAvailableServersException;
use Dkron\Models\Execution;
use Dkron\Models\Job;
use Dkron\Models\Member;
use Dkron\Models\Status;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use stdClass;
use Throwable;

class ApiTest extends TestCase
{
    public static function constructorDataProvider(): array
    {
        $defaults = self::getHttpClient();
        $defaults->client = null;

        return [
            'success:defaults' => [
                'http' => $defaults,
            ],
            'success:endpointsAsString' => [
                'http' => self::getHttpClient(null, 'http://192.168.0.1:8080/'),
            ],
            'success:endpointsAsArray' => [
                'http' => self::getHttpClient(null, [
                    'http://192.168.0.1:8080/',
                    'http://localhost/',
                    'https://example.com/',
                ]),
            ],
            'error:endpointsAsInvalidUrl' => [
                'http' => self::getHttpClient(null, 'test.com'),
                'exception' => InvalidArgumentException::class,
            ],
        ];
    }

    #[DataProvider('constructorDataProvider')]
    public function testConstructor(stdClass $http, ?string $exception = null): void
    {
        if ($exception) {
            $this->expectException($exception);
        }
        $api = new Api($http->endpoints, $http->client);

        $this->assertInstanceOf(Api::class, $api);
        $this->assertInstanceOf(Endpoints::class, $api->getEndpoints());
        $this->assertInstanceOf(Client::class, $api->getHttpClient());
    }

    public function testAllEndpointsCalled(): void
    {
        $request = new Request('GET', '');
        $http = $this->getHttpClient([
            new ConnectException('Client Error', $request),
            new ConnectException('Client Error', $request),
            new ConnectException('Client Error', $request),
        ], [
            'http://192.168.0.1/',
            'http://192.168.0.2/',
            'http://192.168.0.3/',
        ]);

        $api = new Api($http->endpoints, $http->client);
        $exceptionHandled = false;

        try {
            $api->getStatus();
        } catch (Exception $exception) {
            $this->assertInstanceOf(DkronNoAvailableServersException::class, $exception);
            $exceptionHandled = true;
        }

        $this->assertTrue($exceptionHandled);
        $this->assertCount(3, $http->transactions);
    }

    public function testMethodDeleteJob(): void
    {
        $http = $this->getHttpClient([['name' => 'job001']]);
        $api = new Api($http->endpoints, $http->client);
        $jobName = 'job001';

        $deletedJob = $api->deleteJob($jobName);

        $request = $this->getRequest($http);
        $this->assertEquals('/v1/jobs/' . $jobName, $request->getUri()->getPath());
        $this->assertEquals('DELETE', mb_strtoupper($request->getMethod()));
        $this->assertInstanceOf(Job::class, $deletedJob);
        $this->assertEquals('job001', $deletedJob->getName());
    }

    public function testMethodGetJob(): void
    {
        $mockData = [
            'name' => 'test:name',
            'schedule' => 'test:schedule',
        ];
        $http = $this->getHttpClient([$mockData]);
        $api = new Api($http->endpoints, $http->client);

        $job = $api->getJob($mockData['name']);

        $request = $this->getRequest($http);
        $this->assertEquals('/v1/jobs/' . rawurlencode($mockData['name']), $request->getUri()->getPath());
        $this->assertEquals('GET', mb_strtoupper($request->getMethod()));

        $this->assertInstanceOf(Job::class, $job);
        $this->assertEquals($mockData['name'], $job->getName());
        $this->assertEquals($mockData['schedule'], $job->getSchedule());
    }

    public function testMethodGetJobExecutions(): void
    {
        $mockData = [
            ['job_name' => 'nameA', 'success' => true],
            ['job_name' => 'nameB', 'success' => false],
        ];
        $http = $this->getHttpClient([$mockData]);
        $api = new Api($http->endpoints, $http->client);
        $jobName = 'job001';

        $executions = $api->getJobExecutions($jobName);

        $request = $this->getRequest($http);
        $this->assertEquals('/v1/jobs/' . $jobName . '/executions', $request->getUri()->getPath());
        $this->assertEquals('GET', mb_strtoupper($request->getMethod()));

        $this->assertCount(2, $executions);
        foreach ($mockData as $i => $executionData) {
            $execution = $executions[$i];
            $this->assertInstanceOf(Execution::class, $execution);
            $this->assertEquals($executionData['job_name'], $execution->getJobName());
            $this->assertEquals($executionData['success'], $execution->isSuccess());
        }
    }

    public function testMethodDeleteJobExecutions(): void
    {
        $mockData = [
            ['job_name' => 'job001', 'success' => true],
        ];
        $http = $this->getHttpClient([$mockData]);
        $api = new Api($http->endpoints, $http->client);

        $executions = $api->deleteJobExecutions('job001');

        $request = $this->getRequest($http);
        $this->assertEquals('/v1/jobs/job001/executions', $request->getUri()->getPath());
        $this->assertEquals('DELETE', mb_strtoupper($request->getMethod()));
        $this->assertCount(1, $executions);
    }

    public function testMethodGetAllExecutions(): void
    {
        $mockData = [
            ['id' => '1', 'job_name' => 'job1', 'success' => true],
            ['id' => '2', 'job_name' => 'job2', 'success' => false],
        ];
        $http = $this->getHttpClient([$mockData]);
        $api = new Api($http->endpoints, $http->client);

        $executions = $api->getAllExecutions();

        $request = $this->getRequest($http);
        $this->assertEquals('/v1/executions', $request->getUri()->getPath());
        $this->assertEquals('GET', mb_strtoupper($request->getMethod()));
        $this->assertCount(2, $executions);
    }

    public function testMethodGetJobsWithQuery(): void
    {
        $mockData = [
            ['name' => 'nameA', 'schedule' => 'scheduleA'],
        ];
        $http = $this->getHttpClient([$mockData]);
        $api = new Api($http->endpoints, $http->client);

        $jobs = $api->getJobs(['metadata' => ['env' => 'prod']]);

        $request = $this->getRequest($http);
        $this->assertEquals('/v1/jobs', $request->getUri()->getPath());
        $this->assertEquals('metadata%5Benv%5D=prod', $request->getUri()->getQuery());
        $this->assertCount(1, $jobs);
    }

    public function testMethodGetLeader(): void
    {
        $mockData = [
            'Name' => 'leader:name',
            'Addr' => 'leader:addr',
        ];
        $http = $this->getHttpClient([$mockData]);
        $api = new Api($http->endpoints, $http->client);

        $leader = $api->getLeader();

        $request = $this->getRequest($http);
        $this->assertEquals('/v1/leader', $request->getUri()->getPath());
        $this->assertEquals('GET', mb_strtoupper($request->getMethod()));

        $this->assertInstanceOf(Member::class, $leader);
        $this->assertEquals($mockData['Name'], $leader->getName());
        $this->assertEquals($mockData['Addr'], $leader->getAddr());
    }

    public function testMethodGetMembers(): void
    {
        $mockData = [
            ['Name' => 'nameA', 'Addr' => 'addrA'],
            ['Name' => 'nameB', 'Addr' => 'addrB'],
        ];
        $http = $this->getHttpClient([$mockData]);
        $api = new Api($http->endpoints, $http->client);

        $members = $api->getMembers();

        $request = $this->getRequest($http);
        $this->assertEquals('/v1/members', $request->getUri()->getPath());
        $this->assertEquals('GET', mb_strtoupper($request->getMethod()));

        $this->assertCount(2, $members);
        foreach ($mockData as $i => $mockItemData) {
            $member = $members[$i];
            $this->assertInstanceOf(Member::class, $member);
            $this->assertEquals($mockItemData['Name'], $member->getName());
            $this->assertEquals($mockItemData['Addr'], $member->getAddr());
        }
    }

    public function testMethodGetStatus(): void
    {
        $mockData = [
            'agent' => [
                'backend' => 'consul',
                'name' => '217f633ff07d',
                'version' => '3.2.0',
            ],
            'serf' => [
                'encrypted' => 'false',
                'event_queue' => '0',
                'event_time' => '1',
                'failed' => '0',
            ],
            'tags' => [
                'dkron_rpc_addr' => '172.21.0.7:6868',
                'dkron_server' => 'true',
                'dkron_version' => '3.2.0',
            ],
        ];
        $http = $this->getHttpClient([$mockData]);
        $api = new Api($http->endpoints, $http->client);

        $status = $api->getStatus();

        $request = $this->getRequest($http);
        $this->assertEquals('/v1/', $request->getUri()->getPath());
        $this->assertEquals('GET', mb_strtoupper($request->getMethod()));

        $this->assertInstanceOf(Status::class, $status);
        $this->assertEquals($mockData['agent'], $status->getAgent());
        $this->assertEquals($mockData['serf'], $status->getSerf());
        $this->assertEquals($mockData['tags'], $status->getTags());
    }

    public function testMethodIsBusy(): void
    {
        $http = $this->getHttpClient([['busy' => true]]);
        $api = new Api($http->endpoints, $http->client);

        $this->assertTrue($api->isBusy());
    }

    public function testMethodLeaveWithOneEndpoint(): void
    {
        $mockData = [
            ['Name' => 'nameA', 'Addr' => 'addrA'],
        ];
        $http = $this->getHttpClient([$mockData]);
        $api = new Api($http->endpoints, $http->client);

        $members = $api->leave();

        $request = $this->getRequest($http);
        $this->assertEquals('/v1/leave', $request->getUri()->getPath());
        $this->assertCount(1, $members);
    }

    public function testMethodLeaveWithEmptyEndpointWhenMultipleConfigured(): void
    {
        $mockData = [['Name' => 'nameA']];
        $mockEndpoints = [
            'http://192.168.0.1',
            'http://192.168.0.2',
        ];
        $http = $this->getHttpClient([$mockData], $mockEndpoints);
        $api = new Api($http->endpoints, $http->client);

        $this->expectException(InvalidArgumentException::class);
        $api->leave();
    }

    public function testMethodRunJob(): void
    {
        $mockExecution = [
            'job_name' => 'job001',
            'success' => true,
        ];
        $http = $this->getHttpClient([$mockExecution]);
        $api = new Api($http->endpoints, $http->client);
        $jobName = 'job001';

        $execution = $api->runJob($jobName);

        $request = $this->getRequest($http);
        $this->assertEquals('/v1/jobs/' . $jobName, $request->getUri()->getPath());
        $this->assertEquals('POST', mb_strtoupper($request->getMethod()));
        $this->assertInstanceOf(Execution::class, $execution);
        $this->assertEquals('job001', $execution->getJobName());
    }

    public function testMethodToggleJob(): void
    {
        $mockJob = [
            'name' => 'job001',
            'disabled' => true,
        ];
        $http = $this->getHttpClient([$mockJob]);
        $api = new Api($http->endpoints, $http->client);

        $job = $api->toggleJob('job001');

        $request = $this->getRequest($http);
        $this->assertEquals('/v1/jobs/job001/toggle', $request->getUri()->getPath());
        $this->assertEquals('POST', mb_strtoupper($request->getMethod()));
        $this->assertInstanceOf(Job::class, $job);
        $this->assertTrue($job->isDisabled());
    }

    public function testMethodSaveJob(): void
    {
        $mockData = [
            'name' => 'test:name',
            'schedule' => 'test:schedule',
            'executor' => 'shell',
            'executor_config' => [
                'command' => 'ls -la /tmp',
            ],
            'processors' => [
                'log' => [
                    'forward' => true,
                ],
            ],
        ];
        $http = $this->getHttpClient([$mockData]);
        $api = new Api($http->endpoints, $http->client);
        $job = Job::createFromArray($mockData);

        $savedJob = $api->saveJob($job);

        $request = $this->getRequest($http);
        $this->assertEquals('/v1/jobs', $request->getUri()->getPath());
        $this->assertEquals('POST', mb_strtoupper($request->getMethod()));
        $this->assertInstanceOf(Job::class, $savedJob);
        $this->assertEquals('test:name', $savedJob->getName());
    }

    protected static function getHttpClient(?array $responses = null, string|array $endpoints = 'http://127.0.0.1/'): stdClass
    {
        $output = new stdClass();
        $output->endpoints = $endpoints;
        $output->transactions = [];

        if ($responses === null) {
            $responses = [null];
        }

        $responses = array_map(function ($response) {
            if ($response instanceof ResponseInterface || $response instanceof Throwable) {
                return $response;
            }

            return new Response(200, ['Content-Type' => 'application/json'], json_encode($response));
        }, $responses);

        $handler = HandlerStack::create(new MockHandler($responses));
        $handler->push(Middleware::history($output->transactions));

        $output->client = new Client([
            'handler' => $handler,
        ]);

        return $output;
    }

    protected function getRequest(stdClass $http): RequestInterface
    {
        $this->assertCount(1, $http->transactions, 'Request is not available in transactions');

        return $http->transactions[0]['request'];
    }
}
