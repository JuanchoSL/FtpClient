<?php

declare(strict_types=1);

namespace JuanchoSL\FtpClient\Engines;

use JuanchoSL\Exceptions\PreconditionRequiredException;
use JuanchoSL\Exceptions\UnauthorizedException;
use JuanchoSL\FtpClient\Contracts\ClientInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

abstract class AbstractClient implements ClientInterface, LoggerAwareInterface
{

    use LoggerAwareTrait;
    protected $link;
    protected bool $connected = false;
    protected bool $logged = false;

    protected bool $debug = false;

    public function setDebug(bool $debug = false): static
    {
        $this->debug = $debug;
        return $this;
    }

    protected function log(\Stringable|string $message, $log_level, $context = [])
    {
        if (isset($this->logger)) {
            if ($this->debug || $log_level != 'debug') {
                if ($this->debug) {
                    $context['memory'] = memory_get_usage();
                } elseif (array_key_exists('data', $context)) {
                    unset($context['data']);
                }
                $context['Engine'] = (new \ReflectionClass($this))->getShortName();
                $this->logger->log($log_level, $message, $context);
            }
        }
    }

    protected function logCall(string $message, array $context = []):void
    {
        $this->log($message, 'debug', $context);
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function isLogged(): bool
    {
        return $this->logged;
    }

    protected function checkExtension(string ...$extensions): void
    {
        foreach ($extensions as $extension) {
            if (!extension_loaded(\strtolower($extension))) {
                $exception = new PreconditionRequiredException("The {extension} extension is not available");
                $this->log($exception, 'error', [
                    'exception' => $exception,
                    'extension' => strtoupper($extension)
                ]);
                throw $exception;
            }
        }
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

    protected function filterContents(string $dir, bool $files, bool $extended_info = false, ?string $sort = null): array|false
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
    public function listFiles(string $dir = '.', bool $info = false, ?string $sort = null): array|false
    {
        return $this->filterContents($dir, true, $info, $sort);
    }
    public function listDirs(string $dir = '.', bool $info = false, ?string $sort = null): array|false
    {
        return $this->filterContents($dir, false, $info, $sort);
    }

    abstract public function disconnect(): bool;

    function __destruct()
    {
        $this->disconnect();
    }
}