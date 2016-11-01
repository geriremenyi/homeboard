<?php

namespace Resty\Utility\Configuration;

/**
 * Configuration
 *
 * Configuration singleton for application wide
 * configuration load and fetch.
 *
 * @package    Resty
 * @subpackage Utility
 * @author     Gergely Reményi <gergo@remenyicsalad.hu>
 */
class Configuration {

    /**
     * Contains the one and only configuration instance
     *
     * @var Configuration
     */
    private static $instance;

    /**
     * Contains the application wide configurations
     *
     * @var array
     */
    private $configurations = array();

    /**
     * Configuration private constructor.
     */
    private function __construct(){}

    /**
     * Configuration private copy.
     */
    private function __clone(){}

    /**
     * Get the singleton instance
     *
     * @return Configuration
     */
    public static function getInstance() : Configuration {
        return (self::$instance = self::$instance ?? new self());
    }

    /**
     * Get a specific configuration value
     *
     * @param string $type - The type of the variable (containing ini file name)
     * @param string $name - The key of the variable
     * @return string
     */
    public function getConfigurationValue(string $type, string $name = null) {
        if(array_key_exists($type, $this->configurations)) {
            if($name == null) {
                // Return with the entire type object
                return $this->configurations[$type];
            }
            else {
                // Check if name exists in type array
                if(array_key_exists($name, $this->configurations[$type])) {
                    return $this->configurations[$type][$name];
                }
            }
        }

        // TODO Otherwise 500 Error

    }

    /**
     * Set configuration variables by the ini files (folder path)
     *
     * @param string $iniFolder - The folder path of the ini files with the '/' symbol at the end
     * @param bool $forceRefresh - Is force refresh allowed when it is already loaded
     */
    public function setConfigurationsByIniFolder(string $iniFolder = ROOT . DS . 'framework' . DS . 'configuration' . DS, bool $forceRefresh = false) {
        if(empty($this->configurations)) {
            $this->loadIniFiles($iniFolder);
        } else {
            if($forceRefresh) {
                $this->loadIniFiles($iniFolder);
            }
        }
    }

    /**
     * Load load ini files into variables
     *
     * @param string $folder - The folder path of the ini files with the '/' symbol at the end
     */
    private function loadIniFiles(string $folder) {
        $iniFiles = glob($folder . '*.{ini}', GLOB_BRACE);
        foreach ($iniFiles as $ini) {
            if($config = parse_ini_file($ini)) {
                $arrayKeyName = str_replace('.ini', '', substr($ini, strrpos($ini, DS) + 1, strlen($ini) - strrpos($ini, DS)));
                $this->configurations[$arrayKeyName] = $config;
            } else {
                // TODO throw 500 server error because the  config file can not be loaded
            }
        }
    }

    /**
     * Get the entire configuration array
     *
     * @return array
     */
    public function getConfigurations() : array {
        return $this->configurations;
    }

    /**
     * Set the configuration by an array
     *
     * @param array $configurations - Configurations array
     */
    public function setConfigurations(array $configurations) {
        $this->configurations = $configurations;
    }

}