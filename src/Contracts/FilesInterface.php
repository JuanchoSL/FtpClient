<?php

namespace JuanchoSL\FtpClient\Contracts;

interface FilesInterface
{
    /**
     * Sube un fichero local a un servidor FTP remoto
     * @param string $local_file Ruta del fichero a ser subido
     * @param string $remote_file Ruta destino del fichero a subir
     * @return boolean Resultado de la operación
     */
    public function upload(string $local_file, string $remote_file): bool;

    /**
     * Transfiere un fichero desde un servidor FTP al servidor web
     * @param string $remote_file Ruta del fichero a descargar
     * @param string $local_file Ruta donde será guardado el fichero
     * @return boolean Resultado de la operación
     */
    public function download(string $remote_file, string $local_file): bool;
    
    /**
     * Transfiere el contenido de un fichero desde un servidor FTP
     * @param string $remote_file Ruta del fichero a leer
     * @return string|false Contenido del fichero o false
     */
    public function read(string $remote_file): string|false;
    
    /**
     * Escribe es un fichero del servidor remoto
     * @param string $remote_file Ruta del fichero a escribir
     * @param string $contents Contenido a escribir
     * @return boolean Resultado de la operación
     */
    public function write(string $remote_file, string $contents): bool;

    /**
     * Renombra el fichero o directorio pasado
     * @param string $old_name Ruta del recurso a renombrar
     * @param string $new_name Nuevo nombre del recurso
     * @return boolean Resultado de la operación
     */
    public function rename(string $old_name, string $new_name): bool;

    /**
     * Elimina el fichero especificado
     * @param string $path_name Ruta del fichero a eliminar
     * @return boolean Resultado de la operación
     */
    public function delete(string $path_name): bool;

    /**
     * Devuelve el tamaño del fichero especificado
     * @param string $filepath Ruta del fichero
     * @return int Tamaño del fichero en bytes o -1 si no existe
     */
    public function filesize(string $filepath): int;

    /**
     * Devuelve la fecha de la última modificación del fichero especificado
     * @param string $filepath Ruta del fichero
     * @return ?\DateTimeInterface DateTime object o null si hubo errores
     */
    public function lastModified(string $filepath): ?\DateTimeInterface;
}