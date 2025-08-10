<?php

namespace JuanchoSL\FtpClient\Tests\Unit;

use JuanchoSL\FtpClient\Engines\Ftps;
use JuanchoSL\FtpClient\Tests\Common\FtpsCredentials;

class FtpsTest extends AbstractFtp
{
    use FtpsCredentials;

    protected function getInstance()
    {
        return new Ftps();
    }

}