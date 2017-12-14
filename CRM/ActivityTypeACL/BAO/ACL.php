<?php
/*
 +--------------------------------------------------------------------+
 | Activity Type ACL Extension                                  |
 +--------------------------------------------------------------------+
 | Copyright (C) 2016-2017 JMA Consulting                             |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright JMA Consulting (c) 2004-2017
 * $Id$
 *
 */
class CRM_ActivityTypeACL_BAO_ACL extends CRM_Core_DAO {
  /**
   * Static field for all the activity types that are permissioned.
   *
   * @var array
   */
  static $_permissionedActivities = array();

  /**
   * Function to retrieve all permissioned activity types based on current user action.
   *
   * @param array $activities
   *  Array of activity types.
   * @param int|string $action
   *  Current user action.
   * @param bool $resetCache
   *  Re obtain values from database if TRUE.
   * @param bool $label
   *  Display activity type label if TRUE.
   *
   * @return array $activities
   *  Array of permissioned activity types based on current user action.
   **/
  public static function getPermissionedActivities(&$activities = NULL, $action = CRM_Core_Action::VIEW, $resetCache = FALSE, $label = FALSE) {
    if (empty($activities)) {
      $activities = CRM_Core_PseudoConstant::activityType(TRUE, FALSE, FALSE, 'name');
    }
    $actions = array(
      CRM_Core_Action::VIEW => 'view',
      CRM_Core_Action::UPDATE => 'edit',
      CRM_Core_Action::ADD => 'add',
      CRM_Core_Action::DELETE => 'delete',
    );
    // check cached value
    if (CRM_Utils_Array::value($action, self::$_permissionedActivities) && !$resetCache) {
      $activities = self::$_permissionedActivities[$action];
      return self::$_permissionedActivities[$action];
    }
    foreach ($activities as $actTypeId => $type) {
      if (!CRM_Core_Permission::check($actions[$action] . ' activities of type ' . $type)) {
        unset($activities[$actTypeId]);
      }
      elseif ($label) {
        $activities[$actTypeId] = CRM_Core_PseudoConstant::getLabel('CRM_Activity_BAO_Activity', 'activity_type_id', $actTypeId);
      }
    }
    self::$_permissionedActivities[$action] = $activities;
    return $activities;
  }

  /**
   * Function to supply permissioned clause based on various contexts.
   *
   * @param object|array $query
   *  Contains the query or clauses for query.
   * @param string $context
   *  Context on which the clause must be modified.
   **/
  public static function getAdditionalActivityClause(&$query = NULL, $context) {
    $activities = CRM_Core_PseudoConstant::activityType(TRUE, FALSE, FALSE, 'name');
    $permissionedActivities = self::getPermissionedActivities();
    $disallowedActivities = array_diff_key($activities, $permissionedActivities);
    if (empty($disallowedActivities)) {
      return;
    }
    $clause = " NOT IN ( " . implode(",", array_keys($disallowedActivities)) . " ) ";
    if ($context == "activitytab") {
      return "civicrm_activity.activity_type_id" . $clause;
    }
    if ($context == "search") {
      $query[] = "civicrm_activity.activity_type_id" . $clause;
    }
    if ($context == "report") {
      return " AND ( activity_type_id" . $clause . " )";
    }
    if ($context == "summary") {
      $where = $query->getVar('_where');
      $where = " WHERE ( activity_civireport.activity_type_id" . $clause . " )";
      $query->setVar('_where', $where);
    }
    if ($context == "customsearch") {
      $query[] = "activity.activity_type_id" . $clause;
    }
    if ($context == "constituent") {
      $query .= " INNER JOIN civicrm_activity a ON a.id = activity_civireport.id AND
        a.activity_type_id " . $clause;
    }
  }

  /**
   * Function to validate activity type for activity import.
   *
   * @param string $field
   *  The field that is being imported.
   * @param string $fieldValue
   *  The value of the field being imported.
   * @param string $errorMessage
   *  Error message on failed import.
   **/
  public static function checkValidActivityType($field, $fieldValue, &$errorMessage) {
    $label = NULL;
    if ($field == 'activity_type_id') {
      $activityType = CRM_Core_OptionGroup::values('activity_type', FALSE, FALSE, FALSE, " AND v.value = {$fieldValue}", "name");
      $label = CRM_Core_PseudoConstant::getLabel('CRM_Activity_BAO_Activity', 'activity_type_id', $activityType);
    }
    elseif ($field == 'activity_label') {
      $activityType = CRM_Core_OptionGroup::values('activity_type', FALSE, FALSE, FALSE, " AND v.label = '{$fieldValue}'", "name");
      $label = CRM_Core_PseudoConstant::getLabel('CRM_Activity_BAO_Activity', 'activity_type_id', key($activityType));
    }
    if (!empty($label) && !CRM_Core_Permission::check('add activities of type ' . reset($activityType))) {
      CRM_Contact_Import_Parser_Contact::addToErrorMsg(ts('You do not have permission to import activities of type ' . $label), $errorMessage);
    }
  }

}
