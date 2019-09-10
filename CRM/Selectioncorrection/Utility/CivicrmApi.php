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
 * Utility class for encapsulation of the CiviCRM API, reducing repetitions and handling preparations.
 */
class CRM_Selectioncorrection_Utility_CivicrmApi
{
    /**
     * Returns the result of an API get call.
     * @param string $entity The name of the entity to get.
     * @param array $additionalParams A list of additional parameters for the API call.
     * @param array $additionOptions A list of additional options for the API call.
     */
    public static function get ($entity, $additionalParams=[], $additionOptions=[])
    {
        $options = [
            'limit' => 1,
        ];
        if (!empty($additionOptions))
        {
            $options = array_merge($options, $additionOptions);
        }

        $params = [
            'sequential' => 1,
            'options' => $options,
        ];
        if (!empty($additionalParams))
        {
            $params = array_merge($params, $additionalParams);
        }

        $result = civicrm_api3(
            $entity,
            'get',
            [
                $params,
            ]
        );

        return $result;
    }

    /**
     * Returns the values of an API get call.
     * @param string $entity The name of the entity to get.
     * @param array $additionalParams A list of additional parameters for the API call.
     * @param array $additionOptions A list of additional options for the API call.
     */
    public static function getValues ($entity, $additionalParams=[], $additionOptions=[])
    {
        return self::get($entity, $additionalParams, $additionOptions)['values'];
    }

    /**
     * Returns the values of an API get call.
     * Checks the values for the parameters. If one of them is empty, the API call
     * is skipped and an empty array will be returned.
     * @param string $entity The name of the entity to get.
     * @param array $additionalParams A list of additional parameters for the API call.
     * @param array $paramValues A list of all values used in the additional parameters.
     * @param array $additionOptions A list of additional options for the API call.
     */
    public static function getValuesChecked ($entity, $additionalParams, $paramValues, $additionOptions=[])
    {
        foreach ($paramValues as $value)
        {
            if (empty($value))
            {
                return [];
            }
        }

        return self::getValues($entity, $additionalParams, $additionOptions);
    }
}