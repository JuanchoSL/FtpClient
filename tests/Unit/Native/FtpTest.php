<?php

namespace JuanchoSL\FtpClient\Tests\Unit\Native;

use JuanchoSL\FtpClient\Engines\Native\Ftp;
use JuanchoSL\FtpClient\Tests\Common\FtpCredentials;
use JuanchoSL\FtpClient\Tests\Unit\AbstractFtp;

class FtpTest extends AbstractFtp
{
    use FtpCredentials;

    protected function getInstance()
    {
        return new Ftp();
    }

}