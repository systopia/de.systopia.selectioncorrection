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
 * Utility class for handling contacts, getting them via API and extracting information from or with them.
 */
class CRM_Selectioncorrection_Utility_Contacts
{
    /**
     * Extracts contacts of a given type from a list of IDs.
     * @param string[] $contactIds The list of contact IDs.
     * @param string $type The type of the contacts to find.
     * @return string[] The list of contact IDs for all given contacts that are of the given type.
     */
    private static function getContactsWithTypeFromList ($contactIds, $type)
    {
        $contactsOfGivenType = CRM_Selectioncorrection_Utility_CivicrmApi::getValuesChecked(
            'Contact',
            [
                'return' => [
                    'id'
                ],
                'id' => [
                    'IN' => $contactIds
                ],
                'contact_type' => $type,
            ],
            [
                $contactIds,
            ]
        );

        // Convert the list of API result objects into a list of IDs:
        $typedIds = array_map(
            function ($contact)
            {
                return $contact['id'];
            },
            $contactsOfGivenType
        );

        return $typedIds;
    }

    /**
     * Extracts the individuals from a list of contacts.
     * @param string[] $contactIds The list of contact IDs.
     * @return string[] The list of contact IDs for all given contacts that are individuals.
     */
    static function getIndividualsFromContacts ($contactIds)
    {
        return self::getContactsWithTypeFromList($contactIds, 'Individual');
    }

    /**
     * Extracts the organisations from a list of contacts.
     * @param string[] $contactIds The list of contact IDs.
     * @return string[] The list of contact IDs for all given contacts that are organisations.
     */
    static function getOrganisationsFromContacts ($contactIds)
    {
        return self::getContactsWithTypeFromList($contactIds, 'Organization');
    }

    /**
     * Extracts the households from a list of contacts.
     * @param string[] $contactIds The list of contact IDs.
     * @return string[] The list of contact IDs for all given contacts that are households.
     */
    static function getHouseholdsFromContacts ($contactIds)
    {
        return self::getContactsWithTypeFromList($contactIds, 'Household');
    }

    /**
     * Gets the display names for a list of contacts.
     * @param string[] $contactIds The list of contact IDs.
     * @return array A map of "contact ID" => "display name".
     */
    static function getContactDisplayNames ($contactIds)
    {
        $result = CRM_Selectioncorrection_Utility_CivicrmApi::getValuesChecked(
            'Contact',
            [
                'return' => [
                    "id",
                    "display_name",
                ],
                'id' => [
                    'IN' => $contactIds
                ],
            ],
            [
                $contactIds,
            ]
        );

        $contactIdNameMap = [];

        foreach ($result as $contact)
        {
            $contactIdNameMap[$contact['id']] = $contact['display_name'];
        }

        return $contactIdNameMap;
    }
}