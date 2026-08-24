<?php

declare(strict_types=1);

namespace Dkron;

use Dkron\Exception\DkronException;
use Dkron\Exception\DkronNoAvailableServersException;
use Dkron\Exception\DkronResponseException;
use Dkron\Models\Execution;
use Dkron\Models\Job;
use Dkron\Models\Member;
use Dkron\Models\Status;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use JsonException;
use Psr\Http\Message\ResponseInterface;

class Api
{
    public const METHOD_DELETE = 'DELETE';
    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';
    public const METHOD_PUT = 'PUT';
    public const METHOD_PATCH = 'PATCH';
    public const TIMEOUT = 10.0;
    public const URL_PREFIX = '/v1/';

    private Endpoints $endpoints;
    private ClientInterface $httpClient;
    private array $defaultHeaders;

    /**
     * @param string|array<int, string>|Endpoints $endpoints
     * @param array<string, string> $defaultHeaders
     * @throws InvalidArgumentException
     */
    public function __construct(
        string|array|Endpoints $endpoints,
        ?ClientInterface $httpClient = null,
        array $defaultHeaders = [],
        float $timeout = self::TIMEOUT
    ) {
        if (!($endpoints instanceof Endpoints)) {
            $endpoints = new Endpoints($endpoints);
        }
        $this->endpoints = $endpoints;

        if ($httpClient === null) {
            $httpClient = new Client([
                'timeout' => $timeout,
                'headers' => array_merge([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ], $defaultHeaders),
            ]);
        }
        $this->httpClient = $httpClient;
        $this->defaultHeaders = $defaultHeaders;
    }

    public function getEndpoints(): Endpoints
    {
        return $this->endpoints;
    }

    public function getHttpClient(): ClientInterface
    {
        return $this->httpClient;
    }

    /**
     * Get system status
     *
     * @throws DkronException
     */
    public function getStatus(): Status
    {
        $data = $this->request('/', self::METHOD_GET);

        return Status::createFromArray(is_array($data) ? $data : []);
    }

    /**
     * Check if the node / cluster is busy
     *
     * @throws DkronException
     */
    public function isBusy(): bool
    {
        try {
            $data = $this->request('/busy', self::METHOD_GET);
            return is_array($data) && !empty($data);
        } catch (DkronException) {
            return false;
        }
    }

    /**
     * Get the current cluster leader
     *
     * @throws DkronException
     */
    public function getLeader(): Member
    {
        $data = $this->request('/leader', self::METHOD_GET);

        return Member::createFromArray(is_array($data) ? $data : []);
    }

    /**
     * Get all cluster members
     *
     * @return Member[]
     * @throws DkronException
     */
    public function getMembers(): array
    {
        $members = [];
        $responseData = $this->request('/members', self::METHOD_GET);

        if (is_array($responseData)) {
            foreach ($responseData as $memberData) {
                if (is_array($memberData)) {
                    $members[] = Member::createFromArray($memberData);
                }
            }
        }

        return $members;
    }

    /**
     * Force a node to leave the cluster
     *
     * @return Member[]
     * @throws DkronException
     */
    public function leave(?string $endpoint = null): array
    {
        if ($endpoint === null && $this->endpoints->getSize() === 1) {
            $endpoint = $this->endpoints->getAvailableEndpoint();
        }

        if ($endpoint === null) {
            throw new InvalidArgumentException('Parameter endpoint has to be set when multiple endpoints are configured');
        }

        $members = [];
        $responseData = $this->request('/leave', self::METHOD_GET, null, $endpoint);

        if (is_array($responseData)) {
            foreach ($responseData as $memberData) {
                if (is_array($memberData)) {
                    $members[] = Member::createFromArray($memberData);
                }
            }
        }

        return $members;
    }

    /**
     * List all jobs (with optional query filters such as metadata)
     *
     * @param array<string, mixed> $query
     * @return Job[]
     * @throws DkronException
     */
    public function getJobs(array $query = []): array
    {
        $jobs = [];
        $path = '/jobs';
        if (!empty($query)) {
            $path .= '?' . http_build_query($query);
        }

        $responseData = $this->request($path, self::METHOD_GET);

        if (is_array($responseData)) {
            foreach ($responseData as $jobData) {
                if (is_array($jobData)) {
                    $jobs[] = Job::createFromArray($jobData);
                }
            }
        }

        return $jobs;
    }

    /**
     * Get a single job by name
     *
     * @throws DkronException
     */
    public function getJob(string $name): Job
    {
        $data = $this->request('/jobs/' . rawurlencode($name), self::METHOD_GET);

        return Job::createFromArray(is_array($data) ? $data : []);
    }

