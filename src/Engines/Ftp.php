<?php declare(strict_types=1);

namespace JuanchoSL\FtpClient\Engines;

use JuanchoSL\Exceptions\DestinationUnreachableException;
use JuanchoSL\Exceptions\UnauthorizedException;
use JuanchoSL\FtpClient\Contracts\ConnectionInterface;
use JuanchoSL\FtpClient\Engines\AbstractClient;

class Ftp extends AbstractClient implements ConnectionInterface
{

    const DEFAULT_PORT = 21;

    public function connect(string $server, int $port = self::DEFAULT_PORT): bool
    {
        $this->server = $server;
        $this->port = $port;

        $this->checkExtension('ftp');
        $this->link = ftp_connect($server, $port);
        $this->connected = ($this->link !== false);
        if (!$this->isConnected()) {
            $exception = new DestinationUnreachableException("Can not connect to the desired server");
            $this->log($exception, 'error', [
                'exception' => $exception,
                'credentials' => [
                    'host' => $server,
                    'port' => $port,
                ]
            ]);
            throw $exception;
        }
        return $this->isConnected();
    }

    public function login(string $user, #[\SensitiveParameter] string $pass = ''): bool
    {
        $this->user = $user;
        $this->pass = $pass;

        $user = empty($pass) ? 'anonymous' : $user;
        $this->logged = ftp_login($this->link, $user, $pass);
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
    public function pasive(bool $estado = true): bool
    {
        $this->checkConnection();
        ftp_set_option($this->link, FTP_USEPASVADDRESS, $estado);
        $result = ftp_pasv($this->link, $estado);
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    /**
     * Devuelve el identificador del sistema operativo usado por el servidor remoto
     * @return string Sistema operativo remoto
     */
    public function system()
    {
        $this->checkConnection();
        $result = ftp_systype($this->link);
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function disconnect(): bool
    {
        if ($this->isConnected() && @ftp_close($this->link)) {
            $this->connected = false;
            $this->logged = false;
            unset($this->link);
            return true;
        }
        return false;
    }

    public function chmod(string $path, int $permissions): bool
    {
        $this->checkConnection();
        $result = ftp_chmod($this->link, $permissions, $path) !== false;
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function mode(string $path): string
    {
        $this->checkConnection();
        $result = $this->stat($path)['UNIX.mode'] ?? '----';
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }
    public function stat(string $path): array
    {
        $this->checkConnection();
        $results = ftp_mlsd($this->link, $path);
        if (empty($results)) {
            $results = ftp_rawlist($this->link, $path);
            $results = $this->formatDataCommanLine($results);
        }
        if (is_iterable($results)) {
            foreach ($results as $result) {
                if (basename($result['name']) == basename($path) && $result['type'] == 'file') {
                    $results = $result;
                    break;
                }
            }
        }

        /*
        if (empty($stat)) {
            $result = [];
        } else {
            if (false && count($stat) == 1) {
                $result = current($stat);
            } else {
                $result = $stat;
            }
        }
        */
        //$result = (empty($stat)) ? [] : current($stat);
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $results]);
        return empty($results) ? [] : $results;
    }

    public function isDir(string $path): bool
    {
        $filesize = $this->filesize($path);
        $result = $filesize < 0;
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'filesize' => $filesize, 'result' => $result]);
        return $result;
    }

    public function currentDir(): string|false
    {
        $this->checkConnection();
        $result = ftp_pwd($this->link);
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function listDirContents(string $dir = '.', bool $with_dots = false): array|false
    {
        $this->checkConnection();
        $result = $contents = ftp_nlist($this->link, $dir);
        if (empty($result)) {
            $result = $this->stat($dir);
            if (is_array($result) && !empty($result)) {
                if (is_array(current($result)) && array_key_exists('name', current($result))) {
                    $result = array_column($result, 'name');
                }
            }
        }
        if (!$with_dots) {
            $result = ($contents !== false) ? array_values(array_diff($contents, array('..', '.'))) : false;
        }
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function changeDir($dir): bool
    {
        $this->checkConnection();
        $result = @ftp_chdir($this->link, $dir);
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function parentDir(): bool
    {
        $this->checkConnection();
        $result = ftp_cdup($this->link);
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function createDir(string $dir_name): bool
    {
        $this->checkConnection();
        $result = ftp_mkdir($this->link, $dir_name) !== false;
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function deleteDir(string $path_name): bool
    {
        $this->checkConnection();
        $result = @ftp_rmdir($this->link, $path_name);
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function download(string $remote_file, string $local_file): bool
    {
        $this->checkConnection();
        $result = ftp_get($this->link, $local_file, $remote_file, FTP_BINARY);
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function read(string $remote_file): string|false
    {
        $this->checkConnection();
        $tempHandle = fopen('php://temp', 'r+');
        if ($tempHandle) {
            if (@ftp_fget($this->link, $tempHandle, $remote_file, FTP_ASCII, 0)) {
                rewind($tempHandle);
                $contents = stream_get_contents($tempHandle);
            }
            fclose($tempHandle);
        }
        $result = $contents ?? false;
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result !== false]);
        return $result;
    }

    public function write(string $remote_file, string $contents): bool
    {
        $this->checkConnection();
        if (is_file($contents) && file_exists($contents)) {
            $result = @ftp_append($this->link, $remote_file, $contents, FTP_ASCII);
        } else {
            $tempHandle = fopen('php://temp', 'a+');
            if ($tempHandle) {
                fwrite($tempHandle, $contents);
                rewind($tempHandle);
                $result = @ftp_fput($this->link, $remote_file, $tempHandle, FTP_ASCII, 0);
                fclose($tempHandle);
            }
        }
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result ?? false;
    }

    public function upload(string $local_file, string $remote_file): bool
    {
        $this->checkConnection();
        $result = ftp_put($this->link, $remote_file, $local_file, FTP_BINARY);
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function rename(string $old_name, string $new_name): bool
    {
        $this->checkConnection();
        $result = ftp_rename($this->link, $old_name, $new_name);
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function delete(string $path_name): bool
    {
        $this->checkConnection();
        $result = ftp_delete($this->link, $path_name);
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function filesize($filepath): int
    {
        $this->checkConnection();
        $result = ftp_size($this->link, $filepath);
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function lastModified(string $filepath): ?\DateTimeInterface
    {
        $this->checkConnection();
        $stat = $this->stat($filepath);
        if (!empty($stat) && array_key_exists('modify', $stat)) {
            $result = date_create_immutable_from_format("YmdHis", $stat['modify']);
        } else {
            $result = null;
            $time = ftp_mdtm($this->link, $filepath);
            if ($time >= 0) {
                $datetime = new \DateTimeImmutable;
                $result = $datetime->setTimestamp($time);
            }
        }
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }
}