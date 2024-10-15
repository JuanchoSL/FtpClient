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

    protected function filterContents(string $dir, bool $files, bool $extended_info = false, string $sort = null): array|false
    {
        $contents = $this->listDirContents($dir);
        if ($contents !== false) {
            foreach ($contents as $index => $content) {
                if (($files && $this->isDir($content)) || (!$files && !$this->isDir($content))) {
                    unset($contents[$index]);
                } elseif ($extended_info || !is_null($sort)) {
                    $contents[$index] = [
                        'name' => $content,
                        'size' => $this->filesize($content),
                        'mode' => $this->mode($content),
                        'mtime' => $this->lastModified($content)->getTimestamp()
                    ];
                }
            }
            $contents = array_values($contents);
        }

        if (!is_null($sort)) {
            array_multisort(array_column($contents, $sort), SORT_ASC, is_numeric(current($contents)[$sort]) ? SORT_NUMERIC : SORT_STRING, $contents);
            if (!$extended_info) {
                $contents = array_column($contents, 'name');
        }
            /*
            usort($contents, function ($a, $b) {
                return ($a['mtime']->getTimestamp() >= $b['mtime']->getTimestamp()) ? -1 : 1;
                return $a['size'] > $b['size'];
            });
            */
        }
        return $contents;
    }
    public function listFiles(string $dir = '.', bool $info = false, string $sort = null): array|false
    {
        return $this->filterContents($dir, true, $info, $sort);
    }
    public function listDirs(string $dir = '.', bool $info = false, string $sort = null): array|false
    {
        return $this->filterContents($dir, false, $info, $sort);
    }

    abstract public function disconnect(): bool;

    function __destruct()
    {
        $this->disconnect();
    }
}