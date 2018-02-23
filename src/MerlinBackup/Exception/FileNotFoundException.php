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
 * FileNotFoundException class is thrown when a file couldn't be found.
 *
 * Method list: (+) @api, (-) protected or private visibility.
 *
 * IOException __construct(string $message, int $code = 0, \Exception $previous = null, string $path = null);
 *
 * @author Daryl Eisner <deisner@ucsd.edu>
 */
class FileNotFoundException extends IOException
{
    /**
     * Constants.
     *
     * @var string VERSION The version number
     *
     * @api
     */
    public const VERSION = '2.5.0';

    //--------------------------------------------------------------------------

    /**
     * Properties.
     */

    //--------------------------------------------------------------------------

    /**
     * Constructor.
     *
     * @param string $message  The exception message
     * @param int    $code     The exception code
     * @param string $previous The throwable interface through \Exception
     * @param string $path     The file or directory reference in error
     *
     * @api
     */
    public function __construct(string $message, int $code = 0, \Exception $previous = null, string $path = null)
    {
        if (null === $message) {
            $message = null === $path
                ? 'File could not be found.'
                : sprintf('File "%s" could not be found.', $path);
        }

        parent::__construct($message, $code, $previous, $path);
    }

    //--------------------------------------------------------------------------
}
