<?php

namespace JuanchoSL\FtpClient\Contracts;

interface ConnectionInterface
{

    /**
     * Establece una conexión con el servidor FTP
     * @param string $server Dominio o IP del servidor FTP
     * @param int $port Puerto del servidor de ficheros
     * @return boolean Resultado de la operación
     * @throws \JuanchoSL\Exceptions\PreconditionRequiredException Si falta algun módulo o extensión requerido
     * @throws \JuanchoSL\Exceptions\DestinationUnreachableException Si no se puede conectar
     */
    public function connect(string $server, int $port): bool;
    
    /**
     * Realiza un logueo con el servidor FTP
     * @param string $user Nombre de usuario para la conexión
     * @param string $pass Contraseña de la conexión
     * @return boolean Resultado de la operación
     * @throws \JuanchoSL\Exceptions\UnauthorizedException Si no se puede autenticar
     */
    public function login(string $user, #[\SensitiveParameter] string $pass): bool;
    
    /**
     * Cierra la conexión con el servidor FTP
     * @return boolean Resultado de la operación
     */
    public function disconnect(): bool;

    /**
     * Comprueba que está correctamente conectado
     * @return bool Estado de la conexión
     */
    public function isConnected(): bool;

    /**
     * Conprueba que está correctamente autenticado
     * @return bool Estado de la autenticación
     */
    public function isLogged(): bool;

}