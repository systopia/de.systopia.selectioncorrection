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

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Selectioncorrection_Config
{
    // Public constants used for global access:
    public const RelationshipTypeElementIdentifier = 'relationship_types';
    public const FilteredContactsStorageKey = 'filtered_contacts';
    public const FilteredContactPersonsStorageKey = 'filtered_contact_persons';

    /**
     * @param $name string settigs name
     */
    public static function getSetting ($name)
    {
        $settings = self::getSettings();

        return CRM_Utils_Array::value($name, $settings, NULL);
    }

    /**
     * @return array settings
     */
    public static function getSettings ()
    {
        $settings = CRM_Core_BAO_Setting::getItem('de.systopia.selectioncorrection', 'selectioncorrection_settings');

        if ($settings && is_array($settings))
        {
            return $settings;
        }
        else
        {
            return [];
        }
    }

    /**
     * Stores settings
     *
     * @return array settings
     */
    public static function setSettings ($settings)
    {
        CRM_Core_BAO_Setting::setItem($settings, 'de.systopia.selectioncorrection', 'selectioncorrection_settings');
    }
}