<?php

declare(strict_types=1);

namespace Dkron\Bundle;

use Dkron\DependencyInjection\DkronExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class DkronBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new DkronExtension();
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
