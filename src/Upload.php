<?php

declare(strict_types=1);

namespace Dimsog\FileUpload;

use Dimsog\FileUpload\Exceptions\DirectoryDoesNotExistsException;
use Dimsog\FileUpload\Exceptions\DirectoryIsNotWritableException;
use Dimsog\FileUpload\Exceptions\FileExistsException;
use Dimsog\FileUpload\Exceptions\InvalidMimeTypeException;
use Dimsog\FileUpload\Exceptions\MaxFileSizeException;
use Psr\Http\Message\UploadedFileInterface;

class Upload
{
    private string $targetPath;

    private ?string $newFileName = null;

    private ?string $maxFileSize = null;

    private array $mimeTypes = [];

    private string $errorDirectoryIsNotWritable = 'Failed to write file to disk';

    private string $errorMaxFileSize = 'The file is too large. Allowed maximum size is [size].';

    private string $errorMimeTypes = 'Allowed mime types are [mime].';

    private bool $generateUniqueName = false;

    private bool $overwrite = false;

    private bool $createDirectory = false;

    private int $createDirectoryPermissions = 0777;


    public function __construct(string $pathToUpload)
    {
        $this->targetPath = $pathToUpload;
    }

    public function setMaxFileSize(string $maxFileSize): self
    {
        $this->maxFileSize = strtolower($maxFileSize);
        return $this;
    }

    public function setMimeTypes(array $mimeTypes): self
    {
        $this->mimeTypes = $mimeTypes;
        return $this;
    }

    public function setName(string $name): self
    {
        $this->newFileName = $name;
        return $this;
    }

    public function generateUniqueName(): self
    {
        $this->generateUniqueName = true;
        return $this;
    }

    public function overwrite(): self
    {
        $this->overwrite = true;
        return $this;
    }

    public function autoCreateDirectory(int $permissions = 0777): self
    {
        $this->createDirectory = true;
        $this->createDirectoryPermissions = $permissions;
        return $this;
    }

    public function upload(UploadedFileInterface $file): FileInfo
    {
        $fileInfo = new FileInfo($file->getStream()->getMetadata('uri'));

        $this->validateOrFail($fileInfo);
        $this->checkTargetDirectoryPathBeforeUpload();

        $targetPath = $this->generateFullTargetPath($file->getClientFilename());
        if ($this->overwrite === false && file_exists($targetPath)) {
            throw new FileExistsException('File already exists');
        }

        $file->moveTo($targetPath);
        return new FileInfo($targetPath);
    }

    private function validateOrFail(FileInfo $fileInfo): void
    {
        if (!empty($this->mimeTypes) && !in_array($fileInfo->getMimeType(), $this->mimeTypes)) {
            throw new InvalidMimeTypeException($this->buildErrorString($this->errorMimeTypes));
        }
        if (!empty($this->maxFileSize) && $fileInfo->getSize() > $this->getConvertedMaxFileSize()) {
            throw new MaxFileSizeException($this->buildErrorString($this->errorMaxFileSize));
        }
    }

    private function buildErrorString(string $error): string
    {
        return str_replace([
            '[size]',
            '[mime]'
        ], [
            $this->maxFileSize,
            implode(', ', $this->mimeTypes)
        ],
            $error
        );
    }

    private function getConvertedMaxFileSize(): int
    {
        $units = [
            'b' => 1,
            'k' => 1024,
            'm' => 1048576,
            'g' => 1073741824
        ];
        $bytes = (int) $this->maxFileSize;
        $unit = substr($this->maxFileSize, -1);
        if (isset($units[$unit])) {
            $bytes = $bytes * $units[$unit];
        }
        return $bytes;
    }

    private function checkTargetDirectoryPathBeforeUpload(): void
    {
        $targetPath = $this->targetPath;
        if (!file_exists($targetPath)) {
            if (!$this->createDirectory) {
                throw new DirectoryDoesNotExistsException();
            }
            if (!@mkdir($targetPath, $this->createDirectoryPermissions, true)) {
                throw new DirectoryIsNotWritableException($this->errorDirectoryIsNotWritable);
            }
        }
        if (!is_writable($targetPath)) {
            throw new DirectoryIsNotWritableException();
        }
    }

    private function generateFullTargetPath(string $clientFileName): string
    {
        $targetPath = $this->targetPath;
        $fileName = !empty($this->newFileName) ? $this->newFileName : $clientFileName;
        $fullPath = $targetPath . '/' . $fileName;

        if (file_exists($fullPath) && $this->generateUniqueName) {
            $i = 1;
            while (file_exists($fullPath)) {
                $fullPath = $targetPath . '/' . $i . '-' . $fileName;
                $i++;
            }
        }
        return $fullPath;
    }
}