<?php declare(strict_types=1);

namespace JuanchoSL\FtpClient\Engines\Curl;

use JuanchoSL\CurlClient\Engines\Ssh\CurlSshHandler;
use JuanchoSL\CurlClient\Engines\Ssh\CurlSshRequest;
use JuanchoSL\DataManipulation\Manipulators\Strings\StringsManipulators;
use JuanchoSL\Exceptions\DestinationUnreachableException;
use JuanchoSL\Exceptions\UnauthorizedException;
use JuanchoSL\FtpClient\Contracts\ConnectionInterface;
use JuanchoSL\FtpClient\Engines\AbstractClient;
use JuanchoSL\HttpData\Factories\UriFactory;
use Psr\Http\Message\UriInterface;

class CurlSFtp extends AbstractClient implements ConnectionInterface
{

    const DEFAULT_PORT = 22;

    protected bool $ssl = true;
    private string $last_dir = '/';
    private ?string $private_key = null;
    private ?string $public_key = null;
    private string $private_key_password = '';

    public function connect(string $server, int $port = self::DEFAULT_PORT): bool
    {
        $this->server = $server;
        $this->port = $port;

        $this->checkExtension('ssh2', 'curl');
        $this->link = true;//$this->controlConnect();
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
        return $this->isConnected();
    }

