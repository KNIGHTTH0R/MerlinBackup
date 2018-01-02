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

use UCSDMath\Filesystem\FilesystemInterface;
use UCSDMath\MerlinBackup\Exception\MerlinBackupException;
use UCSDMath\MerlinBackup\Exception\FileNotFoundException;
use UCSDMath\Configuration\ConfigurationVault\ConfigurationVaultInterface;

/**
 * MerlinBackup is the default implementation of {@link MerlinBackupInterface} which
 * provides routine MerlinBackup methods that are commonly used in the framework.
 *
 * {@link AbstractMerlinBackup} is basically a base class for various Backup MerlinBackup
 * routines which this class extends.
 *
 * Method list: (+) @api, (-) protected or private visibility.
 *
 * (+) MerlinBackupInterface __construct(FilesystemInterface $filesystem);
 * (+) void __destruct();
 * (+) string getRandomHex(int $length = 32);
 * (+) MerlinBackupInterface setMerlinBackupDefaultEnvironment(string $value);
 *
 * @author Daryl Eisner <deisner@ucsd.edu>
 */
class MerlinBackup extends AbstractMerlinBackup implements MerlinBackupInterface
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
     */

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
        parent::__construct($filesystem, $configVault);
    }

    //--------------------------------------------------------------------------

    /**
     * Destructor.
     *
     * @api
     */
    public function __destruct()
    {
        parent::__destruct();
    }

    //--------------------------------------------------------------------------

    /**
     * Add comments to mysqldump files.
     *
     * @return MerlinBackupInterface The current instance
     */
    public function setDontSkipComments(): MerlinBackupInterface
    {
        return $this
            ->setProperty('mysqlDumpOptions', false, '--skip-comments')
                ->setConfiguredDumpOptions();
    }

    //--------------------------------------------------------------------------

    /**
     * Do not add comments to mysqldump files.
     *
     * @return MerlinBackupInterface The current instance
     */
    public function setSkipComments(): MerlinBackupInterface
    {
        return $this
            ->setProperty('mysqlDumpOptions', true, '--skip-comments')
                ->setConfiguredDumpOptions();
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
     * @param bool $isUpper The option to modify case [upper, lower]
     *
     * @return string The random UUID4
     *
     * @api
     */
    public function getUuid(bool $isUpper = true): string
    {
        /* Generate from PHP 7 Secure Random Generator */
        $data = random_bytes(16);
        assert(strlen($data) === 16);
        $data[6] = chr(ord($data[6]) & static::CLEAR_VERSION | static::UUID4_VERSION);
        $data[8] = chr(ord($data[8]) & static::CLEAR_VARIANT | static::RFC_BIT_SIZE);

        return true === $isUpper
            ? strtoupper(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4)))
            : vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    //--------------------------------------------------------------------------

    /**
     * Get a random hex string (CSPRNG Requires PHP v7.x).
     *
     * @param int $length The length of the token
     *
     * @return string The random token string
     *
     * @api
     */
    public function getRandomHex(int $length = 32): string
    {
        if (!is_callable('random_bytes')) {
            throw new MerlinBackupException('There is no suitable CSPRNG installed on your system');
        }

        return bin2hex(random_bytes($length/2));
    }

    //--------------------------------------------------------------------------

    /**
     * Delete an item value from an array.
     *
     * @param string $item      The string item value to delete
     * @param array  $arrayList The array list to check
     *
     * @return array The filtered result array
     */
    protected function arrayDeleteItem(string $item, array $arrayList): array
    {
        return array_diff($arrayList, array($item));
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
     * Set the default environment (e.g., 'development', 'staging', 'production').
     *
     * @param string $value The default environment type
     *
     * @return MerlinBackupInterface The current instance
     *
     * @api
     */
    public function setMerlinBackupDefaultEnvironment(string $value): MerlinBackupInterface
    {
        return $this->setProperty('vaultDefaultEnvironment', strtolower(trim($value)));
    }

    //--------------------------------------------------------------------------
}
