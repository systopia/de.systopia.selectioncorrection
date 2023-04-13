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


require_once 'selectioncorrection.civix.php';
use CRM_Selectioncorrection_ExtensionUtil as E;


/**
 * Add contact search tasks to submit tax excemption XMLs
 *
 * @param string $objectType specifies the component
 * @param array $tasks the list of actions
 *
 * @access public
 */
function selectioncorrection_civicrm_searchTasks($objectType, &$tasks) {
  if ($objectType == 'contact') {
    $tasks[] = array(
        'title'  => E::ts('Cleanup'),
        'class'  => 'CRM_Selectioncorrection_Form_Task_Cleanup',
        'result' => false);
  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function selectioncorrection_civicrm_config(&$config) {
  _selectioncorrection_civix_civicrm_config($config);

  // Include composer autoload for it to be available everywhere with "use":
  $extRoot = dirname(__FILE__) . DIRECTORY_SEPARATOR;
  $include_path = $extRoot . DIRECTORY_SEPARATOR . 'vendor' . PATH_SEPARATOR . get_include_path( );
  set_include_path( $include_path );
  require_once 'vendor/autoload.php';
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function selectioncorrection_civicrm_install() {
  _selectioncorrection_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function selectioncorrection_civicrm_enable() {
  _selectioncorrection_civix_civicrm_enable();
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *

 // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
function selectioncorrection_civicrm_navigationMenu(&$menu) {
  _selectioncorrection_civix_insert_navigation_menu($menu, 'Mailings', array(
    'label' => E::ts('New subliminal message'),
    'name' => 'mailing_subliminal_message',
    'url' => 'civicrm/mailing/subliminal',
    'permission' => 'access CiviMail',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _selectioncorrection_civix_navigationMenu($menu);
} // */
