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

/**
 * Filter for only allowing contacts having data in an associated table (like email, address etc.).
 */
class CRM_Selectioncorrection_Filter_HasDataInAssociatedTable extends CRM_Selectioncorrection_Filter_BaseClass
{
    private $tableName;
    private $fieldName;

    /**
     * @param string $name The name of this filter for identification.
     * @param string $tableName The name of the associated table. Must have a field named "contact_id" referencing the contact.
     * @param string $fieldName The name of the field that must exist and not be zero or null.
     *
     */
    public function __construct ($name, $tableName, $fieldName, $optional=true)
    {
        $this->name = $name;
        $this->optional = $optional;

        parent::__construct();

        $this->tableName = $tableName;
        $this->fieldName = $fieldName;
    }

    public function addJoin (Select $select)
    {
        $query = $select->leftJoin(
            $this->tableName,
            'id',
            'contact_id'
        );

        // We only want the results where a field entry is found and it is not empty or null:
        $this->addSubwhereIsNotEmptyOrNull($query->where(), $this->fieldName);
    }
}
