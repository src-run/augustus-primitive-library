<?php

/*
 * This file is part of the `src-run/augustus-primitive-library` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 * (c) Scribe Inc      <scr@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Primitive;

use SR\Exception\InvalidArgumentException;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class FileInfo.
 */
class FileInfo extends SplFileInfo
{
    /**
     * @param string      $file
     * @param string|null $relativePath
     * @param string|null $relativePathname
     * @param bool        $resolvePath
     */
    public function __construct($file, $relativePath = null, $relativePathname = null, $resolvePath = true)
    {
        if (true === $resolvePath && false !== $realFile = realpath($file)) {
            $file = $realFile;
        }

        if ($relativePath === null || $relativePathname === null) {
            $relativePathname = static::absoluteToRelativePath($file);
            $relativePath = dirname($relativePathname);
        }

        parent::__construct($file, $relativePath, $relativePathname);
    }

    /**
     * @param \SplFileInfo $file
     *
     * @return static
     */
    public static function createFromSplFileInfo(\SplFileInfo $file)
    {
        return new static($file->getPathname());
    }

    /**
     * @param int $precision
     *
     * @return string
     */
    public function getSizeHuman($precision = 2)
    {
        $bytes = $this->getSize();

        $sizes = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$precision}f", $bytes / pow(1024, $factor)).@$sizes[(int) $factor];
    }

    /**
     * @param string      $filePath
     * @param string|null $relativeTo
     *
     * @return string
     */
    public static function absoluteToRelativePath($filePath, $relativeTo = null)
    {
        if ($relativeTo === null) {
            $relativeTo = getcwd();
        }

        if (substr($filePath, 0, 1) !== '/' || substr($relativeTo, 0, 1) !== '/') {
            throw InvalidArgumentException::create(sprintf('File path (%s) and new relative path (%s) must be absolute pathnames.', $filePath, $relativeTo));
        }

        $filePathParts = explode(DIRECTORY_SEPARATOR, rtrim($filePath, DIRECTORY_SEPARATOR));
        $relativeToParts = explode(DIRECTORY_SEPARATOR, rtrim($relativeTo, DIRECTORY_SEPARATOR));

        $commonPartsCount = 0;

        /* Count how many parts the two paths have in common, since those parts
         * aren't included in the relative path.
         */
        for ($i = 0; $i < max(count($filePathParts), count($relativeToParts)); ++$i) {
            if (!isset($filePathParts[$i]) || !isset($relativeToParts[$i])) {
                break;
            }

            if ($filePathParts[$i] !== $relativeToParts[$i]) {
                break;
            }

            ++$commonPartsCount;
        }

        $relativeParts = [];

        if (count($relativeToParts) > $commonPartsCount) {
            $replacementCount = count($relativeToParts) - $commonPartsCount;
            $relativeParts = array_fill(0, $replacementCount, '..');
        }

        if (count($filePathParts) > $commonPartsCount) {
            $relativeToRemainingParts = array_slice($filePathParts, $commonPartsCount);
            $relativeParts = array_merge($relativeParts, $relativeToRemainingParts);
        }

        return implode('/', $relativeParts);
    }
}

/* EOF */
