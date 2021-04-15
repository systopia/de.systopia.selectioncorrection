<?php
/*-------------------------------------------------------+
| SYSTOPIA MULTI PURPOSE SELECTION CLEANUPS                 |
| Copyright (C) 2019 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

use CRM_Xportx_ExtensionUtil as E;

/**
 * Provides contact base data
 */
class CRM_Xportx_Module_GroupMetadata extends CRM_Xportx_Module {

  protected static $metadata_alias = NULL;

  /**
   * This module can do with any base_table
   * (as long as it has a contact_id column)
   */
  public function forEntity() {
    return 'GroupContact';
  }

  /**
   * Get this module's preferred alias.
   * Must be all lowercase chars: [a-z]+
   */
  public function getPreferredAlias() {
    return 'groupmeta';
  }

  /**
   * add this module's joins clauses to the list
   * they can only refer to the main contact table
   * "contact" or other joins from within the module
   */
  public function addJoins(&$joins) {
    // build term for the contact_id
    $contact_term = $this->getContactIdExpression();

    // build term for the group_id
    $group_alias = $this->export->getBaseAlias();
    $group_term = "{$group_alias}.group_id";

    // add metadata join (only once!)
    if (self::$metadata_alias === NULL) {
      $metadata_alias = $this->getAlias('metadata');
      $joins[] = "LEFT JOIN civicrm_group_contact_metadata {$metadata_alias} ON {$metadata_alias}.contact_id = {$contact_term} AND {$metadata_alias}.group_id = {$group_term}";
      self::$metadata_alias = $metadata_alias;
    } else {
      $metadata_alias = self::$metadata_alias;
    }

    // add relationship joins (both ways)
    $relationship_a_alias = $this->getAlias('relationship_a');
    $relationship_b_alias = $this->getAlias('relationship_b');
    $joins[] = "LEFT JOIN civicrm_relationship {$relationship_a_alias} ON {$relationship_a_alias}.id = {$metadata_alias}.relationship_id AND {$relationship_a_alias}.contact_id_a = {$metadata_alias}.contact_id";
    $joins[] = "LEFT JOIN civicrm_relationship {$relationship_b_alias} ON {$relationship_a_alias}.id = {$metadata_alias}.relationship_id AND {$relationship_b_alias}.contact_id_b = {$metadata_alias}.contact_id";

    // add other contact join
    $related_contact_alias = $this->getAlias('related_contact');
    $joins[] = "LEFT JOIN civicrm_contact {$related_contact_alias} ON {$related_contact_alias}.id = COALESCE({$relationship_a_alias}.contact_id_b, {$relationship_b_alias}.contact_id_a)";

    // add ADDRESS: picked from option
    //  no relation: contact.primary
    //     relation: contact.work (2)
    //      -> else: related.work (2)
    $address_option1_alias = $this->getAlias('address_self');
    $joins[] = "LEFT JOIN civicrm_address {$address_option1_alias} ON {$address_option1_alias}.contact_id = {$contact_term} AND {$address_option1_alias}.is_primary = 1";
    $address_option2_alias = $this->getAlias('address_self_work');
    $joins[] = "LEFT JOIN civicrm_address {$address_option2_alias} ON {$address_option2_alias}.contact_id = {$contact_term} AND {$address_option2_alias}.location_type_id = 2";
    $address_option3_alias = $this->getAlias('address_org_work');
    $joins[] = "LEFT JOIN civicrm_address {$address_option3_alias} ON {$address_option3_alias}.contact_id = {$related_contact_alias}.id AND {$address_option3_alias}.location_type_id = 2";

    // join the final address
    $address_alias = $this->getAlias('address');
    $joins[] = "LEFT JOIN civicrm_address {$address_alias} ON {$address_alias}.id =
      COALESCE(
        IF({$related_contact_alias}.id IS NULL,     {$address_option1_alias}.id, NULL),
        IF({$related_contact_alias}.id IS NOT NULL, {$address_option2_alias}.id, NULL),
        IF({$related_contact_alias}.id IS NOT NULL, {$address_option3_alias}.id, NULL)
       )";

    // add more greetings
    if ($this->hasMoreGreetings()) {
      // add more greetings of the related contact. if none exists, the own
      $greetings_alias = $this->getAlias('greetings');
      $joins[] = "LEFT JOIN civicrm_value_moregreetings {$greetings_alias} ON {$greetings_alias}.entity_id = COALESCE({$related_contact_alias}.id, {$contact_term})";
    }

    // add special magic_addressee field
    foreach ($this->config['fields'] as $field_spec) {
      if ($field_spec['key'] == 'magic_addressee') {
        $contact_addressee_alias = $this->getAlias('contact_addressee');
        $contact_table = CRM_Selectioncorrection_Config::getContactAddresseeTable();
        $joins[] = "LEFT JOIN {$contact_table} {$contact_addressee_alias} ON {$contact_addressee_alias}.entity_id = {$contact_term}";

        $related_addressee_alias = $this->getAlias('related_addressee');
        $related_table = CRM_Selectioncorrection_Config::getRelatedAddresseeTable();
        $joins[] = "LEFT JOIN {$related_table} {$related_addressee_alias} ON {$related_addressee_alias}.entity_id = {$related_contact_alias}.id";

        $main_contact_alias = $this->getAlias('main_contact');
        $joins[] = "LEFT JOIN civicrm_contact {$main_contact_alias} ON {$main_contact_alias}.id = {$contact_term}";

        break;
      }
    }
  }

