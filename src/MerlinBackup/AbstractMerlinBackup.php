<?php

/*
 * This file is part of the UCSDMath package.
 *
 * (c) 2015-2017 UCSD Mathematics | Math Computing Support <mathhelp@math.ucsd.edu>
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
 * (+) MerlinBackupInterface databaseConnect(string $vaultFileDesignator, string $vaultAccountDesignator);
 * (+) MerlinBackupInterface renderDailyMysqlDump(string $vaultAccountDesignator, string $database = null, string $vaultFileDesignator = 'Database');
 * (-) string getCompression();
 * (-) string getCompressionFileType();
 * (-) bool wordContainsDate(string $word);
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
    public const VERSION = '1.16.0';

    //--------------------------------------------------------------------------

    /**
     * Properties.
     *
     * @var    FilesystemInterface         $filesystem             The Filesystem Interface
     * @var    ConfigurationVaultInterface $configVault            The ConfigurationVault Interface
     * @var    mysqli_stmt                 $stmt                   The mysqli_stmt statement {object} returns a statement handle for further operations on the statement
     * @var    mysqli                      $mysqli                 The mysqli Interface
     * @var    string                      $sql                    The SQL prepared statement
     * @var    string                      $repository             The place or directory where database tables are held
     * @var    string                      $backupDirectory        The place or directory where daily backups are stored
     * @var    string                      $todaysDate             The current date for today (e.g., yyyy-mm-dd)
     * @var    string                      $todaysTimestamp        The default timestamp for today (e.g., yyyy-mm-dd 060000)
     * @var    string                      $mysqldump              The location on the system for the mysqldump utility
     * @var    bool                        $isMysqldumpEnabled     The option to enable file dumps of MySQL tables and databases
     * @var    string                      $compressionType        The default file compression type or scheme ('None','GZIP','BZIP2','COMPRESS','LZMA')
     * @var    array                       $compressionFileType    The default file compression types with their associated filename extensions
     * @var    array                       $repositoryArchiveNames The names of the archived directories located in main repository
     * @static MerlinBackupInterface       $instance               The static instance MerlinBackupInterface
     * @static int                         $objectCount            The static count of MerlinBackupInterface
     * @var    array                       $storageRegister        The stored set of data structures used by this class
     * @var    array                       $mysqlDumpOptions       The options to use within the mysqldump statement
     */
    protected $filesystem             = null;
    protected $configVault            = null;
    protected $stmt                   = null;
    protected $mysqli                 = null;
    protected $sql                    = null;
    protected $repository             = null;
    protected $backupDirectory        = null;
    protected $todaysDate             = null;
    protected $todaysTimestamp        = null;
    protected $mysqldump              = null;
    protected $isMysqldumpEnabled     = null;
    protected $compressionType        = null;
    protected $repositoryArchiveNames = null;
    protected $compressionFileType    = ['None' => null, 'GZIP' => '.gz', 'BZIP2' => '.bz2', 'COMPRESS' => '.Z', 'LZMA' => '.lzma'];
    protected static $instance        = null;
    protected static $objectCount     = 0;
    protected $storageRegister        = [];
    protected $mysqlDumpOptions       = [
        '--add-drop-database'         => false,  // Add DROP DATABASE statement before each CREATE DATABASE statement
        '--add-drop-table'            => false,  // Add DROP TABLE statement before each CREATE TABLE statement
        '--add-drop-trigger'          => false,  // Add DROP TRIGGER statement before each CREATE TRIGGER statement
        '--add-locks'                 => false,  // Surround each table dump with LOCK TABLES and UNLOCK TABLES statements
        '--comments'                  => true,   // Add comments to dump file
        '--compact'                   => true,   // Produce more compact output
        '--create-options'            => false,  // Include all MySQL-specific table options in CREATE TABLE statements
        '--debug'                     => false,  // Write debugging log
        '--default-character-set'     => false,  // Specify default character set
        '--disable-keys'              => false,  // For each table, surround INSERT statements with statements to disable and enable keys
        '--extended-insert'           => false,  // Use multiple-row INSERT syntax
        '--flush-logs'                => false,  // Flush MySQL server log files before starting dump
        '--flush-privileges'          => false,  // Emit a FLUSH PRIVILEGES statement after dumping mysql database
        '--lock-all-tables'           => false,  // Lock all tables across all databases
        '--lock-tables'               => false,  // Lock all tables before dumping them
        '--no-data'                   => false,  // Do not dump table contents
        '--opt'                       => true,   // Shorthand for --add-drop-table --add-locks --create-options --disable-keys --extended-insert --lock-tables --quick --set-charset.
        '--quick'                     => false,  // Retrieve rows for a table from the server a row at a time
        '--replace'                   => false,  // Write REPLACE statements rather than INSERT statements
        '--set-charset'               => false,  // Add SET NAMES default_character_set to output
        '--skip-add-drop-table'       => false,  // Do not add a DROP TABLE statement before each CREATE TABLE statement
        '--skip-add-locks'            => false,  // Do not add locks
        '--skip-comments'             => false,  // Do not add comments to dump file
        '--skip-compact'              => false,  // Do not produce more compact output
        '--skip-disable-keys'         => false,  // Do not disable keys
        '--skip-extended-insert'      => false,  // Turn off extended-insert
        '--skip-opt'                  => false,  // Turn off options set by --opt
        '--skip-quick'                => false,  // Do not retrieve rows for a table from the server a row at a time
        '--skip-quote-names'          => false,  // Do not quote identifiers
        '--skip-set-charset'          => false,  // Do not write SET NAMES statement
        '--skip-triggers'             => false,  // Do not dump triggers
        '--skip-tz-utc'               => false,  // Turn off tz-utc
        '--host'                      => true,   // Host to connect to (IP address or hostname)
        '--port'                      => true,   // TCP/IP port number to use for connection (e.g., 3306)
        '--user'                      => true,   // MySQL user name to use when connecting to server
        '--password'                  => true,   // Password to use when connecting to server
        '--protocol'                  => true,   // Connection protocol to use ('TCP','SOCKET','PIPE','MEMORY')
        '--socket'                    => false,  // For connections to localhost, the Unix socket file to use
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
        $this->setProperty('repository', class_exists('\\UCSDMath\\Configuration\\Config') ? Config::MERLIN_MYSQLDUMP_REPOSITORY : self::MERLIN_MYSQLDUMP_REPOSITORY); // required
        $this->setProperty('isMysqldumpEnabled', class_exists('\\UCSDMath\\Configuration\\Config') ? Config::IS_MERLIN_MYSQLDUMP_ENABLED : self::IS_MERLIN_MYSQLDUMP_ENABLED); // required
    }

    //--------------------------------------------------------------------------

    /**
     * Reset to default settings.
     *
     *
     * @return MerlinBackupInterface The current instance
     *
     * @api
     */
    public function reset(): MerlinBackupInterface
    {
        return $this;
    }

    //--------------------------------------------------------------------------

    /**
     * Return a unique v4 UUID (requires: ^PHP7).
     *
     * Generate random block of data and change the individual byte positions.
     * Decided not to use mt_rand() as a number generator (experienced collisions).
     *
     * According to RFC 4122 - Section 4.4, you need to change the following
     *    1) time_hi_and_version (bits 4-7 of 7th octet),
     *    2) clock_seq_hi_and_reserved (bit 6 & 7 of 9th octet)
     *
     * All of the other 122 bits should be sufficiently random.
     * {@see http://tools.ietf.org/html/rfc4122#section-4.4}
     *
     * @param bool $isUpper The option to modify text case [upper, lower]
     *
     * @return string The random UUID
     *
     * @api
     */
    public function getUuid(bool $isUpper = true): string
    {
        $data = random_bytes(16);
        assert(strlen($data) === 16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return true === $isUpper
            ? strtoupper(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4)))
            : vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
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

        /*
         * Open ConfigurationVault Settings.
         *
         * Create an open instance to mysqli (set: $this->mysqli)
         * This user must have permissions to dump the tables for the provided database parameter: $database.
         */
        $result = $this->databaseConnect($vaultFileDesignator, $vaultAccountDesignator)->mysqli->query('SHOW TABLES');
        $database = null === $database ? $this->get('database') : $database;
        $this->setProperty('backupDirectory', sprintf('%s/%s-%s', $this->repository, $this->todaysDate, $database));

        /* Check if our daily backup exists, if so, do nothing. */
        if (!file_exists($this->backupDirectory)) {
            /* Create our backup in this directory */
            mkdir($this->backupDirectory);
            chmod($this->backupDirectory, 0770);
            chgrp($this->backupDirectory, 'link');

            if ($result !== false) {
                /* Collect a list of all database table names */
                while ($row = $result->fetch_array()) {
                    $tables[] = $row[0];
                }
                /* Build mysqldump commands for each table and execute */
                foreach ($tables as $table) {
                    $filename = sprintf('%s/%s-%s-%s.sql%s', $this->backupDirectory, $this->todaysDate, $database, $table, $this->getCompressionFileType());

                    $shellCommand = 'TCP' === $this->get('protocol')
                        ? sprintf(
                            '%s --opt --compact --comments --host=%s --port=%s --protocol=%s --user=%s --password=%s %s %s %s %s > %s',
                            $this->mysqldump,
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
                            '%s --opt --compact --comments --host=%s --port=%s --user=%s --password=%s %s %s %s %s > %s',
                            $this->mysqldump,
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
                    chmod($filename, 0660);
                    chgrp($filename, 'link');
                    touch($filename, strtotime($this->todaysTimestamp), strtotime($this->todaysTimestamp));
                }
                touch($this->backupDirectory, strtotime($this->todaysTimestamp), strtotime($this->todaysTimestamp));
            }
        }

        return $this;
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
     * Set the names of the archived directories located in main repository.
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
     * @param string $handle The defined API connection handler
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
        $this->configVault->reset()->openVaultFile($vaultFileDesignator, $vaultAccountDesignator);

        $this
            ->set('hostname', $this->configVault->get('database_host'))
                ->set('username', $this->configVault->get('database_username'))
                    ->set('password', $this->configVault->get('database_password'))
                        ->set('database', $this->configVault->get('database_name'))
                            ->set('port', $this->configVault->get('database_port'))
                                ->set('socket', $this->configVault->get('database_socket'))
                                    ->set('protocol', $this->configVault->get('database_protocol'))
                                        ->set('charset', $this->configVault->get('database_charset'));

        $this->mysqli = new mysqli(
            (string) $this->get('hostname'),
            (string) $this->get('username'),
            (string) $this->get('password'),
            (string) $this->get('database'),
            (int)    $this->get('port')
            // (string) $this->get('socket')
        );

        $this->verifyDatabaseConnection()
            ->setCharacterEncoding($this->get('charset'))
                ->configVault
                    ->clear();

        return $this;
    }

    //--------------------------------------------------------------------------

    /**
     * Verify database connection.
     *
     * @param string $handle The defined API connection handler
     *
     * @return MerlinBackupInterface The current instance
     *
     * @api
     */
    protected function verifyDatabaseConnection(string $handle = 'mysqli'): MerlinBackupInterface
    {
        return 0 === $this->{$handle}->connect_errno
            ? $this
            : MerlinBackupException("Connection could not be established to Database ({$this->{$handle}->connect_error}). [S104]");
    }

    //--------------------------------------------------------------------------

    /**
     * Set the default character set for the database.
     *
     * @param string $charSet The default character set used in communicating with the database
     * @param string $handle  The defined API connection handler
     *
     * @return MerlinBackupInterface The current instance
     *
     * @api
     */
    protected function setCharacterEncoding(string $charSet = 'utf8mb4', string $handle = 'mysqli'): MerlinBackupInterface
    {
        return true === $this->{$handle}->set_charset($charSet)
            ? $this
            : MerlinBackupException("Error setting a default character set {$charSet}. Required when sending data to and from the database server. [A103]");
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
     * (-) Traversable toIterator($files);
     */
    use MerlinBackupServiceMethods;

    //--------------------------------------------------------------------------

    /**
     * Method implementations inserted:
     *
     * Method list: (+) @api, (-) protected or private visibility.
     *
     * (+) bool exists($files);
     * (+) string getUniqueId(int $length = 16);
     * (+) string reverseString(string $payload);
     * (+) string numberToString(string $payload);
     * (+) string stringToNumber(string $payload);
     * (+) string repeatString(string $str, int $number);
     * (+) string getSha512(string $data = null, bool $isUpper = true);
     * (+) string randomToken(int $length = 32, string $chars = self::PASSWORD_TOKENS);
     * (+) int getRandomInt(int $min = self::MIN_RANDOM_INT, int $max = self::MAX_RANDOM_INT);
     * (-) int stringSize(string $payload);
     * (-) bool isReadable(string $filename);
     */
    use MerlinBackupStandardOperations;

    //--------------------------------------------------------------------------

    /**
     * Method implementations inserted:
     *
     * Method list: (+) @api, (-) protected or private visibility.
     *
     * (+) iterable all();
     * (+) object init();
     * (+) string version();
     * (+) bool isString($str);
     * (+) bool has(string $key);
     * (+) string getClassName();
     * (+) int getInstanceCount();
     * (+) iterable getClassInterfaces();
     * (+) mixed getConst(string $key);
     * (+) bool isValidUuid(string $uuid);
     * (+) bool isValidEmail(string $email);
     * (+) bool isValidSHA512(string $hash);
     * (+) bool doesFunctionExist(string $functionName);
     * (+) bool isStringKey(string $str, iterable $keys);
     * (+) mixed get(string $key, string $subkey = null);
     * (+) mixed getProperty(string $name, string $key = null);
     * (+) mixed __call(string $callback, iterable $parameters);
     * (+) object set(string $key, $value, string $subkey = null);
     * (+) object setProperty(string $name, $value, string $key = null);
     * (-) Exception throwExceptionError(iterable $error);
     * (-) InvalidArgumentException throwInvalidArgumentExceptionError(iterable $error);
     */
    use ServiceFunctions;

    //--------------------------------------------------------------------------
}
