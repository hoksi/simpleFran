<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitbd3e3091cc995475da0c3478ace84309
{
    public static $files = array (
        '7b11c4dc42b3b3023073cb14e519683c' => __DIR__ . '/..' . '/ralouphie/getallheaders/src/getallheaders.php',
        'a5d661e5dc6fd26959b3b66fc5d54d51' => __DIR__ . '/../..' . '/../helper/common.helper.php',
    );

    public static $prefixLengthsPsr4 = array (
        'T' => 
        array (
            'Template_\\' => 10,
        ),
        'P' => 
        array (
            'Psr\\Http\\Message\\' => 17,
        ),
        'L' => 
        array (
            'Laminas\\EventManager\\' => 21,
        ),
        'I' => 
        array (
            'Intervention\\Image\\' => 19,
        ),
        'G' => 
        array (
            'GuzzleHttp\\Psr7\\' => 16,
        ),
        'F' => 
        array (
            'Forbiz\\Model\\' => 13,
        ),
        'C' => 
        array (
            'CodeIgniter\\' => 12,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Template_\\' => 
        array (
            0 => __DIR__ . '/../..' . '/../class/Template_.2.2.8',
        ),
        'Psr\\Http\\Message\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/http-factory/src',
            1 => __DIR__ . '/..' . '/psr/http-message/src',
        ),
        'Laminas\\EventManager\\' => 
        array (
            0 => __DIR__ . '/..' . '/laminas/laminas-eventmanager/src',
        ),
        'Intervention\\Image\\' => 
        array (
            0 => __DIR__ . '/..' . '/intervention/image/src/Intervention/Image',
        ),
        'GuzzleHttp\\Psr7\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/psr7/src',
        ),
        'Forbiz\\Model\\' => 
        array (
            0 => __DIR__ . '/../..' . '/../models',
        ),
        'CodeIgniter\\' => 
        array (
            0 => __DIR__ . '/../..' . '/../class/CodeIgniter',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'Hoksi\\Qb\\CI_DB_driver' => __DIR__ . '/../..' . '/../class/Hoksi/Qb/CI_DB_driver.php',
        'Hoksi\\Qb\\CI_DB_query_builder' => __DIR__ . '/../..' . '/../class/Hoksi/Qb/CI_DB_query_builder.php',
        'Hoksi\\Qb\\NunaQb' => __DIR__ . '/../..' . '/../class/Hoksi/Qb/NunaQb.php',
        'Hoksi\\Qb\\NunaResult' => __DIR__ . '/../..' . '/../class/Hoksi/Qb/NunaResult.php',
        'Hoksi\\Qb\\Qb' => __DIR__ . '/../..' . '/../class/Hoksi/Qb/Qb.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitbd3e3091cc995475da0c3478ace84309::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitbd3e3091cc995475da0c3478ace84309::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitbd3e3091cc995475da0c3478ace84309::$classMap;

        }, null, ClassLoader::class);
    }
}
