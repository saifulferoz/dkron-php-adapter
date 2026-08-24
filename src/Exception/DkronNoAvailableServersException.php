<?php

declare(strict_types=1);

namespace Dkron\Exception;

class DkronNoAvailableServersException extends DkronException
{
    protected $message = 'No available dkron agent';
}

