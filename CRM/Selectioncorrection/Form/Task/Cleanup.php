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
class CRM_Selectioncorrection_Form_Task_Cleanup extends CRM_Contact_Form_Task
{
    // Page identifiers:
    private const LastPageIdentifier = 'last_page';
    private const CurrentPageIdentifier = 'current_page';
    // Page names:
    private const PreselectionPageName = 'preselection';
    private const ConstantPersonDefinitionPageName = 'contact_person_definition';
    // Element identifiers:
    private const RelationshipTypeElementIdentifier = 'relationship_types';
    private const GroupNameElementIdentifier = 'group_name';

    /**
     * @var string $errorMessage
     */
    private $errorMessage = null;

    function preProcess ()
    {
        CRM_Selectioncorrection_Storage::initialise($this);

        parent::preProcess();

        $this->assign('preselection_page_name', self::PreselectionPageName);
        $this->assign('contact_person_definition_page_name', self::ConstantPersonDefinitionPageName);
    }

    function buildPreselectionElements (&$defaults)
    {
        $filterHandler = CRM_Selectioncorrection_FilterHandler::getSingleton();
        $filters = $filterHandler->getFilters();

        //Filter checkboxes:
        foreach ($filters as $filter)
        {
            $checkbox = $this->add(
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

        $this->assign('filter_identifiers', $filterHandler->getFilterIdentifiers());

        // Relationship type for contact persons:
        $this->add(
            'select',
            self::RelationshipTypeElementIdentifier,
            ts('Relationship types for contact persons'),
            $this->getIndividualOrganisationRelationships(),
            true,
            ['multiple' => true]
        );

        // Name for the target group to create:
        $this->add(
            'text',
            self::GroupNameElementIdentifier,
            ts('Group name'),
            null,
            true
        );
    }

    function buildContactPersonDefinitionElements (&$defaults)
    {
        $values = $this->exportValues();

        $contactIds = $this->_contactIds;
        $relationshipIds = $values[self::RelationshipTypeElementIdentifier];

        $treeData = $this->getOrganisationRelationshipContactPersonTree($contactIds, $relationshipIds);

        $contactPersonTree = $treeData['tree'];
        $organisationIds = $treeData['organisationIds'];
        $contactPersonIds = $treeData['contactPersonIds'];

        $organisationNameMapping = $this->getContactDisplayNames($organisationIds);
        $contactpersonNameMapping = $this->getContactDisplayNames($contactPersonIds);
        $relationshipLabelMapping = $this->getRelationshipTypeLabels($relationshipIds);

        $organisationsElementList = [];

        foreach ($contactPersonTree as $organisation => $relationships)
        {
            $elementIdentifiers = [];
            foreach ($relationships as $relationship => $relationshipContactPersonIds)
            {
                // Fill a list with contactPersonId => contactPersonLabel:s
                $contactPersons = [];
                foreach ($relationshipContactPersonIds as $contactPersonId)
                {
                    $contactPersons[$contactPersonId] = $contactpersonNameMapping[$contactPersonId];
                }

                $elementIdentifier = 'contact_persons_' . $organisation . '_' . $relationship;

                $this->add(
                    'select',
                    $elementIdentifier ,
                    $relationshipLabelMapping[$relationship],
                    $contactPersons,
                    false,
                    [
                        'multiple' => 'multiple',
                        'class' => 'crm-select2 huge',
                    ]
                );

                $elementIdentifiers[] = $elementIdentifier;
            }

            $organisatioName = $organisationNameMapping[$organisation];

            $organisationsElementList[$organisatioName] = $elementIdentifiers;
        }

        $this->assign('contact_person_definition_organisations_element_list', $organisationsElementList);

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
    }

    function validatePreselectionElements ()
    {
        $groupName = $this->_submitValues[self::GroupNameElementIdentifier];

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

            $this->errorMessage = $errorMessage;

            return false;
        }
        else
        {
            return true;
        }
    }

    /**
     * Compile task form
     */
    function buildQuickForm ()
    {
        parent::buildQuickForm();

        /**
         * Array holding the default values for every element.
         * Will be set at the end of the function.
         */
        $defaults = [];

        // Add an element containing current page identifier:
        $this->add(
            'hidden',
            self::LastPageIdentifier
        );

        $values = $this->exportValues();

        if (($values[self::LastPageIdentifier] == self::PreselectionPageName) && $this->validatePreselectionElements())
        {
            // We need the elements of the preselection (their data) for the contact person definition,
            // so we have to build them in PHP. They will not be rendered in smarty.
            // Theoretically it would be "cleaner" if we had dummy elements created everytime,
            $this->buildPreselectionElements($defaults);
            $this->buildContactPersonDefinitionElements($defaults);

            $this->assign(self::CurrentPageIdentifier, self::ConstantPersonDefinitionPageName);

            $defaults[self::LastPageIdentifier] = self::ConstantPersonDefinitionPageName;

            CRM_Core_Form::addDefaultButtons(E::ts("Set")); //FIXME: Back button does not work here because of our multi page system.
        }
        else
        {
            $this->buildPreselectionElements($defaults);

            $this->assign(self::CurrentPageIdentifier, self::PreselectionPageName);

            $defaults[self::LastPageIdentifier] = self::PreselectionPageName;

            CRM_Core_Form::addDefaultButtons(E::ts("Filter"), 'submit');
        }

        $this->setDefaults($defaults);

        //print_r($this->_contactIds);
        //print("<br>-----<br>");
        //print_r(CRM_Selectioncorrection_FilterHandler::getSingleton()->performFilters($this->_contactIds));
    }

    function validate()
    {
        parent::validate();

        if ($this->errorMessage !== null)
        {
            $this->_errors[self::GroupNameElementIdentifier] = $this->errorMessage;
        }
    }

    function postProcess ()
    {
        parent::postProcess();

        $filters = CRM_Selectioncorrection_FilterHandler::getSingleton()->getFilters();

        $values = $this->exportValues(null, true);

        if ($values[self::LastPageIdentifier] == self::PreselectionPageName)
        {
            // Set the status for every filter based on the form values:
            foreach ($filters as $filter)
            {
                $identifier = $filter->getIdentifier();

                $isChecked = array_key_exists($identifier, $values);
                $filter->setStatus($isChecked);
            }
        }
        else
        {

        }

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

    // TODO: This function should be moved to an utility class.
    function getIndividualOrganisationRelationships ()
    {
        $relationshipMap = [];

        $individualOrganisationRelationships = civicrm_api3(
            'RelationshipType',
            'get',
            [
                'sequential' => 1,
                'contact_type_a' => "Individual",
                'contact_type_b' => "Organization",
                'return' => [
                    "id",
                    "label_a_b"
                ],
                'options' => [
                    'limit' => 0
                ],

            ]
        );

        foreach ($individualOrganisationRelationships['values'] as $relationship)
        {
            $relationshipMap[$relationship['id']] = $relationship['label_a_b'];
        }

        $organisationIndividualRelationships = civicrm_api3(
            'RelationshipType',
            'get',
            [
                'sequential' => 1,
                'contact_type_a' => "Organization",
                'contact_type_b' => "Individual",
                'return' => [
                    "id",
                    "label_b_a"
                ],
                'options' => [
                    'limit' => 0
                ],
            ]
        );

        foreach ($organisationIndividualRelationships['values'] as $relationship)
        {
            $relationshipMap[$relationship['id']] = $relationship['label_b_a'];
        }

        return $relationshipMap;
    }

    function getOrganisationsFromContacts ($contactIds)
    {
        $result = civicrm_api3(
            'Contact',
            'get',
            [
                'sequential' => 1,
                'return' => [
                    "id"
                ],
                'id' => [
                    'IN' => $contactIds
                ],
                'contact_type' => "Organization",
                'options' => [
                    'limit' => 0
                ],
            ]
        );

        $organisationIds = array_map(
            function ($contact)
            {
                return $contact['id'];
            },
            $result['values']
        );

        return $organisationIds;
    }

    // TODO: This function should be moved to an utility class.
    /**
     * Generates a tree of the following structure:
     *     organisationId -> relationshipTypeId -> contactId
     * @param string[] $contactIds A list of all contact IDs that shall be used for generating the tree.
     * @param string[] $relationshipIds A list of all relationship IDs that define a contact person relationship.
     * @return array Containts three objects: 'tree', 'contactPersonIds' and 'organisationIds'. The two lists have unique values.
     */
    function getOrganisationRelationshipContactPersonTree ($contactIds, $relationshipIds)
    {
        $organisationIds = $this->getOrganisationsFromContacts($contactIds);

        // Get all contacts from relationships with these organisations:
        $result = civicrm_api3(
            'Relationship',
            'get',
            [
                'sequential' => 1,
                'return' => [
                    "contact_id_a",
                    "contact_id_b",
                    "relationship_type_id"
                ],
                'contact_id_a' => [
                    'IN' => $organisationIds
                ],
                'contact_id_b' => [
                    'IN' => $organisationIds
                ],
                'relationship_type_id' => [
                    'IN' => $relationshipIds
                ],
                'is_active' => 1,
                'options' => [
                    'limit' => 0,
                    'or' => [
                        [
                            "contact_id_a",
                            "contact_id_b"
                        ]
                    ]
                ],
            ]
        );

        $tree = [];

        // Add the root level to the tree: All organisations.
        foreach ($organisationIds as $organisationId)
        {
            $tree[$organisationId] = [];
        }

        $contactPersonIds = [];

        // Fill the organisations with their relationships and the relationships
        // with their contacts by looping through all relationships:
        foreach ($result['values'] as $relationship)
        {
            $relationshipType = $relationship['relationship_type_id'];
            $contactA = $relationship['contact_id_a'];
            $contactB = $relationship['contact_id_b'];
            $organisation = null;
            $contactPerson = null;

            // We do not know if contact A or contact B is the organisation,
            // so we check if one of them is a key of the tree's root level.
            // If this is the case the contact must be the organisation,
            // otherwise the other contact is it.
            if (array_key_exists($contactA, $tree))
            {
                $organisation = $contactA;
                $contactPerson = $contactB;
            }
            else
            {
                $organisation = $contactB;
                $contactPerson = $contactA;
            }

            $contactPersonIds[] = $contactPerson;

            // If this relationship type is not present, add it to the tree as node:
            if (!array_key_exists($relationshipType, $tree[$organisation]))
            {
                $tree[$organisation][$relationshipType] = [];
            }

            // Finally, add the contact person the the tree's leave level:
            $tree[$organisation][$relationshipType][] = $contactPerson;
        }

        // Remove duplicate values in the contact persons list:
        array_keys(array_flip($contactPersonIds));

        $result = [
            'tree' => $tree,
            'contactPersonIds' => $contactPersonIds,
            'organisationIds' => $organisationIds,
        ];

        return $result;
    }

    function getContactDisplayNames ($contactIds)
    {
        $result = civicrm_api3(
            'Contact',
            'get',
            [
                'sequential' => 1,
                'return' => [
                    "id",
                    "display_name",
                ],
                'id' => [
                    'IN' => $contactIds
                ],
            '   options' => [
                    'limit' => 0
                ],
            ]
        );

        $contactIdNameMap = [];

        foreach ($result['values'] as $contact)
        {
            $contactIdNameMap[$contact['id']] = $contact['display_name'];
        }

        return $contactIdNameMap;
    }

    /**
     * Gets labels for relationship types.
     * This function factors in if the relationship is Individual->Organisation or Organisation->Individual.
     */
    function getRelationshipTypeLabels ($relationshipIds)
    {
        $result = civicrm_api3(
            'RelationshipType',
            'get',
            [
                'sequential' => 1,
                'return' => [
                    "id",
                    "contact_type_a",
                    "label_a_b",
                    "label_b_a",
                ],
                'id' => [
                    'IN' => $relationshipIds,
                ],
                'options' => [
                    'limit' => 0
                ],
            ]
        );

        $relationshiptypeIdLabelMap = [];

        foreach ($result['values'] as $relationshipType)
        {
            $label = '';
            // We use the label that descripes the relationship from the individual view:
            if ($relationshipType['contact_type_a'] == 'Individual')
            {
                $label = $relationshipType['label_a_b'];
            }
            else
            {
                $label = $relationshipType['label_b_a'];
            }

            $relationshiptypeIdLabelMap[$relationshipType['id']] = $label;
        }

        return $relationshiptypeIdLabelMap;
    }
}
