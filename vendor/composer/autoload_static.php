<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit1a04d0127de8d63f0daa47a57848bedc
{
    public static $prefixLengthsPsr4 = array (
        'A' => 
        array (
            'App\\' => 4,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'App\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit1a04d0127de8d63f0daa47a57848bedc::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit1a04d0127de8d63f0daa47a57848bedc::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit1a04d0127de8d63f0daa47a57848bedc::$classMap;

        }, null, ClassLoader::class);
    }
}
