<?php

namespace JuanchoSL\FtpClient\Tests\Unit;

use JuanchoSL\FtpClient\Engines\SFtp;
use JuanchoSL\FtpClient\Tests\Common\SFtpCredentials;

class SFtpTest extends AbstractFtp
{
    use SFtpCredentials;
    
    protected function getInstance()
    {
        return new SFtp();
    }
}