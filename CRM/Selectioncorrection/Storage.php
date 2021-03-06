<?php
/*-------------------------------------------------------+
| SYSTOPIA MULTI PURPOSE SELECTION CLEANUPS              |
| Copyright (C) 2019 SYSTOPIA                            |
| Author: B. Zschiedrich (zschiedrich@systopia.de)       |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

class CRM_Selectioncorrection_Storage
{
    private const PREFIX = 'selectioncorrection_storage_';
    /**
     * We use the form of the current task to store values persistently.
     * @var CRM_Contact_Form_Task $storage
     */
    private static $storage = null;

    /**
     * Initialises the storage system.
     * @param CRM_Contact_Form_Task $form The form that the storage will be saved in.
     */
    public static function initialise (CRM_Contact_Form_Task $form)
    {
        if (self::$storage === null)
        {
            self::$storage = $form;
        }
    }

    /**
     * Gets a value from the storage by key.
     * @param string $key
     * @return mixed The value of the stored object.
     */
    public static function get ($key)
    {
        return self::$storage->get(self::PREFIX . $key);
    }

    /**
     * Tries to get a value from the storage by key.
     * If it is not set or null the given default will be returned.
     * @param string $key
     * @param mixed $default The default that will be returned if the value is null.
     * @return mixed The value of the stored object or the given default.
     */
    // TODO: Replace with or add "getOrEmpty" and use it in the code.
    public static function getWithDefault ($key, $default)
    {
        $result = self::$storage->get(self::PREFIX . $key);

        if (($result === null))
        {
            return $default;
        }
        else
        {
            return $result;
        }
    }

    /**
     * Sets a value to the storage by key.
     * @param string $key
     * @param mixed $value
     */
    public static function set ($key, $value)
    {
        self::$storage->set(self::PREFIX . $key, $value);
    }

    /**
     * Clears a value from the storage by setting it to null.
     * @param string $key
     */
    public static function clear ($key)
    {
        self::set($key, null);
    }
}