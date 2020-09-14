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
    private const ContactPersonAndCorrectedContactIntersectionIndicator = 0; // Totally accurate names are important.

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

        // Regel 2:
        // Ist ein Haushalt in der Selektion, der weniger als 2 (nach Filter) aktive* Haushaltsmitglieder hat,
        // so wird der Haushalt nicht übernommen und stattdessen die Haushaltsmitglieder aufgenommen
        // (also einer oder keiner):
        $correctedContactsFromHouseholds = $householdCorrection->removeSinglePersonHouseholds($householdContacts);
        $group->add($correctedContactsFromHouseholds);

        // Regel 3:
        // Sind zwei (oder mehr, nach Filter) aktive Haushaltsmitglieder in der Selektion, so sollen diese nicht
        // übernommen, und stattdessen der Haushalt hinzugefügt werden:
        $correctedContactsFromIndividuals = $householdCorrection->addHouseholdsWithMultipleMembersPresent($individualContacts);
        $group->add($correctedContactsFromIndividuals);


        // Regel 8:
        // Einzelkontakte immer aussortierten, wenn auch ihr Haushalt in der Logik ist:

        // BUT use Contacts from the original individuals without the ones we inserted in rule 2:
        $remainingIndividuals = array_diff($individualContacts, $correctedContactsFromHouseholds);

        $contactsToBeRemoved = $householdCorrection->getIndividualsWithHouseholdPresent($remainingIndividuals, $householdContacts);
        $group->remove($contactsToBeRemoved);

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
          CRM_Core_Session::setStatus(E::ts('You can export the results <a href="%1">HERE</a>.', [1 => $url]), E::ts("Export Successful"), 'info');
        }

        // Forward to the created group instead of automatically showing the search result again:
        $showGroupUrl = CRM_Utils_System::url('civicrm/group/search', 'reset=1&force=1&gid=' . $group->getGroupId());

        CRM_Utils_System::redirect($showGroupUrl);
    }
}
