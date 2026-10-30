<?php declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\AutoloadConfig;

/**
 * -------------------------------------------------------------------
 * AUTOLOADER CONFIGURATION
 * -------------------------------------------------------------------
 * This file defines the namespaces and class maps so the Autoloader
 * can find the files as needed.
 */
class Autoload extends AutoloadConfig
{
    /**
     * -------------------------------------------------------------------
     * Namespaces
     * -------------------------------------------------------------------
     * This maps the locations of any namespaces in your application to
     * their location on the file system. These are used by the autoloader
     * to locate files the first time they have been instantiated.
     *
     * @var array<string, list<string>|string>
     */
    public $psr4 = [
        APP_NAMESPACE => APPPATH,
    ];

    /**
     * -------------------------------------------------------------------
     * Class Map
     * -------------------------------------------------------------------
     * The class map provides a map of class names and their exact
     * location on the drive.
     *
     * @var array<string, string>
     */
    public $classmap = [];

    /**
     * -------------------------------------------------------------------
     * Files
     * -------------------------------------------------------------------
     * The files array provides a list of paths to __non-class__ files
     * that will be autoloaded.
     *
     * @var list<string>
     */
    public $files = [];

    /**
     * -------------------------------------------------------------------
     * Helpers
     * -------------------------------------------------------------------
     * Prototype:
     *   $helpers = [
     *       'form',
     *   ];
     *
     * @var list<string>
     */
    // C'EST ICI LA CORRECTION : on ajoute 'auth' pour que Shield fonctionne partout
    public $helpers = ['setting', 'auth'];
}