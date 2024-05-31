<?php

declare(strict_types=1);

namespace JuanchoSL\FtpClient\Factories;

use JuanchoSL\FtpClient\Contracts\ConnectionInterface;
use JuanchoSL\FtpClient\Engines\Ftp;
use JuanchoSL\FtpClient\Engines\Ftps;
use JuanchoSL\FtpClient\Engines\SFtp;
use JuanchoSL\FtpClient\Enums\EnginesEnum;

class EngineFactory
{
    public static function getInstance(EnginesEnum $engine): ConnectionInterface
    {
        return match ($engine) {
            EnginesEnum::FTP => new Ftp(),
            EnginesEnum::FTPS => new Ftps(),
            EnginesEnum::SFTP => new SFtp()
        };
    }
}