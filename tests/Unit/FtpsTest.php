<?php

namespace JuanchoSL\FtpClient\Tests\Unit;

use JuanchoSL\FtpClient\Engines\Ftps;
use JuanchoSL\FtpClient\Tests\Common\FtpCredentials;

class FtpsTest extends AbstractFtp
{
    use FtpCredentials;

    protected function getInstance()
    {
        return new Ftps();
    }
}