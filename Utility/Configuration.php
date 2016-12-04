<?php

namespace Resty\Utility;

use Resty\Exception\ {
    FileNotFoundException, InvalidParametersException
};

/**
 * Configuration
 *
 * Configuration singleton for application wide
 * configuration load.
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
     * Set configuration variables by the ini files (folder path)
     *
     * @param string $environment - Environment to load the configuration for
     * @throws FileNotFoundException
     */
    public function loadConfigurations(string $environment = ENVIRONMENT) {
        $iniFile = ROOT . DS . 'configuration' . DS . $environment . '.ini';

        // Try to load the ini file
        if(!($this->configurations = @parse_ini_file($iniFile, true))) {
            throw new FileNotFoundException('Unable to parse configuration ini file from "' . $iniFile . '"');
        }
    }

    /**
     * Get a specific configuration value
     *
     * @param string $type - Configuration variable type
     * @param string $name - The key of the variable
     * @return string
     * @throws InvalidParametersException
     */
    public function getConfiguration(string $type, string $name = null) {
        if(array_key_exists($type, $this->configurations)) {
            if($name == null) {
                // Return with the entire type object if the exact name is not specified
                return $this->configurations[$type];
            }
            else {
                // Check if name exists in type array
                if(array_key_exists($name, $this->configurations[$type])) {
                    return $this->configurations[$type][$name];
                } else {
                    throw new InvalidParametersException('"' . $name . '" configuration variable is not a valid name in the "' . $type . '" configuration group!');
                }
            }
        } else {
            throw new InvalidParametersException('"' . $type . '" is not a valid configuration group name!');
        }
    }
}