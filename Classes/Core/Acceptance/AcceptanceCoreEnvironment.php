<?php

namespace Noerdisch\TestingFramework\Core\Acceptance;

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

use Codeception\Event\SuiteEvent;
use Codeception\Events;
use Codeception\Extension;
use TYPO3\CMS\Core\Cache\Backend\NullBackend;
use TYPO3\CMS\Styleguide\TcaDataGenerator\Generator;
use Noerdisch\TestingFramework\Core\Testbase;

/**
 * This codeception extension creates a full TYPO3 instance within
 * typo3temp. Own acceptance test suites may extend from this class
 * and change the properties. This can be used to not copy the whole
 * bootstrapTypo3Environment() method but reuse it instead.
 */
class AcceptanceCoreEnvironment extends Extension
{
    /**
     * Additional core extensions to load.
     *
     * To be used in own acceptance test suites.
     *
     * If a test suite needs additional core extensions, for instance as a dependency of
     * an extension that is tested, those core extension names can be noted here and will
     * be loaded.
     *
     * @var array
     */
    protected $coreExtensionsToLoad = [];

    /**
     * Array of test/fixture extensions paths that should be loaded for a test.
     *
     * To be used in own acceptance test suites.
     *
     * Given path is expected to be relative to your document root, example:
     *
     * array(
     *   'typo3conf/ext/some_extension/Tests/Functional/Fixtures/Extensions/test_extension',
     *   'typo3conf/ext/base_extension',
     * );
     *
     * Extensions in this array are linked to the test instance, loaded
     * and their ext_tables.sql will be applied.
     *
     * @var array
     */
    protected $testExtensionsToLoad = [];

    /**
     * Array of test/fixture folder or file paths that should be linked for a test.
     *
     * To be used in own acceptance test suites.
     *
     * array(
     *   'link-source' => 'link-destination'
     * );
     *
     * Given paths are expected to be relative to the test instance root.
     * The array keys are the source paths and the array values are the destination
     * paths, example:
     *
     * array(
     *   'typo3/sysext/impext/Tests/Functional/Fixtures/Folders/fileadmin/user_upload' =>
     *   'fileadmin/user_upload',
     *   'typo3conf/ext/my_own_ext/Tests/Functional/Fixtures/Folders/uploads/tx_myownext' =>
     *   'uploads/tx_myownext'
     * );
     *
     * To be able to link from my_own_ext the extension path needs also to be registered in
     * property $testExtensionsToLoad
     *
     * @var array
     */
    protected $pathsToLinkInTestInstance = [];

    /**
     * This configuration array is merged with TYPO3_CONF_VARS
     * that are set in default configuration and factory configuration
     *
     * To be used in own acceptance test suites.
     *
     * @var array
     */
    protected $configurationToUseInTestInstance = [];

    /**
     * Array of folders that should be created inside the test instance document root.
     *
     * To be used in own acceptance test suites.
     *
     * Per default the following folder are created
     * /fileadmin
     * /typo3temp
     * /typo3conf
     * /typo3conf/ext
     * /uploads
     *
     * To create additional folders add the paths to this array. Given paths are expected to be
     * relative to the test instance root and have to begin with a slash. Example:
     *
     * array(
     *   'fileadmin/user_upload'
     * );
     *
     * @var array
     */
    protected $additionalFoldersToCreate = [];

    /**
     * XML database fixtures to be loaded into database.
     *
     * Given paths are expected to be relative to your document root.
     *
     * @var array
     */
    protected $xmlDatabaseFixtures = [
        'EXT:noerdisch-testing-framework/Resources/Core/Acceptance/Fixtures/be_users.xml',
        'EXT:noerdisch-testing-framework/Resources/Core/Acceptance/Fixtures/be_sessions.xml',
        'EXT:noerdisch-testing-framework/Resources/Core/Acceptance/Fixtures/be_groups.xml',
        'EXT:noerdisch-testing-framework/Resources/Core/Acceptance/Fixtures/sys_category.xml',
        'EXT:noerdisch-testing-framework/Resources/Core/Acceptance/Fixtures/tx_extensionmanager_domain_model_extension.xml',
        'EXT:noerdisch-testing-framework/Resources/Core/Acceptance/Fixtures/tx_extensionmanager_domain_model_repository.xml',
    ];

    /**
     * Events to listen to
     */
    public static $events = [
        Events::SUITE_BEFORE => 'bootstrapTypo3Environment',
        Events::TEST_AFTER => 'cleanupTypo3Environment'
    ];

