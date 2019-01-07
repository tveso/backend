<?php


namespace App\Services;


use App\Services\Storage\StorageService;
use Aws\Sdk;
use Symfony\Component\HttpFoundation\File\File;


class ImageService extends AbstractShowService
{

    private $sdk;
    /**
     * @var StorageService
     */
    private $storageService;

    public function __construct(StorageService $storageService)
    {
        $this->storageService = $storageService;
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
        $this->storageService->upload($filePath, $fileName);
    }

    /**
     * @param string $avatar
     * @param $imageContent
     * @param string $contentType
     * @throws \Exception
     */
    public function uploadFromBody(string $avatar, $imageContent, string $contentType)
    {
        $this->storageService->uploadFromBody($avatar, $imageContent, $contentType);
    }
}