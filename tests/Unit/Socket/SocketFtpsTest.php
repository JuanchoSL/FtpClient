<?php

namespace JuanchoSL\FtpClient\Tests\Unit\Socket;

use JuanchoSL\FtpClient\Engines\Sockets\SocketFtps;
use JuanchoSL\FtpClient\Tests\Common\FtpsCredentials;
use JuanchoSL\FtpClient\Tests\Unit\AbstractFtp;

class SocketFtpsTest extends AbstractFtp
{
    use FtpsCredentials;

    protected function getInstance()
    {
        return new SocketFtps();
    }

}