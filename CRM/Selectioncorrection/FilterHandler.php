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

class CRM_Selectioncorrection_FilterHandler {

  /**
   * A list of all filter classes with associated data.
   */
  private static $filters = [
    //['filter': new abcFilter, 'active': true],
  ];

  /**
   * Lists all available filters.
   * @return array The list of the filters with some metadata.
   */
  public static function listFilters() {
    // FIXME: Implement.
  }

  /**
   * Enables or disables a filter.
   * @param string $filterName The name/identifier of the filter.
   * @param bool $filterIsActive True to enable the filter, false to disable.
   */
  public static function setFilterStatus($filterName, $filterIsActive) {
    // FIXME: Implement.
  }

  /**
   * Performs all active filters on a contact list.
   * @param array $contactIds A list of all contacts to filter.
   * @return array The filtered contact list.
   */
  public static function performFilters($contactIds) {
    // FIXME: Implement.
  }
}