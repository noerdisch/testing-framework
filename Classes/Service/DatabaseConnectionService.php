<?php

namespace Noerdisch\TestingFramework\Service;

/**
 * Copyright notice
 *
 *  (c) Markus Günther <markus.guenther@noerdisch.de>
 *  All rights reserved
 *
 *  This file is part of the TYPO3 CMS project.
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

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\SqlExpectedSchemaService;
use TYPO3\CMS\Install\Service\SqlSchemaMigrationService;
use TYPO3\CMS\Install\Status\ErrorStatus;

/**
 * Service class for handling low level database connection
 *
 * @package Noerdisch\TestingFramework\Service
 */
class DatabaseConnectionService implements SingletonInterface
{
    /**
     * @var DatabaseConnection
     */
    protected $databaseConnection = null;

    /**
     * Returns list of available databases (with access-check based on username/password).
     * Removes mysql organizational tables from database list.
     *
     * @return array List of available databases
     * @throws \BadFunctionCallException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function listDatabases()
    {
        if (!$this->databaseConnection) {
            $this->initializeDatabaseConnection();
        }

        $databaseListArray = [];
        $reservedDatabaseNames = ['mysql', 'information_schema', 'performance_schema'];

        // fetching databases
        $query = 'SELECT SCHEMA_NAME FROM information_schema.SCHEMATA';
        $databaseList = $this->databaseConnection->admin_query($query);

        if ($databaseList === false) {
            throw new \RuntimeException(
                'MySQL Error: Cannot get tablenames: "' . $this->databaseConnection->sql_error() . '"!',
                1510144092
            );
        }

        while ($row = $databaseList->fetch_object()) {
            try {
                $databaseListArray[] = $row->SCHEMA_NAME;
            } catch (\RuntimeException $exception) {
                // The exception happens if we cannot connect to the database
                // (usually due to missing permissions). This is ok here.
                // We catch the exception, skip the database and continue.
            }
        }

        return array_diff($databaseListArray, $reservedDatabaseNames);
    }

    /**
     * Drops given database.
     *
     * @param string $databaseName
     * @return void
     * @throws \InvalidArgumentException
     * @throws \BadFunctionCallException
     * @throws \RuntimeException
     */
    public function dropDatabase($databaseName) {
        if (!$this->databaseConnection) {
            $this->initializeDatabaseConnection();
        }

        $query = 'DROP DATABASE ' . trim($databaseName);
        $result = $this->databaseConnection->admin_query($query);

        if ($result === FALSE) {
            throw new \RuntimeException(
                'MySQL Error: Cannot drop database: "' . $this->databaseConnection->sql_error() . '"!',
                1510147245
            );
        }
    }

    /**
     * Creates given database.
     *
     * @param string $databaseName
     * @return void
     * @throws \InvalidArgumentException
     * @throws \BadFunctionCallException
     * @throws \RuntimeException
     */
    public function createDatabase($databaseName) {
        if (!$this->databaseConnection) {
            $this->initializeDatabaseConnection();
        }

        $query = 'CREATE DATABASE ' . trim($databaseName);
        $result = $this->databaseConnection->admin_query($query);

        if ($result === FALSE) {
            throw new \RuntimeException(
                'MySQL Error: Cannot create database: "' . $this->databaseConnection->sql_error() . '"!',
                1510147802
            );
        }
    }

    /**
     * Create tables and import static rows
     *
     * @return \TYPO3\CMS\Install\Status\StatusInterface[]
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \BadFunctionCallException
     */
    public function importDatabaseData()
    {
        $result = [];

        // Import database data
        if (!$this->databaseConnection) {
            $this->initializeDatabaseConnection();
        }

        /** @var SqlSchemaMigrationService $schemaMigrationService */
        $schemaMigrationService = GeneralUtility::makeInstance(SqlSchemaMigrationService::class);
        /** @var SqlExpectedSchemaService $expectedSchemaService */
        $expectedSchemaService = GeneralUtility::makeInstance(SqlExpectedSchemaService::class);

        // Raw concatenated ext_tables.sql and friends string
        $expectedSchemaString = $expectedSchemaService->getTablesDefinitionString(true);
        $statements = $schemaMigrationService->getStatementArray($expectedSchemaString, true);
        list($_, $insertCount) = $schemaMigrationService->getCreateTables($statements, true);
        $fieldDefinitionsFile = $schemaMigrationService->getFieldDefinitions_fileContent($expectedSchemaString);
        $fieldDefinitionsDatabase = $schemaMigrationService->getFieldDefinitions_database();
        $difference = $schemaMigrationService->getDatabaseExtra($fieldDefinitionsFile, $fieldDefinitionsDatabase);
        $updateStatements = $schemaMigrationService->getUpdateSuggestions($difference);

        foreach (['add', 'change', 'create_table'] as $action) {
            $updateStatus = $schemaMigrationService->performUpdateQueries($updateStatements[$action], $updateStatements[$action]);
            if ($updateStatus !== true) {
                foreach ($updateStatus as $statementIdentifier => $errorMessage) {
                    $result[$updateStatements[$action][$statementIdentifier]] = $errorMessage;
                }
            }
        }

        if (empty($result)) {
            foreach ($insertCount as $table => $count) {
                $insertStatements = $schemaMigrationService->getTableInsertStatements($statements, $table);
                foreach ($insertStatements as $insertQuery) {
                    $insertQuery = rtrim($insertQuery, ';');
                    $this->databaseConnection->admin_query($insertQuery);
                    if ($this->databaseConnection->sql_error()) {
                        $result[$insertQuery] = $this->databaseConnection->sql_error();
                    }
                }
            }
        }

        foreach ($result as $statement => &$message) {
            $errorStatus = GeneralUtility::makeInstance(ErrorStatus::class);
            $errorStatus->setTitle('Database query failed!');
            $errorStatus->setMessage(
                'Query:' . LF .
                ' ' . $statement . LF .
                'Error:' . LF .
                ' ' . $message
            );
            $message = $errorStatus;
        }

        return array_values($result);
    }

