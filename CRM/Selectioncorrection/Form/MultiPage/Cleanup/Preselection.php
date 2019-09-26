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
+-------------------------------------------------------*/

use CRM_Selectioncorrection_ExtensionUtil as E;

class CRM_Selectioncorrection_Form_MultiPage_Cleanup_Preselection extends CRM_Selectioncorrection_MultiPage_PageBase
{
    private const GroupTitleElementIdentifier = 'group_title';

    public const PageName = 'preselection';
    protected $name = self::PageName;

    public function build (&$defaults, $isRebuild=false)
    {
        $filterHandler = CRM_Selectioncorrection_FilterHandler::getSingleton();
        $filters = $filterHandler->getFilters();

        //Filter checkboxes:
        foreach ($filters as $filter)
        {
            $checkbox = $this->pageHandler->add(
                'checkbox',
                $filter->getIdentifier(),
                E::ts($filter->getName())
            );

            if (!$filter->isOptional())
            {
                $checkbox->freeze();
            }
        }

        $defaults = array_merge($defaults, $filterHandler->getFilterStatuses());

        $this->pageHandler->assign('filter_identifiers', $filterHandler->getFilterIdentifiers());

        // Relationship type for contact persons:
        $this->pageHandler->add(
            'select',
            CRM_Selectioncorrection_Config::RelationshipTypeElementIdentifier,
            E::ts('Relationship types for contact persons'),
            CRM_Selectioncorrection_Utility_Relationships::getIndividualOrganisationRelationships(),
            !$isRebuild, // Only a required field if this is no rebuild to not produce false erros in the validation of the next page.
            ['multiple' => true]
        );

        // Title for the target group to create:
        $this->pageHandler->add(
            'text',
            self::GroupTitleElementIdentifier,
            E::ts('Group title'),
            null,
            !$isRebuild // Only a required field if this is no rebuild to not produce false erros in the validation of the next page.
        );

        $this->pageHandler->setTitle(E::ts('Cleanup preparation'));
        // TODO: We could change this to not being called in the build function here but in the BaseClass for
        //       the multi page instead. It could be a property called "title" here instead.
    }

    public function rebuild ()
    {
        // Build is fast, so we can call it and ignore the defaults.
        $defaults = [];
        $this->build($defaults, true);
    }

    public function validate (&$errors)
    {
        $groupTitle = $this->pageHandler->_submitValues[self::GroupTitleElementIdentifier];

        if (CRM_Selectioncorrection_Group::doesGroupExist($groupTitle))
        {
            $errorValues = [1 => $groupTitle];
            $errorMessage = E::ts("A group with '%1' as name or title already exists.", $errorValues);

            $errors[self::GroupTitleElementIdentifier] = $errorMessage;

            return false;
        }
        else
        {
            return true;
        }
    }

    public function process ()
    {
        $values = $this->pageHandler->getPageValues($this->name);

        $filterHandler = CRM_Selectioncorrection_FilterHandler::getSingleton();

        $filters = $filterHandler->getFilters();

        // Set the status for every filter based on the form values:
        foreach ($filters as $filter)
        {
            $identifier = $filter->getIdentifier();

            $isChecked = array_key_exists($identifier, $values);
            $filter->setStatus($isChecked);
        }

        // Perform the filters on the contacts:
        $contactIds = $this->pageHandler->_contactIds;
        $filteredContactIds = $filterHandler->performFilters($contactIds);

        CRM_Selectioncorrection_Storage::set(CRM_Selectioncorrection_Config::FilteredContactsStorageKey, $filteredContactIds);

        // Save the group title:
        CRM_Selectioncorrection_Storage::set(CRM_Selectioncorrection_Config::GroupTitleStorageKey, $values[self::GroupTitleElementIdentifier]);
    }
}
