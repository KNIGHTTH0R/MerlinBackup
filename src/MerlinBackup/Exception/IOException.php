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

namespace UCSDMath\MerlinBackup\Exception;

/**
 * IOException class is thrown when a MerlinBackup operation failure happens.
 *
 * Method list: (+) @api, (-) protected or private visibility.
 *
 * (+) IOExceptionInterface __construct(string $message, int $code = 0, \Exception $previous = null, string $path = null);
 * (+) IOExceptionInterface getPath();
 *
 * @author Daryl Eisner <deisner@ucsd.edu>
 */
class IOException extends \RuntimeException implements IOExceptionInterface
{
    /**
     * Constants.
     *
     * @var string VERSION The version number
     *
     * @api
     */
    public const VERSION = '1.19.0';

    //--------------------------------------------------------------------------

    /**
     * Properties.
     *
     * @var string $path The file or directory reference in error
     */
    private $path;

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
        $this->path = $path;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Returns the associated path for the exception.
     *
     * @return string The path
     */
    public function getPath(): string
    {
        return $this->path;
    }
}
