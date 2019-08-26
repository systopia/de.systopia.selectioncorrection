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

use NilPortugues\Sql\QueryBuilder\Builder\GenericBuilder;

class CRM_Selectioncorrection_FilterHandler {

  private static $singleton = NULL;

  /**
   * A list of all filter classes with associated data.
   */
  private $filters = [];

  /**
  * Get the filter handler singleton
  */
  public static function getSingleton() {
    if (self::$singleton === NULL) {
      self::$singleton = new CRM_Selectioncorrection_FilterHandler();
    }
    return self::$singleton;
  }

  function __construct() {
    $this->filters = [
      new CRM_Selectioncorrection_Filter_NotDeceased(),
    ];
  }

  /**
   * Lists all available filters.
   * @return array The list of the filters with some metadata.
   */
  public function listFilters() {
    // FIXME: Implement.
  }

  /**
   * Enables or disables a filter.
   * @param string $filterName The name/identifier of the filter.
   * @param bool $filterIsActive True to enable the filter, false to disable.
   */
  public function setFilterStatus($filterName, $filterIsActive) {
    // FIXME: Implement.
  }

  /**
   * Performs all active filters on a contact list.
   * @param array $contactIds A list of all contacts to filter.
   * @return array The filtered contact list.
   */
  public function performFilters($contactIds) {
    $builder = new GenericBuilder();

    $query = $builder->select()->setTable('civicrm_contact')->setColumns(['id']);

    // Add all joins to the query:
    foreach ($this->filters as $filter) {
      $query = $filter->addJoin($query);
    }

    // Add all where clauses to the query:
    $query = $query->where();
    foreach ($this->filters as $filter) {
      $query = $filter->addWhere($query);
    }
    $query = $query->end();

    // Fill the named parameters in the query:
    //TODO: Maybe we should change this to use the DAO parameters with %?
    $sql = $builder->write($query);
    $values = $builder->getValues();
    $sql = str_replace(array_keys($values), array_values($values), $sql);

    $queryResult = CRM_Core_DAO::executeQuery($sql);

    print_r($queryResult->fetchAll());
  }
}