<?php
/**
 * Created by PhpStorm.
 * User: doconnell
 * Date: 20/10/16
 * Time: 11:03
 */

namespace SimpleThings\EntityAudit\Exception;


class ConfigurationException extends \Exception
{
    /**
     * ConfigurationException constructor.
     * @param string $configurationKey
     * @param mixed $configurationValue
     * @param string $message
     */
    public function __construct($configurationKey, $configurationValue, $message)
    {
        parent::__construct(
            "$message for configuration key: '$configurationKey', value: ".
            ($configurationValue === NULL ? "NULL" : "'$configurationValue'")
        );
    }
}
