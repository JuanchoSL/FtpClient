<?php declare(strict_types=1);

namespace JuanchoSL\FtpClient\Engines\Sockets;

use JuanchoSL\FtpClient\Contracts\ConnectionInterface;
use JuanchoSL\FtpClient\Contracts\DirectoryInterface;
use JuanchoSL\FtpClient\Contracts\FilesInterface;
use JuanchoSL\SocketClient\Factories\SocketClientFactory;

class SocketFtps extends SocketFtp implements ConnectionInterface, FilesInterface, DirectoryInterface
{

    protected array $features = [];

    public function connect(string $server, int $port = self::DEFAULT_PORT): bool
    {
        $this->checkExtension('sockets', 'openssl');
        return parent::connect($server, $port);
    }

    public function login(string $user, #[\SensitiveParameter] string $pass = ''): bool
    {
        $this->logged = false;

        $result = $this->writeChannel("AUTH SSL", true);
        if (str_starts_with($result, "234 ")) {
            $this->link->setBlockingMode(true);
            $this->link->setCrypto(true);
        }
        parent::login($user, $pass);
        return $this->isLogged();
    }

    protected function createSocket(int $port, $ip = null)
    {
        $proto = "ssl";//(!$this->isConnected() || !$this->isLogged() || $this->elevated) ? 'ssl' : 'tcp';
        $ip ??= $this->server;
        $data_channel = (new SocketClientFactory())->createFromUrl("{$proto}://{$ip}:{$port}");
        $data_channel->setLogger($this->logger);
        $data_channel->connect();
        return $data_channel;
    }

}