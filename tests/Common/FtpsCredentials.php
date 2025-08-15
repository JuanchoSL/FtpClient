<?php

namespace JuanchoSL\FtpClient\Tests\Common;

trait FtpsCredentials
{

    protected function getHost()
    {
        return getenv("FTPSTEST_HOST");
    }
    protected function getPort()
    {
        return 21;
    }
    protected function getUser()
    {
        return getenv("FTPSTEST_USERNAME");
    }
    protected function getPass()
    {
        return getenv("FTPSTEST_PASSWORD");
    }
}