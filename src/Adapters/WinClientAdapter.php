<?php

declare(strict_types=1);

namespace JuanchoSL\FtpClient\Adapters;

use JuanchoSL\FtpClient\Contracts\ClientInterface;

class WinClientAdapter
{
    protected ClientInterface $connection;

    public function __construct(ClientInterface $connection)
    {
        $this->connection = $connection;
    }

    public function icacls(string $path, int $permissions): bool
    {
        return $this->connection->chmod($path, $permissions) !== false;
    }

    public function cacls(string $path): mixed
    {
        return $this->connection->mode($path);
    }

    public function put(string $local_file, string $remote_file): bool
    {
        return (file_exists($local_file)) ? $this->connection->upload($local_file, $remote_file) : $this->connection->write($remote_file, $local_file);
    }

    public function get(string $remote_file, ?string $local_file = null): string|bool
    {
        return (is_null($local_file)) ? $this->connection->read($remote_file) : $this->connection->download($remote_file, $local_file);
    }

    public function filesize(string $file): int
    {
        return $this->connection->filesize($file);
    }

    public function lastModified(string $filepath): ?\DateTimeInterface
    {
        return $this->connection->lastModified($filepath);
    }

    public function del(string $path): bool
    {
        return $this->connection->delete($path);
    }

    public function move(string $original_dir, $new_dir): bool
    {
        return $this->connection->rename($original_dir, $new_dir);
    }

    public function mkdir(string $dir): bool
    {
        return $this->connection->createDir($dir);
    }

    public function rmdir(string $path): bool
    {
        return $this->connection->deleteDir($path);
    }

    public function cd(?string $dir = null): bool|string
    {
        if (is_null($dir)) {
            return $this->connection->currentDir();
        } elseif ($dir == '..') {
            return $this->cdUp();
        } else {
            return $this->connection->changeDir($dir);
        }
    }

    public function cdUp(): bool
    {
        return $this->connection->parentDir();
    }

    public function dir(string $dir = '.'): array|false
    {
        return $this->connection->listDirContents($dir);
    }

    public function dirDirs(string $dir = '.', bool $info = false, ?string $sort = null): array|false
    {
        return $this->connection->listDirs($dir, $info, $sort);
    }

    public function dirFiles(string $dir = '.', bool $info = false, ?string $sort = null): array|false
    {
        return $this->connection->listFiles($dir, $info, $sort);
    }
}