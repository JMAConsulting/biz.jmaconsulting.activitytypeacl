<?php

require_once 'activitytypeacl.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function activitytypeacl_civicrm_config(&$config) {
  _activitytypeacl_civix_civicrm_config($config);
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
  else {
    CRM_Core_Session::singleton()->set('isConstituent', FALSE);
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
}

/**
 * Implementation of hook_civicrm_selectWhereClause
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_selectWhereClause
 */
function activitytypeacl_civicrm_selectWhereClause($entity, &$clauses) {
  if ($entity == "Activity") {
    $constituent = CRM_Core_Session::singleton()->get('isConstituent');
    if (!$constituent) {
      $clauses['activity_type_id'][] = CRM_ActivityTypeACL_BAO_ACL::getAdditionalActivityClause($where, "report");
    }
  }
}
