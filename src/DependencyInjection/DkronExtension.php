<?php

declare(strict_types=1);

namespace Dkron\DependencyInjection;

use Dkron\Api;
use Dkron\Endpoints;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class DkronExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Register Endpoints service
        $endpointsDefinition = new Definition(Endpoints::class, [
            $config['endpoints'],
        ]);
        $endpointsDefinition->setPublic(false);
        $container->setDefinition(Endpoints::class, $endpointsDefinition);
        $container->setAlias('dkron.endpoints', Endpoints::class)->setPublic(true);

        // HTTP Client resolution
        if (!empty($config['http_client'])) {
            $httpClientReference = new Reference($config['http_client']);
        } else {
            $httpClientDefinition = new Definition(Client::class, [
                [
                    'timeout' => $config['timeout'],
                    'headers' => array_merge([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ], $config['headers']),
                ],
            ]);
            $httpClientDefinition->setPublic(false);
            $container->setDefinition('dkron.http_client', $httpClientDefinition);
            $httpClientReference = new Reference('dkron.http_client');
        }

        // Register Api service
        $apiDefinition = new Definition(Api::class, [
            new Reference(Endpoints::class),
            $httpClientReference,
            $config['headers'],
            $config['timeout'],
        ]);
        $apiDefinition->setPublic(true);
        $container->setDefinition(Api::class, $apiDefinition);
        $container->setAlias('dkron.api', Api::class)->setPublic(true);
        $container->setAlias('dkron', Api::class)->setPublic(true);
    }

    public function getAlias(): string
    {
        return 'dkron';
    }
}
