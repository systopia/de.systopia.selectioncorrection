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

use Symfony\Component\Process\Exception\InvalidArgumentException;

/**
 * Handles group logic like creation, adding contacts to it or checking if a group name is already taken.
 */
class CRM_Selectioncorrection_Group
{
    private $contactList = [];
    private $groupTitle = null;
    private $groupName = null;
    private $groupId = null;

    /**
     * Checks if a group does already exists.
     * @param string $groupNameOrTitle The name/title of the group.
     * @return bool True if it exists, otherwise false.
     */
    public static function doesGroupExist ($groupNameOrTitle)
    {
        $foundGroups = CRM_Selectioncorrection_Utility_CivicrmApi::get(
            'Group',
            [
                'return' => [
                    'title'
                ],
                'name' => $groupNameOrTitle,
                'title' => $groupNameOrTitle,
            ],
            [
                'limit' => 1,
                'or' => [
                    [
                        'name',
                        'title',
                    ]
                ]
            ]
        );

        return $foundGroups['count'] > 0;
    }

    private function createGroup ()
    {
        if ($this->groupTitle === null)
        {
            throw new InvalidArgumentException('Group title is not set.');
        }

        $result = CRM_Selectioncorrection_Utility_CivicrmApi::create(
            'Group',
            [
                'title' => $this->groupTitle,
            ]
        );

        // TODO: Do we need this here? Or will this be thrown by the API call aboth anyway?
        // FIXME: Handle the exception. This should not happen because we checked earlier (but could).
        if ($result['is_error'])
        {
            throw new CiviCRM_API3_Exception($result['error_message'], $result['error_code']);
        }

        $resultValue = $result['values'][0];

        $this->groupName = $resultValue['name'];
        $this->groupId = $resultValue['id'];
    }

    public function setGroupTitle ($groupTitle)
    {
        $this->groupTitle = $groupTitle;
    }

    /**
     * The group ID is available after the group has been saved.
     * Otherwise it will be null.
     */
    public function getGroupId ()
    {
        return $this->groupId;
    }

    /**
     * Add a list of contacts to the group.
     * Attention: This is an in-memory action. You need to call "save" to save them permanently in Civi.
     */
    public function add ($contactList)
    {
        $this->contactList = array_merge($this->contactList, $contactList);
    }

    /**
     * Save the group permanently to Civi.
     */
    public function save ()
    {
        $this->createGroup();

        CRM_Selectioncorrection_Utility_CivicrmApi::createChecked(
            'GroupContact',
            [
                'group_id' => $this->groupName, // Even if it is called "group_id", the group name is meant.
                'contact_id' => $this->contactList,
                'status' => 'Added',
            ],
            [
                $this->groupName,
                $this->contactList
            ]
        );
    }
}
