<?php

declare(strict_types=1);

namespace JuanchoSL\FtpClient\Engines;

use JuanchoSL\Exceptions\DestinationUnreachableException;
use JuanchoSL\Exceptions\PreconditionRequiredException;
use JuanchoSL\Exceptions\UnauthorizedException;
use JuanchoSL\FtpClient\Contracts\ConnectionInterface;
use JuanchoSL\FtpClient\Engines\AbstractClient;

class Ftp extends AbstractClient implements ConnectionInterface
{

    public function connect(string $server, int $port = 21): bool
    {
        if (!extension_loaded('ftp')) {
            throw new PreconditionRequiredException("The FTP extension is not available");
        }
        $this->link = ftp_connect($server, $port);
        $this->connected = ($this->link !== false);
        if(!$this->isConnected()){
            throw new DestinationUnreachableException("Can not connect to the desired service");
        }
        return $this->isConnected();
    }

    public function login(string $user, string $pass): bool
    {
        $this->logged = ftp_login($this->link, $user, $pass);
        if (!$this->isLogged()) {
            throw new UnauthorizedException("Failed authenticating with the provided credentials");
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
        return ftp_pasv($this->link, $estado);
    }

    /**
     * Devuelve el identificador del sistema operativo usado por el servidor remoto
     * @return string Sistema operativo remoto
     */
    public function system()
    {
        $this->checkConnection();
        return ftp_systype($this->link);
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
        return ftp_chmod($this->link, $permissions, $path) !== false;
    }

    public function mode(string $path): string
    {
        $this->checkConnection();
        return $this->stat($path)['UNIX.mode'] ?? '----';
    }
    public function stat(string $path): array
    {
        $this->checkConnection();
        //return ftp_mlsd($this->link, $path);
        $stat = ftp_mlsd($this->link, $path);
        if (empty($stat)) {
            return [];
        }
        return current($stat);
    }

    public function isDir(string $path): bool
    {
        return $this->filesize($path) < 0;
    }

    public function currentDir(): string|false
    {
        $this->checkConnection();
        return ftp_pwd($this->link);
    }

    public function listDirContents(string $dir = '.'): array|false
    {
        $this->checkConnection();
        $contents = ftp_nlist($this->link, $dir);
        return ($contents !== false) ? array_values(array_diff($contents, array('..', '.'))) : false;
    }

    public function changeDir($dir): bool
    {
        $this->checkConnection();
        return @ftp_chdir($this->link, $dir);
    }

    public function parentDir(): bool
    {
        $this->checkConnection();
        return ftp_cdup($this->link);
    }

    public function createDir(string $dir_name): bool
    {
        $this->checkConnection();
        return ftp_mkdir($this->link, $dir_name) !== false;
    }

    public function deleteDir(string $path_name): bool
    {
        $this->checkConnection();
        return @ftp_rmdir($this->link, $path_name);
    }

    public function download(string $remote_file, string $local_file): bool
    {
        $this->checkConnection();
        return ftp_get($this->link, $local_file, $remote_file, FTP_BINARY);
    }

    public function read(string $remote_file): string|false
    {
        $this->checkConnection();
        $tempHandle = fopen('php://temp', 'r+');
        //Get file from FTP:
        if ($tempHandle) {
            if (@ftp_fget($this->link, $tempHandle, $remote_file, FTP_ASCII, 0)) {
                rewind($tempHandle);
                $contents = stream_get_contents($tempHandle);
            }
            fclose($tempHandle);
        }
        return $contents ?? false;
    }

    public function write(string $remote_file, string $contents): bool
    {
        $this->checkConnection();
        $tempHandle = fopen('php://temp', 'r+');
        if ($tempHandle) {
            fwrite($tempHandle, $contents);
            rewind($tempHandle);
            $result = @ftp_fput($this->link, $remote_file, $tempHandle, FTP_ASCII, 0);
            fclose($tempHandle);
        }
        return $result ?? false;
    }

    public function upload(string $local_file, string $remote_file): bool
    {
        $this->checkConnection();
        return ftp_put($this->link, $remote_file, $local_file, FTP_BINARY);
    }

    public function rename(string $old_name, string $new_name): bool
    {
        $this->checkConnection();
        return ftp_rename($this->link, $old_name, $new_name);
    }

    public function delete(string $path_name): bool
    {
        $this->checkConnection();
        return ftp_delete($this->link, $path_name);
    }

    public function filesize($filepath): int
    {
        $this->checkConnection();
        return ftp_size($this->link, $filepath);
    }

    public function lastModified(string $filepath): ?\DateTimeInterface
    {
        $this->checkConnection();
        $stat = $this->stat($filepath);
        if (!empty($stat) && array_key_exists('modify', $stat)) {
            return date_create_immutable_from_format("YmdHis", $stat['modify']);
        } else {
            $time = ftp_mdtm($this->link, $filepath);
            if ($time < 0) {
                return null;
            }
            $datetime = new \DateTimeImmutable;
            return $datetime->setTimestamp($time);
        }
    }
}