<?php

namespace JuanchoSL\FtpClient\Tests\Common;

trait FtpCredentials
{

    protected function getHost()
    {
        return getenv("FTPTEST_HOST");
    }
    protected function getPort()
    {
        return 21;
    }
    protected function getUser()
    {
        return getenv("FTPTEST_USERNAME");
    }
    protected function getPass()
    {
        return getenv("FTPTEST_PASSWORD");
    }
}