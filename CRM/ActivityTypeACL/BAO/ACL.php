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
      $activities = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'name', TRUE);
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
    $activities = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'name', TRUE);
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
      if (empty($where)) {
        $op = " WHERE ";
      }
      else {
        $op = $where . " AND ";
      }
      $where = " $op ( activity_civireport.activity_type_id" . $clause . " )";
      $query->setVar('_where', $where);
    }
    if ($context == "customsearch") {
      $query[] = "activity.activity_type_id" . $clause;
    }
    if ($context == "constituent") {
      $query .= " INNER JOIN civicrm_activity a ON a.id = activity_civireport.id AND
        a.activity_type_id " . $clause;
    }
    if ($context == "case") {
      $where = $query->getVar('_where');
      if (empty($where)) {
        $op = " WHERE ";
      }
      else {
        $op = $where . " AND ";
      }
      $where = " $op ( civireport_activity_last_civireport.activity_type_id" . $clause . " ) AND ( activity_last_completed_civireport.activity_type_id" . $clause . " )";
      $query->setVar('_where', $where);
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

  /**
   * Helper function to generate a formatted contact link/name for display in the Case activities tab
   *
   * @param $contactId
   * @param $contactName
   *
   * @return string
   */
  private static function formatContactLink($contactId, $contactName) {
    if (empty($contactId)) {
      return NULL;
    }

    $hasViewContact = CRM_Contact_BAO_Contact_Permission::allow($contactId);

    if ($hasViewContact) {
      $contactViewUrl = CRM_Utils_System::url("civicrm/contact/view", "reset=1&cid={$contactId}");
      return "<a href=\"{$contactViewUrl}\">" . $contactName . "</a>";
    }
    else {
      return $contactName;
    }
  }

  /**
   * Get Case Activities.
   *
   * @param int $caseID
   *   Case id.
   * @param array $params
   *   Posted params.
   * @param int $contactID
   *   Contact id.
   *
   * @param null $context
   * @param int $userID
   * @param null $type (deprecated)
   *
   * @return array
   *   Array of case activities
   *
   */
  public static function getCaseActivities($caseID, &$params, $contactID, $context = NULL, $userID = NULL, $type = NULL) {
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $activities = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'name', TRUE);
    $permissionedActivities = self::getPermissionedActivities();
    $nonPerm = FALSE;
    if (empty($permissionedActivities)) {
      $nonPerm = TRUE;
    }
    $disallowedActivities = array_diff_key($activities, $permissionedActivities);
    $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
    $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

    // CRM-5081 - formatting the dates to omit seconds.
    // Note the 00 in the date format string is needed otherwise later on it thinks scheduled ones are overdue.
    $select = "
           SELECT SQL_CALC_FOUND_ROWS COUNT(ca.id) AS ismultiple,
                  ca.id AS id,
                  ca.activity_type_id AS type,
                  ca.activity_type_id AS activity_type_id,
                  tcc.sort_name AS target_contact_name,
                  tcc.id AS target_contact_id,
                  scc.sort_name AS source_contact_name,
                  scc.id AS source_contact_id,
                  acc.sort_name AS assignee_contact_name,
                  acc.id AS assignee_contact_id,
                  DATE_FORMAT(
                    IF(ca.activity_date_time < NOW() AND ca.status_id=ov.value,
                      ca.activity_date_time,
                      DATE_ADD(NOW(), INTERVAL 1 YEAR)
                    ), '%Y%m%d%H%i00') AS overdue_date,
                  DATE_FORMAT(ca.activity_date_time, '%Y%m%d%H%i00') AS display_date,
                  ca.status_id AS status,
                  ca.subject AS subject,
                  ca.is_deleted AS deleted,
                  ca.priority_id AS priority,
                  ca.weight AS weight,
                  GROUP_CONCAT(ef.file_id) AS attachment_ids ";

    $from = "
             FROM civicrm_case_activity cca
       INNER JOIN civicrm_activity ca
               ON ca.id = cca.activity_id
       INNER JOIN civicrm_activity_contact cas
               ON cas.activity_id = ca.id
              AND cas.record_type_id = {$sourceID}
       INNER JOIN civicrm_contact scc
               ON scc.id = cas.contact_id
        LEFT JOIN civicrm_activity_contact caa
               ON caa.activity_id = ca.id
              AND caa.record_type_id = {$assigneeID}
        LEFT JOIN civicrm_contact acc
               ON acc.id = caa.contact_id
        LEFT JOIN civicrm_activity_contact cat
               ON cat.activity_id = ca.id
              AND cat.record_type_id = {$targetID}
        LEFT JOIN civicrm_contact tcc
               ON tcc.id = cat.contact_id
       INNER JOIN civicrm_option_group cog
               ON cog.name = 'activity_type'
       INNER JOIN civicrm_option_value cov
               ON cov.option_group_id = cog.id
              AND cov.value = ca.activity_type_id
              AND cov.is_active = 1
        LEFT JOIN civicrm_entity_file ef
               ON ef.entity_table = 'civicrm_activity'
              AND ef.entity_id = ca.id
  LEFT OUTER JOIN civicrm_option_group og
               ON og.name = 'activity_status'
  LEFT OUTER JOIN civicrm_option_value ov
               ON ov.option_group_id=og.id
              AND ov.name = 'Scheduled'";

    $where = '
            WHERE cca.case_id= %1
              AND ca.is_current_revision = 1';

    if (!empty($params['source_contact_id'])) {
      $where .= "
              AND cas.contact_id = " . CRM_Utils_Type::escape($params['source_contact_id'], 'Integer');
    }

    if (!empty($params['status_id'])) {
      $where .= "
              AND ca.status_id = " . CRM_Utils_Type::escape($params['status_id'], 'Integer');
    }

    if (!empty($params['activity_deleted'])) {
      $where .= "
              AND ca.is_deleted = 1";
    }
    else {
      $where .= "
              AND ca.is_deleted = 0";
    }

    if (!empty($params['activity_type_id'])) {
      $where .= "
              AND ca.activity_type_id = " . CRM_Utils_Type::escape($params['activity_type_id'], 'Integer');
    }

    if (!empty($params['activity_date_low'])) {
      $fromActivityDate = CRM_Utils_Type::escape(CRM_Utils_Date::processDate($params['activity_date_low']), 'Date');
    }
    if (!empty($fromActivityDate)) {
      $where .= "
              AND ca.activity_date_time >= '{$fromActivityDate}'";
    }

    if (!empty($params['activity_date_high'])) {
      $toActivityDate = CRM_Utils_Type::escape(CRM_Utils_Date::processDate($params['activity_date_high']), 'Date');
      $toActivityDate = $toActivityDate ? $toActivityDate + 235959 : NULL;
    }
    if (!empty($toActivityDate)) {
      $where .= "
              AND ca.activity_date_time <= '{$toActivityDate}'";
    }

    if (!empty($disallowedActivities)) {
      $where .= " AND ca.activity_type_id NOT IN ( " . implode(",", array_keys($disallowedActivities)) . " ) ";
    }
    elseif($nonPerm) {
       $where .= " AND ca.activity_type_id IN (0) ";
    }

    $groupBy = "
         GROUP BY ca.id, tcc.id, scc.id, acc.id, ov.value";

    $sortBy = CRM_Utils_Array::value('sortBy', $params);
    if (!$sortBy) {
      // CRM-5081 - added id to act like creation date
      $orderBy = "
         ORDER BY overdue_date ASC, display_date DESC, weight DESC";
    }
    else {
      $sortBy = CRM_Utils_Type::escape($sortBy, 'String');
      $orderBy = " ORDER BY $sortBy ";
    }

    $page = CRM_Utils_Array::value('page', $params);
    $rp = CRM_Utils_Array::value('rp', $params);

    if (!$page) {
      $page = 1;
    }
    if (!$rp) {
      $rp = 10;
    }
    $start = (($page - 1) * $rp);
    $limit = " LIMIT $start, $rp";

    $query = $select . $from . $where . $groupBy . $orderBy . $limit;
    $queryParams = array(1 => array($caseID, 'Integer'));

    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    $caseCount = CRM_Core_DAO::singleValueQuery('SELECT FOUND_ROWS()');

    $activityTypes = CRM_Case_PseudoConstant::caseActivityType(FALSE, TRUE);
    $activityStatuses = CRM_Core_PseudoConstant::activityStatus();

    $url = CRM_Utils_System::url("civicrm/case/activity",
      "reset=1&cid={$contactID}&caseid={$caseID}", FALSE, NULL, FALSE
    );

    $contextUrl = '';
    if ($context == 'fulltext') {
      $contextUrl = "&context={$context}";
    }
    $editUrl = "{$url}&action=update{$contextUrl}";
    $deleteUrl = "{$url}&action=delete{$contextUrl}";
    $restoreUrl = "{$url}&action=renew{$contextUrl}";
    $viewTitle = ts('View activity');

    $emailActivityTypeIDs = array(
      'Email' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Email'),
      'Inbound Email' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Inbound Email'),
    );

    $caseDeleted = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_Case', $caseID, 'is_deleted');

    $compStatusValues = array_keys(
      CRM_Activity_BAO_Activity::getStatusesByType(CRM_Activity_BAO_Activity::COMPLETED) +
      CRM_Activity_BAO_Activity::getStatusesByType(CRM_Activity_BAO_Activity::CANCELLED)
    );

    if (!$userID) {
      $session = CRM_Core_Session::singleton();
      $userID = $session->get('userID');
    }

    $caseActivities = array();

    $activities = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'name', TRUE);
    while ($dao->fetch()) {
      $caseActivity = array();
      $caseActivityId = $dao->id;

      $allowView = CRM_Case_BAO_Case::checkPermission($caseActivityId, 'view', $dao->activity_type_id, $userID);
      $allowEdit = CRM_Case_BAO_Case::checkPermission($caseActivityId, 'edit', $dao->activity_type_id, $userID);
      $allowDelete = CRM_Case_BAO_Case::checkPermission($caseActivityId, 'delete', $dao->activity_type_id, $userID);

      //do not have sufficient permission
      //to access given case activity record.
      if (!$allowView && !$allowEdit && !$allowDelete) {
        continue;
      }
      if (!CRM_Core_Permission::check('view activities of type ' . $activities[$dao->activity_type_id])) {
        $allowView = FALSE;
      }
      if (!CRM_Core_Permission::check('edit activities of type ' . $activities[$dao->activity_type_id])) {
        $allowEdit = FALSE;
      }
      if (!CRM_Core_Permission::check('delete activities of type ' . $activities[$dao->activity_type_id])) {
        $allowDelete = FALSE;
      }

      $caseActivities[$caseActivityId]['DT_RowId'] = $caseActivityId;
      //Add classes to the row, via DataTables syntax
      $caseActivities[$caseActivityId]['DT_RowClass'] = "crm-entity status-id-$dao->status";

      if (CRM_Utils_Array::crmInArray($dao->status, $compStatusValues)) {
        $caseActivities[$caseActivityId]['DT_RowClass'] .= " status-completed";
      }
      else {
        if (CRM_Utils_Date::overdue($dao->display_date)) {
          $caseActivities[$caseActivityId]['DT_RowClass'] .= " status-overdue";
        }
        else {
          $caseActivities[$caseActivityId]['DT_RowClass'] .= " status-scheduled";
        }
      }

      if (!empty($dao->priority)) {
        if ($dao->priority == CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'priority_id', 'Urgent')) {
          $caseActivities[$caseActivityId]['DT_RowClass'] .= " priority-urgent ";
        }
        elseif ($dao->priority == CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'priority_id', 'Low')) {
          $caseActivities[$caseActivityId]['DT_RowClass'] .= " priority-low ";
        }
      }

      //Add data to the row for inline editing, via DataTable syntax
      $caseActivities[$caseActivityId]['DT_RowAttr'] = array();
      $caseActivities[$caseActivityId]['DT_RowAttr']['data-entity'] = 'activity';
      $caseActivities[$caseActivityId]['DT_RowAttr']['data-id'] = $caseActivityId;

      //Activity Date and Time
      $caseActivities[$caseActivityId]['activity_date_time'] = CRM_Utils_Date::customFormat($dao->display_date);

      //Activity Subject
      $caseActivities[$caseActivityId]['subject'] = $dao->subject;

      //Activity Type
      $caseActivities[$caseActivityId]['type'] = (!empty($activityTypes[$dao->type]['icon']) ? '<span class="crm-i ' . $activityTypes[$dao->type]['icon'] . '"></span> ' : '')
        . $activityTypes[$dao->type]['label'];

      // Activity Target (With Contact) (There can be more than one)
      $targetContact = self::formatContactLink($dao->target_contact_id, $dao->target_contact_name);
      if (empty($caseActivities[$caseActivityId]['target_contact_name'])) {
        $caseActivities[$caseActivityId]['target_contact_name'] = $targetContact;
      }
      else {
        if (strpos($caseActivities[$caseActivityId]['target_contact_name'], $targetContact) === FALSE) {
          $caseActivities[$caseActivityId]['target_contact_name'] .= '; ' . $targetContact;
        }
      }

      // Activity Source Contact (Reporter) (There can only be one)
      $sourceContact = self::formatContactLink($dao->source_contact_id, $dao->source_contact_name);
      $caseActivities[$caseActivityId]['source_contact_name'] = $sourceContact;

      // Activity Assignee (There can be more than one)
      $assigneeContact = self::formatContactLink($dao->assignee_contact_id, $dao->assignee_contact_name);
      if (empty($caseActivities[$caseActivityId]['assignee_contact_name'])) {
        $caseActivities[$caseActivityId]['assignee_contact_name'] = $assigneeContact;
      }
      else {
        if (strpos($caseActivities[$caseActivityId]['assignee_contact_name'], $assigneeContact) === FALSE) {
          $caseActivities[$caseActivityId]['assignee_contact_name'] .= '; ' . $assigneeContact;
        }
      }

      //Activity Status
      $caseActivities[$caseActivityId]['status_id'] = $activityStatuses[$dao->status];

      // FIXME: Why are we not using CRM_Core_Action for these links? This is too much manual work and likely to get out-of-sync with core markup.
      $url = "";
      $css = 'class="action-item crm-hover-button"';
      if ($allowView) {
        $viewUrl = CRM_Utils_System::url('civicrm/case/activity/view', array('cid' => $contactID, 'aid' => $caseActivityId));
        $url = '<a ' . str_replace('action-item', 'action-item medium-pop-up', $css) . 'href="' . $viewUrl . '" title="' . $viewTitle . '">' . ts('View') . '</a>';
      }
      $additionalUrl = "&id={$caseActivityId}";
      if (!$dao->deleted) {
        //hide edit link of activity type email.CRM-4530.
        if (!in_array($dao->type, $emailActivityTypeIDs)) {
          //hide Edit link if activity type is NOT editable (special case activities).CRM-5871
          if ($allowEdit) {
            $url .= '<a ' . $css . ' href="' . $editUrl . $additionalUrl . '">' . ts('Edit') . '</a> ';
          }
        }
        if ($allowDelete) {
          $url .= ' <a ' . str_replace('action-item', 'action-item small-popup', $css) . ' href="' . $deleteUrl . $additionalUrl . '">' . ts('Delete') . '</a>';
        }
      }
      elseif (!$caseDeleted) {
        $url = ' <a ' . $css . ' href="' . $restoreUrl . $additionalUrl . '">' . ts('Restore') . '</a>';
        $caseActivities[$caseActivityId]['status_id'] = $caseActivities[$caseActivityId]['status_id'] . '<br /> (deleted)';
      }

      //check for operations.
      if (CRM_Case_BAO_Case::checkPermission($caseActivityId, 'Move To Case', $dao->activity_type_id)) {
        $url .= ' <a ' . $css . ' href="#" onClick="Javascript:fileOnCase( \'move\',' . $caseActivityId . ', ' . $caseID . ', this ); return false;">' . ts('Move To Case') . '</a> ';
      }
      if (CRM_Case_BAO_Case::checkPermission($caseActivityId, 'Copy To Case', $dao->activity_type_id)) {
        $url .= ' <a ' . $css . ' href="#" onClick="Javascript:fileOnCase( \'copy\',' . $caseActivityId . ',' . $caseID . ', this ); return false;">' . ts('Copy To Case') . '</a> ';
      }
      // if there are file attachments we will return how many and, if only one, add a link to it
      if (!empty($dao->attachment_ids)) {
        $attachmentIDs = array_unique(explode(',', $dao->attachment_ids));
        $caseActivities[$caseActivityId]['no_attachments'] = count($attachmentIDs);
        $url .= implode(' ', CRM_Core_BAO_File::paperIconAttachment('civicrm_activity', $caseActivityId));
      }

      $caseActivities[$caseActivityId]['links'] = $url;
    }

    $caseActivitiesDT = array();
    $caseActivitiesDT['data'] = array_values($caseActivities);
    $caseActivitiesDT['recordsTotal'] = $caseCount;
    $caseActivitiesDT['recordsFiltered'] = $caseCount;

    return $caseActivitiesDT;
  }

  public static function getCaseActivity() {
    // Should those params be passed through the validateParams method?
    $caseID = CRM_Utils_Type::validate($_GET['caseID'], 'Integer');
    $contactID = CRM_Utils_Type::validate($_GET['cid'], 'Integer');
    $userID = CRM_Utils_Type::validate($_GET['userID'], 'Integer');
    $context = CRM_Utils_Type::validate(CRM_Utils_Array::value('context', $_GET), 'String');

    $optionalParameters = array(
      'source_contact_id' => 'Integer',
      'status_id' => 'Integer',
      'activity_deleted' => 'Boolean',
      'activity_type_id' => 'Integer',
      // "Date" validation fails because it expects only numbers with no hyphens
      'activity_date_low' => 'Alphanumeric',
      'activity_date_high' => 'Alphanumeric',
    );

    $params = CRM_Core_Page_AJAX::defaultSortAndPagerParams();
    $params += CRM_Core_Page_AJAX::validateParams(array(), $optionalParameters);

    // get the activities related to given case
    $activities = self::getCaseActivities($caseID, $params, $contactID, $context, $userID);

    CRM_Utils_JSON::output($activities);
  }

}
