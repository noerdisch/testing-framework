<?php

namespace Noerdisch\TestingFramework\Core;

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

use Codeception\Util\Autoload;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\ClassLoadingInformation;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This is a helper class used by unit, functional and acceptance test
 * environment builders.
 * It contains methods to create test environments.
 *
 * This class is for internal use only and may change wihtout further notice.
 *
 * Use the classes "UnitTestCase", "FunctionalTestCase" or "AcceptanceCoreEnvironment"
 * to indirectly benefit from this class in own extensions.
 */
class Testbase
{
    /**
     * This class must be called in CLI environment as a security measure
     * against path disclosures and other stuff. Check this within
     * constructor to make sure this check can't be circumvented.
     */
    public function __construct()
    {
        // Ensure cli only as security measure
        if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
            die('This script supports command line usage only. Please check your command.');
        }
    }

    /**
     * The codeception is installed not in the TYPO3 CMS scope and therefore we need to add
     * the TYPO3 namespace to the Codeception.
     *
     * Luckily Codeception offers a util for autoloading.
     * So we can add all namespaces to codeception we need.
     *
     * @return void
     */
    public function initializeCodeceptionAutoloader()
    {
        $webRoot = $this->getWebRoot();
        Autoload::addNamespace('TYPO3\\CMS\\Core', $webRoot . '/typo3/sysext/core/Classes');
        Autoload::addNamespace(
            'TYPO3\\CMS\\Saltedpasswords',
            $webRoot . '/typo3/sysext/saltedpasswords/Classes'
        );
    }

    /**
     * Makes sure error messages during the tests get displayed no matter what is set in php.ini.
     *
     * @return void
     */
    public function enableDisplayErrors()
    {
        @ini_set('display_errors', 1);
    }

    /**
     * Defines a list of basic constants that are used by GeneralUtility and other
     * helpers during tests setup. Those are sanitized in SystemEnvironmentBuilder
     * to be not defined again.
     *
     * @return void
     * @see SystemEnvironmentBuilder::defineBaseConstants()
     */
    public function defineBaseConstants()
    {
        // This version, branch and copyright
        define('TYPO3_version', '7.6.23');
        define('TYPO3_branch', '7.6');
        define('TYPO3_copyright_year', '1998-2017');

        // A null, a tabulator, a linefeed, a carriage return, a substitution, a CR-LF combination
        defined('NUL') ?: define('NUL', chr(0));
        defined('TAB') ?: define('TAB', chr(9));
        defined('LF') ?: define('LF', chr(10));
        defined('CR') ?: define('CR', chr(13));
        defined('SUB') ?: define('SUB', chr(26));
        defined('CRLF') ?: define('CRLF', CR . LF);

        if (!defined('TYPO3_OS')) {
            // Operating system identifier
            // Either "WIN" or empty string
            $typoOs = '';
            if (!stristr(PHP_OS, 'darwin') && !stristr(PHP_OS, 'cygwin') && stristr(PHP_OS, 'win')) {
                $typoOs = 'WIN';
            }
            define('TYPO3_OS', $typoOs);
        }
    }

    /**
     * Initialize several globals variables
     */
    public function initializeGlobalVariables()
    {
        // Unset variable(s) in global scope (security issue #13959)
        $GLOBALS['TYPO3_MISC'] = [];
        $GLOBALS['T3_VAR'] = [];
        $GLOBALS['T3_SERVICES'] = [];
        $GLOBALS['T3_VAR']['getUserObj'] = [];
    }

    /**
     * Defines the PATH_site and PATH_thisScript constant and sets $_SERVER['SCRIPT_NAME'].
     * For unit tests only
     *
     * @return void
     */
    public function defineSitePath()
    {
        define('PATH_site', $this->getWebRoot());
        define('PATH_thisScript', PATH_site . 'typo3/cli_dispatch.phpsh');
        $_SERVER['SCRIPT_NAME'] = PATH_thisScript;

        if (!file_exists(PATH_thisScript)) {
            $this->exitWithMessage('Unable to determine path to entry script. Please check your path or set an environment variable \'TYPO3_PATH_ROOT\' to your root path.');
        }
    }

    public function definePackagesPath()
    {
        define('TYPO3_PATH_PACKAGES', $this->getPackagesPath());
    }

    /**
     * Defines the constant ORIGINAL_ROOT for the path to the original TYPO3 document root.
     * For functional / acceptance tests only
     * If ORIGINAL_ROOT already is defined, this method is a no-op.
     *
     * @return void
     */
    public function defineOriginalRootPath()
    {
        if (!defined('ORIGINAL_ROOT')) {
            define('ORIGINAL_ROOT', $this->getWebRoot());
        }

        if (!file_exists(ORIGINAL_ROOT . 'typo3/cli_dispatch.phpsh')) {
            $this->exitWithMessage('Unable to determine path to entry script. Please check your path or set an environment variable \'TYPO3_PATH_ROOT\' to your root path.');
        }
    }

    /**
     * Define TYPO3_MODE to BE
     *
     * @return void
     */
    public function defineTypo3ModeBe()
    {
        define('TYPO3_MODE', 'BE');
    }

    /**
     * Sets the environment variable TYPO3_CONTEXT to testing.
     *
     * @return void
     */
    public function setTypo3TestingContext()
    {
        putenv('TYPO3_CONTEXT=Testing');
    }

    /**
     * Creates directories, recursively if required.
     *
     * @param string $directory Absolute path to directories to create
     * @return void
     * @throws \Exception
     */
    public function createDirectory($directory)
    {
        if (is_dir($directory)) {
            return;
        }
        @mkdir($directory, 0777, true);
        clearstatcache();
        if (!is_dir($directory)) {
            throw new \Exception('Directory "' . $directory . '" could not be created', 1404038665);
        }
    }

    /**
     * Checks whether given test instance exists in path and is younger than some minutes.
     * Used in functional tests
     *
     * @param string $instancePath Absolute path to test instance
     * @return bool
     */
    public function recentTestInstanceExists($instancePath)
    {
        if (@file_get_contents($instancePath . '/last_run.txt') <= (time() - 300)) {
            return false;
        } else {
            // Test instance exists and is pretty young -> re-use
            return true;
        }
    }

    /**
     * Remove test instance folder structure if it exists.
     * This may happen if a functional test before threw a fatal or is too old
     *
     * @param string $instancePath Absolute path to test instance
     * @return void
     * @throws \Exception
     */
    public function removeOldInstanceIfExists($instancePath)
    {
        if (is_dir($instancePath)) {
            $success = GeneralUtility::rmdir($instancePath, true);
            if (!$success) {
                throw new \Exception(
                    'Can not remove folder: ' . $instancePath,
                    1376657210
                );
            }
        }
    }

    /**
     * Create last_run.txt file within instance path containing timestamp of "now".
     * Used in functional tests to reuse an instance for multiple tests in one test case.
     *
     * @param string $instancePath Absolute path to test instance
     * @return void
     */
    public function createLastRunTextfile($instancePath)
    {
        // Store the time instance was created
        file_put_contents($instancePath . '/last_run.txt', time());
    }

    /**
     * Link TYPO3 CMS core from "parent" instance.
     * For functional and acceptance tests.
     *
     * @param string $instancePath Absolute path to test instance
     * @throws \Exception
     * @return void
     */
    public function setUpInstanceCoreLinks($instancePath)
    {
        $linksToSet = [
            '../../../../' => $instancePath . '/typo3_src',
            'typo3_src/typo3' => $instancePath . '/typo3',
            'typo3_src/index.php' => $instancePath . '/index.php',
        ];
        foreach ($linksToSet as $from => $to) {
            $success = symlink($from, $to);
            if (!$success) {
                throw new \Exception(
                    'Creating link failed: from ' . $from . ' to: ' . $to,
                    1376657199
                );
            }
        }
    }

    /**
     * Link test extensions to the typo3conf/ext folder of the instance.
     * For functional and acceptance tests.
     *
     * @param string $instancePath Absolute path to test instance
     * @param array $extensionPaths Contains paths to extensions relative to document root
     * @throws \Exception
     * @return void
     */
    public function linkTestExtensionsToInstance($instancePath, array $extensionPaths)
    {
        foreach ($extensionPaths as $extensionPath) {
            $absoluteExtensionPath = ORIGINAL_ROOT . $extensionPath;
            if (!is_dir($absoluteExtensionPath)) {
                throw new \Exception(
                    'Test extension path ' . $absoluteExtensionPath . ' not found',
                    1376745645
                );
            }
            $destinationPath = $instancePath . '/typo3conf/ext/' . basename($absoluteExtensionPath);
            $success = symlink($absoluteExtensionPath, $destinationPath);
            if (!$success) {
                throw new \Exception(
                    'Can not link extension folder: ' . $absoluteExtensionPath . ' to ' . $destinationPath,
                    1376657142
                );
            }
        }
    }

    /**
     * Link paths inside the test instance, e.g. from a fixture fileadmin subfolder to the
     * test instance fileadmin folder.
     * For functional and acceptance tests.
     *
     * @param string $instancePath Absolute path to test instance
     * @param array $pathsToLinkInTestInstance Contains paths as array of source => destination in key => value pairs of folders relative to test instance root
     * @throws \Exception if a source path could not be found and on failing creating the symlink
     * @return void
     */
    public function linkPathsInTestInstance($instancePath, array $pathsToLinkInTestInstance)
    {
        foreach ($pathsToLinkInTestInstance as $sourcePathToLinkInTestInstance => $destinationPathToLinkInTestInstance) {
            $sourcePath = $instancePath . '/' . ltrim($sourcePathToLinkInTestInstance, '/');
            if (!file_exists($sourcePath)) {
                throw new \Exception(
                    'Path ' . $sourcePath . ' not found',
                    1476109221
                );
            }
            $destinationPath = $instancePath . '/' . ltrim($destinationPathToLinkInTestInstance, '/');
            $success = symlink($sourcePath, $destinationPath);
            if (!$success) {
                throw new \Exception(
                    'Can not link the path ' . $sourcePath . ' to ' . $destinationPath,
                    1389969623
                );
            }
        }
    }

    /**
     * Database settings for functional and acceptance tests can be either set by
     * environment variables (recommended), or from an existing LocalConfiguration as fallback.
     * The method fetches these.
     *
     * An unique name will be added to the database name later.
     *
     * @throws \Exception
     * @return array [DB][host], [DB][username], ...
     */
    public function getOriginalDatabaseSettingsFromEnvironmentOrLocalConfiguration()
    {
        $databaseName = trim(getenv('typo3DatabaseName'));
        $databaseHost = trim(getenv('typo3DatabaseHost'));
        $databaseUsername = trim(getenv('typo3DatabaseUsername'));
        $databasePassword = getenv('typo3DatabasePassword');
        $databasePasswordTrimmed = trim($databasePassword);
        $databasePort = trim(getenv('typo3DatabasePort'));
        $databaseSocket = trim(getenv('typo3DatabaseSocket'));
        $databaseDriver = trim(getenv('typo3DatabaseDriver'));
        $databaseCharset = trim(getenv('typo3DatabaseCharset'));
        if ($databaseName || $databaseHost || $databaseUsername || $databasePassword || $databasePort || $databaseSocket || $databaseCharset) {
            // Try to get database credentials from environment variables first
            $originalConfigurationArray = [
                'DB' => [
                    'Connections' => [
                        'Default' => [
                            'driver' => 'mysqli'
                        ],
                    ],
                ],
            ];
            if ($databaseName) {
                $originalConfigurationArray['DB']['Connections']['Default']['dbname'] = $databaseName;
            }
            if ($databaseHost) {
                $originalConfigurationArray['DB']['Connections']['Default']['host'] = $databaseHost;
            }
            if ($databaseUsername) {
                $originalConfigurationArray['DB']['Connections']['Default']['user'] = $databaseUsername;
            }
            if ($databasePassword !== false) {
                $originalConfigurationArray['DB']['Connections']['Default']['password'] = $databasePasswordTrimmed;
            }
            if ($databasePort) {
                $originalConfigurationArray['DB']['Connections']['Default']['port'] = $databasePort;
            }
            if ($databaseSocket) {
                $originalConfigurationArray['DB']['Connections']['Default']['unix_socket'] = $databaseSocket;
            }
            if ($databaseDriver) {
                $originalConfigurationArray['DB']['Connections']['Default']['driver'] = $databaseDriver;
            }
            if ($databaseCharset) {
                $originalConfigurationArray['DB']['Connections']['Default']['charset'] = $databaseCharset;
            }
        } elseif (file_exists(ORIGINAL_ROOT . 'typo3conf/LocalConfiguration.php')) {
            // See if a LocalConfiguration file exists in "parent" instance to get db credentials from
            $originalConfigurationArray = require ORIGINAL_ROOT . 'typo3conf/LocalConfiguration.php';
        } else {
            throw new \Exception(
                'Database credentials for tests are neither set through environment'
                . ' variables, and can not be found in an existing LocalConfiguration file',
                1397406356
            );
        }
        return $originalConfigurationArray['DB'];
    }

    /**
     * Maximum length of database names is 64 chars in mysql. Test this is not exceeded
     * after a suffix has been added.
     *
     * @param string $originalDatabaseName Base name of the database
     * @param array $configuration "LocalConfiguration" array with DB settings
     * @throws \Exception
     */
    public function testDatabaseNameIsNotTooLong($originalDatabaseName, array $configuration)
    {
        // Maximum database name length for mysql is 64 characters
        if (strlen($configuration['DB']['Connections']['Default']['dbname']) > 64) {
            $suffixLength = strlen($configuration['DB']['Connections']['Default']['dbname']) - strlen($originalDatabaseName);
            $maximumOriginalDatabaseName = 64 - $suffixLength;
            throw new \Exception(
                'The name of the database that is used for the functional test (' . $originalDatabaseName . ')' .
                ' exceeds the maximum length of 64 character allowed by MySQL. You have to shorten your' .
                ' original database name to ' . $maximumOriginalDatabaseName . ' characters',
                1377600104
            );
        }
    }

    /**
     * Create LocalConfiguration.php file of the test instance.
     * For functional and acceptance tests.
     *
     * @param string $instancePath Absolute path to test instance
     * @param array $configuration Base configuration array
     * @param array $overruleConfiguration Overrule factory and base configuration
     * @throws \Exception
     * @return void
     */
    public function setUpLocalConfiguration($instancePath, array $configuration, array $overruleConfiguration)
    {
        // Base of final LocalConfiguration is core factory configuration
        $finalConfigurationArray = require ORIGINAL_ROOT . 'typo3/sysext/core/Configuration/FactoryConfiguration.php';
        $finalConfigurationArray = array_replace_recursive($finalConfigurationArray, $configuration);
        $finalConfigurationArray = array_replace_recursive($finalConfigurationArray, $overruleConfiguration);
        $result = $this->writeFile(
            $instancePath . '/typo3conf/LocalConfiguration.php',
            '<?php' . chr(10) .
            'return ' .
            ArrayUtility::arrayExport(
                $finalConfigurationArray
            ) .
            ';'
        );
        if (!$result) {
            throw new \Exception('Can not write local configuration', 1376657277);
        }
    }

    /**
     * Compile typo3conf/PackageStates.php containing default packages like core,
     * a test specific list of additional core extensions, and a list of
     * test extensions.
     * For functional and acceptance tests.
     *
     * @param string $instancePath Absolute path to test instance
     * @param array $defaultCoreExtensionsToLoad Default list of core extensions to load
     * @param array $additionalCoreExtensionsToLoad Additional core extensions to load
     * @param array $testExtensionPaths Paths to extensions relative to document root
     * @throws \Exception
     */
    public function setUpPackageStates(
        $instancePath,
        array $defaultCoreExtensionsToLoad,
        array $additionalCoreExtensionsToLoad,
        array $testExtensionPaths
    )
    {
        $packageStates = [
            'packages' => [],
            'version' => 5,
        ];

        // Register default list of extensions and set active
        foreach ($defaultCoreExtensionsToLoad as $extensionName) {
            $packageStates['packages'][$extensionName] = [
                'packagePath' => 'typo3/sysext/' . $extensionName . '/'
            ];
        }

        // Register additional core extensions and set active
        foreach ($additionalCoreExtensionsToLoad as $extensionName) {
            $packageStates['packages'][$extensionName] = [
                'packagePath' => 'typo3/sysext/' . $extensionName . '/'
            ];
        }

        // Activate test extensions that have been symlinked before
        foreach ($testExtensionPaths as $extensionPath) {
            $extensionName = basename($extensionPath);
            $packageStates['packages'][$extensionName] = [
                'packagePath' => 'typo3conf/ext/' . $extensionName . '/'
            ];
        }

        $result = $this->writeFile(
            $instancePath . '/typo3conf/PackageStates.php',
            '<?php' . chr(10) .
            'return ' .
            ArrayUtility::arrayExport(
                $packageStates
            ) .
            ';'
        );

        if (!$result) {
            throw new \Exception('Can not write PackageStates', 1381612729);
        }
    }


    /**
     * Bootstrap the autoloader for the typo3 instance web root.
     * When starting tasks from the noerdisch-testing-framework phpunit the autoloader
     * does not resolve the classes of the TYPO3 instance. So we initialize the class loader manually.
     *
     * @return void
     */
    public function initializeClassLoader()
    {
        $autoloadFile = $this->getVendorPath() . 'autoload.php';
        if (!is_file($autoloadFile)) {
            $classLoader = require $autoloadFile;
            Bootstrap::getInstance()->initializeClassLoader($classLoader);
        }
    }

    /**
     * Bootstrap basic TYPO3. This bootstraps TYPO3 far enough to initialize database afterwards.
     * For functional and acceptance tests.
     *
     * @param string $instancePath Absolute path to test instance
     * @return void
     * @throws \Noerdisch\TestingFramework\Core\Exception
     */
    public function setUpBasicTypo3Bootstrap($instancePath)
    {
        $_SERVER['PWD'] = $instancePath;
        $_SERVER['argv'][0] = 'index.php';

        $autoloadFile = $this->getVendorPath() . 'autoload.php';
        if (!is_file($autoloadFile)) {
            $classLoader = require $autoloadFile;
            Bootstrap::getInstance()
                ->initializeClassLoader($classLoader)
                ->baseSetup()
                ->loadConfigurationAndInitialize(true);
            $this->dumpClassLoadingInformation();
            Bootstrap::getInstance()->loadTypo3LoadedExtAndExtLocalconf(true)
                ->setFinalCachingFrameworkCacheConfiguration()
                ->unsetReservedGlobalVariables();
        }
    }

    /**
     * For composer installations the vendor folder is part of the typo3_src and in archive based installations it
     * is located in the web root of the TYPO3 instance. We need the vendor folder to get the classloader for instance.
     *
     * @throws \Exception
     * @return string
     */
    protected function getVendorPath()
    {
        $webRoot = $this->getWebRoot();
        $typo3Path = rtrim(realpath($webRoot), '\\/') . '/typo3';
        $vendorPath = is_link($typo3Path) ? $this->getVendorPathFromSymlink(readlink($typo3Path)) : '';

        if (!is_dir($vendorPath)) {
            // None composer installations (e.g. via archive) has the vendor in the webroot folder
            $vendorPath = rtrim(realpath($webRoot), '\\/') . '/vendor';
        }

        if (!is_dir($vendorPath)) {
            throw new \Exception(
                'Could not locate the TYPO3 autoload file. Please take care that the vendor folder is in the web root
                folder or that us use typo3_src symlinks.',
                1509639973
            );
        }

        return $vendorPath . '/';
    }

    /**
     * Extract the vendor path from the given sym link if vendor exists.
     * TYPO3 often uses sym links to the sources so you can find out with the typo3 sym link for instance
     * where the sources are located.
     *
     * We need to use none TYPO3 API methods at this point, because the also the bootstrap is using this method to
     * get the correct classloading information. Without that the TYPO3 API is still undefined.
     *
     * @param string $symLink
     * @return string
     */
    protected function getVendorPathFromSymlink($symLink)
    {
        $linkSegments = explode('/', $symLink);
        if (!in_array('vendor', $linkSegments, true)) {
            return '';
        }

        $vendorSegments = [];
        foreach ($linkSegments as $segment) {
            $vendorSegments[] = $segment;

            if ($segment === 'vendor') {
                break;
            }
        }

        return realpath($this->getWebRoot() . implode('/', $vendorSegments));
    }

    /**
     * Dump class loading information
     *
     * @return void
     */
    public function dumpClassLoadingInformation()
    {
        if (!ClassLoadingInformation::isClassLoadingInformationAvailable()) {
            ClassLoadingInformation::dumpClassLoadingInformation();
            ClassLoadingInformation::registerClassLoadingInformation();
        }
    }

    /**
     * Truncate all tables.
     * For functional and acceptance tests.
     *
     * @throws \Exception
     * @return void
     */
    public function initializeTestDatabaseAndTruncateTables()
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
        $schemaManager = $connection->getSchemaManager();

        foreach ($schemaManager->listTables() as $table) {
            $connection->truncate($table->getName());
            self::resetTableSequences($connection, $table->getName());
        }
    }

    /**
     * Load ext_tables.php files.
     * For functional and acceptance tests.
     *
     * @return void
     */
    public function loadExtensionTables()
    {
        Bootstrap::getInstance()
            ->loadCachedTca()
            ->loadExtensionTables();
    }

    /**
     * Create tables and import static rows.
     * For functional and acceptance tests.
     *
     * @return void
     */
    public function createDatabaseStructure()
    {
        $schemaMigrationService = GeneralUtility::makeInstance(SchemaMigrator::class);
        $sqlReader = GeneralUtility::makeInstance(SqlReader::class);
        $sqlCode = $sqlReader->getTablesDefinitionString(true);

        $createTableStatements = $sqlReader->getCreateTableStatementArray($sqlCode);

        $schemaMigrationService->install($createTableStatements);

        $insertStatements = $sqlReader->getInsertStatementArray($sqlCode);
        $schemaMigrationService->importStaticData($insertStatements);
    }


    /**
     * Get Path to vendor dir
     * Since we are installed in vendor dir, we can safely assume the path of the vendor
     * directory relative to this file
     *
     * @throws \Noerdisch\TestingFramework\Core\Exception
     * @return string
     */
    protected function getPackagesPath()
    {
        return $this->getVendorPath();
    }

    /**
     * Returns the absolute path the TYPO3 document root.
     * This is the "original" document root, not the "instance" root for functional / acceptance tests.
     *
     * @return string the TYPO3 document root using Unix path separators
     */
    protected function getWebRoot()
    {
        if (getenv('TYPO3_PATH_ROOT')) {
            $webRoot = getenv('TYPO3_PATH_ROOT');
        } elseif (getenv('TYPO3_PATH_WEB')) {
            // @deprecated
            $webRoot = getenv('TYPO3_PATH_WEB');
        } else {
            $webRoot = getcwd();
        }

        return rtrim(strtr($webRoot, '\\', '/'), '/') . '/';
    }

    /**
     * Send http headers, echo out a text message and exit with error code
     *
     * @param string $message
     */
    protected function exitWithMessage($message)
    {
        echo $message . chr(10);
        exit(1);
    }

    /**
     * @param string $path
     * @return string
     */
    protected function resolvePath($path)
    {
        if (strpos($path, 'EXT:') === 0) {
            $path = GeneralUtility::getFileAbsFileName($path);
        } elseif (strpos($path, 'PACKAGE:') === 0) {
            $path = $this->getPackagesPath() . str_replace('PACKAGE:', '', $path);
        }
        return $path;
    }

    /**
     * Writes $content to the file $file. This is a simplified version
     * of GeneralUtility::writeFile that does not fix permissions.
     *
     * @param string $file Filepath to write to
     * @param string $content Content to write
     * @return bool TRUE if the file was successfully opened and written to.
     */
    protected function writeFile($file, $content)
    {
        if ($fd = fopen($file, 'wb')) {
            $res = fwrite($fd, $content);
            fclose($fd);
            if ($res === false) {
                return false;
            }
            return true;
        }
        return false;
    }
}
