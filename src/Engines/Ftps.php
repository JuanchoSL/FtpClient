<?php

declare(strict_types=1);

namespace JuanchoSL\FtpClient\Engines;

use JuanchoSL\FtpClient\Contracts\ConnectionInterface;

class Ftps extends Ftp implements ConnectionInterface
{

    public function connect(string $server, int $port = 21): bool
    {
        $this->link = ftp_ssl_connect($server, $port);
        return $this->connected = ($this->link !== false);
    }

    public function login(string $user, string $pass): bool
    {
        parent::login($user, $pass);
        $this->pasive(true);
        return $this->isLogged();
    }
}
