<?php
/*-------------------------------------------------------+
| SYSTOPIA EXTENSIBLE EXPORT EXTENSION                   |
| Copyright (C) 2018 SYSTOPIA                            |
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

    // add metadata join
    $metadata_alias = $this->getAlias('metadata');
    $joins[] = "LEFT JOIN civicrm_group_contact_metadata {$metadata_alias} ON {$metadata_alias}.contact_id = {$contact_term} AND {$metadata_alias}.group_id = {$group_term}";

    // add relationship joins (both ways)
    $relationship_a_alias = $this->getAlias('relationship_a');
    $relationship_b_alias = $this->getAlias('relationship_b');
    $joins[] = "LEFT JOIN civicrm_relationship {$relationship_a_alias} ON {$relationship_a_alias}.contact_id_a = {$metadata_alias}.contact_id";
    $joins[] = "LEFT JOIN civicrm_relationship {$relationship_b_alias} ON {$relationship_b_alias}.contact_id_b = {$metadata_alias}.contact_id";

    // add other contact join
    $related_contact_alias = $this->getAlias('related_contact');
    $joins[] = "LEFT JOIN civicrm_contact {$related_contact_alias} ON {$related_contact_alias}.id = COALESCE({$relationship_a_alias}.contact_id_b, {$relationship_b_alias}.contact_id_a)";
  }

  /**
   * add this module's select clauses to the list
   * they can only refer to the main contact table
   * "contact" or this module's joins
   */
  public function addSelects(&$selects) {
    $related_contact_alias = $this->getAlias('related_contact');
    $value_prefix = $this->getValuePrefix();

    foreach ($this->config['fields'] as $field_spec) {
      $field_name = $field_spec['key'];
      // the default ist a column from the contact table
      $selects[] = "{$related_contact_alias}.{$field_name} AS {$value_prefix}{$field_name}";
    }
  }
}
