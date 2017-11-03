<p align="center">
    <img src="https://cdn.rawgit.com/noerdisch/testing-framework/c60e5753/ext_icon.svg" height="100">
</p>

[![Packagist](https://img.shields.io/packagist/l/noerdisch/testing-framework.svg?style=flat-square)](https://packagist.org/packages/noerdisch/testing-framework)
[![Packagist](https://img.shields.io/packagist/v/noerdisch/testing-framework.svg?style=flat-square)](https://packagist.org/packages/noerdisch/testing-framework)
[![Twitter Follow](https://img.shields.io/twitter/follow/noerdisch.svg?style=social&label=Follow&style=flat-square)](https://twitter.com/noerdisch)


# Nœrdisch testing framework for core and extensions


## Introduction
The nœrdisch testing framework is a backport of the TYPO3 testing framework that has been developed by the TYPO3 core team. The TYPO3 testing framework only supports the TYPO3 version 8 LTS and the TYPO3 9, which is in development at the moment.

The main goal of this framework is it to be TYPO3 7.6 compatible. Since version 8 the developers can use the Doctrine for the database handling and this makes it impossible to use the TYPO3 testing framework for 7.6.
At the moment we don`t care for beeing compatible with 7.6 and the higher versions. So please use the TYPO3 framework for the current and future releases of TYPO3 CMS. This is just build for the purpose of TYPO3 7.6!  

The testing framework is a straight and slim set of classes and configuration to test TYPO3 extensions. This framework is used a base to execute unit, functional and acceptance tests within the TYPO3 extension ecosystem.


## Installation

Not every TYPO3 instance of verion 7.6 is composer driven. So you have the opportunity to install nœrdisch testing framework as TYPO3 extension. You can choose the good old TYPO3 CMS extension manager or use composer.

### Via extension manager

1. First download the nœrdisch testing framework as zip file.
2. Login to you TYPO3 backend and open the extension manager
3. Click on "Upload Extension" (little button below the dropdown)
4. Choose your zip file and click upload

Now your testing framework should be installed.

### Via composer

Since TYPO3 supports composer it is really easy to install extensions via CLI.

```bash
composer require noerdisch/testing-framework
```


## Usage

We mentioned already that the framework enables you to easily test your extensions and even the TYPO3 core via unit, functional and acceptance tests. At the current beta state the extension is able to test unit and functional tests.


### Unit tests

You don`t have to install phpunit on your machine. The testing framework comes with phpunit and codeception.
So after installing the testing framework you are ready to go. For excecuting all core unit tests just enter the following command from your TYPO3 instance root.

```bash
./typo3conf/ext/noerdisch-testing-framework/bin/phpunit -c typo3conf/ext/noerdisch-testing-framework/Resources/Core/Build/UnitTests.xml
```

You can also execute single unit tests or only for one extension.
You only need to append the path to the test file or the extension folder.

```bash
./typo3conf/ext/noerdisch-testing-framework/bin/phpunit -c typo3conf/ext/noerdisch-testing-framework/Resources/Core/Build/UnitTests.xml typo3conf/ext/<YourExtensionName>/Tests/Unit
```

```bash
./typo3conf/ext/noerdisch-testing-framework/bin/phpunit -c typo3conf/ext/noerdisch-testing-framework/Resources/Core/Build/UnitTests.xml typo3conf/ext/<YourExtensionName>/Tests/Unit/SingleTest.php
```


### Functional tests

Functional test are also executed via phpunit. Functional tests need database credentials, therefore you need to passthru some parameter.

* typo3DatabaseName
* typo3DatabaseUsername
* typo3DatabaseHost
* typo3InstallToolPassword
* typo3DatabasePort (when not using the default port 3306)
* TYPO3_PATH_ROOT (when calling the script outsite the TYPO3 instance)

```bash
typo3DatabaseName="fooDB" typo3DatabaseUsername="typo3" typo3DatabasePassword="supersecret" typo3DatabaseHost="127.0.0.1" typo3InstallToolPassword="supersecret" ./typo3conf/ext/noerdisch-testing-framework/bin/phpunit -c typo3conf/ext/noerdisch-testing-framework/Resources/Core/Build/FunctionalTests.xml
```

Of course you can also execute single functional tests. Unit tests and functional tests works CLI wise the same. Just append the path to an extension or a single functional test.

```bash
typo3DatabaseName="fooDB" typo3DatabaseUsername="typo3" typo3DatabasePassword="supersecret" typo3DatabaseHost="127.0.0.1" typo3InstallToolPassword="supersecret" ./typo3conf/ext/noerdisch-testing-framework/bin/phpunit -c typo3conf/ext/noerdisch-testing-framework/Resources/Core/Build/FunctionalTests.xml typo3/sysext/core/Tests/Functional/Page
```