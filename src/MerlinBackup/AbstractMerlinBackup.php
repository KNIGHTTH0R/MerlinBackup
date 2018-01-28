<?php

/*
 * This file is part of the UCSDMath package.
 *
 * (c) 2015-2018 UCSD Mathematics | Math Computing Support <mathhelp@math.ucsd.edu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace UCSDMath\MerlinBackup;

use mysqli;
use mysqli_stmt;
use UCSDMath\Configuration\Config;
use UCSDMath\Filesystem\FilesystemInterface;
use UCSDMath\MerlinBackup\Exception\IOException;
use UCSDMath\MerlinBackup\Exception\MerlinBackupException;
use UCSDMath\MerlinBackup\Exception\FileNotFoundException;
use UCSDMath\MerlinBackup\ExtendedOperations\ServiceFunctions;
use UCSDMath\MerlinBackup\ExtendedOperations\ServiceFunctionsInterface;
use UCSDMath\MerlinBackup\ExtendedOperations\MerlinBackupCloneOperations;
use UCSDMath\MerlinBackup\ExtendedOperations\MerlinBackupCloneOperationsInterface;
use UCSDMath\MerlinBackup\ExtendedOperations\MerlinBackupAccountOperations;
use UCSDMath\MerlinBackup\ExtendedOperations\MerlinBackupAccountOperationsInterface;
use UCSDMath\MerlinBackup\ExtendedOperations\MerlinBackupStandardOperations;
use UCSDMath\MerlinBackup\ExtendedOperations\MerlinBackupStandardOperationsInterface;
use UCSDMath\MerlinBackup\ExtendedOperations\MerlinBackupServiceMethods;
use UCSDMath\MerlinBackup\ExtendedOperations\MerlinBackupServiceMethodsInterface;
use UCSDMath\Configuration\ConfigurationVault\ConfigurationVaultInterface;

/**
 * AbstractMerlinBackup provides an abstract base class implementation of {@link MerlinBackupInterface}.
 * This service groups a common code base implementation that MerlinBackup extends.
 *
 * This component library is used to provide backup services for your MySQL database information.
 *
 * This class was created to manage database table backups in multiple places.
 * We wanted to provide direct dumping to files, store those files locally and to a
 * remote repository, and handle duplicating table data to a live database clone
 * or mini datastore.  This is a work-in-progress, so some features may not be available.
 *
 * Method list: (+) @api, (-) protected or private visibility.
 *
 * (+) MerlinBackupInterface __construct(FilesystemInterface $filesystem, ConfigurationVaultInterface $configVault);
 * (+) void __destruct();
 * (+) MerlinBackupInterface reset();
 * (+) string getUuid(bool $isUpper = true);
 * (+) MerlinBackupInterface setSkipComments();
 * (+) MerlinBackupInterface setDontSkipComments();
 * (+) MerlinBackupInterface setDumpProceduresTriggersFunctionsOnly();
 * (+) MerlinBackupInterface databaseConnect(string $vaultFileDesignator, string $vaultAccountDesignator);
 * (+) MerlinBackupInterface renderDailyMysqlDump(string $vaultAccountDesignator, string $database = null, string $vaultFileDesignator = 'Database');
 * (-) string getCompression();
 * (-) string getCompressionFileType();
 * (-) bool wordContainsDate(string $word);
 * (-) MerlinBackupInterface startupLoggingServices();
 * (-) MerlinBackupInterface setConfiguredDumpOptions();
 * (-) array arrayToDefault(array $array, $value = null);
 * (-) MerlinBackupInterface verifyDatabaseConnection(string $handle = 'mysqli');
 * (-) MerlinBackupInterface setRepositoryArchiveNames(string $sortOrder = 'asc');
 * (-) MerlinBackupInterface setCharacterEncoding(string $charSet = 'utf8mb4', string $handle = 'mysqli');
 *
 * @author Daryl Eisner <deisner@ucsd.edu>
 */
abstract class AbstractMerlinBackup implements MerlinBackupInterface, ServiceFunctionsInterface
{
    /**
     * Constants.
     *
     * @var string VERSION The version number
     *
     * @api
     */
    public const VERSION = '2.3.0';

    //--------------------------------------------------------------------------

