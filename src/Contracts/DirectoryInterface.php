<?php

namespace JuanchoSL\FtpClient\Contracts;

interface DirectoryInterface
{

    /**
     * Crea un directorio en el servidor ftp remoto
     * @param string $dir_name Nombre del directorio a crear
     * @return bool true si el directorio es creado o false si hay error
     */
    public function createDir(string $dir_name): bool;

    /**
     * Cambia a una ruta de directorio diferente dentro del servidor FTP
     * @param string $dir Directorio hacia el que ir
     * @return boolean Resultado de la operación
     */
    public function changeDir(string $dir): bool;

    /**
     * Elimina el directorio especificado
     * @param string $path_name Ruta del directorio a eliminar
     * @return boolean Resultado de la operación
     */
    public function deleteDir(string $path_name): bool;

    /**
     * Muestra el directorio actual
     * @return string|false Ruta actual en el servidor FTP o false en caso de error
     */
    public function currentDir(): string|false;

    /**
     * Vuelve al directorio padre del actual
     * @return boolean Resultado de la operación
     */
    public function parentDir(): bool;

    /**
     * Lista el contenido de la ruta especificada
     * @param string $dir Directorio a listar
     * @return array<int,string>|false Array del contenido de la ruta especificada o false si no existe
     */
    public function listDir(string $dir = '.'): array|false;

    /**
     * Renombra el directorio pasado al nuevo nomvre dado
     * @param string $old_name Ruta del recurso a renombrar
     * @param string $new_name Nueva ruta del recurso
     * @return boolean Resultado de la operación
     */
    public function renameDir(string $old_name, string $new_name): bool;
}