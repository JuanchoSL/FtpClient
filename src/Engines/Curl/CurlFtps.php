<?php declare(strict_types=1);

namespace JuanchoSL\FtpClient\Engines\Curl;

use JuanchoSL\FtpClient\Contracts\ConnectionInterface;

class CurlFtps extends CurlFtp implements ConnectionInterface
{

    protected bool $ssl = true;

}