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
 * Handles group logic like creation, adding contacts to it or checking if a group name is already taken.
 */
class CRM_Selectioncorrection_Group
{
    private $contactList = [];
    private $groupTitle = '';
    private $groupName = '';

    public function __construct ()
    {
        // TODO: Do we need to do something here?
    }

    public static function isGroupTitleFree ($groupTitle)
    {
        // TODO: Get this from the preselection page.
    }

    private function createGroup ()
    {
        $result = CRM_Selectioncorrection_Utility_CivicrmApi::create(
            'Group',
            [
                'title' => $this->groupTitle,
            ]
        );

        // TODO: $result['is_error'] != 0

        $groupId = $result['id'];
        $this->groupName = $result['values'][$groupId]['name'];
    }

    public function setGroupTitle ($groupTitle)
    {
        $this->groupTitle = $groupTitle;
    }

    public function add ($contactList)
    {
        $this->contactList = array_merge($this->contactList, $contactList);
    }

    public function save ()
    {
        $this->createGroup();

        CRM_Selectioncorrection_Utility_CivicrmApi::create(
            'GroupContact',
            [
                'group_id' => $this->groupName,
                'contact_id' => $this->contactList,
                'status' => 'Added',
            ]
        );

        // TODO: Should we clear the contact list here?
    }
}