<?php

declare(strict_types=1);

namespace JuanchoSL\FtpClient\Engines;

use JuanchoSL\Exceptions\PreconditionRequiredException;
use JuanchoSL\Exceptions\UnauthorizedException;
use JuanchoSL\FtpClient\Contracts\ClientInterface;

abstract class AbstractClient implements ClientInterface
{

    protected $link;
    protected bool $connected = false;
    protected bool $logged = false;

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function isLogged(): bool
    {
        return $this->logged;
    }

    protected function checkConnection(): void
    {
        if (!$this->isConnected()) {
            throw new PreconditionRequiredException("You need to connect first");
        }
        if (!$this->isLogged()) {
            throw new UnauthorizedException("You need to login first");
        }
    }

    protected function filterContents(string $dir, bool $files): array|false
    {
        $contents = $this->listDirContents($dir);
        if ($contents !== false) {
            foreach ($contents as $index => $content) {
                if (($files && $this->isDir($content)) || (!$files && !$this->isDir($content))) {
                    unset($contents[$index]);
                }
            }
            $contents = array_values($contents);
        }
        return $contents;
    }
    public function listFiles(string $dir = '.'): array|false
    {
        return $this->filterContents($dir, true);
    }
    public function listDirs(string $dir = '.'): array|false
    {
        return $this->filterContents($dir, false);
    }

    abstract public function disconnect(): bool;

    function __destruct()
    {
        $this->disconnect();
    }
}