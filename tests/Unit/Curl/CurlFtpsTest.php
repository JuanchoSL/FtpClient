<?php

namespace JuanchoSL\FtpClient\Tests\Unit\Curl;

use JuanchoSL\FtpClient\Engines\Curl\CurlFtps;
use JuanchoSL\FtpClient\Tests\Common\FtpsCredentials;
use JuanchoSL\FtpClient\Tests\Unit\AbstractFtp;

class CurlFtpsTest extends AbstractFtp
{
    use FtpsCredentials;

    protected function getInstance()
    {
        return new CurlFtps();
    }

}