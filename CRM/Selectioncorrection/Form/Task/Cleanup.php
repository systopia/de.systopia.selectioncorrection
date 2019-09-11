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
        // TODO: We should forward to the created group here instead of automatically showing the search result again.
        //       -> Group URL: civicrm/group/search?reset=1&force=1&gid=<newGroupId>

        $filteredContacts = CRM_Selectioncorrection_Storage::getWithDefault(CRM_Selectioncorrection_Config::FilteredContactsStorageKey, []);

        $householdCorrection = CRM_Selectioncorrection_HouseholdCorrection::getSingleton();

        $correction = $householdCorrection->removeSinglePersonHouseholds($filteredContacts);
        $filteredContacts = $correction['ids'];
        $metaData = $correction['metaData'];

        $correction = $householdCorrection->addHouseholdsWithMultipleMembersPresent($filteredContacts);
        $filteredContacts = $correction['ids'];
        $metaData = $correction['metaData'];

        $filteredContactPersons = CRM_Selectioncorrection_Storage::getWithDefault(CRM_Selectioncorrection_Config::FilteredContactPersonsStorageKey, []);
        $contactPersonsMetaData = CRM_Selectioncorrection_Storage::getWithDefault(CRM_Selectioncorrection_Config::ContactPersonsMetaDataStorageKey, []);
    }
}
