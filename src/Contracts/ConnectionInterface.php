<?php

namespace JuanchoSL\FtpClient\Contracts;

interface ConnectionInterface
{

    /**
     * Establece una conexión con el servidor FTP
     * @param string $server Dominio o IP del servidor FTP
     * @param int $port Puerto del servidor de ficheros
     * @return boolean Resultado de la operación
     */
    public function connect(string $server, int $port): bool;

    /**
     * Realiza un logueo con el servidor FTP
     * @param string $user Nombre de usuario para la conexión
     * @param string $pass Contraseña de la conexión
     * @return boolean Resultado de la operación
     */
    public function login(string $user, string $pass): bool;

    /**
     * Cierra la conexión con el servidor FTP
     * @return boolean Resultado de la operación
     */
    public function disconnect(): bool;
    public function isConnected(): bool;
    public function isLogged(): bool;

}