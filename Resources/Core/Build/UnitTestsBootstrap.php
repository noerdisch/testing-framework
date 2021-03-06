<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * This file is defined in UnitTests.xml and called by phpunit
 * before instantiating the test suites, it must also be included
 * with phpunit parameter --bootstrap if executing single test case classes.
 *
 * Run whole core unit test suite, example:
 * - cd /var/www/t3master/foo  # Document root of TYPO3 CMS instance (location of index.php)
 * - ./typo3conf/ext/noerdisch-testing-framework/bin/phpunit -d memory_limit=1024M
 *      -c typo3conf/ext/noerdisch-testing-framework/Resources/Core/Build/UnitTests.xml
 *
 * Run single test case, example:
 * - cd /var/www/t3master/foo  # Document root of TYPO3 CMS instance (location of index.php)
 * - ./typo3conf/ext/noerdisch-testing-framework/bin/phpunit -d memory_limit=1024M
 *      -c typo3conf/ext/noerdisch-testing-framework/Resources/Core/Build/UnitTests.xml
 *      typo3/sysext/core/Tests/Unit/DataHandling/DataHandlerTest.php
 */

call_user_func(function () {
    /** @var \Noerdisch\TestingFramework\Core\Testbase $testBase */
    $testBase = new Noerdisch\TestingFramework\Core\Testbase();
    $testBase->initializeClassLoader();
    $testBase->enableDisplayErrors();
    $testBase->defineSitePath();
    $testBase->defineTypo3ModeBe();
    $testBase->setTypo3TestingContext();
    $testBase->definePackagesPath();
    $testBase->createDirectory(PATH_site . 'typo3conf/ext');
    $testBase->createDirectory(PATH_site . 'typo3temp/assets');
    $testBase->createDirectory(PATH_site . 'typo3temp/var/tests');
    $testBase->createDirectory(PATH_site . 'typo3temp/var/transient');
    $testBase->createDirectory(PATH_site . 'uploads');

    // disable TYPO3_DLOG
    define('TYPO3_DLOG', false);

    // Retrieve an instance of class loader and inject to core bootstrap
    $classLoaderFilepath = TYPO3_PATH_PACKAGES . 'autoload.php';
    if (!file_exists($classLoaderFilepath)) {
        die('ClassLoader can\'t be loaded. Please check your path or set an environment variable \'TYPO3_PATH_ROOT\' to your root path.');
    }
    $classLoader = require $classLoaderFilepath;
    \TYPO3\CMS\Core\Core\Bootstrap::getInstance()
        ->initializeClassLoader($classLoader)
        ->baseSetup();

    // Initialize default TYPO3_CONF_VARS
    $configurationManager = new \TYPO3\CMS\Core\Configuration\ConfigurationManager();
    $GLOBALS['TYPO3_CONF_VARS'] = $configurationManager->getDefaultConfiguration();
    // Avoid failing tests that rely on HTTP_HOST retrieval
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] = '.*';

    \TYPO3\CMS\Core\Core\Bootstrap::getInstance()
        ->disableCoreCache()
        ->initializeCachingFramework()
        // Set all packages to active
        ->initializePackageManagement(\TYPO3\CMS\Core\Package\UnitTestPackageManager::class);

    if (!\TYPO3\CMS\Core\Core\Bootstrap::usesComposerClassLoading()) {
        // Dump autoload info if in non composer mode
        \TYPO3\CMS\Core\Core\ClassLoadingInformation::dumpClassLoadingInformation();
        \TYPO3\CMS\Core\Core\ClassLoadingInformation::registerClassLoadingInformation();
    }
});