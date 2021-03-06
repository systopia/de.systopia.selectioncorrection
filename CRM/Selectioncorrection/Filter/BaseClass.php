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
use \NilPortugues\Sql\QueryBuilder\Manipulation\Select;

abstract class CRM_Selectioncorrection_Filter_BaseClass
{
    protected $name = 'BaseClass';
    protected $identifier = '';
    protected $optional = true;
    protected $defaultStatus = true;

    public function __construct ()
    {
        // Replace all non-word (not letters, numbers or underscore) with an underscore.
        // This is needed for it to be an universal identifier usable in HTML and other things.
        $identifier = preg_replace('/[^\w]+/', '_', $this->name);

        $identifier = strtolower($identifier);
        $identifier = 'filter_' . $identifier;

        $this->identifier = $identifier;
    }

    /**
     * @return string The key used for saving the status in the storage.
     */
    private function getStatusStorageKey ()
    {
        return $this->getIdentifier() . '_status';
    }

    /**
     * Adds a subwhere statement for checking if a column is zero or null. \
     * NOTE: This must only be used for numeric (integer, float, boolean etc.) fields, NOT for strings because they equal zero!
     * @param Where $where The where statement this should be added to.
     * @param string $column The column which shall be checked.
     */
    protected function addSubwhereIsZeroOrNull (Where $where, string $column)
    {
        $subwhere = $where->subWhere('OR');

        $subwhere->equals($column, 0)
                 ->isNull($column);
    }

    /**
     * Adds a subwhere statement for checking if a column is not zero or null. \
     * NOTE: This must only be used for numeric (integer, float, boolean etc.) fields, NOT for strings because they equal zero!
     * @param Where $where The where statement this should be added to.
     * @param string $column The column which shall be checked.
     */
    protected function addSubwhereIsNotZeroOrNull (Where $where, string $column)
    {
        $subwhere = $where->subWhere('AND');

        $subwhere->notEquals($column, 0)
                 ->isNotNull($column);
    }

    /**
     * Adds a subwhere statement for checking if a column is not empty or null. \
     * NOTE: This should be used for string type fields and could have unintended behaviour for numeric ones.
     * @param Where $where The where statement this should be added to.
     * @param string $column The column which shall be checked.
     */
    protected function addSubwhereIsNotEmptyOrNull (Where $where, string $column)
    {
        $subwhere = $where->subWhere('AND');

        $subwhere->notEquals($column, '')
                 ->isNotNull($column);
    }

    /**
     * Returns a human readable name for the filter.
     */
    public function getName ()
    {
        return $this->name;
    }

    /**
     * Returns an unique identifier for the filter.
     * @return string
     */
    public function getIdentifier ()
    {
        return $this->identifier;
    }

    public function isOptional ()
    {
        return $this->optional;
    }

    /**
     * Set the status of this filter.
     * @param bool $status True for active and false for inactive.
     */
    public function setStatus ($status)
    {
        $key = $this->getStatusStorageKey();

        CRM_Selectioncorrection_Storage::set($key, $status);
    }

    /**
     * @return bool True if the filter is active, otherwise false.
     */
    public function getStatus ()
    {
        $key = $this->getStatusStorageKey();

        return CRM_Selectioncorrection_Storage::getWithDefault($key, $this->defaultStatus);
    }

    public function addJoin (Select $select)
    {
        // Add nothing as default.
    }

    /**
     * @param Where $where
     */
    public function addWhere (Where $where)
    {
        // Add nothing as default.
    }
}
