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

use CRM_Selectioncorrection_ExtensionUtil as E;

require_once 'CRM/Core/Form.php';

/**
 * Class for encapsulation of a multi page form task.
 */
abstract class CRM_Selectioncorrection_MultiPage_BaseClass extends CRM_Contact_Form_Task
{
    private const LastPageIdentifier = 'last_page';
    private const CurrentPageIdentifier = 'current_page';

    /**
     * A list of all pages with their identifiers as keys.
     * @var CRM_Selectioncorrection_MultiPage_PageBase[] $pages
     */
    private $pages = [];
    /**
     * Containts the pages that follow the page.
     * @var string[] $nextPages "pageName" => "next pageName"
     */
    private $nextPages = [];
    /**
     * @var string $firstPageName
     */
    private $firstPageName = '';
    /**
     * @var string $finalPageName
     */
    private $finalPageName = '';

    /**
     * A map of all occured errors.
     * @var string[] $errors "identifier" => "message"
     */
    protected $errors = [];

    /**
     * Adds a page to the page list.
     */
    protected function addPage (CRM_Selectioncorrection_MultiPage_PageBase $page)
    {
        $pageName = $page->getName();

        $this->pages[$pageName] = $page;

        if (empty($this->firstPageName))
        {
            $this->firstPageName = $pageName;
        }

        if (!empty($this->finalPageName))
        {
            $this->nextPages[$this->finalPageName] = $pageName;
        }

        $this->finalPageName = $pageName;
    }

    /**
     * Adds a list of pages.
     */
    protected function addPages (array $pages)
    {
        foreach ($pages as $page)
        {
            $this->addPage($page);
        }
    }

    /**
     * Returns the current page name, meaning the LAST rendered.
     */
    public function currentPageName ()
    {
        $values = $this->exportValues(null, true);

        return $values[self::LastPageIdentifier];
    }

    public function preProcess ()
    {
        CRM_Selectioncorrection_Storage::initialise($this);

        parent::preProcess();
    }

    public function buildQuickForm ()
    {
        parent::buildQuickForm();

        /**
         * Array holding the default values for every element.
         * Will be set at the end of the function.
         */
        $defaults = [];

        // Add an element containing current page identifier:
        // FIXME: Because of this element, and maybe the smarty and default values the task does not close.
        $this->add(
            'hidden',
            self::LastPageIdentifier
        );

        $lastPageName = $this->currentPageName();
        $nextPageName = '';

        if (empty($lastPageName))
        {
            // If thee last page name is empty, we want to show the first page.
            // For this we have to set the next page name to the first page name:
            $nextPageName = $this->firstPageName;
            // But in addition we have to set the last page to the first page, too,
            // so they are the same to prevent that the build routine tries to render
            // the last page, which is empty.
            $lastPageName = $nextPageName;
        }
        else // If there has been another page before, do the following:
        {
            // Validate the last page to check if there were any errors:
            $this->pages[$lastPageName]->validate($this->errors);

            // Set the next page as long as there is another one:
            if ($lastPageName != $this->finalPageName)
            {
                $nextPageName = $this->nextPages[$lastPageName];
            }
        }

        // If we had errors, show the same page again:
        if (!empty($this->errors))
        {
            $nextPageName = $lastPageName;
        }

        if (($lastPageName != $nextPageName) && !empty($nextPageName))
        {
            // We need the elements of the last page created in PHP (but not rendered in smarty)
            // for the next page to be able to read it's element values.
            // Theoretically it would be "cleaner" if we had dummy elements created everytime.
            $this->pages[$lastPageName]->build($defaults);
        }

        // Building of the next page:
        if (!empty($nextPageName))
        {
            $this->pages[$nextPageName]->build($defaults);

            $this->assign(self::CurrentPageIdentifier, $nextPageName);
        }

        $this->setConstants([self::LastPageIdentifier => $nextPageName]);

        // Set next/submit page buttons:
        if ($nextPageName == $this->finalPageName)
        {
            CRM_Core_Form::addDefaultButtons(E::ts("Set"));
            // FIXME: Back button does not work here because of our multi page system.
        }
        else if (!empty($nextPageName))
        {
            CRM_Core_Form::addDefaultButtons(E::ts("Filter"), 'submit');
        }

        // Finally, set the defaults:
        $this->setDefaults($defaults);
    }

    public function validate ()
    {
        parent::validate();

        if (!empty($this->errors))
        {
            $this->_errors = array_merge($this->_errors, $this->errors);
        }
    }

    public function postProcess ()
    {
        parent::postProcess();

        $values = $this->exportValues(null, true);

        $lastPageName = $this->currentPageName();
        $this->pages[$lastPageName]->process($values);
    }
}
