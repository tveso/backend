<?php
/**
 * Date: 19/12/2018
 * Time: 0:08
 */

namespace App\Services\Storage;


class FileStorage implements StorageService
{

    /**
     * @param string $filePath
     * @param string $fileName
     * @throws \Exception
     */
    public function upload(string $filePath, string $fileName)
    {
        rename($filePath, getenv('AVATAR_PATH')."$fileName.jpg");
    }

    /**
     * @param string $avatar
     * @param $imageContent
     * @param string $contentType
     * @throws \Exception
     */
    public function uploadFromBody(string $avatar, $imageContent, string $contentType)
    {
        file_put_contents(getenv('AVATAR_PATH')."$avatar", $imageContent);
    }
}