    /**
     * Handle SUITE_BEFORE event.
     *
     * Create a full standalone TYPO3 instance within typo3temp/var/tests/acceptance,
     * create a database and create database schema.
     *
     * @param SuiteEvent $suiteEvent
     */
    public function bootstrapTypo3Environment(SuiteEvent $suiteEvent)
    {
        /** @var Testbase $testBase */
        $testBase = new Testbase();
        $testBase->initializeClassLoader();
        $testBase->initializeCodeceptionAutoloader();
        $testBase->enableDisplayErrors();
        $testBase->defineBaseConstants();
        $testBase->defineSitePath();
        $testBase->initializeGlobalVariables();
        $testBase->defineOriginalRootPath();
        $testBase->createDirectory(ORIGINAL_ROOT . 'typo3temp/var/tests/acceptance');
        $testBase->createDirectory(ORIGINAL_ROOT . 'typo3temp/var/transient');

        $instancePath = ORIGINAL_ROOT . 'typo3temp/var/tests/acceptance';

        $testBase->defineTypo3ModeBe();
        $testBase->setTypo3TestingContext();
        $testBase->removeOldInstanceIfExists($instancePath);
        // Basic instance directory structure
        $testBase->createDirectory($instancePath . '/fileadmin');
        $testBase->createDirectory($instancePath . '/typo3temp/var/transient');
        $testBase->createDirectory($instancePath . '/typo3temp/assets');
        $testBase->createDirectory($instancePath . '/typo3conf/ext');
        $testBase->createDirectory($instancePath . '/uploads');
        // Additionally requested directories
        foreach ($this->additionalFoldersToCreate as $directory) {
            $testBase->createDirectory($instancePath . '/' . $directory);
        }
        $testBase->createLastRunTextfile($instancePath);
        $testBase->setUpInstanceCoreLinks($instancePath);
        // ext:styleguide is always loaded
        $testExtensionsToLoad = array_merge(
            [ 'typo3conf/ext/styleguide' ],
            $this->testExtensionsToLoad
        );
        $testBase->linkTestExtensionsToInstance($instancePath, $testExtensionsToLoad);
        $testBase->linkPathsInTestInstance($instancePath, $this->pathsToLinkInTestInstance);
        $localConfiguration['DB'] = $testBase->getOriginalDatabaseSettingsFromEnvironmentOrLocalConfiguration();
        $originalDatabaseName = $localConfiguration['DB']['database'];
        // Append the unique identifier to the base database name to end up with a single database per test case
        $localConfiguration['DB']['database'] = $originalDatabaseName . '_at';
        $testBase->setDatabaseName($localConfiguration['DB']['database']);
        $testBase->testDatabaseNameIsNotTooLong($originalDatabaseName, $localConfiguration);
        // Set some hard coded base settings for the instance. Those could be overruled by
        // $this->configurationToUseInTestInstance if needed again.
        $localConfiguration['BE']['debug'] = true;
        $localConfiguration['BE']['lockHashKeyWords'] = '';
        $localConfiguration['BE']['installToolPassword'] = $this->getInstallToolPassword();
        $localConfiguration['BE']['loginSecurityLevel'] = 'normal';
        $localConfiguration['SYS']['isInitialInstallationInProgress'] = false;
        $localConfiguration['SYS']['isInitialDatabaseImportDone'] = true;
        $localConfiguration['SYS']['displayErrors'] = false;
        $localConfiguration['SYS']['debugExceptionHandler'] = '';
        $localConfiguration['SYS']['trustedHostsPattern'] = 'localhost:8000';
        $localConfiguration['SYS']['encryptionKey'] = 'iAmInvalid';
        // @todo: This sql_mode should be enabled as soon as styleguide and dataHandler can cope with it
        $localConfiguration['SYS']['setDBinit'] = 'SET SESSION sql_mode = \'STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_VALUE_ON_ZERO,NO_ENGINE_SUBSTITUTION,NO_ZERO_DATE,NO_ZERO_IN_DATE,ONLY_FULL_GROUP_BY\';';
        $localConfiguration['SYS']['caching']['cacheConfigurations']['extbase_object']['backend'] = NullBackend::class;
        $testBase->setUpLocalConfiguration($instancePath, $localConfiguration, $this->configurationToUseInTestInstance);
        $defaultCoreExtensionsToLoad = [
            'core',
            'beuser',
            'extbase',
            'fluid',
            'filelist',
            'extensionmanager',
            'lang',
            'setup',
            'rsaauth',
            'saltedpasswords',
            'backend',
            'about',
            'belog',
            'install',
            't3skin',
            'frontend',
            'recordlist',
            'reports',
            'sv',
            'scheduler',
            'tstemplate',
        ];
        $testBase->setUpPackageStates($instancePath, $defaultCoreExtensionsToLoad, $this->coreExtensionsToLoad, $testExtensionsToLoad);
        $testBase->setUpBasicTypo3Bootstrap($instancePath);
        $testBase->initializeDefaultConfiguration();
        $testBase->setUpTestDatabase($localConfiguration['DB']['database'], $originalDatabaseName);
        $testBase->loadExtensionTables();
        $testBase->createDatabaseStructure();

        // Unset a closure or phpunit kicks in with a 'serialization of \Closure is not allowed'
        // Alternative solution:
        // unset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['extbase']);
        $suite = $suiteEvent->getSuite();
        $suite->setBackupGlobals(false);

        foreach ($this->xmlDatabaseFixtures as $fixture) {
            $testBase->importXmlDatabaseFixture($fixture);
        }

        $testBase->initializeBackendUser();
        /** @var Generator $styleguideGenerator */
        //$styleguideGenerator = new Generator();
        //$styleguideGenerator->create();

        // @todo: Find out why that is needed to execute the first test successfully
        $testBase->cleanupTypo3Environment();
    }

    /**
     * Method executed after each test
     *
     * @return void
     */
    public function cleanupTypo3Environment()
    {
        /** @var Testbase $testBase */
        $testBase = new Testbase();
        $localConfiguration['DB'] = $testBase->getOriginalDatabaseSettingsFromEnvironmentOrLocalConfiguration();
        $testBase->setDatabaseName($localConfiguration['DB']['database'] . '_at');
        $testBase->cleanupTypo3Environment();
    }

    /**
     * Set install tool password. This is either a salted password
     * of a given typo3InstallToolPassword environment variable, or
     * a hardcoded value that does not allow login.
     *
     * @return string
     */
    protected function getInstallToolPassword()
    {
        $password = getenv('typo3InstallToolPassword');
        if (!empty($password)) {
            //$saltFactory = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance(null, 'BE');
            return 'adminpassword';//$saltFactory->getHashedPassword($password);
        } else {
            return '$P$notnotnotnotnotnot.validvalidva';
        }
    }
}
