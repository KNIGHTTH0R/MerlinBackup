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
    public const VERSION = '1.16.0';

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
