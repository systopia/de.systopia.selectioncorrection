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

abstract class CRM_Selectioncorrection_Filter_BaseClass
{
    protected $name = 'BaseClass';
    protected $optional = true;

    /**
     * @return string The key used for saving the status in the storage.
     */
    private function getActiveStorageKey ()
    {
        return $this->getIdentifier() . '_active';
    }

    /**
     * Adds a subwhere statement for checking if a column is zero or null.
     * @param Where $where The where statement this should be added to.
     * @param string $column The column which shall be checked.
     */
    protected function addSubwhereIsZeroOrNull(Where $where, string $column)
    {
        $subwhere = $where->subWhere('OR');

        $subwhere->equals($column, 0)
                 ->isNull($column);
    }

    public function getName ()
    {
        return $this->name;
    }

    /**
     * Gets an unique identifier for the filter.
     * @return string
     */
    public function getIdentifier ()
    {
        $identifier = 'filter_' . strtolower($this->name);
        return $identifier;
    }

    public function isOptional ()
    {
        return $this->optional;
    }

    /**
     * Set the status of this filter.
     * @param bool $status True for active and false for inactive.
     */
    public function setActive ($status)
    {
        $key = $this->getActiveStorageKey();

        CRM_Selectioncorrection_Storage::set($key, $status);
    }

    /**
     * @return bool True if the filter is active, otherwise false.
     */
    public function isActive ()
    {
        $key = $this->getActiveStorageKey();

        return CRM_Selectioncorrection_Storage::getWithDefault($key, true);
    }

    public function addJoin ($select)
    {
        // Add nothing as default.
    }

    /**
     * @param Where $where
     */
    abstract public function addWhere (Where $where);
}