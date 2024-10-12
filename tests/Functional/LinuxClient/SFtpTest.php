<?php

namespace JuanchoSL\FtpClient\Tests\Functional\LinuxClient;

use JuanchoSL\FtpClient\Contracts\ConnectionInterface;
use JuanchoSL\FtpClient\Enums\EnginesEnum;
use JuanchoSL\FtpClient\Factories\EngineFactory;
use JuanchoSL\FtpClient\Tests\Common\SFtpCredentials;

class SFtpTest extends AbstractFtp
{
    use SFtpCredentials;
    
    protected function getInstance():ConnectionInterface
    {
        return EngineFactory::getInstance(EnginesEnum::SFTP);
    }
}