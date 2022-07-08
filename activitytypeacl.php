<?php

require_once 'activitytypeacl.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function activitytypeacl_civicrm_config(&$config) {
  _activitytypeacl_civix_civicrm_config($config);
  if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'civicrm/case/report') !== false) {
    CRM_ActivityTypeACL_BAO_ACL::getPermissionedActivities($activityOptions, CRM_Core_Action::VIEW, FALSE, TRUE);
    $flip = array_flip($activityOptions);
    foreach ([
      [TRUE, FALSE],
      [TRUE, TRUE],
      [FALSE, TRUE],
      ] as $value) {
      list($all, $indexName) = $value;
      CRM_Case_PseudoConstant::caseActivityType($indexName, $all);
      $cache = (int) $indexName . '_' . (int) $all;
      if ($cache == "0_1") {
        CRM_Case_PseudoConstant::$activityTypeList[$cache] = array_intersect_key(CRM_Case_PseudoConstant::$activityTypeList[$cache], $activityOptions);
      }
      else {
        CRM_Case_PseudoConstant::$activityTypeList[$cache] = array_intersect_key(CRM_Case_PseudoConstant::$activityTypeList[$cache], $flip);
      }
    }
  }
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function activitytypeacl_civicrm_xmlMenu(&$files) {
  _activitytypeacl_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function activitytypeacl_civicrm_install() {
  _activitytypeacl_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function activitytypeacl_civicrm_uninstall() {
  _activitytypeacl_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function activitytypeacl_civicrm_enable() {
  _activitytypeacl_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function activitytypeacl_civicrm_disable() {
  _activitytypeacl_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function activitytypeacl_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _activitytypeacl_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function activitytypeacl_civicrm_managed(&$entities) {
  _activitytypeacl_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function activitytypeacl_civicrm_caseTypes(&$caseTypes) {
  _activitytypeacl_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function activitytypeacl_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _activitytypeacl_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implementation of hook_civicrm_permission
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_permission
 */
function activitytypeacl_civicrm_permission(&$permissions) {
  $activities = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'name', TRUE);
  $prefix = ts('CiviCRM') . ': ';
  $actions = array('add', 'view', 'edit', 'delete');
  foreach ($activities as $id => $type) {
    $label = CRM_Core_PseudoConstant::getLabel('CRM_Activity_BAO_Activity', 'activity_type_id', $id);
    foreach ($actions as $action) {
      $permissions[$action . ' activities of type ' . $type] = $prefix . ts($action . ' activities of type ') . $label;
    }
  }
}

/**
 * Implementation of hook_civicrm_queryObjects
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_queryObjects
 */
function activitytypeacl_civicrm_queryObjects(&$queryObjects, $type) {
  if ($type == "Contact") {
    $queryObjects[] = new CRM_ActivityTypeACL_BAO_Query();
  }
}

function activitytypeacl_civicrm_apiWrappers(&$wrappers, $apiRequest) {
  if (!empty($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'civicrm/a') !== false) {
    if ($apiRequest['entity'] == 'OptionValue' && $apiRequest['action'] == 'get') {
      $wrappers[] = new CRM_ActivityTypeACL_APIWrappers_ACL();
    }
  }
  if ($apiRequest['entity'] == 'CaseType' && $apiRequest['action'] == 'get') {
    $wrappers[] = new CRM_ActivityTypeACL_APIWrappers_ACL();
  }
  if ($apiRequest['entity'] == 'Activity' && $apiRequest['action'] == 'get') {
    $wrappers[] = new CRM_ActivityTypeACL_APIWrappers_ACL();
  }
}

/**
 * Implementation of hook_civicrm_buildForm
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_buildForm
 */
function activitytypeacl_civicrm_buildForm($formName, &$form) {
  // Restrict activity types available in the "New Activity" creation list on contact summary page.
  if ($formName == "CRM_Activity_Form_ActivityLinks") {
    CRM_ActivityTypeACL_BAO_ACL::getPermissionedActivities($activityOptions, CRM_Core_Action::ADD, FALSE, TRUE);
    $activityTypes = CRM_Core_Smarty::singleton()->get_template_vars('activityTypes');
    foreach ($activityTypes as $key => $activity) {
      if (!array_key_exists($activity['value'], $activityOptions)) {
        unset($activityTypes[$key]);
      }
    }
    $form->assign('activityTypes', $activityTypes);
  }
  // Restrict activity types available in the filters on activity tab on contact summary page.
  if ($formName == "CRM_Activity_Form_ActivityFilter") {
    CRM_ActivityTypeACL_BAO_ACL::getPermissionedActivities($activityOptions, CRM_Core_Action::VIEW, FALSE, TRUE);
    asort($activityOptions);

    $form->add('select', 'activity_type_filter_id', ts('Include'), array('' => ts('- all activity type(s) -')) + $activityOptions);
    $form->add('select', 'activity_type_exclude_filter_id', ts('Exclude'), array('' => ts('- select activity type -')) + $activityOptions);
  }

  // Restrict activity types available in the filters on activity searches.
  if ($formName == "CRM_Activity_Form_Search" || $formName == "CRM_Contact_Form_Search_Advanced") {
    $form->addSelect('activity_type_id', array(
      'entity' => 'activity',
      'label' => ts('Activity Type(s)'),
      'multiple' => 'multiple',
      'option_url' => NULL,
      'placeholder' => ts('- any -'),
      'options' => CRM_ActivityTypeACL_BAO_ACL::getPermissionedActivities($activityOptions, CRM_Core_Action::VIEW, FALSE, TRUE))
    );
  }

  // Restrict view for activities with unpermissioned activity types.
  if ($formName == "CRM_Activity_Form_ActivityView") {
    $activityTypeId = CRM_Utils_Request::retrieve('atype', 'Integer');
    $activityType = CRM_Core_OptionGroup::values('activity_type', FALSE, FALSE, FALSE, " AND v.value = {$activityTypeId}", "name");
    if (!CRM_Core_Permission::check('view activities of type ' . $activityType[$activityTypeId])) {
      CRM_Utils_System::permissionDenied();
      CRM_Utils_System::civiExit();
    }
  }
  if ($formName == "CRM_Report_Form_Contact_Detail") {
    CRM_Core_Session::singleton()->set('isConstituent', TRUE);
  }
  elseif ($formName == "CRM_Report_Form_Activity") {
    CRM_Core_Session::singleton()->set('isActivityDetail', TRUE);
  }
  else {
    CRM_Core_Session::singleton()->set('isConstituent', FALSE);
    CRM_Core_Session::singleton()->set('isActivityDetail', FALSE);
  }

  // Restrict activity types for forms.
  if ($formName == "CRM_Activity_Form_Activity") {

    // Restrict list of activity types available on activity creation form.
    if ($form->_action & CRM_Core_Action::ADD) {
      $unwanted = CRM_Core_OptionGroup::values('activity_type', FALSE, FALSE, FALSE, "AND v.name = 'Print PDF Letter'");
      $fActivityTypes = array_diff_key(CRM_Core_PseudoConstant::ActivityType(FALSE), $unwanted);
      CRM_ActivityTypeACL_BAO_ACL::getPermissionedActivities($allowedActivities, CRM_Core_Action::ADD, FALSE, TRUE);
      $fActivityTypes = array_intersect_key($allowedActivities, $fActivityTypes);

      $form->add('select', 'activity_type_id', ts('Activity Type'),
        array('' => '- ' . ts('select') . ' -') + $fActivityTypes,
        FALSE, array(
          'onchange' => "CRM.buildCustomData( 'Activity', this.value );",
          'class' => 'crm-select2 required',
        )
      );

      // Restrict follow up activities too.
      $form->add('select', 'followup_activity_type_id', ts('Followup Activity'),
        array('' => '- ' . ts('select') . ' -') + $fActivityTypes,
        FALSE, array(
          'class' => 'crm-select2',
        )
      );
    }

    if (!empty($form->_activityTypeId)) {
      $activityType = CRM_Core_OptionGroup::values('activity_type', FALSE, FALSE, FALSE, " AND v.value = {$form->_activityTypeId}", "name");
    }

    // Restrict view for activities with unpermissioned activity types.
    if ($form->_action & CRM_Core_Action::VIEW) {
      if (!CRM_Core_Permission::check('view activities of type ' . $activityType[$form->_activityTypeId])) {
        CRM_Utils_System::permissionDenied();
        CRM_Utils_System::civiExit();
      }

      // Permit edit button display for activities.
      if (CRM_Core_Permission::check('edit activities of type ' . $activityType[$form->_activityTypeId])) {
        $form->assign('canEdit', TRUE);
      }
      // Permit delete button display for activities.
      if (CRM_Core_Permission::check('delete activities of type ' . $activityType[$form->_activityTypeId])) {
        $form->assign('canDelete', TRUE);
      }
    }

    // Restrict delete for activities with unpermissioned activity types.
    if ($form->_action & CRM_Core_Action::DELETE) {
      if (!CRM_Core_Permission::check('delete activities of type ' . $activityType[$form->_activityTypeId])) {
        CRM_Utils_System::permissionDenied();
        CRM_Utils_System::civiExit();
      }
    }

    // Restrict edit for activities with unpermissioned activity types.
    if (($form->_action & CRM_Core_Action::UPDATE) && isset($form->_activityTypeId)) {
      if (!CRM_Core_Permission::check('edit activities of type ' . $activityType[$form->_activityTypeId])) {
        CRM_Utils_System::permissionDenied();
        CRM_Utils_System::civiExit();
      }
      else {
        // Restrict available activities for edit.
        $unwanted = CRM_Core_OptionGroup::values('activity_type', FALSE, FALSE, FALSE, "AND v.name = 'Print PDF Letter'");
        $fActivityTypes = array_diff_key(CRM_Core_PseudoConstant::ActivityType(FALSE), $unwanted);
        CRM_ActivityTypeACL_BAO_ACL::getPermissionedActivities($allowedActivities, CRM_Core_Action::UPDATE, FALSE, TRUE);
        $fActivityTypes = array_intersect_key($allowedActivities, $fActivityTypes);

        $form->add('select', 'activity_type_id', ts('Activity Type'),
          array('' => '- ' . ts('select') . ' -') + $allowedActivities,
          FALSE, array(
            'onchange' => "CRM.buildCustomData( 'Activity', this.value );",
            'class' => 'crm-select2 required',
          )
        );

        // Restrict follow up activities too.
        $form->add('select', 'followup_activity_type_id', ts('Followup Activity'),
          array('' => '- ' . ts('select') . ' -') + $fActivityTypes,
          FALSE, array(
            'class' => 'crm-select2',
          )
        );
      }
    }
  }
  if ($formName == "CRM_Case_Form_CaseView") {
    $xmlProcessor = new CRM_Case_XMLProcessor_Process();
    $aTypes = $xmlProcessor->get($form->_caseType, 'ActivityTypes', TRUE);

    $allActTypes = CRM_Activity_BAO_Activity::buildOptions('activity_type_id', 'validate');
    $allCaseActTypes = CRM_Case_PseudoConstant::caseActivityType();
    $emailActivityType = array_search('Email', $allActTypes);
    $pdfActivityType = array_search('Print PDF Letter', $allActTypes);

    // For the add activity widget.
    CRM_ActivityTypeACL_BAO_ACL::getPermissionedActivities($allowedActivities, CRM_Core_Action::ADD, FALSE, TRUE);

    // For the activity search form.
    CRM_ActivityTypeACL_BAO_ACL::getPermissionedActivities($viewActivities, CRM_Core_Action::VIEW, FALSE, TRUE);
    foreach ($allCaseActTypes as $typeDetails) {
      if (!in_array($typeDetails['name'], array('Open Case'))) {
        $aTypesFilter[$typeDetails['id']] = CRM_Utils_Array::value('label', $typeDetails);
      }
    }
    $aTypes = array_intersect_key($allowedActivities, $aTypes);
    $aTypesFilter = array_intersect_key($viewActivities, $aTypesFilter);
    //CRM_Case_Form_CaseView::activityForm($form, $aTypes);
    $form->add('select', 'activity_type_filter_id', ts('Activity Type'), array('' => ts('- select activity type -')) + $aTypesFilter, FALSE, array('id' => 'activity_type_filter_id_' . $form->_caseID));

    // remove Open Case activity type since we're inside an existing case
    if ($openActTypeId = array_search('Open Case', $allActTypes)) {
      unset($aTypes[$openActTypeId]);
    }

    // Only show "link cases" activity if other cases exist.
    $linkActTypeId = array_search('Link Cases', $allActTypes);
    if ($linkActTypeId) {
      $count = civicrm_api3('Case', 'getcount', array(
        'check_permissions' => TRUE,
        'id' => array('!=' => $form->_caseID),
        'is_deleted' => 0,
      ));
      if (!$count) {
        unset($aTypes[$linkActTypeId]);
      }
    }

    if (!$xmlProcessor->getNaturalActivityTypeSort()) {
      asort($aTypes);
    }

    $activityLinks = array('' => ts('Add Activity'));
    foreach ($aTypes as $type => $label) {
      if ($type == $emailActivityType) {
        $url = CRM_Utils_System::url('civicrm/activity/email/add',
          "action=add&context=standalone&reset=1&caseid={$form->_caseID}&atype=$type",
          FALSE, NULL, FALSE
        );
      }
      elseif ($type == $pdfActivityType) {
        $url = CRM_Utils_System::url('civicrm/activity/pdf/add',
          "action=add&context=standalone&reset=1&cid={$form->_contactID}&caseid={$form->_caseID}&atype=$type",
          FALSE, NULL, FALSE);
      }
      else {
        $url = CRM_Utils_System::url('civicrm/case/activity',
          "action=add&reset=1&cid={$form->_contactID}&caseid={$form->_caseID}&atype=$type",
          FALSE, NULL, FALSE
        );
      }
      $activityLinks[$url] = $label;
    }

    $form->add('select', 'add_activity_type_id', '', $activityLinks, FALSE, array('class' => 'crm-select2 crm-action-menu fa-calendar-check-o twenty'));
  }
}

/**
 * Implements hook_civicrm_postProcess().
 *
 * @param string $formName
 * @param CRM_Core_Form $form
 */
function activitytypeacl_civicrm_postProcess($formName, &$form) {
  if ($formName == "CRM_Admin_Form_Options" && $form->getVar('_gName') == 'activity_type' && ($form->_action & CRM_Core_Action::ADD)) {
    $message = "Please review permissions for the new activity type <a href='%1'>here</a>, or contact your System Administrator.";
    $url = CRM_Utils_System::url('admin/people/permissions', NULL, TRUE);
    $status = ts($message, array(1 => $url));
    CRM_Core_Session::setStatus($status, ts('Activity Type ACL Notice'));
  }
}

/**
 * Implementation of hook_civicrm_alterReportVar
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterReportVar
 */
function activitytypeacl_civicrm_alterReportVar($varType, &$var, &$object) {
  if ($varType == 'sql' && get_class($object) == 'CRM_Report_Form_Contact_Detail') {
    CRM_ActivityTypeACL_BAO_ACL::getAdditionalActivityClause($var->_formComponent['activity_civireport'], "constituent");
  }
  if ($varType == 'columns') {
    if (isset($var['civicrm_activity']['filters']['activity_type_id'])) {
      $var['civicrm_activity']['filters']['activity_type_id'] = array(
        'title' => ts('Activity Type'),
        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
        'options' => CRM_ActivityTypeACL_BAO_ACL::getPermissionedActivities($activityOptions, CRM_Core_Action::VIEW, FALSE, TRUE),
      );
    }
  }
  if ($varType == 'sql' && get_class($object) == 'CRM_Report_Form_ActivitySummary') {
    CRM_ActivityTypeACL_BAO_ACL::getAdditionalActivityClause($var, "summary");
  }
  if ($varType == 'sql' && get_class($object) == 'CRM_Report_Form_Case_TimeSpent') {
    CRM_ActivityTypeACL_BAO_ACL::getAdditionalActivityClause($var, "summary");
  }
  if ($varType == 'sql' && get_class($object) == 'CRM_Report_Form_Case_Detail') {
    CRM_ActivityTypeACL_BAO_ACL::getAdditionalActivityClause($var, "case");
  }
}

/**
 * Implementation of hook_civicrm_selectWhereClause
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_selectWhereClause
 */
function activitytypeacl_civicrm_selectWhereClause($entity, &$clauses) {
  if ($entity == "Activity") {
    $constituent = CRM_Core_Session::singleton()->get('isConstituent');
    // check if the page is the Activity Detaisl report
    $activityDetail = CRM_Core_Session::singleton()->get('isActivityDetail');
    if (!$constituent) {
      $whereClause = CRM_ActivityTypeACL_BAO_ACL::getAdditionalActivityClause($where, "search");
      if (!empty($clauses['activity_type_id'])) {
        $clauses['activity_type_id'] .= $whereClause;
      }
      else {
        $clauses['activity_type_id'] = $whereClause;
      }
    }
    // Filter report mysql query for only activities with permissions
    if ($activityDetail) {
      $permissionedActivities = CRM_ActivityTypeACL_BAO_ACL::getPermissionedActivities();
      $permissionedKeys = array_keys($permissionedActivities);
      $clause = "( " . implode(",", array_keys($permissionedActivities)) . " ) ";
      $clauses['activity_type_id'] = "IN $clause";
    }
  }
}
/**
 * Implementation of hook_civicrm_links
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_links/
 */

function activitytypeacl_civicrm_links($op, $objectName, $objectId, &$links, &$mask, &$values) {
  // get in ids of each of the permissioned activities that are able to add, edit, and delete
  CRM_ActivityTypeACL_BAO_ACL::getPermissionedActivities($activityOptions_view, CRM_Core_Action::VIEW, FALSE, TRUE);
  CRM_ActivityTypeACL_BAO_ACL::getPermissionedActivities($activityOptions_edit, CRM_Core_Action::UPDATE, FALSE, TRUE);
  CRM_ActivityTypeACL_BAO_ACL::getPermissionedActivities($activityOptions_delete, CRM_Core_Action::DELETE, FALSE, TRUE);
  // $objectName = 'Activity' and $op = 'activity.tab.row' indicates where the action links are located
  // to help determine all available $objectName and $op values, it can be helpful to use the CRM_Core_Error::debug_var function
  switch ($objectName) {
    case 'Activity':
      switch ($op) {
        case 'activity.tab.row':
          // use the API to return the information about the activity the link is found in by using the $objectId as a parameter 
          $result = civicrm_api3('Activity', 'get', [
            'sequential' => 1,
            'id' => $objectId,
          ]);
          // get the indicies of each button type
          $linkIndices = [];
          for ($i = 0; $i <= count($links); $i++) {
            if ($links[$i]['name'] == 'View'){
              $linkIndices['View'] = $i;
            }
            elseif ($links[$i]['name'] == 'Edit'){
              $linkIndices['Edit'] = $i;
            }
            elseif ($links[$i]['name'] == 'Delete'){
              $linkIndices['Delete'] = $i;
            }
          };
          unset($i);
          // check if the activity_type_id (a unique id for each activity, ie. Volunteer = 67) is found in the permissioned activities list
          // array_keys is needed because the index of each activity in the actvityOptions arrays correponnds to the action_type_id of the activity
          // the indices of the $links correspond to the add, edit, and delete links
          if (!in_array($result['values'][0]['activity_type_id'], array_keys($activityOptions_view))) {
            unset($links[$linkIndices['View']]);
          }
          if (!in_array($result['values'][0]['activity_type_id'], array_keys($activityOptions_edit))) {
            unset($links[$linkIndices['Edit']]);
          }
          if (!in_array($result['values'][0]['activity_type_id'], array_keys($activityOptions_delete))) {
            unset($links[$linkIndices['Delete']]);
          }
          unset($linkIndices);
        case 'activity.selector.row':
          $result = civicrm_api3('Activity', 'get', [
            'sequential' => 1,
            'id' => $objectId,
          ]);   
          $linkIndices = [];
          for ($i = 0; $i <= count($links); $i++) {
            if ($links[$i]['name'] == 'View'){
              $linkIndices['View'] = $i;
            }
            elseif ($links[$i]['name'] == 'Edit'){
              $linkIndices['Edit'] = $i;
            }
            elseif ($links[$i]['name'] == 'Delete'){
              $linkIndices['Delete'] = $i;
            }
          };
          unset($i);
          if (!in_array($result['values'][0]['activity_type_id'], array_keys($activityOptions_view))) {
            unset($links[$linkIndices['View']]);
          }
          if (!in_array($result['values'][0]['activity_type_id'], array_keys($activityOptions_edit))) {
            unset($links[$linkIndices['Edit']]);
          }
          if (!in_array($result['values'][0]['activity_type_id'], array_keys($activityOptions_delete))) {
            unset($links[$linkIndices['Delete']]);
          }     
          unset($linkIndices);  
      }
  }
}

/**
 * Implementation of hook_civicrm_alterContent
 * 
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterContent/
 */

function activitytypeacl_civicrm_alterContent(&$content, $context, $tplName, &$object) {
  // check if the we are viewing the details of a specific activity
  if($context == "form") {
    if(($tplName == "CRM/Activity/Form/Activity.tpl") && ($object -> _action & CRM_Core_Action::VIEW)) {
      // get values for activites with edit and delete permissions
      CRM_ActivityTypeACL_BAO_ACL::getPermissionedActivities($activityOptions_edit, CRM_Core_Action::UPDATE, FALSE, TRUE);
      CRM_ActivityTypeACL_BAO_ACL::getPermissionedActivities($activityOptions_delete, CRM_Core_Action::DELETE, FALSE, TRUE);
      // CRM_Core_Error::debug_var('array_keys:', array_keys($activityOptions_delete));
      if (!in_array($object -> _activityTypeId, array_keys($activityOptions_edit))) {
        // find the index of the html content string for the edit tag
        $editButtonIndex = strpos($content, "title=\"Edit\"");
        $startEdit = $editButtonIndex;
        $endEdit = $editButtonIndex;
        // decrement the index until the start of the <a> tag is found
        while ($content[$startEdit] != '<') {
          $startEdit--;
        }
        // find the index of the end of the <a> tag
        $endEdit = strpos(substr($content, $startEdit), "</a>");
        $editButton = substr($content, $startEdit, ($endEdit + 4));
        // remove the <a> tag for the edit button from the html
        $content = str_replace($editButton,"",$content);
      }
      if (!in_array($object -> _activityTypeId, array_keys($activityOptions_delete))) {
        $deleteButtonIndex = strpos($content, "title=\"Delete\"");
        $startEdit = $deleteButtonIndex;
        $endEdit = $deleteButtonIndex;
        while ($content[$startEdit] != '<') {
          $startEdit--;
        }
        $endEdit = strpos(substr($content, $startEdit), "</a>");
        $deleteButton = substr($content, $startEdit, ($endEdit + 4));
        $content = str_replace($deleteButton,"",$content);
      }
    }
    if (get_class($object) == "CRM_Case_Form_ActivityView") {
      if(($tplName == "CRM/Case/Form/ActivityView.tpl")) {
        // get values for activites with edit and delete permissions
        CRM_ActivityTypeACL_BAO_ACL::getPermissionedActivities($activityOptions_edit, CRM_Core_Action::UPDATE, FALSE, TRUE);
        CRM_ActivityTypeACL_BAO_ACL::getPermissionedActivities($activityOptions_delete, CRM_Core_Action::DELETE, FALSE, TRUE);
        // get activityID of selected activity from the template (instead of the php file)
        $activityID = CRM_Core_Smarty::singleton()->get_template_vars('activityID');
        // call the API using the activityID to get activity_type_id
        $result = civicrm_api3('Activity', 'get', [
          'sequential' => 1,
          'id' => $activityID,
        ]);
        if (!in_array($result['values'][0]['activity_type_id'], array_keys($activityOptions_edit))) {
          // find the index of the html content string for the edit tag
          $editButtonIndex = strpos($content, "name=\"Edit\"");
          $startEdit = $editButtonIndex;
          $endEdit = $editButtonIndex;
          // decrement the index until the start of the <a> tag is found
          while ($content[$startEdit] != '<') {
            $startEdit--;
          }
          // find the index of the end of the <a> tag
          $endEdit = strpos(substr($content, $startEdit), "</a>");
          $editButton = substr($content, $startEdit, ($endEdit + 4));
          // remove the <a> tag for the edit button from the html
          $content = str_replace($editButton,"",$content);
        }
        if (!in_array($result['values'][0]['activity_type_id'], array_keys($activityOptions_delete))) {
          CRM_Core_Error::debug_var('array_keys_delete:', array_keys($activityOptions_delete));
          CRM_Core_Error::debug_log_message("DELETE ACTIVE");
          $deleteButtonIndex = strpos($content, "name=\"Delete\"");
          $startEdit = $deleteButtonIndex;
          $endEdit = $deleteButtonIndex;
          while ($content[$startEdit] != '<') {
            $startEdit--;
          }
          $endEdit = strpos(substr($content, $startEdit), "</a>");
          $deleteButton = substr($content, $startEdit, ($endEdit + 4));
          $content = str_replace($deleteButton,"",$content);
        }
      }
    }
  }
  // Set the boolean indicating the current page is the Activity Details Report to false
  if ($tplName != 'CRM/Report/Form/Activity.tpl') {
    CRM_Core_Session::singleton()->set('isActivityDetail', FALSE);
  }
}