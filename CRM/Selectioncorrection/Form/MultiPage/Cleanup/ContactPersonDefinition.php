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
    private const OptionIdToContactPersonDataMapStorageKey = 'contact_person_definition_option_id_to_contact_person_data_map';
    private const DirectlyToIncludOrganisationsStorageKey = 'contact_person_defintion_directly_to_include_organisations';
    private const ElementOrganisationMap = 'contact_person_definition_element_organisation_map';
    private const IncludeOrganisationDirectlyIndex = 0;
    private const GroupNameAlreadyInUseError = 'group_name_already_in_use';

    public const PageName = 'contact_person_definition';
    protected $name = self::PageName;

    public function build (&$defaults)
    {
        $values = $this->pageHandler->getPageValues(CRM_Selectioncorrection_Form_MultiPage_Cleanup_Preselection::PageName);

        $relationshipIds = $values[CRM_Selectioncorrection_Config::RelationshipTypeElementIdentifier];
        $contactIds = $this->pageHandler->_contactIds;

        $treeData = CRM_Selectioncorrection_Utility_DataStructures::getOrganisationRelationshipContactPersonTree($contactIds, $relationshipIds);

        $contactPersonTree = $treeData['tree'];
        $organisationIds = $treeData['organisationIds'];
        $contactPersonIds = $treeData['contactPersonIds'];

        $filteredContactPersonIds = CRM_Selectioncorrection_FilterHandler::getSingleton()->performFilters($contactPersonIds);
        $filteredContactPersonIdsAsKeys = array_flip($filteredContactPersonIds);

        $organisationNameMapping = CRM_Selectioncorrection_Utility_Contacts::getContactDisplayNames($organisationIds);
        $contactpersonNameMapping = CRM_Selectioncorrection_Utility_Contacts::getContactDisplayNames($contactPersonIds);
        $relationshipLabelMapping = CRM_Selectioncorrection_Utility_Relationships::getRelationshipTypeLabels($relationshipIds);

        $organisationTypeInformation = CRM_Selectioncorrection_Utility_Contacts::getContactTypes($organisationIds);

        /**
         * @var $idDataMap Maps option value IDs to the contact person data needed for processing.
         *                 A specific index is used to indicate that the organisation itself shall be included.
         */
        $idDataMap = [self::IncludeOrganisationDirectlyIndex => E::ts('direct')];

        /**
         * @var array $elementOrganisationMap Maps an element identifier to an organisation.
         */
        $elementOrganisationMap = [];

        /**
         * @var string[] $directlyToIncludOrganisations A list of organisations having no valid contact persons and shall therefor be included directly.
         */
        $directlyToIncludOrganisations = [];

        foreach ($contactPersonTree as $organisation => $relationshipTypes)
        {
            /**
             * @var $idLabelMap A map of option value IDs to labels, used by the select element.
             */
            $idLabelMap = [self::IncludeOrganisationDirectlyIndex => $idDataMap[self::IncludeOrganisationDirectlyIndex]];

            foreach ($relationshipTypes as $relationshipType => $contactPersons)
            {
                foreach ($contactPersons as $contactPersonData)
                {
                    $contactPersonId = $contactPersonData['contactId'];

                    // If the contact person has been filtered out, ignore it:
                    if (!array_key_exists($contactPersonId, $filteredContactPersonIdsAsKeys))
                    {
                        continue;
                    }

                    $idDataMap[] = $contactPersonData;
                    $optionId = count($idDataMap) - 1;

                    $optionLabel = $contactpersonNameMapping[$contactPersonId] . ' (' . $relationshipLabelMapping[$relationshipType] . ')';

                    $idLabelMap[$optionId] = $optionLabel;
                }
            }

            // If the count of ID labels is one, there was not a single contact person added (meaning there was no valid one).
            // In this case we include the organisation directly and therefor we do not need to show it in the selection list:
            if (count($idLabelMap) == 1)
            {
                $directlyToIncludOrganisations[] = $organisation;
                continue;
            }

            // Image/Info popup for the organisation:
            $typeInfo = $organisationTypeInformation[$organisation];
            $organisationImage = CRM_Contact_BAO_Contact_Utils::getImage(
                empty($typeInfo['contact_sub_type']) ? $typeInfo['contact_type'] : $typeInfo['contact_sub_type'],
                FALSE,
                $organisation
            );

            // Identifier for the element containing all contact persons for the organisation:
            $elementIdentifier = 'contact_persons_' . $organisation;
            $elementLabel = $organisationNameMapping[$organisation] . $organisationImage;

            $elementOrganisationMap[$elementIdentifier] = $organisation;

            $this->pageHandler->add(
                'select',
                $elementIdentifier ,
                $elementLabel,
                $idLabelMap,
                true,
                [
                    'multiple' => 'multiple',
                    'class' => 'crm-select2 huge',
                ]
            );
        }

        // The element organisation map is used in Smarty as an element list for rendering.
        $this->pageHandler->assign(self::ElementOrganisationMap, $elementOrganisationMap);

        // We will need the option ID to contact person data map in the process to identify the selected contact person IDs
        // and generate the necessary meta data regarding the contact person IDs and their relationships:
        CRM_Selectioncorrection_Storage::set(self::OptionIdToContactPersonDataMapStorageKey, $idDataMap);

        // The element organisation map is saved for being able to identify which elements are relevant and to which organisation
        // they belong to in the process stage. Furthermore, it is used to quickly regenerate the element structure
        // in the rebuild method without having to reprocess any of the above steps:
        CRM_Selectioncorrection_Storage::set(self::ElementOrganisationMap, $elementOrganisationMap);

        CRM_Selectioncorrection_Storage::set(self::DirectlyToIncludOrganisationsStorageKey, $directlyToIncludOrganisations);

        $this->pageHandler->setTitle(E::ts('Cleanup contact person selection'));
        // TODO: We could change this to not being called in the build function here but in the BaseClass for
        //       the multi page instead. It could be a property called "title" here instead.
    }

    public function rebuild ()
    {
        $elementOrganisationMap = CRM_Selectioncorrection_Storage::getWithDefault(self::ElementOrganisationMap, []);
        $elementIdentifiers = array_keys($elementOrganisationMap);

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
        $groupTitle = CRM_Selectioncorrection_Storage::get(CRM_Selectioncorrection_Config::GroupTitleStorageKey);

        if (CRM_Selectioncorrection_Group::doesGroupExist($groupTitle))
        {
            $errorValues = [1 => $groupTitle];
            $errorMessage = E::ts("Someone created the group '%1' in the background. Please rename it or go back and choose another group title.", $errorValues);

            $errors[self::GroupNameAlreadyInUseError] = $errorMessage;

            return false;
        }
        else
        {
            return true;
        }
    }

    public function process ()
    {
        $elementOrganisationMap = CRM_Selectioncorrection_Storage::getWithDefault(self::ElementOrganisationMap, []);
        $elementIdentifiers = array_keys($elementOrganisationMap);

        $idDataMap = CRM_Selectioncorrection_Storage::getWithDefault(self::OptionIdToContactPersonDataMapStorageKey, []);

        $pageValues = $this->pageHandler->getPageValues($this->name);

        // The following isn't that complicated:
        // "array_intersect_key" gives back all entries in the first given array which keys are also present in the second given array,
        // "array_flip" flips keys and values of an array, which is needed because "elementIdentifiers" is a list of identifiers, which
        // are the keys in "pageValues".
        $elementValues = array_intersect_key($pageValues, array_flip($elementIdentifiers));

        $contactIds = [];
        $contactRelationshipsMap = [];
        foreach ($elementValues as $elementIdentifier => $optionIds)
        {
            foreach ($optionIds as $optionId)
            {
                if ($optionId == self::IncludeOrganisationDirectlyIndex)
                {
                    // If we shall include the organisation directly, we do it by pushing the organisation to the contact list:
                    $contactIds[] = $elementOrganisationMap[$elementIdentifier];
                }
                else
                {
                    $optionData = $idDataMap[$optionId];
                    $contactId = $optionData['contactId'];
                    $relationshipId = $optionData['relationshipId'];

                    $contactIds[] = $contactId;

                    if (array_key_exists($contactId, $contactRelationshipsMap))
                    {
                        $contactRelationshipsMap[$contactId][] = $relationshipId;
                    }
                    else
                    {
                        $contactRelationshipsMap[$contactId] = [$relationshipId];
                    }
                }
            }
        }

        // Now we also have to include the organisations that shall be directly included
        // into the organisations list because they have now valid contact persons:
        $directlyToIncludOrganisations = CRM_Selectioncorrection_Storage::getWithDefault(self::DirectlyToIncludOrganisationsStorageKey, []);
        foreach ($directlyToIncludOrganisations as $organisationId)
        {
            $contactIds[] = $organisationId;
        }

        // Make the IDs unique to prevent duplicates:
        $contactIds = array_unique($contactIds);

        CRM_Selectioncorrection_Storage::set(CRM_Selectioncorrection_Config::FilteredContactPersonsStorageKey, $contactIds);

        // Create the meta data for every filtered selected contact's relationships:
        $metaData = [];
        foreach ($contactIds as $contactId)
        {
            // Directly included organisations have no relationships, so we have to check if the key is set:
            if (array_key_exists($contactId, $contactRelationshipsMap))
            {
                foreach ($contactRelationshipsMap[$contactId] as $relationshipId)
                {
                    $metaData[] = [
                        'contact_id' => $contactId,
                        'relationship_id' => $relationshipId,
                    ];
                }
            }
        }

        CRM_Selectioncorrection_Storage::set(CRM_Selectioncorrection_Config::ContactPersonsMetaDataStorageKey, $metaData);
    }
}
