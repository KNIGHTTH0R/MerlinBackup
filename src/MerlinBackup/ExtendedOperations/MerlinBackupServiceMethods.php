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

namespace UCSDMath\MerlinBackup\ExtendedOperations;

use UCSDMath\MerlinBackup\Exception\IOException;
use UCSDMath\MerlinBackup\MerlinBackupInterface;

/**
 * MerlinBackupServiceMethods is the default implementation of {@link MerlinBackupServiceMethodsInterface} which
 * provides routine MerlinBackup methods that are commonly used in the framework.
 *
 * {@link MerlinBackupServiceMethods} is a trait method implimentation requirement used in this framework.
 * This set is specifically used in MerlinBackup classes.
 *
 * use UCSDMath\MerlinBackup\ExtendedOperations\MerlinBackupServiceMethods;
 * use UCSDMath\MerlinBackup\ExtendedOperations\MerlinBackupServiceMethodsInterface;
 *
 * Method list: (+) @api, (-) protected or private visibility.
 *
 * (-) Traversable toIterator($files);
 *
 * MerlinBackupServiceMethods provides a common set of implementations where needed. The MerlinBackupServiceMethods
 * trait and the MerlinBackupServiceMethodsInterface should be paired together.
 *
 * @author Daryl Eisner <deisner@ucsd.edu>
 */
trait MerlinBackupServiceMethods
{
    /**
     * Properties.
     */

    //--------------------------------------------------------------------------

    /**
     * Abstract Method Requirements.
     */

    //--------------------------------------------------------------------------

    /**
     * Return as PHP Traversable Instance.
     *
     * {@see https://webmozart.io/blog/2012/10/07/give-the-traversable-interface-some-love/}
     *
     * @param mixed $files The string, array, object.
     *
     * @return \Traversable
     */
    protected function toIterator($files): \Traversable
    {
        if (!$files instanceof \Traversable) {
            $files = new \ArrayObject(is_array($files) ? $files : array($files));
        }

        return $files;
    }

    //--------------------------------------------------------------------------
}
