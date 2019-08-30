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

/**
 * Class for encapsulation of a multi page form task.
 * Used as a page in the multi page system.
 */
abstract class CRM_Selectioncorrection_MultiPage_PageBase
{
    // TODO: Find a way to share constants (identifier names) between pages!

    protected $name = 'PageBase';
    /**
     * @var CRM_Selectioncorrection_MultiPage_BaseClass $pageHandler
     */
    protected $pageHandler = null;

    function __construct ($pageHandler)
    {
        $this->pageHandler = $pageHandler;
    }

    public function getName ()
    {
        return $this->name;
    }

    abstract public function build (&$defaults);
    abstract public function validate (&$errors);
    abstract public function process ($values);
}
