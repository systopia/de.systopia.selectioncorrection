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

use NilPortugues\Sql\QueryBuilder\Builder\GenericBuilder;

/**
 * Handles meta data logic like adding contacts to it and save them in the database.
 * Meta data is a list of contact ID and relationship ID used in the exporter extension.
 */
class CRM_Selectioncorrection_MetaData
{
    private const TableName = 'civicrm_group_contact_metadata';

    private $list = [];
    private $groupId = null;

    public function __construct ()
    {
        // TODO: Do we need to do something here?
    }

    public function setGroupId ($groupId)
    {
        if ($groupId === null)
        {
            throw new InvalidArgumentException('Group id must not be null.');
        }

        $this->groupId = $groupId;
    }

    /**
     * Add a list of meta data.
     * Attention: This is an in-memory action. You need to call "save" to save them permanently in the table.
     */
    public function add ($metaDataList)
    {
        $this->list = array_merge($this->list, $metaDataList);
    }

    /**
     * Save the meta data list permanently to the table.
     */
    public function save ()
    {
        if ($this->groupId === null)
        {
            throw new InvalidArgumentException('Group id is not set.');
        }

        // In the following, we build the query once and fill it with dummy data:

        $builder = new GenericBuilder();

        $query = $builder->insert()->setTable(self::TableName);

        $query = $query->setValues(
            [
                /* %1 */ 'group_id' => $this->groupId,
                /* %2 */ 'contact_id' => 'contact_id_dummy',
                /* %3 */ 'relationship_id' => 'relationship_id_dummy',
            ]
        );

        $sql = $builder->write($query);
        $sql = CRM_Selectioncorrection_Utility_QueryBuilder::convertBuilderStatementToCiviStatement($sql);

        $values = $builder->getValues();
        $values = CRM_Selectioncorrection_Utility_QueryBuilder::convertBuilderValuesToCiviValues($values);

        // After that, we execute the query with the values of every meta data we have:
        foreach ($this->list as $metaData)
        {
            // The following is a small hack. We keep the values with their keys and types set and only
            // change the real value of it to the current meta data. Then we can pass it again to DAO:
            $values[2][0] = $metaData['contact_id'];
            $values[3][0] = $metaData['relationship_id'];

            // FIXME: Exception handling?
            CRM_Core_DAO::executeQuery($sql, $values);
        }
    }
}