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
    private const GroupNameElementIdentifier = 'group_name';
    private const RelationshipTypeElementIdentifier = 'relationship_types';

    protected $name = 'preselection';

    public function build (&$defaults)
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
            self::RelationshipTypeElementIdentifier,
            ts('Relationship types for contact persons'),
            CRM_Selectioncorrection_Utility_Relationships::getIndividualOrganisationRelationships(),
            true,
            ['multiple' => true]
        );

        // Name for the target group to create:
        $this->pageHandler->add(
            'text',
            self::GroupNameElementIdentifier,
            ts('Group name'),
            null,
            true
        );
    }

    public function rebuild ()
    {
        // Build is fast, so we can call it and ignore the defaults.
        $defaults = [];
        $this->build($defaults);
    }

    public function validate (&$errors)
    {
        $groupName = $this->pageHandler->_submitValues[self::GroupNameElementIdentifier];

        $foundGroups = civicrm_api3(
            'Group',
            'get',
            [
                'sequential' => 1,
                'return' => [
                    "title",
                ],
                'name' => $groupName,
                'title' => $groupName,
                'options' => [
                    'limit' => 1,
                    'or' => [
                        [
                            "name",
                            "title",
                        ]
                    ]
                ],
            ]
        );

        if ($foundGroups['count'] > 0)
        {
            $existingGroupTitle = $foundGroups['values'][0]['title'];
            $errorValues = [1 => $groupName, 2 => $existingGroupTitle];
            $errorMessage = E::ts("A group with '%1' as name or title already exists. Have a look at group '%2'.", $errorValues);

            $errors[self::GroupNameElementIdentifier] = $errorMessage;

            return false;
        }
        else
        {
            return true;
        }
    }

    public function process ()
    {
        $filters = CRM_Selectioncorrection_FilterHandler::getSingleton()->getFilters();
        $values = $this->pageHandler->getFilteredExportValues();

        // Set the status for every filter based on the form values:
        foreach ($filters as $filter)
        {
            $identifier = $filter->getIdentifier();

            $isChecked = array_key_exists($identifier, $values);
            $filter->setStatus($isChecked);
        }
    }
}
