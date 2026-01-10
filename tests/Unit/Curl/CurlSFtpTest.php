<?php

namespace JuanchoSL\FtpClient\Tests\Unit\Curl;

use JuanchoSL\FtpClient\Engines\Curl\CurlSFtp;
use JuanchoSL\FtpClient\Tests\Common\SFtpCredentials;
use JuanchoSL\FtpClient\Tests\Unit\AbstractFtp;

class CurlSFtpTest extends AbstractFtp
{
    use SFtpCredentials;

    protected function getInstance()
    {
        return new CurlSFtp();
    }

}