    /**
     * Initialize database connection and test connection with given credentials.
     *
     * @param bool $ignoreDatabaseName
     * @return bool TRUE if connect was successful
     * @throws \BadFunctionCallException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    protected function initializeDatabaseConnection($ignoreDatabaseName = TRUE)
    {
        /** @var $databaseConnection \TYPO3\CMS\Core\Database\DatabaseConnection */
        $this->databaseConnection = GeneralUtility::makeInstance(DatabaseConnection::class);

        if (isset($GLOBALS['TYPO3_CONF_VARS']['DB']['database']) && !$ignoreDatabaseName) {
            $this->databaseConnection->setDatabaseName($GLOBALS['TYPO3_CONF_VARS']['DB']['database']);
        }

        $this->databaseConnection->setDatabaseUsername($this->getConfiguredUsername());
        $this->databaseConnection->setDatabasePassword($this->getConfiguredPassword());
        $this->databaseConnection->setDatabaseHost($this->getConfiguredHost());
        $this->databaseConnection->setDatabasePort($this->getConfiguredPort());
        $this->databaseConnection->setDatabaseSocket($this->getConfiguredSocket());

        $this->databaseConnection->initialize();

        return (bool)@$this->databaseConnection->sql_pconnect();
    }

    /**
     * Returns configured username, if set
     *
     * @return string
     */
    protected function getConfiguredUsername()
    {
        $username = isset($GLOBALS['TYPO3_CONF_VARS']['DB']['username']) ? $GLOBALS['TYPO3_CONF_VARS']['DB']['username'] : '';
        return $username;
    }

    /**
     * Returns configured password, if set
     *
     * @return string
     */
    protected function getConfiguredPassword()
    {
        $password = isset($GLOBALS['TYPO3_CONF_VARS']['DB']['password']) ? $GLOBALS['TYPO3_CONF_VARS']['DB']['password'] : '';
        return $password;
    }

    /**
     * Returns configured host with port split off if given
     *
     * @return string
     */
    protected function getConfiguredHost()
    {
        $host = isset($GLOBALS['TYPO3_CONF_VARS']['DB']['host']) ? $GLOBALS['TYPO3_CONF_VARS']['DB']['host'] : '';
        $port = isset($GLOBALS['TYPO3_CONF_VARS']['DB']['port']) ? $GLOBALS['TYPO3_CONF_VARS']['DB']['port'] : '';
        if (strlen($port) < 1 && substr_count($host, ':') === 1) {
            list($host) = explode(':', $host);
        }
        return $host;
    }

    /**
     * Returns configured port. Gets port from host value if port is not yet set.
     *
     * @return int
     */
    protected function getConfiguredPort()
    {
        $host = isset($GLOBALS['TYPO3_CONF_VARS']['DB']['host']) ? $GLOBALS['TYPO3_CONF_VARS']['DB']['host'] : '';
        $port = isset($GLOBALS['TYPO3_CONF_VARS']['DB']['port']) ? $GLOBALS['TYPO3_CONF_VARS']['DB']['port'] : '';
        if ($port === '' && substr_count($host, ':') === 1) {
            $hostPortArray = explode(':', $host);
            $port = $hostPortArray[1];
        }
        return (int)$port;
    }

    /**
     * Returns configured socket, if set
     *
     * @return string|NULL
     */
    protected function getConfiguredSocket()
    {
        $socket = isset($GLOBALS['TYPO3_CONF_VARS']['DB']['socket']) ? $GLOBALS['TYPO3_CONF_VARS']['DB']['socket'] : '';
        return $socket;
    }
}