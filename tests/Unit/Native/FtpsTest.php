<?php

namespace JuanchoSL\FtpClient\Tests\Unit\Native;

use JuanchoSL\FtpClient\Engines\Native\Ftps;
use JuanchoSL\FtpClient\Tests\Common\FtpsCredentials;
use JuanchoSL\FtpClient\Tests\Unit\AbstractFtp;

class FtpsTest extends AbstractFtp
{
    use FtpsCredentials;

    protected function getInstance()
    {
        return new Ftps();
    }

}