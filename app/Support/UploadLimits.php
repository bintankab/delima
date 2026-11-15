<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadLimits
{
    /**
     * The effective maximum upload size in bytes, i.e. the smaller of
     * `upload_max_filesize` and `post_max_size` from php.ini.
     */
    public static function maxDocumentBytes(): int
    {
        return (int) UploadedFile::getMaxFilesize();
    }

    /**
     * The effective maximum upload size in kilobytes, for Laravel's
     * file validation rule (`max:<kilobytes>`).
     */
    public static function maxDocumentKilobytes(): int
    {
        return intdiv(self::maxDocumentBytes(), 1024);
    }

    /**
     * The effective maximum upload size in whole megabytes, for display
     * in validation messages.
     */
    public static function maxDocumentMegabytes(): int
    {
        return intdiv(self::maxDocumentBytes(), 1024 * 1024);
    }
}
