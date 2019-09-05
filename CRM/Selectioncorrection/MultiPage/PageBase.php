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
    // TODO: Should we do something with the possibility of naming conflicts between elements in consecutive pages?

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

    /**
     * Build function, called to build the quick form.
     */
    abstract public function build (&$defaults);
    /**
     * Rebuld function, called to rebuild the structure of the build (meaning all elements from
     * build with the same names and types but ideally without filled in data) as fast as possible.
     * This is needed to allow using the form->exportValues function, which will only result data
     * from elements that have been created yet...
     */
    abstract public function rebuild ();
    /**
     * Validate function, called to validate user input from the form.
     */
    abstract public function validate (&$errors);
    /**
     * Process function, called to process the data from the form.
     */
    abstract public function process ($values);
}
