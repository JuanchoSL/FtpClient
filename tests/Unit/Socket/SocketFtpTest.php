<?php

namespace JuanchoSL\FtpClient\Tests\Unit\Socket;

use JuanchoSL\FtpClient\Engines\Sockets\SocketFtp;
use JuanchoSL\FtpClient\Tests\Common\FtpCredentials;
use JuanchoSL\FtpClient\Tests\Unit\AbstractFtp;

class SocketFtpTest extends AbstractFtp
{
    use FtpCredentials;

    protected function getInstance()
    {
        return new SocketFtp();
    }

}