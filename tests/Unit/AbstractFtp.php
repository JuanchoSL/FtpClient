<?php

namespace JuanchoSL\FtpClient\Tests\Unit;

use JuanchoSL\Logger\Composers\TextComposer;
use JuanchoSL\Logger\Logger;
use JuanchoSL\Logger\Repositories\FileRepository;
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

    protected function getDirName(): string
    {
        return md5("juancho-test-" . date('Y-m-d') . '-unit-' . get_called_class());
    }
    public function setUp(): void
    {
        date_default_timezone_set("Europe/Madrid");
        $this->my_file_path = implode(DIRECTORY_SEPARATOR, [dirname(__DIR__, 2), 'etc']);
        $this->my_dir = $this->getDirName();

        $this->ftp = $this->getInstance();
        //$logger = new Logger((new FileRepository(dirname(__FILE__, 3) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'socket.log'))->setComposer(new TextComposer()));
        //$this->ftp->setLogger($logger);
        //$this->ftp->setDebug(true);
        $connect = $this->ftp->connect($this->getHost(), $this->getPort());
        $this->assertTrue($connect, "Check conection");
        $login = $this->ftp->login($this->getUser(), $this->getPass());
        if (method_exists($this->ftp, 'pasive')) {
            $this->ftp->pasive(true);
        }
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
        $this->assertTrue(empty($this->ftp->listDirContents($this->my_dir)), "Directory is empty");
        $this->assertTrue($this->ftp->changeDir($this->my_dir), "Change dir successfull");
        $this->assertTrue($this->ftp->upload($this->my_file_path . DIRECTORY_SEPARATOR . $this->my_file_name, $this->my_file_name), "Upload file");
        $this->assertContains($this->my_file_name, $this->ftp->listFiles(), "The file is into directory");
        //$this->assertFalse(empty($this->ftp->listDirContents('.')));
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

    public function testStatFile()
    {
        $this->assertTrue($this->ftp->changeDir($this->my_dir), "Change to the selected dir");
        $stat = $this->ftp->stat($this->my_file_name);
        $this->assertIsArray($stat, "Check than stat is an array");
        $this->assertArrayHasKey("name", $stat, "Stat have name");
        $this->assertArrayHasKey("modify", $stat, "Stat have last modified time");
        //$this->assertArrayHasKey("size", $stat, "Stat have size");
    }

    public function testRenameFile()
    {
        $this->assertTrue($this->ftp->changeDir($this->my_dir), "Change to the desired dir");
        $new_file_name = $this->my_file_name . "." . md5(get_called_class()) . ".old";
        $this->assertTrue($this->ftp->rename($this->my_file_name, $new_file_name), "Rename the file");
    }

    public function testDownloadFile()
    {
        $this->assertTrue($this->ftp->changeDir($this->my_dir), "change to the desired file");
        $new_file_name = $this->my_file_name . "." . md5(get_called_class()) . ".old";
        //$new_file_name = $this->my_file_name . ".old";
        $this->assertTrue($this->ftp->download($new_file_name, $this->my_file_path . DIRECTORY_SEPARATOR . $new_file_name), "Download the file");
    }

    public function testListFolder()
    {
        $contents = $this->ftp->listDirContents($this->my_dir);
        $this->assertFalse(empty($contents), "The dir contents is not empty using mode 1");
        $this->assertTrue($this->ftp->changeDir($this->my_dir), "Change to the desired file");
        $contents2 = $this->ftp->listDirContents();
        $this->assertFalse(empty($contents2), "The dir contents is not empty using mode 2");
        $this->assertSameSize($contents, $contents2, "mode 1 and 2 have the same results");
    }

    public function testDeleteFile()
    {
        $this->assertTrue($this->ftp->changeDir($this->my_dir), "change to the desired dir");
        $this->assertTrue($this->ftp->delete($this->my_file_name . "." . md5(get_called_class()) . ".old"), "Delete the file");
        $this->assertTrue(unlink($this->my_file_path . DIRECTORY_SEPARATOR . $this->my_file_name . "." . md5(get_called_class()) . ".old"), "Delete local file");
        $this->assertTrue(empty($this->ftp->listDirContents()), "The dir is empty");
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