<?php declare(strict_types=1);

namespace JuanchoSL\FtpClient\Factories;

use JuanchoSL\FtpClient\Contracts\ClientInterface;
use JuanchoSL\FtpClient\Contracts\ConnectionInterface;
use JuanchoSL\FtpClient\Engines\Native\Ftp;
use JuanchoSL\FtpClient\Engines\Native\Ftps;
use JuanchoSL\FtpClient\Engines\Native\SFtp;
use JuanchoSL\FtpClient\Enums\EnginesEnum;
use JuanchoSL\HttpData\Factories\UriFactory;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerAwareInterface;

class EngineFactory
{
    public static function getInstance(EnginesEnum $engine): ConnectionInterface&ClientInterface&LoggerAwareInterface
    {
        return match ($engine) {
            EnginesEnum::FTP => new Ftp(),
            EnginesEnum::FTPS => new Ftps(),
            EnginesEnum::SFTP => new SFtp()
        };
    }

}