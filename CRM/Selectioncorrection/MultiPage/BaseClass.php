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
     * Maps a page name to the name of the page comming before it.
     * @var string[] $previousPages "pageName" => "previous pageName"
     */
    private $previousPages = [];
    /**
     * Maps a page name to the name of the page comming after it.
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
     * Do everything needed to initialise the class, especially adding the pages.
     * This function must be overriden by the child class.
     */
    protected abstract function initialise ();

    /**
     * Do something after the final page has been processed.
     * This function can be overriden by the child class.
     */
    protected function doFinalProcess ()
    {
    }

    /**
     * Adds a page to the page list.
     */
    protected function addPage (CRM_Selectioncorrection_MultiPage_PageBase $page)
    {
        $pageName = $page->getName();

        if (empty($this->firstPageName))
        {
            $this->firstPageName = $pageName;
        }

        if (!empty($this->finalPageName))
        {
            $this->previousPages[$pageName] = $this->finalPageName;
            $this->nextPages[$this->finalPageName] = $pageName;
        }

        $this->pages[$pageName] = $page;

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
     * Returns the export values without internal values and optionally with
     * a filter that returns only the values which keys are in this list.
     * @param string[]|null $filter A list of elements that shall be returned.
     * @param bool $removeCiviValues If true, Civi values like qfKey and entryURL will be removed from the values list.
     * @return array The filtered values.
     */
    protected function getFilteredExportValues ($filter=null, $removeCiviValues=true)
    {
        $values = $this->exportValues($filter, true);

        if ($removeCiviValues)
        {
            unset($values['qfKey']);
            unset($values['entryURL']);
        }

        return $values;
    }

    /**
     * Returns the values for a given page.
     * @param string $pageName The name of the page.
     * @return array The page values. Empty if there are none found.
     */
    public function getPageValues ($pageName)
    {
        return CRM_Selectioncorrection_Storage::getWithDefault($pageName . '_values', []);
    }

    /**
     * Returns the name of last rendered and shown page name.
     * @return string
     */
    public function getLastPageName ()
    {
        $values = $this->getFilteredExportValues();

        return $values[self::LastPageIdentifier];
    }

    public function preProcess ()
    {
        CRM_Selectioncorrection_Storage::initialise($this);

        parent::preProcess();

        $this->initialise();
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
        $this->add(
            'hidden',
            self::LastPageIdentifier
        );

        $lastPageName = $this->getLastPageName();
        $nextPageName = '';

        if (empty($lastPageName))
        {
            // If the last page name is empty, we want to show the first page.
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

        if ($nextPageName != $this->firstPageName)
        {
            $lastPage = $this->pages[$lastPageName];

            // We need the elements of the last page created in PHP (but not rendered in smarty)
            // for the next page to be able to read it's element values.
            // Rebuild does the minimum amount of effort to achieve this.
            $lastPage->rebuild($defaults);

            // We can only check for errors after the page has been rebuild...
            $noErrorsFound = $this->validate();
            if (!$noErrorsFound)
            {
                // If there were any errors in the core function found, like a required field with
                // no value, we roll back and set the pages to the previous state.
                // The previous page of the last one has to be rebuild and the last page, rebuild
                // seconds ago, will be fully build afterwards, overriding the "dummy" elements in
                // the rebuild function.

                $nextPageName = $lastPageName;

                if ($lastPageName != $this->firstPageName)
                {
                    $lastPageName = $this->previousPages[$lastPageName];

                    $lastPage = $this->pages[$lastPageName];

                    $lastPage->rebuild($defaults);
                }
            }
            else if (($lastPageName == $this->finalPageName) || ($lastPageName == $this->previousPages[$nextPageName]))
            {
                // Only process the last page if there has been no errors and it really is the previous page for the next one or the final one.

                // For later access, we save the values for this page in the storage:
                CRM_Selectioncorrection_Storage::set($lastPageName . '_values', $this->getFilteredExportValues());

                // We process the last page at this place and not at postProcess because here we can
                // be sure it is called before the build process of the next page. Otherwise the next
                // page could not access anything done in the process routine of the last page:
                $lastPage->process();
            }
        }

        // Building of the next page:
        if (!empty($nextPageName))
        {
            $this->pages[$nextPageName]->build($defaults);

            $this->assign(self::CurrentPageIdentifier, $nextPageName);
        }
        else
        {
            // If there is no next page, this was the last one and we need to call the final process:
            $this->doFinalProcess();
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
        $noParentError = parent::validate();

        if (!empty($this->errors))
        {
            $this->_errors += $this->errors;
        }

        return $noParentError && (count($this->_errors) == 0);
    }

    public function postProcess ()
    {
        parent::postProcess();

        // We do not need to do anything here because we process the pages ourself in the buildQuickForm routine.
    }
}
