<?php

declare(strict_types=1);

namespace Dimsog\FileUpload;

class FileInfo extends \SplFileInfo
{
    public function getMimeType(): ?string
    {
        $mimeType = mime_content_type($this->getRealPath());
        if ($mimeType === false) {
            return null;
        }
        return $mimeType;
    }
}