<?php

namespace JuanchoSL\FtpClient\Tests\Unit;

use PHPUnit\Framework\TestCase;

abstract class AbstractFtp extends TestCase
{

    protected $ftp = null;
    protected $my_dir = "";
    protected $my_file_path = "";
    protected $my_file_name = 'test.txt';

    abstract protected function getInstance();
    abstract protected function getHost();
    abstract protected function getPort();
    abstract protected function getUser();
    abstract protected function getPass();

    public function setUp(): void
    {
        $this->my_file_path = implode(DIRECTORY_SEPARATOR, [dirname(__DIR__, 2), 'etc']);
        $this->my_dir = "juancho-test-" . date('Y-m-d');

        $this->ftp = $this->getInstance();
        $connect = $this->ftp->connect($this->getHost(), $this->getPort());
        $this->assertTrue($connect, "Check conection");
        $login = $this->ftp->login($this->getUser(), $this->getPass());
        $this->assertTrue($login, "check login");
    }
    public function tearDown(): void
    {
        if (isset($this->ftp) && $this->ftp->isConnected()) {
            $this->ftp->disconnect();
            unset($this->ftp);
        }
    }

    public function testConection()
    {
        $this->assertTrue($this->ftp->isConnected(), "Check that is connected");
        $this->assertTrue($this->ftp->isLogged(), "Check that is logged");
        //$this->assertFalse(empty($this->ftp->system()), "Check system");
    }

    public function testReadable()
    {
        $root_dir = $this->ftp->currentDir();
        $this->assertFalse(empty($root_dir), "Check that currrent dir is not empty");
    }

    public function testCreateDir()
    {
        $this->assertTrue($this->ftp->createDir($this->my_dir), "Check that directory is created");
    }

    public function testChangeDir()
    {
        $this->assertTrue($this->ftp->changeDir($this->my_dir), "Directory has been changed");
        $this->assertStringEndsWith($this->my_dir, $this->ftp->currentDir(), "Current directory is the selected directory");
    }

    public function testUploadFile()
    {
        $this->assertTrue(empty($this->ftp->listDir($this->my_dir)), "Directory is empty");
        $this->assertTrue($this->ftp->changeDir($this->my_dir), "Change dir successfull");
        $this->assertTrue($this->ftp->upload($this->my_file_path . DIRECTORY_SEPARATOR . $this->my_file_name, $this->my_file_name), "Upload file");
        //$this->assertFalse(empty($this->ftp->listDir($this->my_dir)));
    }
    public function testRead()
    {
        $this->assertTrue($this->ftp->changeDir($this->my_dir), "Change dir successfull");
        $content = $this->ftp->read($this->my_file_name);
        $this->assertNotFalse($content);
        $this->assertEquals(file_get_contents($this->my_file_path . DIRECTORY_SEPARATOR . $this->my_file_name), $content);
    }

    public function testWrite()
    {
        $this->assertTrue($this->ftp->changeDir($this->my_dir), "Change dir successfull");
        $this->assertTrue($this->ftp->write($this->my_file_name, "Esto es un texto nuevo"));
    }

    public function testSizeFile()
    {
        $this->assertTrue($this->ftp->changeDir($this->my_dir), "Change to the selected dir");
        $size = $this->ftp->filesize($this->my_file_name);
        $this->assertIsInt($size, "Size is an integer");
        $this->assertGreaterThanOrEqual(0, $size, "Size is greater or equals than 0");
    }
    public function testModifiedFile()
    {
        $this->assertTrue($this->ftp->changeDir($this->my_dir), "Change to the selected dir");
        $date = $this->ftp->lastModified($this->my_file_name);
        $this->assertInstanceOf(\DateTimeInterface::class, $date, "Check than last modified date is a instance");
        $this->assertEquals(date('Y-m-d'), $date->format("Y-m-d"), "Date from last modified is the today date");
    }

    public function testRenameFile()
    {
        $this->assertTrue($this->ftp->changeDir($this->my_dir), "Change to the desired dir");
        $new_file_name = $this->my_file_name . ".old";
        $this->assertTrue($this->ftp->rename($this->my_file_name, $new_file_name), "Rename the file");
    }

    public function testDownloadFile()
    {
        $this->assertTrue($this->ftp->changeDir($this->my_dir), "change to the desired file");
        $new_file_name = $this->my_file_name . ".old";
        $this->assertTrue($this->ftp->download($new_file_name, $this->my_file_path . DIRECTORY_SEPARATOR . $new_file_name), "Download the file");
    }

    public function testListFolder()
    {
        $contents = $this->ftp->listDir($this->my_dir);
        $this->assertFalse(empty($contents), "The dir contents is not empty using mode 1");
        $this->assertTrue($this->ftp->changeDir($this->my_dir), "Change to the desired file");
        $contents2 = $this->ftp->listDir();
        $this->assertFalse(empty($contents2), "The dir contents is not empty using mode 2");
        $this->assertSameSize($contents, $contents2, "mode 1 and 2 have the same results");
    }

    public function testDeleteFile()
    {
        $this->assertTrue($this->ftp->changeDir($this->my_dir), "change to the desired dir");
        $this->assertTrue($this->ftp->delete($this->my_file_name . ".old"), "Delete the file");
        $this->assertTrue(unlink($this->my_file_path . DIRECTORY_SEPARATOR . $this->my_file_name . ".old"), "Delete local file");
        $this->assertTrue(empty($this->ftp->listDir()), "The dir is empty");
    }

    public function testDeleteDir()
    {
        $this->assertTrue($this->ftp->deleteDir($this->my_dir), "Delete directory");
    }

    public function testDisconnect()
    {
        $this->assertTrue($this->ftp->disconnect(), "Disconnect");
    }
}