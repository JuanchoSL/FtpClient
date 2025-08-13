<?php

namespace JuanchoSL\FtpClient\Tests\Functional\Client;

use JuanchoSL\FtpClient\Contracts\ConnectionInterface;
use JuanchoSL\FtpClient\Enums\EnginesEnum;
use JuanchoSL\FtpClient\Factories\EngineFactory;
use JuanchoSL\FtpClient\Tests\Common\FtpsCredentials;

class FtpsTest extends AbstractFtp
{
    use FtpsCredentials;


    protected function getInstance(): ConnectionInterface
    {
        return EngineFactory::getInstance(EnginesEnum::FTPS);
    }
}