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

// TODO: A lot of things here could be unitified and outsourced into their own functions, thus be used multiple times.
class CRM_Selectioncorrection_HouseholdCorrection
{
    private static $singleton = NULL;

    /**
     * A list of all relationship type IDs with a household as second contact.
     */
    private $householdRelationshipTypeIds = [];

    function __construct ()
    {
        $result = CRM_Selectioncorrection_Utility_CivicrmApi::getValues(
            'RelationshipType',
            [
                'return' => [
                    "id"
                ],
                'contact_type_b' => 'Household',
                'is_active' => 1,
            ]
        );

        foreach ($result as $value)
        {
            $this->householdRelationshipTypeIds[] = $value['id'];
        }
    }

    /**
    * Get the filter handler singleton
    */
    public static function getSingleton ()
    {
        if (self::$singleton === NULL)
        {
            self::$singleton = new self();
        }

        return self::$singleton;
    }

    /**
     * Removes households from the list where there is only one or less individuals with an active
     * household relationship that complains to the active filters. If the household is removed
     * and there is such an individual that isn't already in the list, it is added.
     * @param string[] $householdIds A list of IDs that contains ONLY the households that shall be corrected.
     * @return string[] The list of corrected IDs (both households and individuals).
     */
    public function removeSinglePersonHouseholds ($householdIds)
    {
        // Get all active relationships for the households:
        $relationships = CRM_Selectioncorrection_Utility_CivicrmApi::getValuesChecked(
            'Relationship',
            [
                'return' => [
                    'contact_id_a',
                    'contact_id_b'
                ],
                'relationship_type_id' => [
                    'IN' => $this->householdRelationshipTypeIds
                ],
                'contact_id_b' => [
                    'IN' => $householdIds
                ],
                'is_active' => 1,
            ],
            [
                $householdIds,
                $this->householdRelationshipTypeIds
            ]
        );

        // From the API result with the relationships, create lists for all members and a map of members to households:
        $allActiveMembers = [];
        $memberHouseholdsMap = [];
        foreach ($relationships as $relationship)
        {
            $contactId = $relationship['contact_id_a'];

            $allActiveMembers[] = $contactId;

            if (!array_key_exists($contactId, $memberHouseholdsMap))
            {
                $memberHouseholdsMap[$contactId] = [];
            }
            $memberHouseholdsMap[$contactId][] = $relationship['contact_id_b'];
        }

        $filteredMembers = CRM_Selectioncorrection_FilterHandler::getSingleton()->performFilters($allActiveMembers);

        // Fill a map of households to their filtered members:
        $filteredHouseholdMembersMap = [];
        foreach ($filteredMembers as $member)
        {
            foreach ($memberHouseholdsMap[$member] as $household)
            {
                if (!array_key_exists($household, $filteredHouseholdMembersMap))
                {
                    $filteredHouseholdMembersMap[$household] = [];
                }
                $filteredHouseholdMembersMap[$household][] = $member;
            }
        }

        // Now we can perform the household correction based on the filtered members count for each household:
        $toBeDeletedIds = [];
        $toBeAddedIds = [];
        foreach ($filteredHouseholdMembersMap as $household => $members)
        {
            $memberCount = count($members);

            if ($memberCount < 2)
            {
                // Remove the household from the list:
                $toBeDeletedIds[] = $household;

                if ($memberCount == 1)
                {
                    // Add the member to the list:
                    $toBeAddedIds[] = $members[0];
                }
            }
        }

        // Remove the to be deleted IDs from the contact IDs:
        $correctedContactIds = array_diff($householdIds, $toBeDeletedIds);
        // Add the to be added IDs to the corrected contact IDs:
        $correctedContactIds = array_merge($correctedContactIds, $toBeAddedIds);
        // Finally make every entry unique to prevent duplicate IDs:
        // NOTE: This is not necessary as the group->add method goes sure that there are no duplicates
        //       and the API call later does this, too... but it's cleaner...
        $correctedContactIds = array_unique($correctedContactIds);

        return $correctedContactIds;
    }

