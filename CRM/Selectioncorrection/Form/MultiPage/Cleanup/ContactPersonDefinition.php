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

class CRM_Selectioncorrection_Form_MultiPage_Cleanup_ContactPersonDefinition extends CRM_Selectioncorrection_MultiPage_PageBase
{
    private const RelationshipTypeElementIdentifier = 'relationship_types';
    private const ElementListStorageKey = 'contact_person_definition_element_identifiers';

    protected $name = 'contact_person_definition';

    public function build (&$defaults)
    {
        $values = $this->pageHandler->getFilteredExportValues();

        $contactIds = $this->pageHandler->_contactIds;
        $relationshipIds = $values[self::RelationshipTypeElementIdentifier];

        $treeData = CRM_Selectioncorrection_Utility_DataStructures::getOrganisationRelationshipContactPersonTree($contactIds, $relationshipIds);

        $contactPersonTree = $treeData['tree'];
        $organisationIds = $treeData['organisationIds'];
        $contactPersonIds = $treeData['contactPersonIds'];

        $organisationNameMapping = CRM_Selectioncorrection_Utility_Contacts::getContactDisplayNames($organisationIds);
        $contactpersonNameMapping = CRM_Selectioncorrection_Utility_Contacts::getContactDisplayNames($contactPersonIds);
        $relationshipLabelMapping = CRM_Selectioncorrection_Utility_Relationships::getRelationshipTypeLabels($relationshipIds);

        /**
         * @var array $organisationsElementList List of all element lists per organisation.
         */
        $organisationsElementList = [];
        /**
         * @var array $elementList List of all elements.
         */
        $elementList = [];

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

                $this->pageHandler->add(
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
            $elementList = array_merge($elementList, $elementIdentifiers);
        }

        // Save the element list in the storage so we can easily rebuild the build structure.
        CRM_Selectioncorrection_Storage::set(self::ElementListStorageKey, $elementList);

        $this->pageHandler->assign('contact_person_definition_organisations_element_list', $organisationsElementList);

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

    public function rebuild ()
    {
        $elementIdentifiers = CRM_Selectioncorrection_Storage::getWithDefault(self::ElementListStorageKey, []);

        foreach ($elementIdentifiers as $elementIdentifier)
        {
            $this->pageHandler->add(
                'select',
                $elementIdentifier ,
                $elementIdentifier,
                [],
                false,
                [
                    'multiple' => 'multiple',
                ]
            );
        }
    }

    public function validate (&$errors)
    {
        // TODO: Implement.
    }

    public function process ($values)
    {
        // TODO: Implement.
    }
}
