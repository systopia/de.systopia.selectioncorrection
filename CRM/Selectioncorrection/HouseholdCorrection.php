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

class CRM_Selectioncorrection_HouseholdCorrection
{
    // TODO: We need the relationship types for households. So this class must become a singleton.
    //       In it's init function we search for all relationship types with a household as second contact
    //       per API and save them in here.

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
     */
    public function removeSinglePersonHouseholds ($contactIds)
    {
        $householdIds = CRM_Selectioncorrection_Utility_Contacts::getHouseholdsFromContacts($contactIds);

        // Get all active relationships for the households:
        $result = CRM_Selectioncorrection_Utility_CivicrmApi::getValuesChecked(
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

        // From the API result, create lists for all members and a map of members to households:
        $allActiveMembers = [];
        $memberHouseholdsMap = [];
        foreach ($result as $value)
        {
            $contactId = $value['contact_id_a'];

            $allActiveMembers[] = $contactId;

            if (!array_key_exists($contactId, $memberHouseholdsMap))
            {
                $memberHouseholdsMap[$contactId] = [];
            }
            $memberHouseholdsMap[$contactId][] = $value['contact_id_b'];
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
                    // Add the member to the list if not already present:
                    $toBeAddedIds[] = $members[0];
                }
            }
        }

        // Remove the to be deleted IDs from the contact IDs:
        $correctedContactIds = array_diff($contactIds, $toBeDeletedIds);

        $correctedContactIds = array_merge($correctedContactIds, $toBeAddedIds);
        $correctedContactIds = array_unique($correctedContactIds);

        return $correctedContactIds;
    }

    /**
     * If there are multiple individuals in the contact list with an active relationship to the
     * same household, they are removed and the household is added instead.
     */
    public function addHouseholdsWithMultipleMembersPresent ()
    {

    }
}
