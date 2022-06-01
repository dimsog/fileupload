<?php

namespace Tests\Unit;

use Dimsog\FileUpload\Upload;
use GuzzleHttp\Psr7\UploadedFile;
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
}