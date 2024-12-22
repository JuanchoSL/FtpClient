<?php

namespace JuanchoSL\FtpClient\Tests\Functional\WinClient;

use JuanchoSL\FtpClient\Adapters\WinClientAdapter;
use JuanchoSL\FtpClient\Contracts\ConnectionInterface;
use PHPUnit\Framework\TestCase;

abstract class AbstractFtp extends TestCase
{

    protected $ftp = null;
    protected $adapter = null;
    protected $my_dir = "";
    protected $my_file_path = "";
    protected $my_file_name = 'test.txt';

    abstract protected function getInstance(): ConnectionInterface;
    abstract protected function getHost();
    abstract protected function getPort();
    abstract protected function getUser();
    abstract protected function getPass();
    public function setUp(): void
    {
        $this->my_file_path = implode(DIRECTORY_SEPARATOR, [dirname(__DIR__, 3), 'etc']);
        $this->my_dir = "juancho-test-" . date('Y-m-d') . '-functional-windows';
        if (empty($this->adapter)) {
            if (empty($this->ftp)) {
                $this->ftp = $this->getInstance();
                $connect = $this->ftp->connect($this->getHost(), $this->getPort());
                $this->assertTrue($connect, "Check conection");
                $login = $this->ftp->login($this->getUser(), $this->getPass());
                $this->assertTrue($login, "check login");
            }
            $this->adapter = new WinClientAdapter($this->ftp);
        }
    }
    public function tearDown(): void
    {
        if (isset($this->ftp) && $this->ftp->isConnected()) {
            $this->ftp->disconnect();
            unset($this->ftp);
            unset($this->adapter);
        }
    }

    public function testReadable()
    {
        $root_dir = $this->adapter->cd();
        $this->assertFalse(empty($root_dir), "Check that current dir is not empty");
    }

    public function testCreateDir()
    {
        $this->assertTrue($this->adapter->mkdir($this->my_dir), "Check that directory is created");
        $this->assertContains($this->my_dir, $this->adapter->dirDirs(), "The directory is into directory");
    }

    public function testChangeDir()
    {
        $this->assertTrue($this->adapter->cd($this->my_dir), "Directory has been changed");
        $this->assertStringEndsWith($this->my_dir, $this->adapter->cd(), "Current directory is the selected directory");
    }

    public function testUploadFile()
    {
        //echo $this->my_dir;exit;
        $this->assertTrue(empty($this->adapter->dir($this->my_dir)), "Directory is empty");
        $this->assertTrue($this->adapter->cd($this->my_dir), "Change dir {$this->my_dir} successfull");
        $this->assertTrue($this->adapter->put($this->my_file_path . DIRECTORY_SEPARATOR . $this->my_file_name, $this->my_file_name), "Upload file");
        $this->assertContains($this->my_file_name, $this->adapter->dirFiles(), "The file is into directory");
        //$this->assertFalse(empty($this->adapter->listDirContents($this->my_dir)));
    }
    public function testRead()
    {
        $this->assertTrue($this->adapter->cd($this->my_dir), "Change dir successfull");
        $content = $this->adapter->get($this->my_file_name);
        $this->assertNotFalse($content);
        $this->assertEquals(file_get_contents($this->my_file_path . DIRECTORY_SEPARATOR . $this->my_file_name), $content);
    }

    public function testWrite()
    {
        $this->assertTrue($this->adapter->cd($this->my_dir), "Change dir successfull");
        $this->assertTrue($this->adapter->put("Esto es un texto nuevo", $this->my_file_name));
    }

    public function testSizeFile()
    {
        $this->assertTrue($this->adapter->cd($this->my_dir), "Change to the selected dir");
        $size = $this->adapter->filesize($this->my_file_name);
        $this->assertIsInt($size, "Size is an integer");
        $this->assertGreaterThanOrEqual(0, $size, "Size is greater or equals than 0");
    }
    public function testModifiedFile()
    {
        $this->assertTrue($this->adapter->cd($this->my_dir), "Change to the selected dir");
        $date = $this->adapter->lastModified($this->my_file_name);
        $this->assertInstanceOf(\DateTimeInterface::class, $date, "Check than last modified date is a instance");
        $this->assertEquals(date('Y-m-d'), $date->format("Y-m-d"), "Date from last modified is the today date");
    }

    public function testRenameFile()
    {
        $this->assertTrue($this->adapter->cd($this->my_dir), "Change to the desired dir");
        $new_file_name = $this->my_file_name . ".old";
        $this->assertTrue($this->adapter->move($this->my_file_name, $new_file_name), "Rename the file");
    }

    public function testDownloadFile()
    {
        $this->assertTrue($this->adapter->cd($this->my_dir), "change to the desired file");
        $new_file_name = $this->my_file_name . ".old";
        $this->assertTrue($this->adapter->get($new_file_name, $this->my_file_path . DIRECTORY_SEPARATOR . $new_file_name), "Download the file");
    }

    public function testListFolder()
    {
        $contents = $this->adapter->dir($this->my_dir);
        $this->assertFalse(empty($contents), "The dir contents is not empty using mode 1");
        $this->assertTrue($this->adapter->cd($this->my_dir), "Change to the desired file");
        $contents2 = $this->adapter->dir();
        $this->assertFalse(empty($contents2), "The dir contents is not empty using mode 2");
        $this->assertSameSize($contents, $contents2, "mode 1 and 2 have the same results");
    }

    public function testDeleteFile()
    {
        $this->assertTrue($this->adapter->cd($this->my_dir), "change to the desired dir");
        $this->assertTrue($this->adapter->del($this->my_file_name . ".old"), "Delete the file");
        $this->assertTrue(unlink($this->my_file_path . DIRECTORY_SEPARATOR . $this->my_file_name . ".old"), "Delete local file");
        $this->assertTrue(empty($this->adapter->dir()), "The dir is empty");
    }

    public function testDeleteDir()
    {
        $this->assertTrue($this->adapter->rmdir($this->my_dir), "Delete directory");
    }

}