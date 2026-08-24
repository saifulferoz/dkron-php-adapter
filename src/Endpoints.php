<?php

declare(strict_types=1);

namespace Dkron;

use Dkron\Exception\DkronNoAvailableServersException;
use InvalidArgumentException;

class Endpoints
{
    /** @var array<int, array{available: bool, url: string}> */
    private array $endpoints = [];

    private int $offset = 0;

    /**
     * @param string|array<int, string> $endpoints
     * @throws InvalidArgumentException
     */
    public function __construct(string|array $endpoints)
    {
        if (is_string($endpoints)) {
            $endpoints = [$endpoints];
        }

        if (count($endpoints) === 0) {
            throw new InvalidArgumentException('Parameter endpoints cannot be empty');
        }

        // Sanitize and deduplicate
        $sanitized = array_map(fn (string $endpoint) => $this->sanitize($endpoint), $endpoints);
        $unique = array_values(array_unique($sanitized));

        shuffle($unique);

        foreach ($unique as $endpoint) {
            $this->endpoints[] = [
                'available' => true,
                'url' => $endpoint,
            ];
        }
    }

    /**
     * @throws DkronNoAvailableServersException
     */
    public function getAvailableEndpoint(): string
    {
        $availableEndpoints = $this->getAvailableEndpoints();
        $length = count($availableEndpoints);

        if ($length === 0) {
            throw new DkronNoAvailableServersException();
        }

        if ($this->offset >= $length) {
            $this->offset = 0;
        }

        $endpoint = $availableEndpoints[$this->offset];
        $this->offset++;

        return $endpoint;
    }

    /**
     * @return string[]
     */
    public function getAvailableEndpoints(): array
    {
        $available = array_values(array_filter(
            $this->endpoints,
            fn (array $endpoint) => $endpoint['available']
        ));

        return array_map(fn (array $endpoint) => $endpoint['url'], $available);
    }

    public function getSize(): int
    {
        return count($this->endpoints);
    }

    public function hasEndpoint(string $endpoint): bool
    {
        $url = $this->sanitize($endpoint);
        foreach ($this->endpoints as $item) {
            if ($item['url'] === $url) {
                return true;
            }
        }

        return false;
    }

    public function isEndpointAvailable(string $endpoint): bool
    {
        $url = $this->sanitize($endpoint);
        foreach ($this->endpoints as $item) {
            if ($item['url'] === $url) {
                return $item['available'];
            }
        }

        return false;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function setEndpointAsUnavailable(string $endpoint): void
    {
        $url = $this->sanitize($endpoint);
        foreach ($this->endpoints as $i => $item) {
            if ($item['url'] === $url) {
                $this->endpoints[$i]['available'] = false;
                return;
            }
        }

        throw new InvalidArgumentException(sprintf('Endpoint %s not found', $endpoint));
    }

    public function reset(): void
    {
        foreach ($this->endpoints as $i => $item) {
            $this->endpoints[$i]['available'] = true;
        }
        $this->offset = 0;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function sanitize(string $endpoint): string
    {
        if (filter_var($endpoint, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException(sprintf('Endpoint %s has to be a valid URL', $endpoint));
        }

        $url = parse_url($endpoint);
        if (!$url || !isset($url['scheme']) || !isset($url['host'])) {
            throw new InvalidArgumentException(sprintf('Endpoint %s is not a valid URL structure', $endpoint));
        }

        $sanitized = $url['scheme'] . '://' . $url['host'];
        if (isset($url['port'])) {
            $sanitized .= ':' . $url['port'];
        }

        return strtolower($sanitized);
    }
}