    /**
     * Properties.
     *
     * @var    FilesystemInterface         $filesystem                   The Filesystem Interface
     * @var    ConfigurationVaultInterface $configVault                  The ConfigurationVault Interface
     * @var    ServiceRequestContainer     $service                      The ServiceRequestContainer Interface
     * @var    PersistenceInterface        $persist                      The Persistence Interface
     * @var    mysqli_stmt                 $stmt                         The mysqli_stmt statement {object} returns a statement handle for further operations on the statement
     * @var    mysqli                      $mysqli                       The mysqli Interface
     * @var    string                      $sql                          The SQL prepared statement
     * @var    string                      $repository                   The place or directory where database tables are held
     * @var    int                         $repositoryExpireTime         The time in days to expire the repository archives (e.g., 365)
     * @var    string                      $databaseDirectory            The group location for storing related database dumps
     * @var    string                      $backupDirectory              The directory repository location (for storage of daily backups)
     * @var    string                      $backupDirectoryGroup         The directory group settings for the repository (storage of all daily backups)
     * @var    int                         $backupDirectoryPermissions   The directory permission settings for the repository (e.g., 0775 - storage of all daily backups)
     * @var    string                      $backupDailyBackupGroup       The directory group settings for the daily backups
     * @var    int                         $backupDailyBackupPermissions The directory permission settings for the daily backups (e.g., 0660)
     * @var    string                      $todaysDate                   The current date for today (e.g., yyyy-mm-dd)
     * @var    string                      $todaysTimestamp              The default timestamp for today (e.g., yyyy-mm-dd 060000)
     * @var    string                      $mysqldump                    The location on the system for the mysqldump utility
     * @var    bool                        $isMysqldumpEnabled           The option to enable file dumps of MySQL tables and databases
     * @var    bool                        $isLoggingEnabled             The option to provide logging services (internally configured only)
     * @var    string                      $compressionType              The default file compression type or scheme ('None','GZIP','BZIP2','COMPRESS','LZMA')
     * @var    array                       $compressionFileType          The default file compression types with their associated filename extensions
     * @var    array                       $repositoryArchiveNames       The names of the archived directories located in main repository
     * @static MerlinBackupInterface       $instance                     The static instance MerlinBackupInterface
     * @static int                         $objectCount                  The static count of MerlinBackupInterface
     * @var    array                       $storageRegister              The stored set of data structures used by this class
     * @var    string                      $dumpType                     The preferred dump type (the database vs. each table in a database)
     * @var    string                      $configuredDumpOptions        The calculated options used in the mysqldump request
     * @var    array                       $mysqlDumpOptions             The options to use within the mysqldump statement
     */
    protected $filesystem                   = null;
    protected $configVault                  = null;
    protected $service                      = null;
    protected $persist                      = null;
    protected $stmt                         = null;
    protected $mysqli                       = null;
    protected $sql                          = null;
    protected $repository                   = null;
    protected $repositoryExpireTime         = null;
    protected $databaseDirectory            = null;
    protected $backupDirectory              = null;
    protected $backupDailyBackupGroup       = null;
    protected $backupDailyBackupPermissions = null;
    protected $backupDirectoryGroup         = null;
    protected $backupDirectoryPermissions   = null;
    protected $todaysDate                   = null;
    protected $todaysTimestamp              = null;
    protected $mysqldump                    = null;
    protected $mysql                        = null;
    protected $isMysqldumpEnabled           = null;
    protected $isLoggingEnabled             = false;
    protected $compressionType              = null;
    protected $repositoryArchiveNames       = null;
    protected $compressionFileType          = ['None' => null, 'GZIP' => '.gz', 'BZIP2' => '.bz2', 'COMPRESS' => '.Z', 'LZMA' => '.lzma'];
    protected static $instance              = null;
    protected static $objectCount           = 0;
    protected $storageRegister              = [];
    protected $dumpType                     = 'tables';  // ['tables', 'database']
    protected $configuredDumpOptions        = null;
    protected $mysqlDumpOptions             = [
        '--add-drop-database'               => false,  // Add DROP DATABASE statement before each CREATE DATABASE statement
        '--add-drop-table'                  => false,  // Add DROP TABLE statement before each CREATE TABLE statement
        '--add-drop-trigger'                => false,  // Add DROP TRIGGER statement before each CREATE TRIGGER statement
        '--add-locks'                       => false,  // Surround each table dump with LOCK TABLES and UNLOCK TABLES statements
        '--comments'                        => true,   // Add comments to dump file
        '--compact'                         => true,   // Produce more compact output
        '--create-options'                  => false,  // Include all MySQL-specific table options in CREATE TABLE statements
        '--debug'                           => false,  // Write debugging log
        '--default-character-set'           => false,  // Specify default character set
        '--disable-keys'                    => false,  // For each table, surround INSERT statements with statements to disable and enable keys
        '--extended-insert'                 => false,  // Use multiple-row INSERT syntax
        '--flush-logs'                      => false,  // Flush MySQL server log files before starting dump
        '--flush-privileges'                => false,  // Emit a FLUSH PRIVILEGES statement after dumping mysql database
        '--lock-all-tables'                 => false,  // Lock all tables across all databases
        '--lock-tables'                     => false,  // Lock all tables before dumping them
        '--no-create-db'                    => false,  // Do not write CREATE DATABASE statements
        '--no-create-info'                  => false,  // Do not write CREATE TABLE statements that re-create each dumped table
        '--no-data'                         => false,  // Do not dump table contents
        '--opt'                             => true,   // Shorthand for --add-drop-table --add-locks --create-options --disable-keys --extended-insert --lock-tables --quick --set-charset.
        '--quick'                           => false,  // Retrieve rows for a table from the server a row at a time
        '--replace'                         => false,  // Write REPLACE statements rather than INSERT statements
        '--routines'                        => false,  // Dump stored routines (procedures and functions) from dumped databases
        '--set-charset'                     => false,  // Add SET NAMES default_character_set to output
        '--skip-add-drop-table'             => false,  // Do not add a DROP TABLE statement before each CREATE TABLE statement
        '--skip-add-locks'                  => false,  // Do not add locks
        '--skip-comments'                   => false,  // Do not add comments to dump file
        '--skip-compact'                    => false,  // Do not produce more compact output
        '--skip-disable-keys'               => false,  // Do not disable keys
        '--skip-extended-insert'            => false,  // Turn off extended-insert
        '--skip-opt'                        => false,  // Turn off options set by --opt
        '--skip-quick'                      => false,  // Do not retrieve rows for a table from the server a row at a time
        '--skip-quote-names'                => false,  // Do not quote identifiers
        '--skip-set-charset'                => false,  // Do not write SET NAMES statement
        '--skip-triggers'                   => false,  // Do not dump triggers
        '--skip-triggers'                   => false,  // Do not dump triggers (you must specify this option to avoid dumping of triggers)
        '--skip-tz-utc'                     => false,  // Turn off tz-utc
        '--triggers'                        => false,  // Dump triggers for each dumped table (this is Default)
        '--host'                            => false,  // Host to connect to (IP address or hostname)
        '--port'                            => false,  // TCP/IP port number to use for connection (e.g., 3306)
        '--user'                            => false,  // MySQL user name to use when connecting to server
        '--password'                        => false,  // Password to use when connecting to server
        '--protocol'                        => false,  // Connection protocol to use ('TCP','SOCKET','PIPE','MEMORY')
        '--socket'                          => false,  // For connections to localhost, the Unix socket file to use
    ];

