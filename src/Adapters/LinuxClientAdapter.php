<?php

declare(strict_types=1);

namespace JuanchoSL\FtpClient\Adapters;

use JuanchoSL\FtpClient\Contracts\ClientInterface;

class LinuxClientAdapter
{
    protected ClientInterface $connection;

    public function __construct(ClientInterface $connection)
    {
        $this->connection = $connection;
    }

    public function chmod(string $path, int $permissions): bool
    {
        return $this->chmod($path, $permissions) !== false;
    }

    public function put(string $local_file, string $remote_file): bool
    {
        return (file_exists($local_file)) ? $this->connection->upload($local_file, $remote_file) : $this->connection->write($remote_file, $local_file);
    }

    public function get(string $remote_file, string $local_file = null): string|bool
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

    public function mkdir(string $dir): bool
    {
        return $this->connection->createDir($dir);
    }

    public function mv(string $original_dir, $new_dir): bool
    {
        return $this->connection->rename($original_dir, $new_dir);
    }

    public function rm(string $path): bool
    {
        return ($this->connection->isDir($path)) ? $this->connection->deleteDir($path) : $this->connection->delete($path);
    }

    public function cd(string $dir): bool
    {
        return $this->connection->changeDir($dir);
    }

    public function cdUp(): bool
    {
        return $this->connection->parentDir();
    }

    public function pwd(): string|false
    {
        return $this->connection->currentDir();
    }

    public function ls(string $dir = '.'): array|false
    {
        return $this->connection->listDirContents($dir);
    }

    public function lsDirs(string $dir = '.'): array|false
    {
        return $this->connection->listDirs($dir);
    }

    public function lsFiles(string $dir = '.'): array|false
    {
        return $this->connection->listFiles($dir);
    }
}