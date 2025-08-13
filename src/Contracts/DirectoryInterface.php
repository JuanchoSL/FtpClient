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
     * @param bool $with_dots Indica si queremos incluir o no los elementos '.' y '..'
     * @return array<int,string>|false Array del contenido de la ruta especificada o false si no existe
     */
    public function listDirContents(string $dir = '.', bool $with_dots = false): array|false;

    /**
     * Lista los directorios de la ruta especificada
     * @param string $dir Directorio a listar
     * @param bool $info True para devolver info extendida de cada elemento, false solo para nombres
     * @param string $sort Nombre del atributo a usar para ordenar, ascendente, puede ser
     * - name
     * - mtime
     * - size
     * - mode
     * @return array<int,string>|false Array del contenido de la ruta especificada o false si no existe
     */
    public function listDirs(string $dir = '.', bool $info = true, ?string $sort = null): array|false;

    /**
     * Lista los ficheros de la ruta especificada
     * @param string $dir Directorio a listar
     * @param bool $info True para devolver info extendida de cada elemento, false solo para nombres
     * @param string $sort Nombre del atributo a usar para ordenar, ascendente, puede ser
     * - name
     * - mtime
     * - size
     * - mode
     * @return array<int,string>|false Array del contenido de la ruta especificada o false si no existe
     */
    public function listFiles(string $dir = '.', bool $info = true, ?string $sort = null): array|false;

}