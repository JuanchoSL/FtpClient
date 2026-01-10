<?php

namespace JuanchoSL\FtpClient\Tests\Unit\Native;

use JuanchoSL\FtpClient\Engines\Native\SFtp;
use JuanchoSL\FtpClient\Tests\Common\SSH2DsaCredentials;
use JuanchoSL\FtpClient\Tests\Unit\AbstractFtp;

class SFtpDsaTest extends AbstractFtp
{
    use SSH2DsaCredentials;

    protected function getInstance(): SFtp
    {
        return new Sftp();
    }

    public function setUp(): void
    {
        $this->my_file_path = implode(DIRECTORY_SEPARATOR, [dirname(__DIR__, 3), 'etc']);
        $this->my_dir = $this->getDirName();

        $this->ftp = $this->getInstance();
        $connect = $this->ftp->connect($this->getHost(), $this->getPort());
        $this->assertTrue($connect, "Check conection");
        $login = $this->ftp->setCredentials($this->getPublicKey(), $this->getPrivateKey());
        $login = $this->ftp->login($this->getUser(), $this->getPass());
        $this->assertTrue($login, "check login");
    }
}