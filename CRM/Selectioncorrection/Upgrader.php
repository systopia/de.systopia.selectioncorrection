<?php
use CRM_Selectioncorrection_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Selectioncorrection_Upgrader extends CRM_Selectioncorrection_Upgrader_Base
{
  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Called when the extension is installed.
   */
  public function install ()
  {
    $this->executeSqlFile('sql/createMetaDataTable.sql');
  }

  /**
   * Called when the extension is uninstalled.
   */
  public function uninstall ()
  {
   $this->executeSqlFile('sql/dropMetaDataTable.sql');
  }
}
