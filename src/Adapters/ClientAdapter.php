<?php

declare(strict_types=1);

namespace JuanchoSL\FtpClient\Adapters;

use JuanchoSL\FtpClient\Contracts\ClientInterface;

class ClientAdapter implements ClientInterface
{
    protected ClientInterface $connection;

    public function __construct(ClientInterface $connection)
    {
        $this->connection = $connection;
    }

    public function chmod(string $path, int $permissions): bool
    {
        return $this->connection->chmod($path, $permissions) !== false;
    }

    public function mode(string $path): mixed
    {
        return $this->connection->mode($path);
    }

    public function upload(string $local_file, string $remote_file): bool
    {
        return $this->connection->upload($local_file, $remote_file);
    }

    public function download(string $remote_file, string $local_file): bool
    {
        return $this->connection->download($remote_file, $local_file);
    }

    public function read(string $remote_file): string|false
    {
        return $this->connection->read($remote_file);
    }

    public function write(string $remote_file, string $contents): bool
    {
        return $this->connection->write($remote_file, $contents);
    }

    public function filesize(string $file): int
    {
        return $this->connection->filesize($file);
    }

    public function lastModified(string $filepath): ?\DateTimeInterface
    {
        return $this->connection->lastModified($filepath);
    }

    public function delete(string $file): bool
    {
        return $this->connection->delete($file);
    }

    public function rename(string $original_dir, $new_dir): bool
    {
        return $this->connection->rename($original_dir, $new_dir);
    }

    public function createDir(string $dir): bool
    {
        return $this->connection->createDir($dir);
    }

    public function deleteDir(string $dir): bool
    {
        return $this->connection->deleteDir($dir);
    }

    public function changeDir(string $dir): bool
    {
        return $this->connection->changeDir($dir);
    }

    public function parentDir(): bool
    {
        return $this->connection->parentDir();
    }

    public function currentDir(): string|false
    {
        return $this->connection->currentDir();
    }
    public function isDir(string $path): bool
    {
        return $this->connection->isDir($path);
    }

    public function listDirContents(string $dir = '.'): array|false
    {
        return $this->connection->listDirContents($dir);
    }

    public function listDirs(string $dir = '.', bool $info = false, ?string $sort = null): array|false
    {
        return $this->connection->listDirs($dir, $info, $sort);
    }

    public function listFiles(string $dir = '.', bool $info = false, ?string $sort = null): array|false
    {
        return $this->connection->listFiles($dir, $info, $sort);
    }
}