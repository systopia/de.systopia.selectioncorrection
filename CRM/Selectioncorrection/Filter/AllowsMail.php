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

use \NilPortugues\Sql\QueryBuilder\Syntax\Where;

class CRM_Selectioncorrection_Filter_AllowsMail extends CRM_Selectioncorrection_Filter_BaseClass
{
    protected $name = 'AllowsMail';

    /**
    * @param Where $where
    */
    public function addWhere (Where $where)
    {
        $this->addSubwhereIsZeroOrNull($where, 'do_not_mail');
    }
}