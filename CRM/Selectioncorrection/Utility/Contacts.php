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
     * Extracts the organisations from a list of contacts.
     * @param string[] $contactIds The list of contact IDs.
     * @return string[] The list of contact IDs for all given contacts that are organisations.
     */
    static function getOrganisationsFromContacts ($contactIds)
    {
        $result = civicrm_api3(
            'Contact',
            'get',
            [
                'sequential' => 1,
                'return' => [
                    "id"
                ],
                'id' => [
                    'IN' => $contactIds
                ],
                'contact_type' => "Organization",
                'options' => [
                    'limit' => 0
                ],
            ]
        );

        $organisationIds = array_map(
            function ($contact)
            {
                return $contact['id'];
            },
            $result['values']
        );

        return $organisationIds;
    }

    /**
     * Gets the display names for a list of contacts.
     * @param string[] $contactIds The list of contact IDs.
     * @return array A map of "contact ID" => "display name".
     */
    static function getContactDisplayNames ($contactIds)
    {
        $result = civicrm_api3(
            'Contact',
            'get',
            [
                'sequential' => 1,
                'return' => [
                    "id",
                    "display_name",
                ],
                'id' => [
                    'IN' => $contactIds
                ],
            '   options' => [
                    'limit' => 0
                ],
            ]
        );

        $contactIdNameMap = [];

        foreach ($result['values'] as $contact)
        {
            $contactIdNameMap[$contact['id']] = $contact['display_name'];
        }

        return $contactIdNameMap;
    }
}