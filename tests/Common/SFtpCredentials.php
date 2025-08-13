<?php

namespace JuanchoSL\FtpClient\Tests\Common;

trait SFtpCredentials
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
}