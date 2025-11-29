<?php

declare(strict_types=1);

namespace JuanchoSL\FtpClient\Engines;

use JuanchoSL\Exceptions\DestinationUnreachableException;
use JuanchoSL\FtpClient\Contracts\ConnectionInterface;

class Ftps extends Ftp implements ConnectionInterface
{

    public function connect(string $server, int $port = self::DEFAULT_PORT): bool
    {
        $this->server = $server;
        $this->port = $port;

        $this->checkExtension('ftp', 'openssl');
        $this->link = ftp_ssl_connect($server, $port);
        $this->connected = ($this->link !== false);
        if (!$this->isConnected()) {
            $exception = new DestinationUnreachableException("Can not connect to the desired server");
            $this->log($exception, 'error', [
                'exception' => $exception,
                'credentials' => [
                    'host' => $server,
                    'port' => $port,
                ]
            ]);
            throw $exception;
        }
        return $this->isConnected();
    }

    public function login(string $user, #[\SensitiveParameter] string $pass = ''): bool
    {
        parent::login($user, $pass);
        $this->pasive(true);
        return $this->isLogged();
    }
}
