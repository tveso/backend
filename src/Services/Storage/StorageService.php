<?php
/**
 * Date: 18/12/2018
 * Time: 23:58
 */

namespace App\Services\Storage;


interface StorageService
{

    /**
     * @param string $filePath
     * @param string $fileName
     * @throws \Exception
     */
    public function upload(string $filePath, string $fileName);

    /**
     * @param string $avatar
     * @param $imageContent
     * @param string $contentType
     * @throws \Exception
     */
    public function uploadFromBody(string $avatar, $imageContent, string $contentType);
}