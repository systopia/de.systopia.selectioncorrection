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

class CRM_Selectioncorrection_Filter_BulkmailSet extends CRM_Selectioncorrection_Filter_BaseClass
{
    protected $name = 'Bulkmail set';
    protected $defaultStatus = false;

    /**
    * @param Select $select
    */
    public function addJoin (Select $select)
    {
        $select->innerJoin(
            'civicrm_email',
            'id',
            'contact_id'
        )
        ->on()
        ->equals('is_primary', 1)
        ->equals('is_bulkmail', 1);
    }
}
