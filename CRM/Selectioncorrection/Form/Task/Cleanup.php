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

use CRM_Selectioncorrection_ExtensionUtil as E;
require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Selectioncorrection_Form_Task_Cleanup extends CRM_Contact_Form_Task {

  private const PreselectionPageName = 'preselection';
  private const ConstantPersonDefinitionPageName = 'contact_person_definition';

  function preProcess() {
    CRM_Selectioncorrection_Storage::initialise($this);

    $this->assign('preselection_page_name', self::PreselectionPageName);
    $this->assign('contact_person_definition_page_name', self::ConstantPersonDefinitionPageName);
  }

  /**
   * Compile task form
   */
  function buildQuickForm() {
    // Add an element containing current page identifier:
    $this->add(
      'hidden',
      'last_page'
    );

    // Preselection elements:
    $checkbox = $this->add(
      'checkbox',
      'filter_1',
      E::ts('Inactive')
    );
    $checkbox->freeze();

    $this->add(
        'select',
        'contact_person_org_1434',
        E::ts('Contact Person'),
        ['value' => 'label', 'value2' => 'label2'],
        FALSE,
        ['class' => 'crm-select2 huge', 'multiple' => 'multiple']
    );

    // Contact person definition elements:
    //$contact_person[] = [
    //    'org_id' => 43,
    //    'img' =>  CRM_Contact_BAO_Contacteset=1 [filter_1] =_Utils::getImage(empty($contact['contact_sub_type']) ? $contact['contact_type'] : $contact['contact_sub_type'], FALSE, $contact['id']),
    //    'contacts ' => [
    //      43 => [
    //          'name' => 'sda',
    //          'img' =>  CRM_Contact_BAO_Contact_Utils::getImage(empty($contact['contact_sub_type']) ? $contact['contact_type'] : $contact['contact_sub_type'], FALSE, $contact['id']),
    //      ],
    //    ],
    //];
    //$popup_img = CRM_Contact_BAO_Contact_Utils::getImage(empty($contact['contact_sub_type']) ? $contact['contact_type'] : $contact['contact_sub_type'], FALSE, $contact['id']);
    //$this->assign("contact_person_org_1434_popup", $popup_img);
    // {$contact_person_org_1434_popup}

    $values = $this->exportValues();

    if ($values['last_page'] == 'preselection') { // TODO: The page identifiers should be a class constant.
      $this->assign('current_page', self::ConstantPersonDefinitionPageName);

      $this->setDefaults([
        'last_page' => self::ConstantPersonDefinitionPageName,
      ]);

      CRM_Core_Form::addDefaultButtons(E::ts("Set")); //FIXME: Back button does not work here because of our multi page system.
    } else {
      $this->assign('current_page', self::PreselectionPageName);

      $this->setDefaults([
        'last_page'               => self::PreselectionPageName,
        'filter_1'                => true,
        'contact_person_org_1434' => ['value', 'value2'],
      ]);

      CRM_Core_Form::addDefaultButtons(E::ts("Filter"), 'submit');
    }

    CRM_Selectioncorrection_FilterHandler::getSingleton()->performFilters([]);
  }

  function postProcess() {
    $values = $this->exportValues();

//    $selected_config = $values['export_configuration'];
//    $configurations = CRM_Xportx_Export::getExportConfigurations();
//
//    if (empty($configurations[$selected_config])) {
//      throw new Exception("No configuration found");
//    }
//
//    // run export
//    $configuration = $configurations[$selected_config];
//    $export = new CRM_Xportx_Export($configuration);
//    $export->writeToStream($this->_contactIds);


  }
}
