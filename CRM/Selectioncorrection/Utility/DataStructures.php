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
     *     organisationId -> relationshipTypeId -> [contactId, relationshipId]
     * @param string[] $organisationIds A list of all organisation IDs that shall be used for generating the tree.
     * @param string[] $relationshipIds A list of all relationship IDs that define a contact person relationship.
     * @return array Containts four objects: 'tree', 'contactPersonIds' and 'organisationIds'. The last two have unique values.
     */
    static function getOrganisationRelationshipContactPersonTree ($organisationIds, $relationshipIds)
    {
        // Get all contacts from relationships with these organisations:
        $result = CRM_Selectioncorrection_Utility_CivicrmApi::getValuesChecked(
            'Relationship',
            [
                'return' => [
                    'id',
                    'contact_id_a',
                    'contact_id_b',
                    'relationship_type_id',
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
            ],
            [
                $organisationIds,
                $relationshipIds,
            ],
            [
                'or' => [
                    [
                        "contact_id_a",
                        "contact_id_b"
                    ]
                ]
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
        foreach ($result as $relationship)
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

            // Add the contact person and the relationship to the tree's leave level:
            $tree[$organisation][$relationshipType][] = [
                'contactId' => $contactPerson,
                'relationshipId' => $relationship['id'],
            ];
        }

        array_unique($contactPersonIds);

        $result = [
            'tree' => $tree,
            'contactPersonIds' => $contactPersonIds,
            'organisationIds' => $organisationIds,
        ];

        return $result;
    }
}