    //--------------------------------------------------------------------------

    /**
     * Constructor.
     *
     * @param FilesystemInterface         $filesystem  The Filesystem Interface
     * @param ConfigurationVaultInterface $configVault The ConfigurationVault Interface
     *
     * @api
     */
    public function __construct(FilesystemInterface $filesystem, ConfigurationVaultInterface $configVault)
    {
        $this->setProperty('filesystem', $filesystem);
        $this->setProperty('configVault', $configVault);
        $this->setProperty('todaysDate', date('Y-m-d'));
        $this->setProperty('compressionType', self::DEFAULT_COMPRESSION_TYPE);
        $this->setProperty('todaysTimestamp', sprintf('%s %s', date('Y-m-d'), self::DEFAULT_TIME));
        $this->setProperty('mysqldump', class_exists('\\UCSDMath\\Configuration\\Config') ? Config::MERLIN_MYSQLDUMP_UTILITY : self::MERLIN_MYSQLDUMP_UTILITY); // required
        $this->setProperty('mysql', class_exists('\\UCSDMath\\Configuration\\Config') ? Config::MERLIN_MYSQL_UTILITY : self::MERLIN_MYSQL_UTILITY); // required
        $this->setProperty('repository', class_exists('\\UCSDMath\\Configuration\\Config') ? Config::MERLIN_MYSQLDUMP_REPOSITORY : self::MERLIN_MYSQLDUMP_REPOSITORY); // required
        $this->setProperty('repositoryExpireTime', class_exists('\\UCSDMath\\Configuration\\Config') ? Config::MERLIN_MYSQLDUMP_REPOSITORY_EXPIRETIME : self::MERLIN_MYSQLDUMP_REPOSITORY_EXPIRETIME); // required
        $this->setProperty('isMysqldumpEnabled', class_exists('\\UCSDMath\\Configuration\\Config') ? Config::IS_MERLIN_MYSQLDUMP_ENABLED : self::IS_MERLIN_MYSQLDUMP_ENABLED); // required
        $this->setProperty('backupDirectoryGroup', self::MERLIN_MYSQLDUMP_REPOSITORY_GROUP);
        $this->setProperty('backupDirectoryPermissions', self::MERLIN_MYSQLDUMP_REPOSITORY_PERMISSIONS);
        $this->setProperty('backupDailyBackupGroup', self::MERLIN_MYSQLDUMP_DAILYBACKUP_GROUP);
        $this->setProperty('backupDailyBackupPermissions', self::MERLIN_MYSQLDUMP_DAILYBACKUP_PERMISSIONS);
        $this->startupLoggingServices();
        $this->setConfiguredDumpOptions();
    }

