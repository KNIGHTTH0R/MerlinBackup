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

/**
 * MerlinBackupInterface is the interface implemented by all MerlinBackup classes.
 *
 * Method list: (+) @api.
 *
 * (+) MerlinBackupInterface reset();
 *
 * @author Daryl Eisner <deisner@ucsd.edu>
 *
 * @api
 */
interface MerlinBackupInterface
{
    /**
     * Constants.
     *
     * @var string CHARSET                                  The preferred character encoding set
     * @var string DEFAULT_TIME                             The default time used for timestamps on system
     * @var string HIDE_WARNINGS                            The option to hide command line warnings
     * @var string HASH_FUNCTION                            The default tool for hashing simple hashes
     * @var string TEST_DATA                                The hi there statement
     * @var int    MIN_RANDOM_INT                           The input length
     * @var int    MAX_RANDOM_INT                           The input length
     * @var string PASSWORD_TOKENS                          The string characters used for simple tokens
     * @var string SEED_HASH_TOKENS                         The string characters used for seeding tokens
     * @var string HEXADECIMAL_TOKENS                       The string characters used for hexadecimal tokens
     * @var string PRIMARY_HASH_TOKENS                      The string characters used for primary hash tokens
     * @var string DEFAULT_MYSQL_ADDRESS                    The default ip address (generally for testing)
     * @var string DEFAULT_MYSQL_HOSTNAME                   The default hostname (generally for testing)
     * @var string DEFAULT_MYSQL_USERNAME                   The default username (generally for testing)
     * @var string DEFAULT_MYSQL_PASSWORD                   The default password (generally for testing)
     * @var string DEFAULT_MYSQL_PORT                       The default port (generally for testing)
     * @var string DEFAULT_MIN_HASHIDS_LENGTH               The default minimum hash lengths (setting)
     * @var string DEFAULT_COMPRESSION_TYPE                 The default compression scheme ('None','GZIP','BZIP2','COMPRESS','LZMA')
     * @var bool   IS_COMPRESSION_ENABLED                   The option to enable compression of MySQL tables and database dump files
     * @var string MERLIN_MYSQLDUMP_UTILITY                 The mysqldump utility location on the system
     * @var string MERLIN_MYSQLDUMP_REPOSITORY              The directory repository location (for storage of daily backups)
     * @var string MERLIN_MYSQLDUMP_REPOSITORY_GROUP        The directory group settings for the repository (storage of all daily backups)
     * @var string MERLIN_MYSQLDUMP_REPOSITORY_PERMISSIONS  The directory permission settings for the repository (e.g., 0775 - storage of all daily backups)
     * @var string MERLIN_MYSQLDUMP_DAILYBACKUP_GROUP       The directory group settings for the daily backups
     * @var string MERLIN_MYSQLDUMP_DAILYBACKUP_PERMISSIONS The directory permission settings for the daily backups (e.g., 0660)
     * @var bool   IS_MERLIN_MYSQLDUMP_ENABLED              The option to enable file dumps of MySQL tables/databases
     */
    public const CHARSET                                    = 'utf-8';
    public const DEFAULT_TIME                               = '060000';
    public const HIDE_WARNINGS                              = '2>/dev/null';
    public const HASH_FUNCTION                              = 'sh1';
    public const TEST_DATA                                  = 'Hi There...';
    public const MIN_RANDOM_INT                             = 1;
    public const MAX_RANDOM_INT                             = 9999999999999999;
    public const PASSWORD_TOKENS                            = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    public const SEED_HASH_TOKENS                           = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    public const HEXADECIMAL_TOKENS                         = '0123456789ABCDEFabcdef';
    public const PRIMARY_HASH_TOKENS                        = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz/#!&$@*_.~+^=';
    public const DEFAULT_MYSQL_ADDRESS                      = '127.0.0.1';
    public const DEFAULT_MYSQL_HOSTNAME                     = 'localhost';
    public const DEFAULT_MYSQL_USERNAME                     = 'root';
    public const DEFAULT_MYSQL_PASSWORD                     = 'Sic-Parvis-Magna';
    public const DEFAULT_MYSQL_PORT                         = 3306;
    public const DEFAULT_MIN_HASHIDS_LENGTH                 = 30;
    public const DEFAULT_COMPRESSION_TYPE                   = 'GZIP';
    public const IS_COMPRESSION_ENABLED                     = true;
    public const MERLIN_MYSQLDUMP_UTILITY                   = '/usr/bin/mysqldump';
    public const MERLIN_MYSQLDUMP_REPOSITORY                = '/home/link/backup';
    public const MERLIN_MYSQLDUMP_REPOSITORY_GROUP          = 'link';
    public const MERLIN_MYSQLDUMP_REPOSITORY_PERMISSIONS    = 0770;
    public const MERLIN_MYSQLDUMP_DAILYBACKUP_GROUP         = 'link';
    public const MERLIN_MYSQLDUMP_DAILYBACKUP_PERMISSIONS   = 0660;
    public const IS_MERLIN_MYSQLDUMP_ENABLED                = false;

    //--------------------------------------------------------------------------

    /**
     * Reset to default settings.
     *
     *
     * @return MerlinBackupInterface The current instance
     *
     * @api
     */
    public function reset(): MerlinBackupInterface;
}
