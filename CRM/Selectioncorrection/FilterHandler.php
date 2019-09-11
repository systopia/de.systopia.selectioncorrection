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

class CRM_Selectioncorrection_FilterHandler
{
    private static $singleton = NULL;

    /**
     * A list of all filter classes with associated data.
     */
    private $filters = [];

    function __construct ()
    {
        $this->filters = [
            new CRM_Selectioncorrection_Filter_NotDeceased(),
            new CRM_Selectioncorrection_Filter_AllowsMail(),
            new CRM_Selectioncorrection_Filter_AllowsEmail(),
        ];
    }

    /**
    * Get the filter handler singleton
    */
    public static function getSingleton ()
    {
        if (self::$singleton === NULL)
        {
            self::$singleton = new self();
        }

        return self::$singleton;
    }

    /**
     * Get all available filters.
     * @return CRM_Selectioncorrection_Filter_BaseClass[] The list of the filters.
     */
    public function getFilters ()
    {
        return $this->filters;
    }

    /**
     * Get the identifiers of all available filters.
     * @return string[] The list of filter identifiers.
     */
    public function getFilterIdentifiers ()
    {
        $filterIdentifiers = [];

        foreach ($this->filters as $filter)
        {
            $filterIdentifiers[] = $filter->getIdentifier();
        }
        return $filterIdentifiers;
    }

    /**
     * Get the statuses of all available filters.
     * @return string[] Key is the identifier, value the status.
     */
    public function getFilterStatuses ()
    {
        $filterStatuses = [];

        foreach ($this->filters as $filter)
        {
            $filterStatuses[$filter->getIdentifier()] = $filter->getStatus();
        }
        return $filterStatuses;
    }

    /**
     * Performs all active filters on a contact list.
     * @param array $contactIds A list of all contacts to filter.
     * @return array The filtered contact list.
     */
    public function performFilters ($contactIds)
    {
        if (empty($contactIds))
        {
            return [];
        }

        $builder = new GenericBuilder();

        $query = $builder->select()->setTable('civicrm_contact')->setColumns(['id']);

        // Add all joins to the query:
        foreach ($this->filters as $filter)
        {
            $filter->addJoin($query);
        }

        $where = $query->where();

        // Add all where clauses to the query:
        foreach ($this->filters as $filter)
        {
            $filter->addWhere($where);
        }

        // Include only the selected contacts:
        $where = $where->in('id', $contactIds);

        $query = $where->end();

        $sql = $builder->write($query);
        $values = $builder->getValues();
        // We need to reverse the array for keys with higher numbers are replaced before keys with
        // smaller ones. This is needed to prevent that ":v20" will be partly replaced with ":v2":
        $values = array_reverse($values);
        // Fill the named parameters in the query:
        //TODO: Maybe we should change this to use the DAO parameters with % instead?
        $sql = str_replace(array_keys($values), array_values($values), $sql);

        $queryResult = CRM_Core_DAO::executeQuery($sql);

        $resultIds = [];

        while ($queryResult->fetch())
        {
            $resultIds[] = $queryResult->id;
        }

        return $resultIds;
    }
}