    //--------------------------------------------------------------------------

    /**
     * Reset all array keys to a default setting value.
     *
     * @param bool  $array The array to set item values to some default value
     * @param mixed $value The default value to set
     *
     * @return array The array with all keys set to the same value
     *
     * @api
     */
    protected function arrayToDefault(array $array, $value = null): array
    {
        return array_fill_keys(array_keys($array), $value);
    }

    //--------------------------------------------------------------------------

    /**
     * Reset to default settings.
     *
     * @return MerlinBackupInterface The current instance
     *
     * @api
     */
    public function reset(): MerlinBackupInterface
    {
        return $this
            ->setProperty('mysqlDumpOptions', $this->arrayToDefault($this->mysqlDumpOptions, false))
                ->setProperty('mysqlDumpOptions', true, '--opt')
                    ->setProperty('mysqlDumpOptions', true, '--compact')
                        ->setProperty('mysqlDumpOptions', true, '--comments')
                            ->setProperty('dumpType', 'tables')
                                ->setProperty('storageRegister', array())
                                    ->setProperty('configuredDumpOptions', null);
    }

    //--------------------------------------------------------------------------

    /**
     * Set the configured dump options for a msqldump.
     *
     * @return MerlinBackupInterface The current instance
     */
    protected function setConfiguredDumpOptions(): MerlinBackupInterface
    {
        $configuredDumpOptions = [];

        foreach ($this->mysqlDumpOptions as $key => $value) {
            if (true === $value) {
                $configuredDumpOptions[] = $key;
            }
        }

        return $this->setProperty('configuredDumpOptions', implode(' ', $configuredDumpOptions));
    }

    //--------------------------------------------------------------------------

    /**
     * Setup options for the backup of Triggers, Functions, and Procedures only for a database dump.
     *
     * {@internal looking for something like:
     *    mysqldump --routines --no-create-info --no-data --no-create-db --skip-opt <database> > outputfile.sql }
     *
     * @return MerlinBackupInterface The current instance
     */
    public function setDumpProceduresTriggersFunctionsOnly(): MerlinBackupInterface
    {
        return $this
            ->setProperty('dumpType', 'database')
                ->setProperty('mysqlDumpOptions', true, '--routines')
                    ->setProperty('mysqlDumpOptions', true, '--no-data')
                        ->setProperty('mysqlDumpOptions', true, '--skip-opt')
                            ->setProperty('mysqlDumpOptions', true, '--no-create-db')
                                ->setProperty('mysqlDumpOptions', true, '--no-create-info')
                                    ->setProperty('mysqlDumpOptions', false, '--opt')
                                        ->setConfiguredDumpOptions();
    }

