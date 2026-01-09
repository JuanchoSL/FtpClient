<?php declare(strict_types=1);

namespace JuanchoSL\FtpClient\Engines\Sockets;

use JuanchoSL\Exceptions\DestinationUnreachableException;
use JuanchoSL\Exceptions\UnauthorizedException;
use JuanchoSL\FtpClient\Contracts\ConnectionInterface;
use JuanchoSL\FtpClient\Contracts\DirectoryInterface;
use JuanchoSL\FtpClient\Contracts\FilesInterface;
use JuanchoSL\FtpClient\Engines\AbstractClient;
use JuanchoSL\SocketClient\Factories\SocketClientFactory;
use JuanchoSL\Validators\Types\Strings\StringValidation;

class SocketFtp extends AbstractClient implements ConnectionInterface, FilesInterface, DirectoryInterface
{

    const DEFAULT_PORT = 21;

    protected $link;
    protected $data_channel;
    protected bool $pasive = false;
    protected bool $elevated = false;
    public function connect(string $server, int $port = self::DEFAULT_PORT): bool
    {
        $this->server = $server;
        $this->port = $port;

        $this->checkExtension('sockets');
        $this->link = $this->createSocket($this->port, $this->server);
        $this->connected = ($this->link !== false);
        if (!$this->isConnected()) {
            $exception = new DestinationUnreachableException("Can not connect to the desired server");
            $this->log($exception, 'error', [
                'exception' => $exception,
                'credentials' => [
                    'host' => $server,
                    'port' => $port,
                    'metadata' => $this->link->getMetadata(),
                    'transports' => stream_get_transports(),
                    'wrappers' => stream_get_wrappers()
                ]
            ]);
            throw $exception;
        }
        $this->logCall("Connected to: {$this->server}:{$this->port}", [
            'server' => $this->server,
            'port' => $this->port,
            'connected' => intval($this->isConnected()),
        ]);
        $this->readChannel();
        return $this->isConnected();
    }

