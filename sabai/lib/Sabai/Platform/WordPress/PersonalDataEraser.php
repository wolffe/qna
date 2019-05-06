<?php
class Sabai_Platform_WordPress_PersonalDataEraser
{
    protected $_application, $_bundleName, $_fields;
    
    public function __construct(Sabai $application, $bundleName, array $fields)
    {
        $this->_application = $application;
        $this->_bundleName = $bundleName;
        $this->_fields = $fields;
    }
    
    public function erase($email, $page)
    {
        $ret = array('items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true);
        if (($user = get_user_by('email', $email))
            && $user->ID
        ) {
            $results = $this->_application->Entity_PersonalData_erase($this->_bundleName, $this->_fields, $email, $user->ID);
            if (!empty($results['deleted'])) $ret['items_removed'] = $results['deleted'];
            if (!empty($results['retained'])) $ret['items_retained'] = $results['retained'];
            if (!empty($results['messages'])) $ret['messages'] = $results['messages'];
        }
        return $ret;
    }
}