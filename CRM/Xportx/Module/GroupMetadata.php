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






  /** @var array list of aliases to avoid duplicate joins */
  protected static $unique_joins_by_module_class = [];

  /** @var array list of aliases to avoid duplicate joins */
  protected $alias_mapping = [];

  /**
   * Experimental function to try to join a target table only ONCE
   *
   * @param array $joins
   *  the $joins list passed to the module's addJoins function, to be
   *
   * @param string $requested_alias
   *  the preferred alias, but will be altered either through "join sharing" or the getAlias function
   *
   * @param string $join_template
   *  the join template, with the _actual_ $alias represented as a token, see below.
   *
   * @param string $alias_token
   *  the string that represents the final alias in the join expression.
   *
   * @return string
   *   the alias to refer to the join
   *
   * @todo migrate to CRM_Xportx_Module if it works well
   * @todo only allow within instances of the same module?
   */
  protected function addUniqueJoin(&$joins, $requested_alias, $join_template, $alias_token = 'ALIAS')
  {
    $module_class = get_class($this);
    if (isset(self::$unique_joins_by_module_class[$module_class][$join_template])) {
      // we have already joined this, probably in another instance of this module
      $shared_alias = self::$unique_joins_by_module_class[$module_class][$join_template];

      // register as local alias
      $this->alias_mapping[$requested_alias] = $shared_alias;
      return $shared_alias;

    } else {
      // this is the first of this kind/pattern:
      $alias = $this->getAlias($requested_alias);
      $join = preg_replace("/{$alias_token}/", $alias, $join_template);
      $joins[] = $join;
      self::$unique_joins_by_module_class[$module_class][$join_template] = $alias;
      return $alias;
    }
  }

  /**
   * Get a unique alias for the given (internal) name
   * $name must be all lowercase chars: [a-z]+
   *
   * @todo migrate to CRM_Xportx_Module if it works well
   */
  protected function getAlias($name)
  {
    return $this->alias_mapping[$name] ?? parent::getAlias($name);
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
    $join_template = "LEFT JOIN civicrm_group_contact_metadata ALIAS ON ALIAS.contact_id = {$contact_term} AND ALIAS.group_id = {$group_term}";
    $metadata_alias = $this->addUniqueJoin($joins, 'metadata', $join_template);

    // add relationship joins (both ways) (only once!)
    $join_template = "LEFT JOIN civicrm_relationship ALIAS ON ALIAS.id = {$metadata_alias}.relationship_id AND ALIAS.contact_id_a = {$metadata_alias}.contact_id";
    $relationship_a_alias = $this->addUniqueJoin($joins, 'relationship_a', $join_template);
    $join_template = "LEFT JOIN civicrm_relationship ALIAS ON {$relationship_a_alias}.id = {$metadata_alias}.relationship_id AND ALIAS.contact_id_b = {$metadata_alias}.contact_id";
    $relationship_b_alias = $this->addUniqueJoin($joins, 'relationship_b', $join_template);

    // add other contact join
    $join_template = "LEFT JOIN civicrm_contact ALIAS ON ALIAS.id = COALESCE({$relationship_a_alias}.contact_id_b, {$relationship_b_alias}.contact_id_a)";
    $related_contact_alias = $this->addUniqueJoin($joins, 'related_contact', $join_template);

    // add ADDRESS: picked from option
    //  no relation: contact.primary
    //     relation: contact.work (2)
    //      -> else: related.work (2)
    $join_template_self = "LEFT JOIN civicrm_address ADDRESS_SELF ON ADDRESS_SELF.contact_id = {$contact_term} AND ADDRESS_SELF.is_primary = 1";
    $address_option1_alias = $this->addUniqueJoin($joins,'address_self', $join_template_self, 'ADDRESS_SELF');

    $join_template_self_work = "LEFT JOIN civicrm_address ADDRESS_SELF_WORK ON ADDRESS_SELF_WORK.contact_id = {$contact_term} AND ADDRESS_SELF_WORK.location_type_id = 2";
    $address_option2_alias = $this->addUniqueJoin($joins,'address_self_work', $join_template_self_work, 'ADDRESS_SELF_WORK');

    $join_template_self_option3 = "LEFT JOIN civicrm_address ADDRESS_COMPANY ON ADDRESS_COMPANY.contact_id = {$related_contact_alias}.id AND ADDRESS_COMPANY.location_type_id = 2";
    $address_option3_alias = $this->addUniqueJoin($joins,'address_org_work', $join_template_self_option3, 'ADDRESS_COMPANY');

    // join the final address
    $join_template_address = "LEFT JOIN civicrm_address ALIAS ON ALIAS.id =
      COALESCE(
        IF({$related_contact_alias}.id IS NULL,     {$address_option1_alias}.id, NULL),
        IF({$related_contact_alias}.id IS NOT NULL, {$address_option2_alias}.id, NULL),
        IF({$related_contact_alias}.id IS NOT NULL, {$address_option3_alias}.id, NULL)
       )";
    $address_alias = $this->addUniqueJoin($joins,'address', $join_template_address);

    // add more greetings
    if ($this->hasMoreGreetings()) {
      // add more greetings of the related contact. if none exists, the own
      $join_template = "LEFT JOIN civicrm_value_moregreetings ALIAS ON ALIAS.entity_id = COALESCE({$related_contact_alias}.id, {$contact_term})";
      $greetings_alias = $this->addUniqueJoin($joins, 'greetings', $join_template);
    }

    // add special magic_addressee field
    foreach ($this->config['fields'] as $field_spec) {
      if ($field_spec['key'] == 'magic_addressee' || $field_spec['key'] == 'magic_job_title') {
        $contact_table = CRM_Selectioncorrection_Config::getContactAddresseeTable();
        $join_template = "LEFT JOIN {$contact_table} ALIAS ON ALIAS.entity_id = {$contact_term}";
        $contact_addressee_alias = $this->addUniqueJoin($joins, 'contact_addressee', $join_template);

        $related_table = CRM_Selectioncorrection_Config::getRelatedAddresseeTable();
        $join_template = "LEFT JOIN {$related_table} ALIAS ON ALIAS.entity_id = {$related_contact_alias}.id";
        $related_addressee_alias = $this->addUniqueJoin($joins, 'related_addressee', $join_template);

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

      } elseif ($field_name == 'magic_job_title') {
        // the "magic_job_title" the contact's job title, IF they have a work address
        $selects[] = "IF({$address_alias}.location_type_id = 2, {$main_contact_alias}.job_title, '')
                      AS {$value_prefix}{$field_name}";

      } elseif ($field_name == 'magic_addressee') {
        // the "magic_addressee" is the organisation name, determined by the following factors:
        //  1) empty for non-work address
        //  2) contact.organization_name if not empty
        //  3) custom field organisation name

        // prep for custom field
        $contact_addressee_alias = $this->getAlias('contact_addressee');
        $contact_field = CRM_Selectioncorrection_Config::getContactAddresseeField();

        $selects[] = "COALESCE(
          IF({$address_alias}.location_type_id <> 2, '', NULL),
          IF(LENGTH({$main_contact_alias}.organization_name) > 0, {$main_contact_alias}.organization_name, NULL), 
          {$contact_addressee_alias}.{$contact_field}
        ) AS {$value_prefix}{$field_name}";

        // this was implementing a very different magic_addressee spec, see ticket #13357:
        //      } elseif ($field_name == 'magic_addressee') {
        //        // the "magic_addressee" is the organisation name, depending on the setup:
        //        //  1) empty for private contacts with private address
        //        //  2  custom field for private contacts with non-private address
        //        //  3) custom field for contact person with (own) work address
        //        //  4) related organisation's name for contact person with no own work address
        //        $contact_addressee_alias = $this->getAlias('contact_addressee');
        //        $contact_field = CRM_Selectioncorrection_Config::getContactAddresseeField();
        //
        //        $selects[] = "COALESCE(
        //          IF(({$metadata_alias}.contact_id IS NULL OR {$address_alias}.contact_id = {$metadata_alias}.contact_id)
        //             AND {$address_alias}.location_type_id = 1,     '',                                          NULL),
        //          IF(({$metadata_alias}.contact_id IS NULL OR {$address_alias}.contact_id = {$metadata_alias}.contact_id)
        //             AND {$address_alias}.location_type_id <> 1,      {$contact_addressee_alias}.{$contact_field}, NULL),
        //          IF({$address_self_work}.id = {$address_alias}.id
        //             AND {$address_alias}.location_type_id = 2,      {$contact_addressee_alias}.{$contact_field}, NULL),
        //          IF({$address_self_work}.id IS NULL,                {$related_contact}.display_name,             NULL)
        //        ) AS {$value_prefix}{$field_name}";

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
