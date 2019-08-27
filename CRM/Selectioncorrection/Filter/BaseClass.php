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
    protected $active = true;

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

    public function isActive ()
    {
        return $this->active;
        // TODO: This value must be saved in the storage. Either here (encapsulated but circular) or directly in the form.
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