    public function login(string $user, #[\SensitiveParameter] string $pass = ''): bool
    {
        $this->user = $user;
        $this->pass = $pass;
        $this->logged = false;

        $user = empty($pass) ? 'anonymous' : $user;
        $result = $this->writeChannel(sprintf("USER %s", $this->user), true);
        if (str_starts_with($result, "331 ") || str_starts_with($result, "530 ") OR str_starts_with($result, "220 ")) {
            $result = $this->writeChannel(sprintf("PASS %s", $this->pass), true);
            $this->logged = str_starts_with($result, '230 ');
            $this->elevated = str_contains($result, "elevated");
            if ($this->elevated) {
                $response = $this->writeChannel("PBSZ 0");
                if (str_starts_with($response, '200 ')) {
                    $response = $this->writeChannel("PROT P");
                }
            }
        }
        $this->link->setBlockingMode(false);

        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $this->isLogged()]);
        if (!$this->isLogged()) {
            $exception = new UnauthorizedException("Failed authenticating with the provided credentials");
            $this->log($exception, 'error', [
                'exception' => $exception,
                'credentials' => [
                    'user' => $user,
                    'pass' => $pass,
                ]
            ]);
            throw $exception;
        }
        return $this->isLogged();
    }

    /**
     * Activa o desactiva el modo pasivo en las comunicaciones con el servidor
     * @param boolean $estado Nuevo estado de la comunicación
     * @return boolean Resultado de la operación
     */
    public function pasive(bool $estado = true)
    {
        $this->pasive = $estado;
        return true;

        $this->checkConnection();
        if (!$estado || !empty($this->data_channel)) {
            //$this->data_channel->disconnect();//bloquea
            unset($this->data_channel);
            if (!$estado) {
                return true;
            }
        }

        if (StringValidation::isIpV6($this->server) || in_array($this->server, ['localhost'])) {
            $result = $this->writeChannel("EPSV");
        } else {
            $result = $this->writeChannel("PASV");
        }
        if (str_starts_with($result, '22')) {
            if (str_starts_with($result, '227 ')) {
                preg_match('/(\d+),(\d+),(\d+),(\d+),(\d+),(\d+)/', trim($result, "\r\n"), $matches);
                $port = ($matches[5] * 256) + $matches[6];
                $this->data_channel = $this->createSocket($port, implode('.', [$matches[1], $matches[2], $matches[3], $matches[4]]));
            } elseif (str_starts_with($result, '229 ')) {
                preg_match('/([\d]+)([a-zA-Z0-9 ]+)\(\|\|\|(\d+)\|\)/', trim($result, "\r\n"), $matches);//(|||20020|)
                $port = intval($matches[3]);
                $this->data_channel = $this->createSocket($port);
            }
            $this->data_channel->setBlockingMode(false);
        }
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result ?? true]);
        return ($estado) ? !empty($this->data_channel) : empty($this->data_channel);//str_starts_with($result, '227 ');

    }

    /**
     * Devuelve el identificador del sistema operativo usado por el servidor remoto
     * @return string Sistema operativo remoto
     */
    public function system()
    {
        $this->checkConnection();
        $result = $this->writeChannel("SYST");
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function disconnect(): bool
    {
        if ($this->isConnected() && isset($this->link)) {
            $result = $this->writeChannel("QUIT");
            if (str_starts_with($result, '221')) {
                //$this->pasive(false);
                $this->connected = false;
                $this->logged = false;
                $this->link->disconnect();
                unset($this->link);
                return true;
            }
        }
        return false;
    }

    public function chmod(string $path, int $permissions): bool
    {
        //@TODO
        $this->checkConnection();
        $result = false;//ftp_chmod($this->link, $permissions, $path) !== false;
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function stat(string $dir): array
    {
        $this->checkConnection();
        $list_option = 2;

        $list = $this->writeChannel("FEAT", true);
        if (!str_contains($list, "211 ")) {
            do {
                $line = $this->readChannel();
                if (!empty($line)) {
                    $list .= $line . "\r\n";
                }
            } while (!str_starts_with($line, "211 "));
        }
        if (str_starts_with($list, "211-")) {
            $list = explode("\r\n", $list);
            $list = array_slice($list, 1, -1);
            if (in_array('LIST', $list)) {
                $list_option = 2;
            } elseif (in_array('MLSD', $list)) {
                $list_option = 3;
            }
        }
        $results = [];
        switch ($list_option) {
            case 2:
                //modo stat. TWeb OK
                $cwd = $this->currentDir();
                $this->changeDir($dir);
                $contents = $this->dataConnect(sprintf("LIST"));

                $this->changeDir($cwd);
                if (!empty($contents)) {
                    $result = explode("\r\n", $contents);
                    $results = $this->formatDataCommanLine($result);
                }
                break;

            case 3:
                //modo stat mejorado, con permisos. TWeb OK
                //$cwd = $this->currentDir();
                //$this->changeDir($dir);
                $contents = $this->dataConnect(sprintf("MLSD"));
                //$this->changeDir($cwd);
                if (!empty($contents)) {
                    $result = explode("\r\n", $contents);
                    $results = $this->formatDataLine($result);
                }

                break;
        }
        if (is_iterable($results)) {
            foreach ($results as $result) {
                if (basename($result['name']) == basename($dir) && $result['type'] == 'file') {
                    $results = $result;
                    break;
                }
            }
        }
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $results]);
        return $results ?? [];
    }
    /*
        public function isDir(string $path): bool
        {
            $filesize = $this->filesize($path);
            $result = $filesize < 0;
            $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'filesize' => $filesize, 'result' => $result]);
            return $result;
        }
    */
    public function currentDir(): string|false
    {
        $this->checkConnection();
        $result = $this->writeChannel("PWD");
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        preg_match("/\d+\s\"(.+)\"/", $result, $data);
        return $data[1] ?? false;
    }

    public function listDirContents(string $dir = '.', bool $with_dots = false): array|false
    {
        $this->checkConnection();
        $result = $this->dataConnect(sprintf("NLST %s", ($dir == '.') ? $this->currentDir() : $dir));
        if ($result === false) {
            $result = $this->stat($dir);
            if (is_array($result) && !empty($result)) {
                if (is_array(current($result)) && array_key_exists('name', current($result))) {
                    $result = array_column($result, 'name');
                }
            }
        } elseif (!empty($result) && !str_starts_with($result, "226-")) {
            $result = explode("\r\n", $result);
        } else {
            $result = [];
        }
        if (!empty($result)) {
            $result = array_filter($result);
            if (!$with_dots) {
                $result = (!empty($result)) ? array_values(array_diff($result, array('..', '.'))) : false;
            }
        }
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function changeDir($dir): bool
    {
        if ($dir != '.') {
            $dir = (empty($dir)) ? $this->currentDir() : $dir;
            $this->checkConnection();
            $result = $this->writeChannel(sprintf("CWD %s", $dir));
            $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
            return str_contains($result, '250 ');
        }
        return true;
    }

    public function parentDir(): bool
    {
        $this->checkConnection();
        $result = $this->writeChannel("CDUP");
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return str_starts_with($result, '250 OK');
    }

    public function createDir(string $dir_name): bool
    {
        $this->checkConnection();
        $result = $this->writeChannel(sprintf("MKD %s", $dir_name));
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return str_starts_with($result, "257 ");
    }

    public function deleteDir(string $path_name): bool
    {
        $this->checkConnection();
        $result = $this->writeChannel(sprintf("RMD %s", $path_name));
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return str_starts_with($result, "250 ");
    }

    public function download(string $remote_file, string $local_file): bool
    {
        $this->checkConnection();
        $result = $this->read($remote_file);
        $result = file_put_contents($local_file, $result);
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return is_integer($result);
    }

    public function read(string $remote_file): string|false
    {
        $this->checkConnection();
        $result = $this->dataConnect(sprintf("RETR %s", $remote_file));
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result !== false]);
        return $result;
    }

    public function write(string $remote_file, string $contents): bool
    {
        $this->checkConnection();
        $result = $this->dataConnect(sprintf("STOR %s", $remote_file), $contents);
        //$this->pasive(false);
        $this->data_channel->disconnect();
        $this->link->setBlockingMode(true);
        $result = $this->readChannel();
        $this->link->setBlockingMode(false);
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return str_starts_with($result, "226");
    }

    public function upload(string $local_file, string $remote_file): bool
    {
        $this->checkConnection();
        $result = $this->write($remote_file, file_get_contents($local_file));
        //$result = $this->writeChannel(sprintf("MFMT %d %s", date("YmdHis"), $remote_file));
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function rename(string $old_name, string $new_name): bool
    {
        $this->checkConnection();
        $result = $this->writeChannel(sprintf("RNFR %s", $old_name), true);
        if (str_starts_with($result, "350 ")) {
            $result = $this->writeChannel(sprintf("RNTO %s", $new_name), true);
        }
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return str_starts_with($result, "250 ");
    }

    public function delete(string $path_name): bool
    {
        $this->checkConnection();
        $result = $this->writeChannel(sprintf("DELE %s", $path_name));
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return str_starts_with($result, "250 ");
    }

    public function filesize($filepath): int
    {
        $this->checkConnection();
        $result = $this->writeChannel("TYPE I");
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        if (!str_starts_with($result, "200 ")) {
            return 0;
        }
        $result = $this->writeChannel(sprintf("SIZE %s", $filepath));
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return intval(substr($result, 4));
    }

    public function lastModified(string $filepath): ?\DateTimeInterface
    {
        $this->checkConnection();
        $stat = $this->stat($filepath);
        if (!empty($stat) && array_key_exists('modify', $stat)) {
            $result = date_create_immutable_from_format("YmdHis", $stat['modify']);
        } else {
            $result = null;
            $time = $this->writeChannel(sprintf("MDTM %s", $filepath));
            if (str_starts_with($time, "213 ")) {
                $result = date_create_immutable_from_format("YmdHis", substr($time, 4));
            }
        }
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function writeChannel($command, bool $wait = true): string
    {
        $this->logCall('COMMAND: {message}', ['message' => $command]);
        $this->link->write($command . "\r\n");
        if ($wait) {
            $this->link->setBlockingMode($wait);
            $response = $this->readChannel();
            $this->link->setBlockingMode(false);
            if ($this->isLogged()) {
            }
            $this->logCall('response', [
                'data' => $response,
                'metadata' => $this->link->getMetadata(),
                'transports' => stream_get_transports(),
                'wrappers' => stream_get_wrappers()
            ]);
        }
        return $response ?? '';
    }
    public function readChannel()
    {
        $buffer = $this->link->read();
        $this->logCall($buffer, ['size' => strlen($buffer)]);
        return $buffer;
    }

    protected function dataConnect($command, ?string $data = null): string|false
    {
        //$result = $this->pasive(true);

        if ($this->pasive) {
            if (StringValidation::isIpV6($this->server) || in_array($this->server, ['localhost'])) {
                $result = $this->writeChannel("EPSV");
            } else {
                $result = $this->writeChannel("PASV");
            }
            if (str_starts_with($result, '22')) {
                if (str_starts_with($result, '227 ')) {
                    preg_match('/(\d+),(\d+),(\d+),(\d+),(\d+),(\d+)/', trim($result, "\r\n"), $matches);
                    $port = ($matches[5] * 256) + $matches[6];
                    $this->data_channel = $this->createSocket($port, implode('.', [$matches[1], $matches[2], $matches[3], $matches[4]]));
                } elseif (str_starts_with($result, '229 ')) {
                    preg_match('/([\d]+)([a-zA-Z0-9 ]+)\(\|\|\|(\d+)\|\)/', trim($result, "\r\n"), $matches);//(|||20020|)
                    $port = intval($matches[3]);
                    $this->data_channel = $this->createSocket($port);
                }
                $this->data_channel->setBlockingMode(false);
            }
        }
        $response = $this->writeChannel($command);
        if (str_starts_with($response, '521 ')) {
            $response = $this->writeChannel("PBSZ 0");
            if (str_starts_with($response, '200 ')) {
                $response = $this->writeChannel("PROT P");
                if (str_starts_with($response, '200 ')) {
                    $response = $this->writeChannel($command);
                }
            }
        }
        if (str_starts_with($response, '150 ')) {
            if ($this->elevated) {
                $parent = $this->link;
                $this->data_channel->setBlockingMode(true);
                $this->data_channel->setCrypto(true, $parent());
            }
            if (!empty($data)) {
                $this->data_channel->write($data);
            } else {
                $this->data_channel->setBlockingMode(true);
                $buffer = $this->data_channel->read();
                $buffer = (empty($buffer)) ? false : $buffer;
                if (!str_contains($response, '226')) {
                    $this->link->setBlockingMode(true);
                    $response = $this->readChannel();
                    $this->link->setBlockingMode(false);
                }
                if (str_contains($response, '226') && empty($buffer)) {
                    $buffer = $this->data_channel->read();
                } else {
                }
                $this->data_channel->setBlockingMode(false);
                $this->data_channel->write("");
            }
        }
        //$this->pasive(false);
        return $buffer ?? false;
    }

    protected function createSocket(int $port, $ip = null)
    {
        $ip ??= $this->server;
        $data_channel = (new SocketClientFactory)->createFromUrl("tcp://{$ip}:{$port}");
        $data_channel->setLogger($this->logger);
        $data_channel->connect();
        return $data_channel;
    }
}