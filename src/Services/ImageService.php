<?php


namespace App\Services;


use Aws\Sdk;
use Symfony\Component\HttpFoundation\File\File;


class ImageService extends AbstractShowService
{

    private $sdk;

    public function __construct(Sdk $sdk)
    {
        $this->sdk = $sdk;
    }

    public function checkDimensions(File $file, array $dimensionsAssert)
    {
        try{

        $dimensions = getimagesize($file->getPath().'\\'.$file->getFilename());
        } catch (\Exception $e) {
            return false;
        }
        $width = $dimensions[0];
        $height = $dimensions[0];
        $widthAssert  = $dimensionsAssert[0];
        $heightAssert = $dimensionsAssert[1];
        return $width <= $widthAssert and $height <= $heightAssert;
    }

    /**
     * @param string $filePath
     * @param string $fileName
     * @throws \Exception
     */
    public function upload(string $filePath, string $fileName)
    {
        if(!file_exists($filePath)) {
            throw new \Exception();
        }
        $keyName = 'avatars/'.basename($fileName);
        $s3Client = $this->sdk->createS3();
        try{
            $s3Client->putObject(
                [
                    'Bucket' => 'tveso',
                    'Key' => $keyName,
                    'ContentType'  => mime_content_type($filePath),
                    'SourceFile' => $filePath,
                    'ACL' => 'public-read'
                ]
            );
        } catch (\Exception $e) {
            throw new \Exception();
        }
    }

    /**
     * @param string $avatar
     * @param $imageContent
     * @param string $contentType
     * @throws \Exception
     */
    public function uploadFromBody(string $avatar, $imageContent, string $contentType)
    {
        $keyName = 'avatars/'.basename($avatar);
        $s3Client = $this->sdk->createS3();
        try{
            $s3Client->putObject(
                [
                    'Bucket' => 'tveso',
                    'Key' => $keyName,
                    'ContentType'  => $contentType,
                    'Body' => $imageContent,
                    'ACL' => 'public-read'
                ]
            );
        } catch (\Exception $e) {
            throw $e;
        }
    }
}