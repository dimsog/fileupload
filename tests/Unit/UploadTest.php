<?php

namespace Tests\Unit;

use Dimsog\FileUpload\Upload;
use GuzzleHttp\Psr7\UploadedFile;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;

class UploadTest extends TestCase
{
    public function testSuccessUpload()
    {
        $file = new UploadedFile(
            __DIR__ . '/test.txt',
            null,
            UPLOAD_ERR_OK,
            'test-new.txt'
        );
        $fileUpload = new Upload(__DIR__);
        $fileUpload->setMaxFileSize('10M');
        $fileUpload->setMimeTypes(['text/plain']);
        $fileUpload->upload($file);

        $this->assertFileDoesNotExist(__DIR__ . '/test.txt');
        $this->assertFileExists(__DIR__ . '/test-new.txt');
        rename(__DIR__ .'/test-new.txt', __DIR__ . '/test.txt');
        $this->assertFileExists(__DIR__ . '/test.txt');
    }

    public function testAutoCreateDirectory()
    {
        $stream = Utils::streamFor('123');
        $file = new UploadedFile(
            $stream,
            $stream->getSize(),
            UPLOAD_ERR_OK,
            $clientFileName = 'test-auto-create-directory.txt'
        );
        $uploadDirectory = __DIR__ . '/new-directory';
        $fileUpload = new Upload($uploadDirectory);
        $fileUpload->autoCreateDirectory();
        $fileUpload->upload($file);
        $this->assertDirectoryExists($uploadDirectory);
        $this->assertFileExists($uploadDirectory . '/' . $clientFileName);

        unlink($uploadDirectory . '/' . $clientFileName);
        rmdir($uploadDirectory);
    }
}