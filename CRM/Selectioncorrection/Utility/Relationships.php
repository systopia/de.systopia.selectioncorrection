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
     * Gets the list of eligible relationship as defined by the configuration
     *
     * @return array A map of "relationship type id" => "relationship label", with the label being the
     *               the one for the individual-organisation perspective.
     */
    static function getIndividualOrganisationRelationships ()
    {
        $relationshipMap = [];

        $relationship_type_ids = CRM_Selectioncorrection_Config::getRelationshipTypeIDS();
        if ($relationship_type_ids) {

          $individualOrganisationRelationships = CRM_Selectioncorrection_Utility_CivicrmApi::getValues(
              'RelationshipType',
              [
                  'id' => ['IN' => $relationship_type_ids],
                  'return' => [
                      "id",
                      "label_a_b"
                  ],
              ]
          );

          foreach ($individualOrganisationRelationships as $relationship)
          {
            $relationshipMap[$relationship['id']] = $relationship['label_a_b'];
          }

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
        $result = CRM_Selectioncorrection_Utility_CivicrmApi::getValuesChecked(
            'RelationshipType',
            [
                'return' => [
                    "id",
                    "contact_type_a",
                    "label_a_b",
                    "label_b_a",
                ],
                'id' => [
                    'IN' => $relationshipIds,
                ],
            ],
            [
                $relationshipIds
            ]
        );

       $relationshiptypeIdLabelMap = [];

       foreach ($result as $relationshipType)
       {
           $label = '';
           // We use the label that describes the relationship from the individual view:
           if (CRM_Utils_Array::value('contact_type_a', $relationshipType, 'Individual') == 'Individual')
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