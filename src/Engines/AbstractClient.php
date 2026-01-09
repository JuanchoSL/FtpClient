<?php declare(strict_types=1);

namespace JuanchoSL\FtpClient\Engines;

use JuanchoSL\DataManipulation\Manipulators\Strings\DateManipulators;
use JuanchoSL\DataManipulation\Manipulators\Strings\StringsManipulators;
use JuanchoSL\Exceptions\PreconditionRequiredException;
use JuanchoSL\Exceptions\UnauthorizedException;
use JuanchoSL\FtpClient\Contracts\ClientInterface;
use JuanchoSL\FtpClient\Contracts\ConnectionInterface;
use JuanchoSL\FtpClient\Engines\Native\Ftp;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

abstract class AbstractClient implements ConnectionInterface, ClientInterface, LoggerAwareInterface
{

    use LoggerAwareTrait;

    protected $link;
    protected bool $connected = false;
    protected bool $logged = false;

    protected bool $debug = false;
    protected string $server;
    protected int $port;
    protected string $user = 'anonymous';
    protected string $pass = '';

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

    protected function logCall(string $message, array $context = []): void
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
        if (true) {

            $contents = $this->stat($dir);
            if ($contents !== false) {
                foreach ($contents as $index => $content) {
                    if (($files && $content['type'] != 'file') || (!$files && $content['type'] != 'dir')) {
                        unset($contents[$index]);
                    }
                }
            }
        } else {
            $contents = $this->listDirContents($dir, false);
            if ($contents !== false) {
                foreach ($contents as $index => $content) {
                    $content = ($this instanceof Ftp) ? $content : $dir . DIRECTORY_SEPARATOR . $content;
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
            }
        }
        $contents = array_values($contents);

        if (!is_null($sort)) {
            array_multisort(array_column($contents, $sort), SORT_ASC, is_numeric(current($contents)[$sort]) ? SORT_NUMERIC : SORT_STRING, $contents);
        }
        if (!$extended_info) {
            $contents = array_column($contents, 'name');
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

    public function mode(string $path): string
    {
        $this->checkConnection();
        $result = $this->stat($path)['UNIX.mode'] ?? '----';
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function isDir(string $path): bool
    {
        /*$filesize = $this->filesize($path);
        $result = '';*/
        $result = $this->stat($path)['type'] !== 'file';
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    abstract public function connect(string $host, int $port = self::DEFAULT_PORT): bool;
    abstract public function login(string $user, #[\SensitiveParameter] string $pass = ''): bool;
    abstract public function disconnect(): bool;
    abstract public function stat(string $path): array;

    function __destruct()
    {
        if ($this->isConnected()) {
            $this->disconnect();
        }
    }

    public function __serialize(): array
    {
        return [
            'server' => $this->server,
            'port' => $this->port,
            'user' => $this->user,
            'pass' => $this->pass,
        ];
    }

    public function __unserialize(array $con_data): void
    {
        $this->connect($con_data['server'], $con_data['port']);
        $this->login($con_data['user'], $con_data['pass']);
    }

    public function __debugInfo(): array
    {
        $data = $this->__serialize();
        $data['pass'] = '*****';
        return $data;
    }

    protected function formatDataCommanLine($result, $path = null)
    {
        foreach ($result as $index => $data) {
            if (empty($data)) {
                unset($result[$index]);
                continue;
            }
            preg_match('/^(\S+)\s+(\d+)\s+(\S+)\s+(\S+)\s+(\d+)\s+(.{12})\s(.+)$/m', $data, $matches);
            if (empty($matches)) {
                unset($result[$index]);
                continue;
            }

            $tmp = [
                'type' => '',
                'modify' => (!empty($matches[6])) ? (new DateManipulators())->fromString($matches[6])->format("YmdHis") : '',
                'UNIX.mode' => '0',
                'UNIX.uid' => $matches[3] ?? '',//(string) $string->substring(17, 21)->trim(),
                'UNIX.gid' => $matches[4] ?? '',//(string) $string->substring(21, 30)->trim(),
                'unique' => '',
                'name' => $matches[7] ?? '',//(string) $string->substring(57)->trim(),
            ];

            $string = new StringsManipulators($matches[1]);

            $umode = $string->substring(1);
            $modes = explode(' ', (string) $umode->chunk(3, ' ')->trim());
            foreach ($modes as $mode) {
                $n = 0;
                foreach (str_split($mode) as $char) {
                    switch ($char) {
                        case '':
                        case '-':
                            $n += 0;
                            break;
                        case 'r':
                            $n += 4;
                            break;
                        case 'w':
                            $n += 2;
                            break;
                        case 'x':
                            $n += 1;
                            break;
                    }
                }
                $tmp['UNIX.mode'] .= "$n";
            }

            if ($tmp['name'] == '.') {
                $tmp['type'] = 'cdir';
            } elseif ($tmp['name'] == '..') {
                $tmp['type'] = 'pdir';
            } elseif ($string->substring(0, 1)->trim() == 'd') {
                $tmp['type'] = 'dir';
            } else {
                $tmp['type'] = 'file';
            }

            $size = ($tmp['type'] == 'file') ? "size" : "sizd";
            $tmp[$size] = $matches[5];

            $result[$index] = $tmp;
        }
        return $result;
    }

    protected function formatDataLine($result, $path = null)
    {
        foreach ($result as $index => $content) {
            if (stripos($content, ";") === false) {
                unset($result[$index]);
                continue;
            }
            $datas = explode(";", $content);
            $sub = [];
            foreach ($datas as $data) {
                if (!str_contains($data, "=")) {
                    $data = "name={$data}";
                }
                list($key, $value) = explode("=", $data, 2);
                $sub[trim($key)] = trim($value);
                if (isset($path) && pathinfo($path, PATHINFO_BASENAME) == trim($value)) {
                    return $sub;
                }
            }
            $result[$index] = $sub;
        }
        return $result;
    }
}