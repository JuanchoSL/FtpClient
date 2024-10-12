<?php

declare(strict_types=1);

namespace JuanchoSL\FtpClient\Engines;

use JuanchoSL\Exceptions\DestinationUnreachableException;
use JuanchoSL\FtpClient\Contracts\ConnectionInterface;
use JuanchoSL\FtpClient\Engines\AbstractClient;

class SFtp extends AbstractClient implements ConnectionInterface
{

    private $conn;
    private string $last_dir = '/';

    public function connect(string $server, int $port = 22): bool
    {
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
        if (!($this->link = ssh2_connect($server, $port, null))) {
            throw new DestinationUnreachableException("Cannot connect to server");
        }
        //$fingerprint = ssh2_fingerprint($this->link, SSH2_FINGERPRINT_MD5 | SSH2_FINGERPRINT_HEX);
        return $this->connected = ($this->link !== false);
    }
    public function getFingerprint(): string
    {
        return ssh2_fingerprint($this->link, SSH2_FINGERPRINT_MD5);
    }
    /**
     * @return array<string,string|array<string,string>>
     */
    public function getNegotiation(): array
    {
        return ssh2_methods_negotiated($this->link);
    }

    public function login(string $user, string $pass): bool
    {
        if (empty($pass)) {
            $this->logged = ssh2_auth_none($this->link, $user) !== false;
        } elseif (is_file($pass) && file_exists($pass)) {
            $this->logged = ssh2_auth_pubkey_file($this->link, $user, $pass, str_replace('.pub', '', $pass));
        } else {
            $this->logged = @ssh2_auth_password($this->link, $user, $pass);
            /*            
                        $pkey = ssh2_publickey_init($this->link);
                        $list = ssh2_publickey_list($pkey);
                        foreach ($list as $key) {
                            echo "Key: {$key['name']}\n";
                            echo "Blob: " . chunk_split(base64_encode($key['blob']), 40, "\n") . "\n";
                            echo "Comment: {$key['attrs']['comment']}\n\n";
                        }
                        exit;
            */
        }
        $this->conn = ssh2_sftp($this->link);
        return ($this->logged !== false);
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
        return ssh2_sftp_chmod($this->conn, $this->getFullPath($path), $permissions) !== false;
    }

    public function mode(string $path): string
    {
        $this->checkConnection();
        return substr(decoct($this->stat($path)['mode']), -4);
    }

    public function stat(string $path): array
    {
        $this->checkConnection();
        return ssh2_sftp_stat($this->conn, $this->getFullPath($path));
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
        return ssh2_scp_send($this->link, $local_file, $this->getFullPath($remote_file));
    }

    public function download(string $remote_file, string $local_file): bool
    {
        $this->checkConnection();
        return ssh2_scp_recv($this->link, $this->getFullPath($remote_file), $local_file);
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
        return $contents;
    }

    public function write(string $remote_file, string $contents): bool
    {
        $this->checkConnection();
        $tempHandle = @fopen($this->getWrappedPath($remote_file), 'r+');
        if (!$tempHandle) {
            return false;
        }
        $writed = fwrite($tempHandle, $contents);
        fclose($tempHandle);
        return $writed !== false;
    }

    public function delete(string $file): bool
    {
        $this->checkConnection();
        return ssh2_sftp_unlink($this->conn, $this->getFullPath($file));
    }

    public function rename(string $original_dir, $new_dir): bool
    {
        $this->checkConnection();
        return ssh2_sftp_rename($this->conn, $this->getFullPath($original_dir), $this->getFullPath($new_dir));
    }

    public function isDir(string $file): bool
    {
        $this->checkConnection();
        return is_dir($this->getWrappedPath($file));
    }
    public function filesize(string $file): int
    {
        $this->checkConnection();
        $size = @filesize($this->getWrappedPath($file));
        return (!$size) ? -1 : $size;
    }

    public function lastModified(string $filepath): ?\DateTimeInterface
    {
        $this->checkConnection();
        $time = filemtime($this->getWrappedPath($filepath));
        if (!$time) {
            return null;
        }
        $datetime = new \DateTimeImmutable;
        return $datetime->setTimestamp($time);
    }

    public function createDir(string $dir): bool
    {
        $this->checkConnection();
        return ssh2_sftp_mkdir($this->conn, $dir);
    }

    public function deleteDir(string $dir): bool
    {
        $this->checkConnection();
        return ssh2_sftp_rmdir($this->conn, $this->getFullPath($dir));
    }

    public function changeDir(string $dir): bool
    {
        $this->checkConnection();
        if (substr($dir, 0, 1) === '/') {
            $this->last_dir = $dir;
        } else {
            $this->last_dir .= '/' . $dir;
        }
        return true;
    }

    public function parentDir(): bool
    {
        $this->checkConnection();
        if (substr_count($this->last_dir, '/') == 1) {
            return false;
        }
        $this->last_dir = substr($this->last_dir, 0, strrpos($this->last_dir, '/'));
        return true;
    }

    public function currentDir(): string|false
    {
        $this->checkConnection();
        return $this->last_dir;
    }

    public function listDirContents(string $dir = '.'): array|false
    {
        $this->checkConnection();
        $contents = scandir($this->getWrappedPath($dir));
        return ($contents !== false) ? array_values(array_diff($contents, array('..', '.'))) : false;
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