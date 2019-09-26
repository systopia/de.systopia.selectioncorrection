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
 * Filter for only allowing contacts having a custom field (meaning a flag) in the custom group table not set (meaning it is zero or null).
 */
class CRM_Selectioncorrection_Filter_CustomFieldNotSet extends CRM_Selectioncorrection_Filter_BaseClass
{
    private $tableName;
    private $columnName;

    public function __construct ($name, $group, $field, $optional=true)
    {
        $this->name = $name;
        $this->optional = $optional;

        parent::__construct();

        $customFieldData = [];

        try
        {
            $customFieldData = CRM_Selectioncorrection_CustomData::getCustomField($group, $field);
        }
        catch (CiviCRM_API3_Exception $exception)
        {
            // If we get a CiviCRM_API3_Exception here, the custom field's group cannot be found.
            // In this case, we set the customFieldData to an empty array, letting the filter know
            // that its custom field is not available.
            $customFieldData = [];
        }

        if (empty($customFieldData))
        {
            // If the customFieldData is empty, the custom field or its group cannot be found.
            // In this case we deactivate the filter and make it non-optional, so the user can see
            // that the filter is not available.
            // TODO: Should we tell the user why?

            $this->setStatus(false);
            $this->optional = false;
        }
        else
        {
            $this->tableName = $customFieldData['table_name'];
            $this->columnName = $customFieldData['column_name'];
        }
    }

    /**
    * @param Select $select
    */
    public function addJoin (Select $select)
    {
        $query = $select->leftJoin(
            $this->tableName,
            'id',
            'entity_id'
        );

        // We only want the results where no field entry is found or it is zero or null:
        $this->addSubwhereIsZeroOrNull($query->where(), $this->columnName);
    }
}