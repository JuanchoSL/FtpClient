<?php

namespace JuanchoSL\FtpClient\Tests\Unit;

use JuanchoSL\FtpClient\Engines\Ftp;
use JuanchoSL\FtpClient\Tests\Common\FtpCredentials;

class FtpTest extends AbstractFtp
{
    use FtpCredentials;

    protected function getInstance()
    {
        return new Ftp();
    }
}