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

/**
 * Handles meta data logic like adding contacts to it and save them in the database.
 */
class CRM_Selectioncorrection_MetaData
{
    private $list = [];

    function __construct ()
    {
        // TODO: Do we need to do something here?
    }

    function add ($metaDataList)
    {
        $this->list = array_merge($this->list, $metaDataList);
    }

    function save ()
    {
        // TODO: Implement.
    }
}