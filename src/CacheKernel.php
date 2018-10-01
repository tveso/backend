<?php
// src/CacheKernel.php
namespace App;

use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache;

class CacheKernel extends HttpCache
{
    protected function getOptions()
    {
        return array(
            'stale_if_error'         => 0,
        );
    }
}