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

/**
 * Filter for only allowing contacts having a field (meaning a flag) in the contact table not set (meaning it is zero or null).
 */
class CRM_Selectioncorrection_Filter_ContactFieldNotSet extends CRM_Selectioncorrection_Filter_BaseClass
{
    private $field;

    public function __construct ($name, $field, $optional=true)
    {
        $this->name = $name;
        $this->field = $field;
        $this->optional = $optional;

        parent::__construct();
    }

    /**
    * @param Where $where
    */
    public function addWhere (Where $where)
    {
        $this->addSubwhereIsZeroOrNull($where, $this->field);
    }
}