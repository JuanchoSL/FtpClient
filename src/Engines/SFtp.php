<?php

declare(strict_types=1);

namespace JuanchoSL\FtpClient\Engines;

use JuanchoSL\Exceptions\DestinationUnreachableException;
use JuanchoSL\Exceptions\ServiceUnavailableException;
use JuanchoSL\Exceptions\UnauthorizedException;
use JuanchoSL\FtpClient\Contracts\ConnectionInterface;
use JuanchoSL\FtpClient\Engines\AbstractClient;

class SFtp extends AbstractClient implements ConnectionInterface
{

    const DEFAULT_PORT = 22;
    
    private $conn;
    private string $last_dir = '/';

    public function connect(string $server, int $port = self::DEFAULT_PORT): bool
    {
        $this->checkExtension('ssh2');
        $methods = array(
            'kex' => 'diffie-hellman-group-exchange-sha256,diffie-hellman-group1-sha1,diffie-hellman-group14-sha1,diffie-hellman-group-exchange-sha1',
            'hostkey' => 'ssh-rsa',
            'client_to_server' => array(
                'crypt' => 'aes256-ctr',
                'mac' => 'hmac-sha1',
                'comp' => 'none'
            ),
            'server_to_client' => array(
                'crypt' => 'aes256-ctr',
                'mac' => 'hmac-sha1',
                'comp' => 'none'
            )
        );
        $callbacks = array('disconnect' => 'my_ssh_disconnect');
        $this->link = ssh2_connect($server, $port, null);
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

    public function getFingerprint(): string
    {
        $result = ssh2_fingerprint($this->link, SSH2_FINGERPRINT_MD5);
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    /**
     * @return array<string,string|array<string,string>>
     */
    public function getNegotiation(): array
    {
        $result = ssh2_methods_negotiated($this->link);
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function login(string $user, #[\SensitiveParameter] string $pass): bool
    {
        if (empty($pass)) {
            $this->logged = ssh2_auth_none($this->link, $user) !== false;
            $method = 'none';
        } elseif (is_file($pass) && file_exists($pass)) {
            $this->logged = ssh2_auth_pubkey_file($this->link, $user, $pass, str_replace('.pub', '', $pass));
            $method = 'certificate';
        } else {
            $this->logged = @ssh2_auth_password($this->link, $user, $pass);
            $method = 'password';
        }
        if (!$this->isLogged()) {
            $exception = new UnauthorizedException("Failed authenticating with the provided credentials");
            $this->log($exception, 'error', [
                'exception' => $exception,
                'credentials' => [
                    'user' => $user,
                    'pass' => $pass,
                    'method' => $method
                ]
            ]);
            throw $exception;
        }
        $this->conn = ssh2_sftp($this->link);
        if ($this->conn === false) {
            $exception = new ServiceUnavailableException("Can not initialize the sftp subsystem");
            $this->log($exception, 'error', [
                'exception' => $exception,
            ]);
            throw $exception;
        }
        return $this->isLogged();
    }

    public function disconnect(): bool
    {
        //$this->exec('echo "EXITING" && exit;');
        if ($this->isLogged() && ssh2_disconnect($this->conn)) {
            unset($this->conn);
            $this->logged = false;
            if ($this->isConnected() && ssh2_disconnect($this->link)) {
                $this->connected = false;
                unset($this->link);
                return true;
            }
        }
        return false;
    }

    public function chmod(string $path, int $permissions): bool
    {
        $this->checkConnection();
        $result = ssh2_sftp_chmod($this->conn, $this->getFullPath($path), $permissions) !== false;
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function mode(string $path): string
    {
        $this->checkConnection();
        $result = substr(decoct($this->stat($path)['mode']), -4);
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function stat(string $path): array
    {
        $this->checkConnection();
        $result = ssh2_sftp_stat($this->conn, $this->getFullPath($path));
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }
    /*
    public function exec($command)
    {
        if (empty($this->link) || !$this->isLogged()) {
            return false;
        }
        
        $stream = ssh2_exec($this->link, $command);
        $stream_error = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
        stream_set_blocking($stream_error, true);
        stream_set_blocking($stream, true);
        $output = stream_get_contents($stream);
        $error = stream_get_contents($stream_error);
        fclose($stream);
        fclose($stream_error);
        return $output;
    }
    */
    public function upload(string $local_file, string $remote_file): bool
    {
        $this->checkConnection();
        $result = ssh2_scp_send($this->link, $local_file, $this->getFullPath($remote_file));
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function download(string $remote_file, string $local_file): bool
    {
        $this->checkConnection();
        $result = ssh2_scp_recv($this->link, $this->getFullPath($remote_file), $local_file);
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function read(string $remote_file): string|false
    {
        $this->checkConnection();
        $stream = @fopen($this->getWrappedPath($remote_file), 'r');
        if (!$stream) {
            return false;
        }
        $contents = stream_get_contents($stream);
        @fclose($stream);
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $contents !== false]);
        return $contents;
    }

    public function write(string $remote_file, string $contents): bool
    {
        $this->checkConnection();
        if (is_file($contents) && file_exists($contents)) {
            $contents = file_get_contents($contents);
        }
        $tempHandle = @fopen($this->getWrappedPath($remote_file), 'a+');
        if (!$tempHandle) {
            return false;
        }
        $writed = fwrite($tempHandle, $contents);
        fclose($tempHandle);
        $result = $writed !== false;
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function delete(string $file): bool
    {
        $this->checkConnection();
        $result = ssh2_sftp_unlink($this->conn, $this->getFullPath($file));
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function rename(string $original_dir, $new_dir): bool
    {
        $this->checkConnection();
        $result = ssh2_sftp_rename($this->conn, $this->getFullPath($original_dir), $this->getFullPath($new_dir));
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function isDir(string $file): bool
    {
        $this->checkConnection();
        $result = is_dir($this->getWrappedPath($file));
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }
    public function filesize(string $file): int
    {
        $this->checkConnection();
        $size = @filesize($this->getWrappedPath($file));
        $result = (!$size) ? -1 : $size;
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function lastModified(string $filepath): ?\DateTimeInterface
    {
        $this->checkConnection();
        $time = filemtime($this->getWrappedPath($filepath));
        if (!$time) {
            return null;
        }
        $datetime = new \DateTimeImmutable;
        $result = $datetime->setTimestamp($time);
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'time' => $time, 'result' => $result]);
        return $result;
    }

    public function createDir(string $dir): bool
    {
        $this->checkConnection();
        $result = ssh2_sftp_mkdir($this->conn, $dir);
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function deleteDir(string $dir): bool
    {
        $this->checkConnection();
        $result = ssh2_sftp_rmdir($this->conn, $this->getFullPath($dir));
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function changeDir(string $dir): bool
    {
        $this->checkConnection();
        if (!$this->isDir($dir)) {
            return false;
        }
        if (substr($dir, 0, 1) === '/') {
            $this->last_dir = $dir;
        } else {
            $this->last_dir .= '/' . $dir;
        }
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $this->last_dir]);
        return true;
    }

    public function parentDir(): bool
    {
        $this->checkConnection();
        if (substr_count($this->last_dir, '/') == 1) {
            return false;
        }
        $last_dir = substr($this->last_dir, 0, strrpos($this->last_dir, '/'));
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'before' => $this->last_dir, 'after' => $last_dir]);
        $this->last_dir = $last_dir;
        return true;
    }

    public function currentDir(): string|false
    {
        $this->checkConnection();
        $result = $this->last_dir;
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function listDirContents(string $dir = '.'): array|false
    {
        $this->checkConnection();
        $contents = scandir($this->getWrappedPath($dir));
        $result = ($contents !== false) ? array_values(array_diff($contents, array('..', '.'))) : false;
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    protected function getWrappedPath(string $path): string
    {
        return $this->getWrapper() . $this->getFullPath($path);
    }

    protected function getWrapper(): string
    {
        return "ssh2.sftp://" . intval($this->conn);
    }

    protected function getFullPath(string $path): string
    {
        if (substr($path, 0, 1) != '/') {
            $path = str_replace('//', '/', $this->last_dir . '/' . $path);
        }
        return $path;
    }
}