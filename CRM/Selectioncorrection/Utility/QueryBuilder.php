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
 * Utility class for common methods making the combination of the query builder and the CiviCRM DAO easier.
 */
class CRM_Selectioncorrection_Utility_QueryBuilder
{
    /**
     * Will convert builder values in the format ":v<n>" to Civi values in the format "%<n>" inside an sql statement string.
     * @param string $sqlStatement
     * @return array
     */
    public static function convertBuilderStatementToCiviStatement ($sqlStatement)
    {
        $pattern = '/:v(\d+)/i';
        $replacement = '%${1}';

        $result = preg_replace($pattern, $replacement, $sqlStatement);

        return $result;
    }

    /**
     * Will convert builder values in the format ":v<n>" to Civi values in the format "%<n>".
     * @param array $builderValues
     * @return array
     */
    public static function convertBuilderValuesToCiviValues ($builderValues)
    {
        $result = [];

        foreach($builderValues as $key => $value)
        {
            $convertedKey = substr($key, 2); // Remove the leading ":v".

            $typeOfValue = gettype($value); // Yep, we have to get the type because Civi need it...
            $typeOfValue = ucfirst($typeOfValue); // Ohhh, yeah, and Civi only recognises types with "their" capitalisation...

            $convertedValue = [
                $value,
                $typeOfValue
            ];

            $result[$convertedKey] = $convertedValue;
        }

        return $result;
    }
}