    /**
     * Create or update a job
     *
     * @throws DkronException
     */
    public function saveJob(Job $job): Job
    {
        $data = $this->request('/jobs', self::METHOD_POST, $job->getDataToSubmit());

        return is_array($data) ? Job::createFromArray($data) : $job;
    }

    /**
     * Alias for saveJob
     *
     * @throws DkronException
     */
    public function createJob(Job $job): Job
    {
        return $this->saveJob($job);
    }

    /**
     * Delete a job by name
     *
     * @throws DkronException
     */
    public function deleteJob(string $name): ?Job
    {
        $data = $this->request('/jobs/' . rawurlencode($name), self::METHOD_DELETE);

        return is_array($data) ? Job::createFromArray($data) : null;
    }

    /**
     * Manually trigger/run a job
     *
     * @throws DkronException
     */
    public function runJob(string $name): ?Execution
    {
        $data = $this->request('/jobs/' . rawurlencode($name), self::METHOD_POST);

        return is_array($data) ? Execution::createFromArray($data) : null;
    }

    /**
     * Toggle a job (enable / disable)
     *
     * @throws DkronException
     */
    public function toggleJob(string $name): Job
    {
        $data = $this->request('/jobs/' . rawurlencode($name) . '/toggle', self::METHOD_POST);

        return Job::createFromArray(is_array($data) ? $data : []);
    }

    /**
     * Get execution history for a job
     *
     * @return Execution[]
     * @throws DkronException
     */
    public function getJobExecutions(string $name): array
    {
        $executions = [];
        $responseData = $this->request('/jobs/' . rawurlencode($name) . '/executions', self::METHOD_GET);

        if (is_array($responseData)) {
            foreach ($responseData as $executionData) {
                if (is_array($executionData)) {
                    $executions[] = Execution::createFromArray($executionData);
                }
            }
        }

        return $executions;
    }

    /**
     * Delete executions history for a job
     *
     * @return Execution[]
     * @throws DkronException
     */
    public function deleteJobExecutions(string $name): array
    {
        $executions = [];
        $responseData = $this->request('/jobs/' . rawurlencode($name) . '/executions', self::METHOD_DELETE);

        if (is_array($responseData)) {
            foreach ($responseData as $executionData) {
                if (is_array($executionData)) {
                    $executions[] = Execution::createFromArray($executionData);
                }
            }
        }

        return $executions;
    }

    /**
     * Get all executions across the cluster
     *
     * @return Execution[]
     * @throws DkronException
     */
    public function getAllExecutions(): array
    {
        $executions = [];
        $responseData = $this->request('/executions', self::METHOD_GET);

        if (is_array($responseData)) {
            foreach ($responseData as $executionData) {
                if (is_array($executionData)) {
                    $executions[] = Execution::createFromArray($executionData);
                }
            }
        }

        return $executions;
    }

    /**
     * Send HTTP request to available Dkron server
     *
     * @param string|array|Endpoints|null $endpoints
     * @throws DkronException
     */
    protected function request(
        string $url,
        string $method = self::METHOD_GET,
        mixed $data = null,
        string|array|Endpoints|null $endpoints = null
    ): mixed {
        if ($endpoints === null) {
            $endpoints = $this->endpoints;
        }

        if (!($endpoints instanceof Endpoints)) {
            $endpoints = new Endpoints($endpoints);
        }

        while ($endpoint = $endpoints->getAvailableEndpoint()) {
            try {
                $options = [];
                if ($data !== null) {
                    $options['json'] = $data;
                }
                if (!empty($this->defaultHeaders)) {
                    $options['headers'] = $this->defaultHeaders;
                }

                $fullUrl = $endpoint . self::URL_PREFIX . ltrim($url, '/');
                $response = $this->httpClient->request($method, $fullUrl, $options);

                $statusCode = $response->getStatusCode();
                if ($statusCode === 204) {
                    return [];
                }

                $body = (string)$response->getBody();
                if ($body === '' || $body === 'null') {
                    return [];
                }

                $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

                return $decoded;
            } catch (ConnectException) {
                $endpoints->setEndpointAsUnavailable($endpoint);
            } catch (JsonException $exception) {
                throw new DkronResponseException('json_decode error: ' . $exception->getMessage(), $exception->getCode(), $exception);
            } catch (DkronException $exception) {
                throw $exception;
            } catch (\Throwable $exception) {
                throw new DkronException($exception->getMessage(), (int)$exception->getCode(), $exception);
            }
        }

        throw new DkronNoAvailableServersException();
    }
}