    public function login(string $user, #[\SensitiveParameter] string $pass = ''): bool
    {
        $this->user = $user;
        $this->pass = $pass;
        $handle = $this->getHandler()->prepareHead($this->getUri(''));
        $response = CurlSshRequest::execute($handle);
        $result = (string) $response->getBody();
        $this->logged = empty($result);
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'PWD' => $this->last_dir, 'result' => $this->isLogged()]);
        if (!$this->isLogged()) {
            $result = (new StringsManipulators($result))->eol(PHP_EOL)->__tostring();
            $exception = new UnauthorizedException("Failed authenticating with the provided credentials");
            $this->log($exception, 'error', [
                'exception' => $exception,
                'error' => $result,
                'credentials' => [
                    'user' => $user,
                    'pass' => $pass,
                ]
            ]);
            throw $exception;
        }
        return $this->isLogged();
    }

    public function setCredentials(#[\SensitiveParameter] string $public_key, #[\SensitiveParameter] string $private_key, #[\SensitiveParameter] string $key_password = ''): static
    {
        $this->public_key = $public_key;
        $this->private_key = $private_key;
        $this->private_key_password = $key_password;
        return $this;
    }

    /**
     * Devuelve el identificador del sistema operativo usado por el servidor remoto
     * @return string Sistema operativo remoto
     */
    public function system()
    {
        $this->checkConnection();
        $result = '';
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'PWD' => $this->last_dir, 'result' => $result]);
        return $result;
    }

    public function disconnect(): bool
    {
        if ($this->isConnected() && isset($this->link)) {
            unset($this->link);
            return true;
        }
        return false;
    }

    public function chmod(string $path, int $permissions): bool
    {
        $this->checkConnection();
        $result = '';
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'PWD' => $this->last_dir, 'result' => $result]);
        return $result;
    }

    public function stat(string $dir): array
    {
        $this->checkConnection();
        $dir = ($dir == '.') ? '' : ltrim($dir, '/');
        $handle = $this->getHandler()->prepareStat($this->getUri(dirname($dir)));
        $response = CurlSshRequest::execute($handle);
        $result = (string) $response->getBody();
        $result = (new StringsManipulators($result))->eol(PHP_EOL)->__tostring();
        $results = explode(PHP_EOL, $result);
        $results = $this->formatDataCommanLine($results);
        if (is_iterable($results)) {
            foreach ($results as $result) {
                if (basename($result['name']) == basename($dir) && $result['type'] == 'file') {
                    $results = $result;
                    break;
                }
            }
        }

        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'PWD' => $this->last_dir, 'result' => $results]);
        return $results ?? [];
    }

    public function currentDir(): string|false
    {
        $this->checkConnection();
        return $this->last_dir;
    }

    public function listDirContents(string $dir = '.', bool $with_dots = false): array|false
    {
        $this->checkConnection();
        $dir = ($dir == '.') ? '' : ltrim($dir, '/');
        $handle = $this->getHandler()->prepareList($this->getUri($dir . "/"));
        $response = CurlSshRequest::execute($handle);
        $result = (string) $response->getBody();
        $result = (new StringsManipulators($result))->eol(PHP_EOL)->__tostring();
        $result = explode(PHP_EOL, $result);
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'PWD' => $this->last_dir, 'result' => $result]);
        if (!empty($result)) {
            $result = array_filter($result);
            if (!$with_dots) {
                $result = (!empty($result)) ? array_values(array_diff($result, array('..', '.'))) : false;
            }
        }

        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'PWD' => $this->last_dir, 'result' => $result]);
        return $result;
    }

    public function changeDir($dir): bool
    {
        $this->checkConnection();
        $this->last_dir .= trim($dir, '/');
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'PWD' => $this->last_dir, 'result' => $this->last_dir]);
        return true;
    }

    public function parentDir(): bool
    {
        $this->checkConnection();
        $this->last_dir = dirname($this->last_dir);
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'PWD' => $this->last_dir]);
        return true;
    }

    public function createDir(string $dir_name): bool
    {
        $this->checkConnection();
        $handle = $this->getHandler()->preparePost($this->getUri($dir_name . "/"), '');
        $response = CurlSshRequest::execute($handle);
        $result = (string) $response->getBody();
        $result = (new StringsManipulators($result))->eol(PHP_EOL)->__tostring();
        $result = explode(PHP_EOL, $result);
        $result = $this->formatDataCommanLine($result);
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'PWD' => $this->last_dir, 'result' => $result]);
        return count($result) == 2;
    }

    public function deleteDir(string $path_name): bool
    {
        $this->checkConnection();
        $handle = $this->getHandler()->prepareDelete($this->getUri($path_name . "/"));
        $response = CurlSshRequest::execute($handle);
        $result = (string) $response->getBody();
        $result = (new StringsManipulators($result))->eol(PHP_EOL)->__tostring();
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'PWD' => $this->last_dir, 'result' => $result]);
        return ($result == 1);
    }

    public function download(string $remote_file, string $local_file): bool
    {
        $this->checkConnection();
        $result = $this->read($remote_file);
        $result = file_put_contents($local_file, $result);
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'PWD' => $this->last_dir, 'result' => $result]);
        return is_integer($result);
    }

    public function read(string $remote_file): string|false
    {
        $this->checkConnection();
        $handle = $this->getHandler()->prepareGet($this->getUri($remote_file));
        $response = CurlSshRequest::execute($handle);
        $result = (string) $response->getBody();
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'PWD' => $this->last_dir, 'result' => !empty($result)]);
        return $result;
    }

    public function write(string $remote_file, string $contents): bool
    {
        $this->checkConnection();
        $handle = $this->getHandler()->preparePost($this->getUri($remote_file), $contents);
        $response = CurlSshRequest::execute($handle);
        $result = (string) $response->getBody();
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'PWD' => $this->last_dir, 'result' => $result]);
        return empty($result);
    }

    public function upload(string $local_file, string $remote_file): bool
    {
        $this->checkConnection();
        $result = $this->write($remote_file, file_get_contents($local_file));
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'PWD' => $this->last_dir, 'result' => $result]);
        return $result;
    }

    public function rename(string $old_name, string $new_name): bool
    {
        $this->checkConnection();
        $handle = $this->getHandler()->preparePut($this->getUri($old_name), '');
        curl_setopt($handle, CURLOPT_QUOTE, array(sprintf("RENAME %s %s", $this->last_dir . '/' . $old_name, $this->last_dir . '/' . $new_name)));
        $response = CurlSshRequest::execute($handle);
        $result = (string) $response->getBody();
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'PWD' => $this->last_dir, 'result' => $result]);
        return true;
    }

    public function delete(string $path_name): bool
    {
        $this->checkConnection();
        $handle = $this->getHandler()->prepareDelete($this->getUri($path_name));
        curl_setopt($handle, CURLOPT_QUOTE, array(sprintf("RM %s", $this->last_dir . '/' . $path_name)));
        $response = CurlSshRequest::execute($handle);
        $result = (string) $response->getBody();
        $result = (new StringsManipulators($result))->eol(PHP_EOL)->__tostring();
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'PWD' => $this->last_dir, 'result' => $result]);
        return boolval($result);
    }

    public function filesize($filepath): int
    {
        $this->checkConnection();
        $result = $this->stat($filepath)['size'];
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'PWD' => $this->last_dir, 'result' => $result]);
        return intval(substr($result, 4));
    }

    public function lastModified(string $filepath): ?\DateTimeInterface
    {
        $this->checkConnection();
        $result = $this->stat($filepath)['modify'];
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'PWD' => $this->last_dir, 'result' => $result]);
        if (!empty($result)) {
            $result = date_create_immutable_from_format("YmdHis", $result);
        } else {
            $result = null;
        }
        return $result;
    }

    protected function getUri(string $request_target = ''): UriInterface
    {
        return (new UriFactory())->createUri(sprintf("sftp://%s:%s@%s:%d/%s/%s", urlencode($this->user), urlencode($this->pass), $this->server, $this->port, trim($this->last_dir, '/'), $request_target));
    }

    protected function getHandler(): CurlSshHandler
    {
        $options = [];
        if (isset($this->private_key, $this->public_key)) {
            $options[CURLOPT_SSH_PRIVATE_KEYFILE] = $this->private_key;
            $options[CURLOPT_SSH_PUBLIC_KEYFILE] = $this->public_key;
            $options[CURLOPT_KEYPASSWD] = (!empty($this->private_key_password)) ? $this->private_key_password : null;
        }
        if (isset($this->user)) {
            $options[CURLOPT_TLSAUTH_USERNAME] = $this->user;
        }
        if (isset($this->pass)) {
            $options[CURLOPT_TLSAUTH_PASSWORD] = $this->pass;
        }
        return new CurlSshHandler($options);
    }
}