  /**
   * add this module's select clauses to the list
   * they can only refer to the main contact table
   * "contact" or this module's joins
   */
  public function addSelects(&$selects) {
    $metadata_alias     = self::$metadata_alias;
    $main_contact_alias = $this->getAlias('main_contact');
    $related_contact    = $this->getAlias('related_contact');
    $address_alias      = $this->getAlias('address');
    $greetings_alias    = $this->getAlias('greetings');
    $address_self_work  = $this->getAlias('address_self_work');
    $value_prefix       = $this->getValuePrefix();

    foreach ($this->config['fields'] as $field_spec) {
      $field_name = $field_spec['key'];
      if (substr($field_name, 0, 5) == 'addr_') {
        // add address field
        $column_name = substr($field_name, 5);
        $selects[] = "{$address_alias}.{$column_name} AS {$value_prefix}{$field_name}";

      } elseif ($field_name == 'magic_addressee') {
        // the "magic_addressee" is the organisation name, determined by the following factors:
        //  1) empty for non-work address
        //  2) contact.organization_name if not empty
        //  3) custom field organisation name
        $contact_field = CRM_Selectioncorrection_Config::getContactAddresseeField();

        $selects[] = "COALESCE(
          IF({$address_alias}.location_type_id <> 2, '', NULL),
          IF(LENGTH({$main_contact_alias}.organization_name) > 0, {$main_contact_alias}.organization_name, NULL)
          {$main_contact_alias}.{$contact_field}
        ) AS {$value_prefix}{$field_name}";

      } elseif ($field_name == 'magic_addressee_old') {
        // the "magic_addressee" is the organisation name, depending on the setup:
        //  1) empty for private contacts with private address
        //  2  custom field for private contacts with non-private address
        //  3) custom field for contact person with (own) work address
        //  4) related organisation's name for contact person with no own work address
        $contact_addressee_alias = $this->getAlias('contact_addressee');
        $contact_field = CRM_Selectioncorrection_Config::getContactAddresseeField();

        $selects[] = "COALESCE(
          IF(({$metadata_alias}.contact_id IS NULL OR {$address_alias}.contact_id = {$metadata_alias}.contact_id) 
             AND {$address_alias}.location_type_id = 1,     '',                                          NULL),
          IF(({$metadata_alias}.contact_id IS NULL OR {$address_alias}.contact_id = {$metadata_alias}.contact_id) 
             AND {$address_alias}.location_type_id <> 1,      {$contact_addressee_alias}.{$contact_field}, NULL),
          IF({$address_self_work}.id = {$address_alias}.id 
             AND {$address_alias}.location_type_id = 2,      {$contact_addressee_alias}.{$contact_field}, NULL),
          IF({$address_self_work}.id IS NULL,                {$related_contact}.display_name,             NULL)
        ) AS {$value_prefix}{$field_name}";

      } elseif (substr($field_name, 0, 9) == 'greeting_') {
        // add greetings field
        $selects[] = "{$greetings_alias}.{$field_name} AS {$value_prefix}{$field_name}";

        } else {
        // the default is a column from the contact table
        $selects[] = "{$related_contact}.{$field_name} AS {$value_prefix}{$field_name}";
      }
    }
  }

  /**
   * Add group clauses to the generic one
   *
   * @return array clauses
   */
  public function getGroupClauses() {
    $metadata_alias = $this->getAlias('metadata');
    if (self::$metadata_alias == $metadata_alias) {
      // only join the one metadata alias
      return ["{$metadata_alias}.relationship_id"];
    } else {
      return [];
    }
  }

  /**
   * Check if the moregreetings extension is present
   * @return bool
   */
  protected function hasMoreGreetings() {
    return function_exists('moregreetings_civicrm_enable');
  }
}