    //--------------------------------------------------------------------------

    /**
     * Start logging services.
     *
     * @return MerlinBackupInterface The current instance
     */
    protected function startupLoggingServices(): MerlinBackupInterface
    {
        if (class_exists('UCSDMath\DependencyInjection\ServiceRequestContainer')) {
            $this->setProperty('service', \UCSDMath\DependencyInjection\ServiceRequestContainer::serviceConnect());
            $this->setProperty('persist', $this->service->Persistence);
            $this->setProperty('isLoggingEnabled', true);
        }

        return $this;
    }

    //--------------------------------------------------------------------------

    /**
     * Render Daily Dumps to Storage Area.
     *
     * @param string $vaultAccountDesignator The Configuration Vault database user account ['root','webadmin','johndeere', etc.]
     * @param string $database               The database to backup
     * @param string $vaultFileDesignator    The Configuration Vault file designator ['Database','Account','Administrator','SMTP']
     *
     * @return MerlinBackupInterface The current instance
     *
     * @api
     */
    public function renderDailyMysqlDump(string $vaultAccountDesignator, string $database = null, string $vaultFileDesignator = 'Database'): MerlinBackupInterface
    {
        /* turned off by default on our development platform */
        if (false === $this->isMysqldumpEnabled) {
            return $this;
        }

        /* Ensure we have a repository to store our mysqldumps */
        if (!$this->filesystem->exists($this->repository)) {
            /* Create our repository and define environmental settings */
            $this->filesystem->mkdir($this->repository);
            $this->filesystem->chmod($this->repository, $this->getProperty('backupDirectoryPermissions'));
            $this->filesystem->chgrp($this->repository, $this->getProperty('backupDirectoryGroup'));
        }

        /*
         * Open ConfigurationVault Settings.
         *
         * Create an open instance to mysqli (set: $this->mysqli)
         * This user must have permissions to dump the tables for the provided database parameter: $database.
         */
        $result = $this->databaseConnect($vaultFileDesignator, $vaultAccountDesignator)->mysqli->query('SHOW TABLES');
        $database = null === $database ? $this->get('database') : $database;
        $this->setProperty('databaseDirectory', sprintf('%s/%s', $this->repository, $database));
        $this->setProperty('backupDirectory', sprintf('/%s/%s-%s', $this->databaseDirectory, $this->todaysDate, $database));

        /* Ensure we have a database directory in the repository for storing daily mysqldumps */
        if (!$this->filesystem->exists($this->databaseDirectory)) {
            /* Create our repository and define environmental settings */
            $this->filesystem->mkdir($this->databaseDirectory);
            $this->filesystem->chmod($this->databaseDirectory, $this->getProperty('backupDirectoryPermissions'));
            $this->filesystem->chgrp($this->databaseDirectory, $this->getProperty('backupDirectoryGroup'));
        }

        /* Check if our daily backup exists, if so, do nothing. */
        if (!$this->filesystem->exists($this->backupDirectory)) {
            /* Create our backup in this directory */
            $this->filesystem->mkdir($this->backupDirectory);
            $this->filesystem->chmod($this->backupDirectory, $this->getProperty('backupDirectoryPermissions'));
            $this->filesystem->chgrp($this->backupDirectory, $this->getProperty('backupDirectoryGroup'));

            if ($result !== false) {
                /* Collect a list of all database table names */
                $tables = [];
                while ($row = $result->fetch_array()) {
                    $tables[] = $row[0];
                }
                /* Build mysqldump commands for each table and execute */
                foreach ($tables as $table) {
                    $filename = sprintf('%s/%s-%s-%s.sql%s', $this->backupDirectory, $this->todaysDate, $database, $table, $this->getCompressionFileType());

                    $shellCommand = 'TCP' === $this->get('protocol')
                        ? sprintf(
                            '%s %s --host=%s --port=%s --protocol=%s --user=%s --password=%s %s %s %s %s > %s',
                            $this->mysqldump,
                            $this->configuredDumpOptions,  // Default (--opt --compact --comments)
                            $this->get('hostname'),
                            $this->get('port'),
                            $this->get('protocol'),
                            $this->get('username'),
                            $this->get('password'),
                            $database,
                            $table,
                            self::HIDE_WARNINGS,
                            $this->getCompression(),
                            $filename
                        )
                        : sprintf(
                            '%s %s --host=%s --port=%s --user=%s --password=%s %s %s %s %s > %s',
                            $this->mysqldump,
                            $this->configuredDumpOptions,  // Default (--opt --compact --comments)
                            self::DEFAULT_MYSQL_HOSTNAME,
                            $this->get('port'),
                            $this->get('username'),
                            $this->get('password'),
                            $database,
                            $table,
                            self::HIDE_WARNINGS,
                            $this->getCompression(),
                            $filename
                        );
                    /* Provide error handling here */
                    shell_exec($shellCommand);
                    $this->filesystem->chmod($filename, $this->getProperty('backupDailyBackupPermissions'));
                    $this->filesystem->chgrp($filename, $this->getProperty('backupDailyBackupGroup'));
                    $this->filesystem->touch($filename, strtotime($this->todaysTimestamp), strtotime($this->todaysTimestamp));
                }
                $this->filesystem->touch($this->backupDirectory, strtotime($this->todaysTimestamp), strtotime($this->todaysTimestamp));
            }
        }

        if (true === $this->isLoggingEnabled) {
            $this->persist->createSystemLog(sprintf('-- Merlin: Daily database dump availble for: %s', $this->backupDirectory));
        }

        return $this;
    }