    /**
     * If there are multiple individuals in the contact list with an active relationship to the
     * same household, they are removed and the household is added instead.
     * @param string[] $individualIds A list of IDs that contains ONLY the individuals that shall be corrected.
     * @return string[] The list of corrected IDs (both individuals and households).
     */
    public function addHouseholdsWithMultipleMembersPresent ($individualIds)
    {
        // Get all active relationships for the individuals to their households:
        $relationships = CRM_Selectioncorrection_Utility_CivicrmApi::getValuesChecked(
            'Relationship',
            [
                'return' => [
                    'contact_id_a',
                    'contact_id_b',
                ],
                'relationship_type_id' => [
                    'IN' => $this->householdRelationshipTypeIds
                ],
                'contact_id_a' => [
                    'IN' => $individualIds
                ],
                'is_active' => 1,
            ],
            [
                $individualIds,
                $this->householdRelationshipTypeIds
            ]
        );

        // From the API result with the relationships, create a map of households to members and fill the meta data:
        $householdsMembersMap = [];
        foreach ($relationships as $relationship)
        {
            $householdId = $relationship['contact_id_b'];
            $memberId = $relationship['contact_id_a'];

            if (!array_key_exists($householdId, $householdsMembersMap))
            {
                $householdsMembersMap[$householdId] = [];
            }
            $householdsMembersMap[$householdId][] = $memberId;
        }

        // Now we can perform the household correction based on the members count for each household:
        $toBeDeletedIds = [];
        $toBeAddedIds = [];
        foreach ($householdsMembersMap as $household => $members)
        {
            $memberCount = count($members);

            if ($memberCount > 1)
            {
                // Remove the members from the list:
                $toBeDeletedIds = array_merge($toBeDeletedIds, $members);

                // Add the household to the list:
                $toBeAddedIds[] = $household;
            }
        }

        // Remove the to be deleted IDs from the contact IDs:
        $correctedContactIds = array_diff($individualIds, $toBeDeletedIds);
        // Add the to be added IDs to the corrected contact IDs:
        $correctedContactIds = array_merge($correctedContactIds, $toBeAddedIds);
        // Finally make every entry unique to prevent duplicate IDs:
        // NOTE: This is not necessary as the group->add method goes sure that there are no duplicates
        //       and the API call later does this, too... but it's cleaner...
        $correctedContactIds = array_unique($correctedContactIds);

        return $correctedContactIds;
    }

    /**
     * From the contact list given, extract all individuals whose household is in the list as well and return them.
     * @param string[] $individualIds
     * @param string[] $householdIds
     * @return string[] The list of individuals that
     */
    public function getIndividualsWithHouseholdPresent ($individualIds, $householdIds)
    {
        // Get all active relationships for the individuals to their households:
        $relationships = CRM_Selectioncorrection_Utility_CivicrmApi::getValuesChecked(
            'Relationship',
            [
                'return' => [
                    'contact_id_a',
                    'contact_id_b',
                ],
                'relationship_type_id' => [
                    'IN' => $this->householdRelationshipTypeIds
                ],
                'contact_id_a' => [
                    'IN' => $individualIds
                ],
                'is_active' => 1,
            ],
            [
                $individualIds,
                $this->householdRelationshipTypeIds
            ]
        );

        // We need the households as keys for a well-performaning check if they exist in the list:
        $householdIdsAsKeys = array_flip($householdIds);

        // Now check for every member if one of their households is the households list:
        $individualsWithHouseholdPresent = [];
        foreach ($relationships as $relationship)
        {
            $individualId = $relationship['contact_id_a'];
            $householdId = $relationship['contact_id_b'];

            if (array_key_exists($householdId, $householdIdsAsKeys))
            {
                $individualsWithHouseholdPresent[] = $individualId;
            }
        }

        // Finally make every entry unique to prevent duplicate IDs:
        // NOTE: This is not necessary as the group->add method goes sure that there are no duplicates
        //       and the API call later does this, too... but it's cleaner...
        $individualsWithHouseholdPresent = array_unique($individualsWithHouseholdPresent);

        return $individualsWithHouseholdPresent;
    }
}
