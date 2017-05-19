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

namespace UCSDMath\MerlinBackup\ExtendedOperations;

use UCSDMath\MerlinBackup\Exception\IOException;
use UCSDMath\MerlinBackup\Exception\MerlinBackupException;

/**
 * MerlinBackupStandardOperations is the default implementation of {@link MerlinBackupStandardOperationsInterface} which
 * provides routine MerlinBackup methods that are commonly used in the framework.
 *
 * {@link MerlinBackupStandardOperations} is a trait method implimentation requirement used in this framework.
 * This set is specifically used in MerlinBackup classes.
 *
 * use UCSDMath\MerlinBackup\ExtendedOperations\MerlinBackupStandardOperations;
 * use UCSDMath\MerlinBackup\ExtendedOperations\MerlinBackupStandardOperationsInterface;
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
 * (+) int getRandomInt(int $min = self::MIN_RANDOM_INT, int $max = self::MAX_RANDOM_INT);
 * (-) int stringSize(string $payload);
 * (-) bool isReadable(string $filename);
 * (-) string randomToken(int $length = 32, string $chars = self::PASSWORD_TOKENS);
 *
 * MerlinBackupStandardOperations provides a common set of implementations where needed. The MerlinBackupStandardOperations
 * trait and the MerlinBackupStandardOperationsInterface should be paired together.
 *
 * @author Daryl Eisner <deisner@ucsd.edu>
 */
trait MerlinBackupStandardOperations
{
    /**
     * Properties.
     */

    //--------------------------------------------------------------------------

    /**
     * Abstract Method Requirements.
     */
    abstract protected function toIterator($files): \Traversable;
    abstract public function getProperty(string $name, string $key = null);

    //--------------------------------------------------------------------------

    /**
     * Check the existance of files or directories.
     *
     * @param string|iterable|\Traversable $files The filename, array of files, or \Traversable.
     *
     * @return bool
     *
     * @api
     */
    public function exists($files): bool
    {
        foreach ($this->toIterator($files) as $file) {
            if (strlen($file) > 258) {
                throw new IOException('Could not check if file exist because path length exceeds 258 characters.', 0, null, $file);
            }
            if (!file_exists($file)) {
                return false;
            }
        }

        return true;
    }

    //--------------------------------------------------------------------------

    /**
     * Return a unique id (CSPRNG Requires PHP v7.x).
     *
     * @param int $length The byte length
     *
     * @return string The ASCII string containing the hexadecimal of the input string
     *
     * @api
     */
    public function getUniqueId(int $length = 16): string
    {
        if (!is_callable('random_bytes')) {
            throw new MerlinBackupException('There is no suitable CSPRNG installed on your system');
        }

        /* Create string of cryptographic random bytes */
        return bin2hex(random_bytes($length));
    }

    //--------------------------------------------------------------------------

    /**
     * Reverse a string.
     *
     * @param string $payload The string to be reversed
     *
     * @return string The reversed string.
     *
     * @api
     */
    public function reverseString(string $payload): string
    {
        return strrev($payload);
    }

    //--------------------------------------------------------------------------

    /**
     * Convert numbers to string.
     *
     * Converts the zero-padded decimal number (3-digit) back to it's
     * character equivalent created by stringToNumber(string $payload)
     *
     * @param string $payload The data to decode
     *
     * @return string The string conversion
     *
     * @api
     */
    public function numberToString(string $payload): string
    {
        return join(array_map('chr', str_split($payload, 3)));
    }

    //--------------------------------------------------------------------------

    /**
     * Convert string to number.
     *
     * Converts every byte into its decimal number equivalent, zero-padding
     * it to a fixed length of 3 digits so it can be unambiguously converted back.
     *
     * @param string $payload The data to encode
     *
     * @return string The data as a string of numbers
     *
     * @api
     */
    public function stringToNumber(string $payload): string
    {
        return join(array_map(
            function ($number) {
                return sprintf('%03d', $number);
            },
            unpack('C*', $payload)
        ));
    }

    //--------------------------------------------------------------------------

    /**
     * Repeat a string.
     *
     * @param string $str    The string to be repeated
     * @param int    $number The number of time the string should be repeated
     *
     * @return string The repeated string.
     *
     * @api
     */
    public function repeatString(string $str, int $number): string
    {
        return str_repeat($str, $number);
    }

    //--------------------------------------------------------------------------

    /**
     * Return a SHA-512 hash (CSPRNG Requires PHP v7.x).
     *
     * @param string $data    The string to translate
     * @param bool   $isUpper The option to modify case [upper, lower]
     *
     * @return string The hash string (128-characters)
     *
     * @api
     */
    public function getSha512(string $data = null, bool $isUpper = true): string
    {
        if (!is_callable('random_bytes')) {
            throw new MerlinBackupException('There is no suitable CSPRNG installed on your system');
        }
        $data = null === $data ? $this->getUniqueId() : $data;

        return true === $isUpper ? strtoupper(hash('sha512', $data)) : hash('sha512', $data);
    }

    //--------------------------------------------------------------------------

    /**
     * Generates cryptographically secure pseudo-random integers (CSPRNG Requires PHP v7.x).
     *
     * @param int $min The lowest value to be returned, which must be PHP_INT_MIN or higher
     * @param int $max The highest value to be returned, which must be less than or equal to PHP_INT_MAX.
     *
     * @return int A cryptographically secure random integer in a range min-to-max
     *
     * @throws MerlinBackupException When an invalid max value is provided
     *
     * @api
     */
    public function getRandomInt(int $min = self::MIN_RANDOM_INT, int $max = self::MAX_RANDOM_INT): int
    {
        if (!is_callable('random_bytes')) {
            throw new MerlinBackupException('There is no suitable CSPRNG installed on your system');
        }

        if ($max < $min) {
            throw new MerlinBackupException(sprintf('Maximum integer must not be less than minimum: Max is: %s, Min is: %s. (%s)', $max, $min, __METHOD__));
        }

        return random_int($min, $max);
    }

    //--------------------------------------------------------------------------

    /**
     * Provides the length of a string.
     *
     * @param string $payload The string being checked for length
     *
     * @return int Returns the number of characters
     */
    protected function stringSize(string $payload): int
    {
        return mb_strlen($payload, self::CHARSET);
    }

    //--------------------------------------------------------------------------

    /**
     * Tells whether a file exists and is readable.
     *
     * @param string $filename Path to the file
     *
     * @return bool
     *
     * @throws IOException When windows path is longer than 258 characters
     */
    protected function isReadable(string $filename): bool
    {
        if ('\\' === \DIRECTORY_SEPARATOR && strlen($filename) > 258) {
            throw new IOException('Could not check if file is readable because path length exceeds 258 characters.', 0, null, $filename);
        }

        return is_readable($filename);
    }

    //--------------------------------------------------------------------------

    /**
     * Get a random token string (CSPRNG Requires PHP v7.x).
     *
     * @param int $length The length of the token
     * @param string $chars The string characters to use for the token
     *
     * @return string The random token string
     *
     * @api
     */
    public function randomToken(int $length = 32, string $chars = self::PASSWORD_TOKENS): string
    {
        if (!is_callable('random_bytes')) {
            throw new MerlinBackupException('There is no suitable CSPRNG installed on your system');
        }
        [$bytes, $count, $result] = [random_bytes($length), strlen($chars), null];
        foreach (str_split($bytes) as $byte) {
            $result .= $chars[ord($byte) % $count];
        }

        return $result;
    }

    //--------------------------------------------------------------------------
}
