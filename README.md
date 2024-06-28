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
| isConnected  | Check if is connected |
| isLogged     | Check if is logged    |

#### Directories

| Method       | Description |
| ------------ |-------------|
| listDir      | For list the directory contents     |
| currentDir   | The current directory path     |
| createDir    | For create a new directory     |
| changeDir    | For change to the selected directory     |
| parentDir    | For change to the parent directory     |
| deleteDir    | For remove a directory     |
| renameDir    | For rename or move a directory     |

#### Files

| Method        | Description |
| ------------- |-------------|
| upload        | For upload a file     |
| write         | For write contents into remote file     |
| download      | For download a file     |
| read          | For read contents from remote file     |
| rename        | For rename or move a file     |
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