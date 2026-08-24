<?php

declare(strict_types=1);

namespace Dkron\Tests\Bundle;

use Dkron\Api;
use Dkron\Bundle\DkronBundle;
use Dkron\DependencyInjection\DkronExtension;
use Dkron\Endpoints;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class DkronBundleTest extends TestCase
{
    public function testBundleGetExtension(): void
    {
        $bundle = new DkronBundle();
        $this->assertInstanceOf(DkronExtension::class, $bundle->getContainerExtension());
        $this->assertNotEmpty($bundle->getPath());
    }

    public function testExtensionLoadsDefaultConfiguration(): void
    {
        $container = new ContainerBuilder(new ParameterBag(['kernel.debug' => false]));
        $extension = new DkronExtension();

        $config = [
            'dkron' => [
                'endpoints' => ['http://127.0.0.1:8080', 'http://127.0.0.2:8080'],
                'timeout' => 15.0,
                'headers' => [
                    'Authorization' => 'Bearer secret-token',
                ],
            ],
        ];

        $extension->load($config, $container);

        $this->assertTrue($container->hasDefinition(Endpoints::class));
        $this->assertTrue($container->hasDefinition(Api::class));
        $this->assertTrue($container->hasAlias('dkron.api'));
        $this->assertTrue($container->hasAlias('dkron'));
        $this->assertTrue($container->hasAlias('dkron.endpoints'));

        $container->compile();

        $api = $container->get(Api::class);
        $this->assertInstanceOf(Api::class, $api);
        $this->assertInstanceOf(Endpoints::class, $api->getEndpoints());
        $this->assertEquals(2, $api->getEndpoints()->getSize());

        $endpoints = $container->get('dkron.endpoints');
        $this->assertInstanceOf(Endpoints::class, $endpoints);
    }

    public function testExtensionWithSingleEndpointString(): void
    {
        $container = new ContainerBuilder(new ParameterBag(['kernel.debug' => false]));
        $extension = new DkronExtension();

        $config = [
            'dkron' => [
                'endpoints' => 'http://127.0.0.1:8080',
            ],
        ];

        $extension->load($config, $container);
        $container->compile();

        $api = $container->get(Api::class);
        $this->assertInstanceOf(Api::class, $api);
        $this->assertEquals(1, $api->getEndpoints()->getSize());
    }

    public function testExtensionWithCustomHttpClient(): void
    {
        $container = new ContainerBuilder(new ParameterBag(['kernel.debug' => false]));
        $extension = new DkronExtension();

        // Register custom client service
        $customClientMock = $this->createMock(ClientInterface::class);
        $container->set('custom.http.client', $customClientMock);

        $config = [
            'dkron' => [
                'endpoints' => ['http://127.0.0.1:8080'],
                'http_client' => 'custom.http.client',
            ],
        ];

        $extension->load($config, $container);
        $container->compile();

        $api = $container->get(Api::class);
        $this->assertInstanceOf(Api::class, $api);
    }
}
