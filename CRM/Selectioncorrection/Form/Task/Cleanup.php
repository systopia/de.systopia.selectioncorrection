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

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Selectioncorrection_Form_Task_Cleanup extends CRM_Selectioncorrection_MultiPage_BaseClass
{
    protected function initialise ()
    {
        $pages = [
            new CRM_Selectioncorrection_Form_MultiPage_Cleanup_Preselection($this),
            new CRM_Selectioncorrection_Form_MultiPage_Cleanup_ContactPersonDefinition($this),
        ];

        $this->addPages($pages);
    }

    protected function doFinalProcess ()
    {
        $groupTitle = CRM_Selectioncorrection_Storage::get(CRM_Selectioncorrection_Config::GroupTitleStorageKey);

        $group = new CRM_Selectioncorrection_Group();
        $group->setGroupTitle($groupTitle);

        $metaData = new CRM_Selectioncorrection_MetaData();

        $filteredContacts = CRM_Selectioncorrection_Storage::getWithDefault(CRM_Selectioncorrection_Config::FilteredContactsStorageKey, []);
        $householdContacts = CRM_Selectioncorrection_Utility_Contacts::getHouseholdsFromContacts($filteredContacts);
        $individualContacts = CRM_Selectioncorrection_Utility_Contacts::getIndividualsFromContacts($filteredContacts);

        $householdCorrection = CRM_Selectioncorrection_HouseholdCorrection::getSingleton();

        $correctedContacts = $householdCorrection->removeSinglePersonHouseholds($householdContacts);
        $group->add($correctedContacts);

        $correctedContacts = $householdCorrection->addHouseholdsWithMultipleMembersPresent($individualContacts);
        $group->add($correctedContacts);

        $filteredContactPersons = CRM_Selectioncorrection_Storage::getWithDefault(CRM_Selectioncorrection_Config::FilteredContactPersonsStorageKey, []);
        $contactPersonsMetaData = CRM_Selectioncorrection_Storage::getWithDefault(CRM_Selectioncorrection_Config::ContactPersonsMetaDataStorageKey, []);

        $group->add($filteredContactPersons);
        $metaData->add($contactPersonsMetaData);

        $group->save();
        $metaData->setGroupId($group->getGroupId());
        $metaData->save();

        // add a nice popup for easier export
        if (function_exists('xportx_civicrm_enable')) {
          $url = CRM_Utils_System::url('civicrm/xportx/group', 'group_id=' . $group->getGroupId());
          CRM_Core_Session::setStatus(E::ts("You can export the results <a href=\"%1\">HERE</a>.", [1 => $url]), E::ts("Export Successful"), 'info');
        }

        // Forward to the created group instead of automatically showing the search result again:
        $showGroupUrl = CRM_Utils_System::url('civicrm/group/search', 'reset=1&force=1&gid=' . $group->getGroupId());

        CRM_Utils_System::redirect($showGroupUrl);
    }
}
