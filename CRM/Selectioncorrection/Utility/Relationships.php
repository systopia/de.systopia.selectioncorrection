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
 * Utility class for handling relationships, getting them via API and extracting information from or with them.
 */
class CRM_Selectioncorrection_Utility_Relationships
{
    /**
     * Gets relationships of which one contact must be an individual and the other on an organisation.
     * Returns both individual-organisation and organisation-individual relationships.
     * @return array A map of "relationship type id" => "relationship label", with the label being the
     *               the one for the individual-organisation perspective.
     */
    static function getIndividualOrganisationRelationships ()
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

    /**
    * Gets labels for relationship types.
    * This function factors in if the relationship is Individual->Organisation or Organisation->Individual.
    * @return array A map of "relationship type ID" => "relationship label", with the label being the
     *               the one for the individual-organisation perspective.
    */
    static function getRelationshipTypeLabels ($relationshipIds)
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