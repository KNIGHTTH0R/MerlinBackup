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

namespace UCSDMath\MerlinBackup\Exception;

/**
 * IOExceptionInterface is the interface for file and input/output stream related
 * exceptions thrown by all MerlinBackup classes.
 *
 * Method list: (+) @api.
 *
 * (+) string getPath();
 *
 * @author Daryl Eisner <deisner@ucsd.edu>
 *
 * @api
 */
interface IOExceptionInterface extends ExceptionInterface
{
    /**
     * Returns the associated path for the exception
     *
     * @return string The path
     */
    public function getPath(): string;

    //--------------------------------------------------------------------------
}
