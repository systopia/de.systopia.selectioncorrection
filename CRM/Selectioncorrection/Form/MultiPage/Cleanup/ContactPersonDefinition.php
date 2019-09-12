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
    private const ElementListStorageKey = 'contact_person_definition_element_identifiers';
    private const IdentifierContactRelationshipMapStorageKey = 'contact_person_definition_identifier_contact_relationship_map';

    protected $name = 'contact_person_definition';

    public function build (&$defaults)
    {
        $values = $this->pageHandler->getFilteredExportValues();

        $contactIds = $this->pageHandler->_contactIds;
        $relationshipIds = $values[CRM_Selectioncorrection_Config::RelationshipTypeElementIdentifier];

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

        /**
         * @var array $identifierContactRelationshipMap A map for connecting identifiers and contacts to relationships.
         */
        $identifierContactRelationshipMap = [];

        foreach ($contactPersonTree as $organisation => $relationships)
        {
            $elementIdentifiers = [];

            foreach ($relationships as $relationship => $relationshipContactPersonIds)
            {
                // Fill a list with contactPersonId => contactPersonLabel
                // and one with contactPersonId => relationshipId
                // TODO: We need a way to include the organisation directly.
                $contactpersonsLabelMap = [];
                $contactpersonRelationshipMap = [];
                foreach ($relationshipContactPersonIds as $contactPersonData)
                {
                    $contactPersonId = $contactPersonData['contactId'];
                    $relationshipId = $contactPersonData['relationshipId'];

                    $contactpersonsLabelMap[$contactPersonId] = $contactpersonNameMapping[$contactPersonId];
                    $contactpersonRelationshipMap[$contactPersonId] = $relationshipId;
                }

                $elementIdentifier = 'contact_persons_' . $organisation . '_' . $relationship;

                $this->pageHandler->add(
                    'select',
                    $elementIdentifier ,
                    $relationshipLabelMapping[$relationship],
                    $contactpersonsLabelMap,
                    false,
                    [
                        'multiple' => 'multiple',
                        'class' => 'crm-select2 huge',
                    ]
                );

                $elementIdentifiers[] = $elementIdentifier;
                $identifierContactRelationshipMap[$elementIdentifier] = $contactpersonRelationshipMap;
            }

            $organisatioName = $organisationNameMapping[$organisation];

            $organisationsElementList[$organisatioName] = $elementIdentifiers;
            $elementList = array_merge($elementList, $elementIdentifiers);
        }

        // Save the element list in the storage so we can easily rebuild the build structure.
        CRM_Selectioncorrection_Storage::set(self::ElementListStorageKey, $elementList);

        $this->pageHandler->assign('contact_person_definition_organisations_element_list', $organisationsElementList);

        // Save the identifier contact relationship map in the storage so we can create meta data out of it in the process method later:
        CRM_Selectioncorrection_Storage::set(self::IdentifierContactRelationshipMapStorageKey, $identifierContactRelationshipMap);

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

    public function process ()
    {
        // TODO: There should be a way to include the organisation directly, see build method.

        $elementIdentifiers = CRM_Selectioncorrection_Storage::getWithDefault(self::ElementListStorageKey, []);
        $identifierContactRelationshipMap = CRM_Selectioncorrection_Storage::getWithDefault(self::IdentifierContactRelationshipMapStorageKey, []);

        $elementValues = $this->pageHandler->getFilteredExportValues($elementIdentifiers);

        $contactIds = [];
        $contactRelationshipsMap = [];
        foreach ($elementValues as $elementIdentifier => $elementContactIds)
        {
            if (empty($elementContactIds))
            {
                continue;
            }

            $contactIds = array_merge($contactIds, $elementContactIds);

            // We will need a contact relationships map later for creating the meta data for the filtered contacts:
            foreach ($elementContactIds as $contactId)
            {
                $relationshipId = $identifierContactRelationshipMap[$elementIdentifier][$contactId];

                if (!array_key_exists($contactId, $contactRelationshipsMap))
                {
                    $contactRelationshipsMap[$contactId] = [];
                }

                $contactRelationshipsMap[$contactId][] = $relationshipId;
            }
        }

        // Make the IDs unique to prevent duplicates:
        $contactIds = array_unique($contactIds);

        $filteredContactIds = CRM_Selectioncorrection_FilterHandler::getSingleton()->performFilters($contactIds);
        CRM_Selectioncorrection_Storage::set(CRM_Selectioncorrection_Config::FilteredContactPersonsStorageKey, $filteredContactIds);

        // Create the meta data for every filtered selected contact's relationships:
        $metaData = [];
        foreach ($filteredContactIds as $contactId)
        {
            foreach ($contactRelationshipsMap[$contactId] as $relationshipId)
            {
                $metaData[] = [
                    'contact_id' => $contactId,
                    'relationship_id' => $relationshipId,
                ];
            }
        }

        CRM_Selectioncorrection_Storage::set(CRM_Selectioncorrection_Config::ContactPersonsMetaDataStorageKey, $metaData);
    }
}
