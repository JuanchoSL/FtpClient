<?php

namespace JuanchoSL\FtpClient\Tests\Unit;

use JuanchoSL\FtpClient\Engines\SFtp;
use JuanchoSL\FtpClient\Tests\Common\SFtpAgentCredentials;

class SFtpAgentTest extends AbstractFtp
{
    use SFtpAgentCredentials;
    
    protected function getInstance()
    {
        return new SFtp();
    }
}