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
    private static $storage = NULL;

    /**
     * Initialises the storage system.
     * @param CRM_Contact_Form_Task $form The form that the storage will be saved in.
     */
    public static function initialise ($form)
    {
        if (self::$storage === NULL)
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
     * Sets a value to the storage by key.
     * @param string $key
     * @param mixed $value
     */
    public static function set ($key, $value)
    {
        self::$storage->set(self::PREFIX . $key, $value);
    }
}