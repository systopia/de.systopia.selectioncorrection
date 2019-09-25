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
+-------------------------------------------------------*/

use \NilPortugues\Sql\QueryBuilder\Manipulation\Select;

class CRM_Selectioncorrection_Filter_NotInGroup extends CRM_Selectioncorrection_Filter_BaseClass
{
    protected $name = 'Not in a group';

    /**
    * @param Select $select
    */
    public function addJoin (Select $select)
    {
        $query = $select->leftJoin(
            'civicrm_group_contact',
            'id',
            'contact_id'
        )
        ->on()
        ->equals('status', 'Added')
        ->end();

        // We only want the results where no active group is found:
        $query->where()
              ->isNull('id');
    }
}