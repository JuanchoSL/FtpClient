<?php declare(strict_types=1);

namespace JuanchoSL\FtpClient\Engines\Curl;

use JuanchoSL\CurlClient\Engines\Ftp\CurlFtpHandler;
use JuanchoSL\CurlClient\Engines\Ftp\CurlFtpRequest;
use JuanchoSL\DataManipulation\Manipulators\Strings\StringsManipulators;
use JuanchoSL\Exceptions\DestinationUnreachableException;
use JuanchoSL\Exceptions\UnauthorizedException;
use JuanchoSL\FtpClient\Contracts\ConnectionInterface;
use JuanchoSL\FtpClient\Engines\AbstractClient;
use JuanchoSL\HttpData\Factories\UriFactory;

class CurlFtp extends AbstractClient implements ConnectionInterface
{

    const DEFAULT_PORT = 21;

    protected bool $pasive = false;
    protected bool $ssl = false;
    private string $last_dir = '/';

    public function connect(string $server, int $port = self::DEFAULT_PORT): bool
    {
        $this->server = $server;
        $this->port = $port;

        $this->checkExtension('curl');
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
        $this->logged = true;

        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'PWD' => $this->last_dir, 'result' => $this->isLogged()]);
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
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'PWD' => $this->last_dir, 'result' => $estado]);
        return $this->pasive = $estado;
        return $this;
        return ($estado) ? !empty($this->data_channel) : empty($this->data_channel);//str_starts_with($result, '227 ');
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
            //      $this->link->disconnect();
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
        $handle = (new CurlFtpHandler())->setPasive($this->pasive)->setSsl($this->ssl, false)->prepareStat((new UriFactory())->createUri(sprintf("ftp://%s:%s@%s:%d/%s", urlencode($this->user), urlencode($this->pass), $this->server, $this->port, trim($this->last_dir, '/') . '/' . dirname($dir))));
        $response = CurlFtpRequest::execute($handle);
        //$request = (new RequestFactory())->createRequest(RequestMethodInterface::METHOD_GET, (new UriFactory())->createUri(sprintf("ftps://%s:%s@%s:%d/%s", urlencode($this->user), urlencode($this->pass), $this->server, $this->port, $this->last_dir . '/' . $dir.'/')));
        //$response = (new PsrCurlClient())->sendRequest($request);
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
        $handle = (new CurlFtpHandler())->setSsl($this->ssl, false)->prepareList((new UriFactory())->createUri(sprintf("ftp://%s:%s@%s:%d/%s", urlencode($this->user), urlencode($this->pass), $this->server, $this->port, $this->last_dir . '/' . $dir . '/')));
        $response = CurlFtpRequest::execute($handle);
        //$request = (new RequestFactory())->createRequest(RequestMethodInterface::METHOD_GET, (new UriFactory())->createUri(sprintf("ftps://%s:%s@%s:%d/%s", urlencode($this->user), urlencode($this->pass), $this->server, $this->port, $this->last_dir . '/' . $dir.'/')));
        //$response = (new PsrCurlClient())->sendRequest($request);
        $result = (string) $response->getBody();
        $result = (new StringsManipulators($result))->eol(PHP_EOL)->__tostring();
        $result = explode(PHP_EOL, $result);
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'PWD' => $this->last_dir, 'result' => $result]);
        //$result = $this->formatDataCommanLine($result);
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
        $result = '';
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'PWD' => $this->last_dir, 'result' => $result]);
        return str_starts_with($result, '250 OK');
    }

    public function createDir(string $dir_name): bool
    {
        $this->checkConnection();
        $handle = (new CurlFtpHandler())->setSsl($this->ssl, false)->preparePost((new UriFactory())->createUri(sprintf("ftp://%s:%s@%s:%d/%s/", urlencode($this->user), urlencode($this->pass), $this->server, $this->port, trim($this->last_dir, '/') . '/' . $dir_name)), '');
        $response = CurlFtpRequest::execute($handle);
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
        $handle = (new CurlFtpHandler())->setPasive($this->pasive)->setSsl($this->ssl, false)->prepareDelete((new UriFactory())->createUri(sprintf("ftp://%s:%s@%s:%d/%s", urlencode($this->user), urlencode($this->pass), $this->server, $this->port, trim($this->last_dir, '/') . '/' . $path_name . '/')));
        $response = CurlFtpRequest::execute($handle);
        $result = (string) $response->getBody();
        $result = (new StringsManipulators($result))->eol(PHP_EOL)->__tostring();
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'PWD' => $this->last_dir, 'result' => $result]);
        return true;//$result;
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
        $handle = (new CurlFtpHandler())->setPasive($this->pasive)->setSsl($this->ssl, false)->prepareGet((new UriFactory())->createUri(sprintf("ftp://%s:%s@%s:%d/%s", urlencode($this->user), urlencode($this->pass), $this->server, $this->port, trim($this->last_dir, '/') . '/' . $remote_file)));
        $response = CurlFtpRequest::execute($handle);
        $result = (string) $response->getBody();
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'PWD' => $this->last_dir, 'result' => !empty($result)]);
        return $result;
    }

    public function write(string $remote_file, string $contents): bool
    {
        $this->checkConnection();
        $handle = (new CurlFtpHandler())->setPasive($this->pasive)->setSsl($this->ssl, false)->preparePost((new UriFactory())->createUri(sprintf("ftp://%s:%s@%s:%d/%s", urlencode($this->user), urlencode($this->pass), $this->server, $this->port, trim($this->last_dir, '/') . '/' . $remote_file)), $contents);
        $response = CurlFtpRequest::execute($handle);
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
        $handle = (new CurlFtpHandler())->setSsl($this->ssl, false)->preparePut((new UriFactory())->createUri(sprintf("ftp://%s:%s@%s:%d/%s", urlencode($this->user), urlencode($this->pass), $this->server, $this->port, $this->last_dir . '/' . $old_name)), '');
        curl_setopt($handle, CURLOPT_QUOTE, array(sprintf("RNFR %s", $this->last_dir . '/' . $old_name), sprintf("RNTO %s", $this->last_dir . '/' . $new_name)));
        $response = CurlFtpRequest::execute($handle);
        $result = (string) $response->getBody();
        $this->logCall(__FUNCTION__, ['parameters' => func_get_args(), 'PWD' => $this->last_dir, 'result' => $result]);
        return true;
    }

    public function delete(string $path_name): bool
    {
        $this->checkConnection();
        $handle = (new CurlFtpHandler())->setPasive($this->pasive)->setSsl($this->ssl, false)->prepareDelete((new UriFactory())->createUri(sprintf("ftp://%s:%s@%s:%d/%s", urlencode($this->user), urlencode($this->pass), $this->server, $this->port, trim($this->last_dir, '/') . '/' . $path_name)));
        $response = CurlFtpRequest::execute($handle);
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

}