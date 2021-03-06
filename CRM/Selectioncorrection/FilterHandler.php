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
    private static $singleton = null;

    /**
     * A list of all filter classes with associated data.
     */
    private $filters = [];

    /**
     * A list of all internal filters which are applied but not listed.
     * This is used for non-optional filters that shall be not visible, like the "is not deleted" filter.
     */
    private $internalFilters = [];

    function __construct ()
    {
        $this->filters = [
            new CRM_Selectioncorrection_Filter_ContactFieldNotSet('Is not deceased', 'is_deceased', false),
            new CRM_Selectioncorrection_Filter_ContactFieldNotSet('Allows email', 'do_not_email'),
            new CRM_Selectioncorrection_Filter_HasDataInAssociatedTable('Has email address', 'civicrm_email', 'email'),
            new CRM_Selectioncorrection_Filter_ContactFieldNotSet('Allows mail', 'do_not_mail'),
            // FIXME: The following filter is insufficient. A street address is not enough for postal mailing.
            new CRM_Selectioncorrection_Filter_HasDataInAssociatedTable('Has mail address', 'civicrm_address', 'street_address'),
            //new CRM_Selectioncorrection_Filter_BulkmailSet(), // DISABLED FOR NOW.
            //new CRM_Selectioncorrection_Filter_NotInGroup(),  // DISABLED FOR NOW.
            new CRM_Selectioncorrection_Filter_CustomFieldNotSet('Allows event invitation (custom)', 'besonderheiten', 'bes_29883'),
            new CRM_Selectioncorrection_Filter_CustomFieldNotSet('Allows RW (custom)', 'besonderheiten', 'bes_30170'),
            new CRM_Selectioncorrection_Filter_CustomFieldNotSet('Not digital RW (custom)', 'besonderheiten', 'bes_30186'),
            new CRM_Selectioncorrection_Filter_CustomFieldNotSet('Allows WK (custom)', 'besonderheiten', 'bes_30395'),
            new CRM_Selectioncorrection_Filter_CustomFieldNotSet('Allows JB (custom)', 'besonderheiten', 'bes_30445'),
        ];

        $this->internalFilters = [
            new CRM_Selectioncorrection_Filter_ContactFieldNotSet('Is not deleted', 'is_deleted'),
        ];
    }

    /**
    * Get the filter handler singleton
    */
    public static function getSingleton ()
    {
        if (self::$singleton === null)
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
     * Get all currently enabled filters.
     * @return CRM_Selectioncorrection_Filter_BaseClass[] The list of the filters.
     */
    public function getActiveFilters ()
    {
        $activeFilters = [];

        foreach ($this->filters as $filter)
        {
            if ($filter->getStatus())
            {
                $activeFilters[] = $filter;
            }
        }

        return $activeFilters;
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

        $select = $builder->select();
        $query = $select->setTable('civicrm_contact')->setColumns(['id']);

        $activeFilters = $this->getActiveFilters();

        // Add all joins to the select statement:
        foreach ($activeFilters as $filter)
        {
            $filter->addJoin($select);
        }
        foreach ($this->internalFilters as $filter)
        {
            $filter->addJoin($select);
        }

        $where = $query->where();

        // Add all where clauses to the query:
        foreach ($activeFilters as $filter)
        {
            $filter->addWhere($where);
        }
        foreach ($this->internalFilters as $filter)
        {
            $filter->addWhere($where);
        }

        // Include only the selected contacts:
        $where = $where->in('id', $contactIds);

        $query = $where->end();

        $sql = $builder->write($query);
        $sql = CRM_Selectioncorrection_Utility_QueryBuilder::convertBuilderStatementToCiviStatement($sql);

        $values = $builder->getValues();
        $values = CRM_Selectioncorrection_Utility_QueryBuilder::convertBuilderValuesToCiviValues($values);

        $queryResult = CRM_Core_DAO::executeQuery($sql, $values);

        $resultIds = [];

        while ($queryResult->fetch())
        {
            $resultIds[] = $queryResult->id;
        }

        return $resultIds;
    }
}
