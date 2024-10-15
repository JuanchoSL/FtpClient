# FtpClient

## Description

Little methods collection in order to create connections to remote file servers

## How to use
You needs to know the remote file server type connection in order to select the rigth instance

* Ftp
* Ftps over SSL
* Sftp over SSH


### Instance

#### Direct instantiation
```php
$connection = new Ftp();
```
#### Using a factory
```php
$connection = EngineFactory::getInstance(EnginesEnum::FTP);
```

### Connect
```php
$connection->connect("ftp.servername.com", 21);
```

### Authenticate
```php
$connection->login("username", "password");
```

### Operate
You have a few generic methods and can operate over directories and files

#### Generic

| Method       | Description |
| ------------ |-------------|
| connect      | Connect to a server |
| isConnected  | Check if is connected |
| login        | Login into server    |
| disconnect   | Disconnect from server |
| isLogged     | Check if is logged    |
| isDir        | Check if is a dir    |
| chmod        | Change permissions    |
| mode         | Retrieve permissions    |
| rename       | Rename file or dir    |

#### Directories

| Method       | Description |
| ------------ |-------------|
| listDirContents      | For list the directory contents     |
| listDirs      | For list the directory subdirs     |
| listFiles     | For list the directory files     |
| currentDir   | The current directory path     |
| createDir    | For create a new directory     |
| changeDir    | For change to the selected directory     |
| parentDir    | For change to the parent directory     |
| deleteDir    | For remove a directory     |

#### Files

| Method        | Description |
| ------------- |-------------|
| upload        | For upload a file     |
| write         | For write contents into remote file     |
| download      | For download a file     |
| read          | For read contents from remote file     |
| delete        | For delete a file     |
| filesize      | For retrieve the filesize     |
| lastModified  | For retrieve the file last modification      |

#### For FTP and FTPS

| Method       | Description |
| ------------ |-------------|
| pasive       | true or false for apply pasive mode     |
| system       | The server operative system     |

#### For SFTP

| Method         | Description |
| -------------- |-------------|
| getFingerprint | Retrieve and return the server fingerprint   |
| getNegotiation | Retrieve and return the negotiation methods  |