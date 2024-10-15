<?php

declare(strict_types=1);

namespace JuanchoSL\FtpClient\Engines;

use JuanchoSL\Exceptions\DestinationUnreachableException;
use JuanchoSL\Exceptions\PreconditionRequiredException;
use JuanchoSL\FtpClient\Contracts\ConnectionInterface;

class Ftps extends Ftp implements ConnectionInterface
{

    public function connect(string $server, int $port = 21): bool
    {
        if (!extension_loaded('ftp')) {
            throw new PreconditionRequiredException("The FTP extension is not available");
        }
        if (!extension_loaded('openssl')) {
            throw new PreconditionRequiredException("The OPENSSL extension is not available");
        }
        $this->link = ftp_ssl_connect($server, $port);
        $this->connected = ($this->link !== false);
        if(!$this->isConnected()){
            throw new DestinationUnreachableException("Can not connect to the desired service");
        }
        return $this->isConnected();
    }

    public function login(string $user, string $pass): bool
    {
        parent::login($user, $pass);
        $this->pasive(true);
        return $this->isLogged();
    }
}
