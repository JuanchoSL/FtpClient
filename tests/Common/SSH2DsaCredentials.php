<?php

namespace JuanchoSL\FtpClient\Tests\Common;

trait SSH2DsaCredentials
{

    protected function getHost()
    {
        return getenv("SFTPTEST_HOST");
    }
    protected function getPort()
    {
        return 22;
    }
    protected function getUser()
    {
        return getenv("SFTPTEST_USERNAME");
    }
    protected function getPass()
    {
        return getenv("SFTPTEST_PASSWORD");
    }
    protected function getPublicKey()
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . getenv('SFTPTEST_DSA_PUBLIC');
    }
    protected function getPrivateKey()
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . getenv('SFTPTEST_DSA_PRIVATE');
    }
}