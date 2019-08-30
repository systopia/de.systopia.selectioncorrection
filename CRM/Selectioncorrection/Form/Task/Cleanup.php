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
            CRM_Selectioncorrection_Utility_Relationships::getIndividualOrganisationRelationships(),
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

        $treeData = CRM_Selectioncorrection_Utility_DataStructures::getOrganisationRelationshipContactPersonTree($contactIds, $relationshipIds);

        $contactPersonTree = $treeData['tree'];
        $organisationIds = $treeData['organisationIds'];
        $contactPersonIds = $treeData['contactPersonIds'];

        $organisationNameMapping = CRM_Selectioncorrection_Utility_Contacts::getContactDisplayNames($organisationIds);
        $contactpersonNameMapping = CRM_Selectioncorrection_Utility_Contacts::getContactDisplayNames($contactPersonIds);
        $relationshipLabelMapping = CRM_Selectioncorrection_Utility_Relationships::getRelationshipTypeLabels($relationshipIds);

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
}
