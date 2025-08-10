# FtpClient

## Description

Little methods collection in order to create connections to remote file servers

## How to use

You needs to know the remote file server type connection in order to select the rigth instance

- Ftp
- Ftps over SSL
- Sftp over SSH

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

#### Authenticate with public key

For use public and private key over Sftp connection, set the credentials before login

```php
$connection->setCredentials($absolute_path_to_publickey,$absolute_path_to_privatekey,$private_key_password);
```

### Operate

You have a few generic methods and can operate over directories and files

#### Generic

| Method      | Description            |
| ----------- | ---------------------- |
| connect     | Connect to a server    |
| isConnected | Check if is connected  |
| login       | Login into server      |
| disconnect  | Disconnect from server |
| isLogged    | Check if is logged     |
| isDir       | Check if is a dir      |
| chmod       | Change permissions     |
| mode        | Retrieve permissions   |
| rename      | Rename file or dir     |

#### Directories

| Method          | Description                          |
| --------------- | ------------------------------------ |
| listDirContents | For list the directory contents      |
| listDirs        | For list the directory subdirs       |
| listFiles       | For list the directory files         |
| currentDir      | The current directory path           |
| createDir       | For create a new directory           |
| changeDir       | For change to the selected directory |
| parentDir       | For change to the parent directory   |
| deleteDir       | For remove a directory               |

#### Files

| Method       | Description                             |
| ------------ | --------------------------------------- |
| upload       | For upload a file                       |
| write        | For write contents into remote file     |
| download     | For download a file                     |
| read         | For read contents from remote file      |
| delete       | For delete a file                       |
| filesize     | For retrieve the filesize               |
| lastModified | For retrieve the file last modification |

#### For FTP and FTPS

| Method | Description                         |
| ------ | ----------------------------------- |
| pasive | true or false for apply pasive mode |
| system | The server operative system         |

#### For SFTP

| Method         | Description                                      |
| -------------- | ------------------------------------------------ |
| getFingerprint | Retrieve and return the server fingerprint       |
| getNegotiation | Retrieve and return the negotiation methods      |
| setCredentials | Set public and private key for use on connection |

## Adapters

You can use a wrapper in order to call the library functions using the native names from few OS

| Client          | Linux        | Windows      | Description                             |
| --------------- | ------------ | ------------ | --------------------------------------- |
| chmod           | chmod        | icacls       | Change permissions                      |
| mode            | stat         | cacls        | Retrieve permissions                    |
| rename          | mv           | move         | Rename file or dir                      |
| listDirContents | ls           | dir          | For list the directory contents         |
| listDirs        | lsDirs       | dirDirs      | For list the directory subdirs          |
| listFiles       | lsFiles      | dirFiles     | For list the directory files            |
| currentDir      | pwd          | cd           | The current directory path              |
| createDir       | mkdir        | mkdir        | For create a new directory              |
| changeDir       | cd           | cd           | For change to the selected directory    |
| parentDir       | cdUp         | cdUp         | For change to the parent directory      |
| deleteDir       | rm           | rmdir        | For remove a directory                  |
| upload          | put          | put          | For upload a file                       |
| write           | put          | put          | For write contents into remote file     |
| download        | get          | get          | For download a file                     |
| read            | get          | get          | For read contents from remote file      |
| delete          | rm           | del          | For delete a file                       |
| filesize        | filesize     | filesize     | For retrieve the filesize               |
| lastModified    | lastModified | lastModified | For retrieve the file last modification |
