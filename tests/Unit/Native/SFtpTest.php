<?php

namespace JuanchoSL\FtpClient\Tests\Unit\Native;

use JuanchoSL\FtpClient\Engines\Native\SFtp;
use JuanchoSL\FtpClient\Tests\Common\SFtpCredentials;
use JuanchoSL\FtpClient\Tests\Unit\AbstractFtp;

class SFtpTest extends AbstractFtp
{
    use SFtpCredentials;
    
    protected function getInstance()
    {
        return new SFtp();
    }
}