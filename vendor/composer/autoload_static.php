<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitad6299e9ea8a94c3204d998e6f536905
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'PHPMailer\\PHPMailer\\' => 20,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'PHPMailer\\PHPMailer\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpmailer/phpmailer/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitad6299e9ea8a94c3204d998e6f536905::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitad6299e9ea8a94c3204d998e6f536905::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitad6299e9ea8a94c3204d998e6f536905::$classMap;

        }, null, ClassLoader::class);
    }
}
