<?php

class CRM_ActivityTypeACL_APIWrappers_ACL implements API_Wrapper {

  /**
   * Conditionally changes contact_type parameter for the API request.
   */
  public function fromApiInput($apiRequest) {
    if ($apiRequest['entity'] == 'Activity' && $apiRequest['action'] == 'get') {
      CRM_ActivityTypeACL_BAO_ACL::getPermissionedActivities($activityOptions, CRM_Core_Action::VIEW, FALSE, TRUE);
      $activityOptions[CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Open Case')] = "Open Case";
      $apiRequest['params']['activity_type_id'] = ['IN' => array_keys($activityOptions)];
    }
    return $apiRequest;
  }

  public function toApiOutput($apiRequest, $result) {
    if ($apiRequest['entity'] == 'CaseType' && $apiRequest['action'] == 'get') {
      if (isset($result['id'])) {
        foreach ($result['values'][0]['definition'] as $set => &$value) {
          if ($set == "activityTypes") {
            self::unsetActivitySet($value);
          }
          if ($set == "activitySets") {
            foreach ($value as $type => &$val) {
              foreach ($val['activityTypes'] as $types => &$values) {
                self::unsetActivitySet($values);
              }
            }
          }
        }
      }
    }
    return $result;
  }

  public static function unsetActivitySet(&$activity) {
    CRM_ActivityTypeACL_BAO_ACL::getPermissionedActivities($activityOptions, CRM_Core_Action::ADD, FALSE, TRUE);
    foreach ($activity as $key => $name) {
      if (!empty($name['name']) && !array_search($name['name'], $activityOptions) && $name['name'] != "Open Case") {
        unset($activity[$key]);
      }
    }
  }
}
