<?php

namespace JuanchoSL\FtpClient\Tests\Unit\Curl;

use JuanchoSL\FtpClient\Engines\Curl\CurlFtp;
use JuanchoSL\FtpClient\Tests\Common\FtpCredentials;
use JuanchoSL\FtpClient\Tests\Unit\AbstractFtp;

class CurlFtpTest extends AbstractFtp
{
    use FtpCredentials;

    protected function getInstance()
    {
        return new CurlFtp();
    }

}