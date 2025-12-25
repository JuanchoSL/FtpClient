<?php declare(strict_types=1);

namespace JuanchoSL\FtpClient\Engines;

use JuanchoSL\Exceptions\DestinationUnreachableException;
use JuanchoSL\Exceptions\ServiceUnavailableException;
use JuanchoSL\Exceptions\UnauthorizedException;
use JuanchoSL\FtpClient\Contracts\ConnectionInterface;
use JuanchoSL\FtpClient\Engines\AbstractClient;

class SFtp extends AbstractClient implements ConnectionInterface
{

    const DEFAULT_PORT = 22;

    protected $conn;
    private string $last_dir = '/';
    private ?string $private_key = null;
    private ?string $public_key = null;
    private string $private_key_password = '';

    public function connect(string $server, int $port = self::DEFAULT_PORT): bool
    {
        $this->server = $server;
        $this->port = $port;

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
        $this->link = @ssh2_connect($server, $port, null);
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

    /**
     * Format of fingerprint, can be SSH2_FINGERPRINT_MD5 or SSH2_FINGERPRINT_SHA1 and associated with SSH2_FINGERPRINT_HEX or SSH2_FINGERPRINT_RAW
     * @link https://www.php.net/manual/es/function.ssh2-fingerprint.php
     * @param int $type SSH2_FINGERPRINT_MD5 or SSH2_FINGERPRINT_SHA1 constant
     * @return string Server fingerprint
     */
    public function getFingerprint(int $flags): string
    {
        if (!$this->isConnected()) {
            $exception = new DestinationUnreachableException("You needs to connect first to server");
            $this->log($exception, 'error', [
                'exception' => $exception,
            ]);
            throw $exception;
        }
        $result = ssh2_fingerprint($this->link, $flags);
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

    public function setCredentials(#[\SensitiveParameter] string $public_key, #[\SensitiveParameter] string $private_key, #[\SensitiveParameter] string $key_password = ''): static
    {
        $this->public_key = $public_key;
        $this->private_key = $private_key;
        $this->private_key_password = $key_password;
        return $this;
    }

    public function login(string $user, #[\SensitiveParameter] string $pass = ''): bool
    {
        $this->user = $user;
        $this->pass = $pass;

        if (isset($this->public_key, $this->private_key)) {
            $this->logged = @ssh2_auth_pubkey_file($this->link, $user, $this->public_key, $this->private_key, $this->private_key_password);
            if (!empty($pass) && !$this->logged) {
                $this->logged = @ssh2_auth_password($this->link, $user, $pass);
                $method = 'certificate+password';
            } else {
                $method = 'certificate';
            }
        } elseif (empty($pass)) {
            $this->logged = @ssh2_auth_none($this->link, $user) !== false;
            $method = 'none';
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
        $this->conn = @ssh2_sftp($this->link);
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
        if ($this->isLogged() && !empty($this->conn) && @ssh2_disconnect($this->conn)) {
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
        //$result = substr(decoct($this->stat($path)['mode']), -4);
        $result = $this->stat($path)['UNIX.mode'];
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $result]);
        return $result;
    }

    public function stat(string $request): array
    {
        $this->checkConnection();
        if ($this->isDir($request)) {
            $paths = $this->listDirContents($request, true);
        } else {
            $paths = [basename($request)];
            $request = dirname($request);
        }
        $results = [];
        foreach ($paths as $path) {
            //$this->logCall("stating {url}", ['url' => $request . DIRECTORY_SEPARATOR . $path]);
            $res = @ssh2_sftp_stat($this->conn, $this->getFullPath($request . '/' . $path));
            if ($res === false) {
                continue;
            }
            $size = $this->isDir($request . '/' . $path) ? 'sizd' : 'size';
            $result = [
                'name' => $path,
                $size => $res['size'],
                'unique' => '',
                'UNIX.mode' => substr(decoct($res['mode']), -4),
                'UNIX.uid' => $res['uid'] ?? $res['UNIX.uid'] ?? '',
                'UNIX.gid' => $res['gid'] ?? $res['UNIX.gid'] ?? '',
                'modify' => date("YmdHis", $res['mtime'])
            ];
            if ($path == '.') {
                $result['type'] = 'cdir';
            } elseif ($path == '..') {
                $result['type'] = 'pdir';
            } else {
                $result['type'] = $this->isDir($request . '/' . $path) ? 'dir' : 'file';
            }
            $results[] = $result;
        }
        if (count($results) == 1) {
            $results = current($results);
        }
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $results]);
        return $results;
    }

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
        if (file_exists($local_file)) {
            $result = ssh2_scp_recv($this->link, $this->getFullPath($remote_file), $local_file);
        } else {
            $data = $this->read($remote_file);
            $result = (file_put_contents($local_file, $data) !== false);
        }

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
        if (!$this->isDir($this->last_dir)) {
            return false;
        }
        if (substr($dir, 0, 1) === '/') {
            $this->last_dir = $dir;
        } else {
            $this->last_dir .= '/' . $dir;
        }
        if (substr($this->last_dir, -3) == '/..') {
            $this->last_dir = substr($this->last_dir, 0, -3);
            return $this->parentDir();
            //$dirs = explode('/', $this->last_dir);
            //$this->last_dir = implode('/', array_slice($dirs, 0, count($dirs) - 2));
        } else if (substr($this->last_dir, -2) == '/.') {
            $this->last_dir = substr($this->last_dir, 0, -2);
        }
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'result' => $this->last_dir]);
        return true;
    }

    public function parentDir(): bool
    {
        $this->checkConnection();
        if (substr_count($this->last_dir, '/') == 1 && strlen($this->last_dir) < 2) {
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

    public function listDirContents(string $dir = '.', bool $with_dots = false): array|false
    {
        $this->checkConnection();
        $result = $contents = @scandir($this->getWrappedPath($dir));
        if (!$with_dots) {
            $result = ($contents !== false) ? array_values(array_diff($contents, array('..', '.'))) : false;
        }
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

    public function __serialize(): array
    {
        return [
            'server' => $this->server,
            'port' => $this->port,
            'user' => $this->user,
            'pass' => $this->pass,
            'public' => $this->public_key,
            'private' => $this->private_key,
            'private_pass' => $this->private_key_password,
        ];
    }

    public function __unserialize(array $con_data): void
    {
        $this->connect($con_data['server'], $con_data['port']);
        if (!empty($con_data['public']) && !empty($con_data['private'])) {
            $this->setCredentials($con_data['public'], $con_data['private'], $con_data['private_pass']);
        }
        $this->login($con_data['user'], $con_data['pass']);
    }

    public function __debugInfo(): array
    {
        $data = $this->__serialize();
        $data['pass'] = '*****';
        $data['private'] = '*****';
        $data['private_pass'] = '*****';
        return $data;
    }
}