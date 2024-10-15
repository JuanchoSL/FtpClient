<?php

namespace JuanchoSL\FtpClient\Tests\Functional\LinuxClient;

use JuanchoSL\FtpClient\Contracts\ConnectionInterface;
use JuanchoSL\FtpClient\Enums\EnginesEnum;
use JuanchoSL\FtpClient\Factories\EngineFactory;
use JuanchoSL\FtpClient\Tests\Common\FtpCredentials;

class FtpsTest extends AbstractFtp
{
    use FtpCredentials;


    protected function getInstance():ConnectionInterface
    {
        return EngineFactory::getInstance(EnginesEnum::FTPS);
    }
}