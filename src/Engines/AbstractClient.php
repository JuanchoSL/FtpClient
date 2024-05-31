<?php

declare(strict_types=1);

namespace JuanchoSL\FtpClient\Engines;

use FTP\Connection;
use JuanchoSL\Exceptions\PreconditionRequiredException;
use JuanchoSL\Exceptions\UnauthorizedException;
use JuanchoSL\FtpClient\Contracts\ClientInterface;

abstract class AbstractClient implements ClientInterface
{

    protected $link;
    protected bool $connected = false;
    protected bool $logged = false;

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function isLogged(): bool
    {
        return $this->logged;
    }

    protected function checkConnection(): void
    {
        if (!$this->isConnected()) {
            throw new PreconditionRequiredException("You need to connect first");
        }
        if (!$this->isLogged()) {
            throw new UnauthorizedException("You need to login first");
        }
    }

    abstract public function disconnect(): bool;

    function __destruct()
    {
        $this->disconnect();
    }
}