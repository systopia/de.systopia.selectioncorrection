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
    public const GroupTitleStorageKey = 'group_title';
    public const FilteredContactsStorageKey = 'filtered_contacts';
    public const FilteredContactPersonsStorageKey = 'filtered_contact_persons';
    public const ContactPersonsMetaDataStorageKey = 'contact_persons_meta_data';

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

    /**
     * Get the list of relationship type IDs that should be offered in the cleanup routine
     * @return array list of IDs
     */
    public static function getRelationshipTypeIDS() {
      // FIXME: move to config
      return [12,19,20];
    }

    /**
     * Configuration for the magic_addressee field, the contact's part
     *
     * @return string DB field name for the contact's addressee field
     */
    public static function getContactAddresseeField() {
      $field = CRM_Selectioncorrection_CustomData::getCustomField('individual_zusatzinfo', 'individual_employer');
      return $field['column_name'];
    }

    /**
     * Configuration for the magic_addressee field, the related organisation's part
     *
     * @return string DB field name for the contact's addressee field
     */
    public static function getRelatedAddresseeField() {
      $field = CRM_Selectioncorrection_CustomData::getCustomField('more_greetings_group', 'greeting_field_4');
      return $field['column_name'];
    }


    /**
     * Configuration for the magic_addressee field, the related organisation's part
     *
     * @return string DB field name for the contact's addressee field
     */
    public static function getContactAddresseeTable() {
      return CRM_Selectioncorrection_CustomData::getGroupTable('individual_zusatzinfo');
    }

    /**
     * Configuration for the magic_addressee field, the related organisation's part
     *
     * @return string DB field name for the contact's addressee field
     */
    public static function getRelatedAddresseeTable() {
      return CRM_Selectioncorrection_CustomData::getGroupTable('more_greetings_group');
    }
}