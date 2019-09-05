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

/**
 * Utility class for handling complex data structures, getting them via API and extracting information from or with them.
 */
class CRM_Selectioncorrection_Utility_DataStructures
{
    /**
     * Generates a tree of the following structure:
     *     organisationId -> relationshipTypeId -> contactId
     * @param string[] $contactIds A list of all contact IDs that shall be used for generating the tree.
     * @param string[] $relationshipIds A list of all relationship IDs that define a contact person relationship.
     * @return array Containts three objects: 'tree', 'contactPersonIds' and 'organisationIds'. The two lists have unique values.
     */
    static function getOrganisationRelationshipContactPersonTree ($contactIds, $relationshipIds)
    {
        $organisationIds = CRM_Selectioncorrection_Utility_Contacts::getOrganisationsFromContacts($contactIds);

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
}