    //--------------------------------------------------------------------------

    /**
     * Set the names of the archived directories located in the main repository.
     *
     * The names are defined by ISO formatted dates followed by the database name.
     *    Example: 2018-01-28-johndeere_equipment_database
     *
     * @param string $sortOrder The sort order of the list items ('asc','desc')
     *
     * @return MerlinBackupInterface The current instance
     */
    protected function setRepositoryArchiveNames(string $sortOrder = 'asc'): MerlinBackupInterface
    {
        $sortOrder = 'asc' === $sortOrder ? \SCANDIR_SORT_ASCENDING : \SCANDIR_SORT_DESCENDING;
        $directoryNames = scandir($this->repository, $sortOrder);
        $archiveListing = [];

        /* Are the names formatted correctly? Archives provide format: yyyy-mm-dd-database */
        foreach ($directoryNames as $directoryName) {
            if (true === $this->wordContainsDate($directoryName)) {
                $archiveListing[] = $directoryName;
            }
        }

        $this->setProperty('repositoryArchiveNames', $archiveListing);

        return $this;
    }

    //--------------------------------------------------------------------------

    /**
     * Provide a list of old archive names from an array.
     *
     * The names are defined by ISO formatted dates followed by the database name.
     *    Example: 2018-01-28-johndeere_equipment_database
     *
     * @param array $isodatelist The list of dates (ISO formatted)
     * @param array $expireTime  The days from today to expire archive
     *
     * @return array The filtered result
     */
    protected function listOldArchives(array $isodatelist, $expireTime = self::MERLIN_MYSQLDUMP_REPOSITORY_EXPIRETIME): array
    {
        $expireTime = null === $this->repositoryExpireTime ? $expireTime : $this->repositoryExpireTime;
        $archiveDate = date('Y-m-d', strtotime(sprintf('-%s days', $expireTime)));
        $currentArchiveList = [];
        foreach ($isodatelist as $date) {
            if ($date > $archiveDate && preg_match("^[0-9]{4}-[0-1][0-9]-[0-3][0-9]", $date)) {
                $currentArchiveList[] = $date;
            }
        }

        return array_diff($isodatelist, $currentArchiveList);
    }

    //--------------------------------------------------------------------------

    /**
     * Verify database connection.
     *
     * @return string The compression type used with pipe
     *
     * @api
     */
    protected function getCompression(): string
    {
        return 'None' === $this->compressionType
            ? (string) null
            : sprintf('| %s', strtolower($this->compressionType));
    }

    //--------------------------------------------------------------------------

