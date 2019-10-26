<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit7dd24b75ecf3d6bbc7fe3428731e42ac
{
    public static $files = array (
        'decc78cc4436b1292c6c0d151b19445c' => __DIR__ . '/..' . '/phpseclib/phpseclib/phpseclib/bootstrap.php',
    );

    public static $prefixLengthsPsr4 = array (
        'p' => 
        array (
            'phpseclib\\' => 10,
        ),
        'I' => 
        array (
            'Id4me\\Test\\' => 11,
            'Id4me\\RP\\' => 9,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'phpseclib\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpseclib/phpseclib/phpseclib',
        ),
        'Id4me\\Test\\' => 
        array (
            0 => __DIR__ . '/..' . '/id4me/id4me-rp/tests',
        ),
        'Id4me\\RP\\' => 
        array (
            0 => __DIR__ . '/..' . '/id4me/id4me-rp/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit7dd24b75ecf3d6bbc7fe3428731e42ac::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit7dd24b75ecf3d6bbc7fe3428731e42ac::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}