<?php

namespace JuanchoSL\FtpClient\Contracts;

interface ClientInterface extends DirectoryInterface, FilesInterface
{

    /**
     * Change the file or directory permissions
     * @param string $path Fullpath of the file or directory
     * @param int $permissions Octal value for the new permissions
     * @return bool Result of the operation
     */
    public function chmod(string $path, int $permissions): bool;

    /**
     * Show the file or directory permissions
     * @param string $path Fullpath of the file or directory
     * @return mixed Result of the operation
     */
    public function mode(string $path): mixed;

    /**
     * Check if the path is a directory
     * @param string $path Path to check
     * @return bool true if is a directory, false otherwise
     */
    public function isDir(string $path): bool;
    
    /**
     * Renombra el fichero o directorio pasado
     * @param string $old_name Ruta del recurso a renombrar
     * @param string $new_name Nuevo nombre del recurso
     * @return boolean Resultado de la operación
     */
    public function rename(string $old_name, string $new_name): bool;

}