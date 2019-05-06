<?php
class Sabai_Addon_System_Helper_Roles extends Sabai_Helper
{
    public function help(Sabai $application, $property = null, $excludeGuest = false)
    {
        $ret = array();
        $roles = $application->getModel('Role', 'System')->fetch()->getArray(null, 'name');
        $platform_roles = $application->getPlatform()->getUserRoles();
        // Add default guest role
        $platform_roles['_guest_'] = $application->_t(_n_noop('Guest', 'Guest', 'sabai'));
        foreach ($platform_roles as $role_name => $role_title) {
            $role_name_lc = strtolower($role_name);
            if (!isset($roles[$role_name_lc])) {
                if (isset($roles[$role_name])) { // for older version of 1.3 and 1.2
                    $role = $roles[$role_name];
                    unset($roles[$role_name]);
                } else {
                    $role = $application->getModel(null, 'System')->create('Role')->markNew();
                }
                $role->name = $role_name_lc;
                $role->title = $role_title;
                $commit = true;
            } else {
                $role = $roles[$role_name_lc];
            }
            $ret[$role->name] = isset($property) ? $role->$property : $role;
        }
        if ($removed_roles = array_diff_key($roles, $platform_roles)) {
            foreach ($removed_roles as $role_name => $removed_role) {
                $removed_role->markRemoved();
            }
            $commit = true;
        }
        if (!empty($commit)) {
            $application->getModel(null, 'System')->commit();
        }
        if ($excludeGuest) {
            unset($ret['_guest_']);
        } else {
            if (isset($property) && $property === 'title') {
                $ret['_guest_'] = $application->Translate($ret['_guest_']);
            }
        }
        return $ret;
    }
}