    /**
     * Verify that a string contains/begins with a date.
     *
     * Regex options for (yyyy-mm-dd) either contains or begins with:
     *    + preg_match('/\b(\d{4})-(\d{2})-(\d{2})\b/', $word, $parts)
     *    + preg_match('/^(\d{4})-(\d{2})-(\d{2})\b/', $word, $parts)
     *
     * @param string $word The string that may have a format date listing (e.g., yyyy-mm-dd)
     *
     * @return bool The result
     */
    protected function wordContainsDate(string $word): bool
    {
        /* Begins with a valid date: yyy-mm-dd */
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})\b/', $word, $parts)) {
            return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);
        }

        return false;
    }

    //--------------------------------------------------------------------------

    /**
     * Verify database connection.
     *
     * @return string The command to pipe through
     *
     * @api
     */
    protected function getCompressionFileType(): string
    {
        return (string) $this->compressionFileType[$this->compressionType];
    }

    //--------------------------------------------------------------------------

    /**
     * Establish a database connection.
     *
     * @param string $vaultFileDesignator    The Configuration Vault file designator ['Database','Account','Administrator','SMTP']
     * @param string $vaultAccountDesignator The Configuration Vault database user account ['root','webadmin','johndeere', etc.]
     *
     * @return MerlinBackupInterface The current instance
     *
     * @api
     */
    public function databaseConnect(string $vaultFileDesignator, string $vaultAccountDesignator): MerlinBackupInterface
    {
        $this->setVaultConnectionCredentials($vaultFileDesignator, $vaultAccountDesignator);
        $this->mysqli = new mysqli(
            (string) $this->get('hostname'),
            (string) $this->get('username'),
            (string) $this->get('password'),
            (string) $this->get('database'),
            (int)    $this->get('port')
            // (string) $this->get('socket')
        );
        $this->verifyDatabaseConnection()->setCharacterEncoding($this->get('charset'))->configVault->clear();

        return $this;
    }

    //--------------------------------------------------------------------------

    /**
     * Setup database credentials for the connection.
     *
     * @param string $vaultFileDesignator    The Configuration Vault file designator ['Database','Account','Administrator','SMTP']
     * @param string $vaultAccountDesignator The Configuration Vault database user account ['root','webadmin','johndeere', etc.]
     *
     * @return MerlinBackupInterface The current instance
     */
    protected function setVaultConnectionCredentials(string $vaultFileDesignator, string $vaultAccountDesignator): MerlinBackupInterface
    {
        $this->configVault->reset()->openVaultFile($vaultFileDesignator, $vaultAccountDesignator);
        return $this->set('hostname', $this->configVault->get('database_host'))
            ->set('username', $this->configVault->get('database_username'))
                ->set('password', $this->configVault->get('database_password'))
                    ->set('database', $this->configVault->get('database_name'))
                        ->set('port', $this->configVault->get('database_port'))
                            ->set('socket', $this->configVault->get('database_socket'))
                                ->set('protocol', $this->configVault->get('database_protocol'))
                                    ->set('charset', $this->configVault->get('database_charset'));
    }

    //--------------------------------------------------------------------------

    /**
     * Destructor.
     *
     * @api
     */
    public function __destruct()
    {
        static::$objectCount--;
    }

    //--------------------------------------------------------------------------

    /**
     * Method implementations inserted:
     *
     * Method list: (+) @api, (-) protected or private visibility.
     *
     * (+) array all();
     * (+) object init();
     * (+) string version();
     * (+) bool isString($str);
     * (+) bool has(string $key);
     * (+) string getClassName();
     * (+) int getInstanceCount();
     * (+) mixed getConst(string $key);
     * (+) array getClassInterfaces();
     * (+) bool isValidUuid(string $uuid);
     * (+) bool isValidEmail(string $email);
     * (+) bool isValidSHA512(string $hash);
     * (+) bool doesFunctionExist(string $functionName);
     * (+) bool isStringKey(string $str, array $keys);
     * (+) mixed get(string $key, string $subkey = null);
     * (+) mixed getProperty(string $name, string $key = null);
     * (+) mixed __call(string $callback, array $parameters);
     * (+) object set(string $key, $value, string $subkey = null);
     * (+) object setProperty(string $name, $value, string $key = null);
     * (-) Exception throwExceptionError(array $error);
     * (-) InvalidArgumentException throwInvalidArgumentExceptionError(array $error);
     */
    use ServiceFunctions;

    //--------------------------------------------------------------------------
}
