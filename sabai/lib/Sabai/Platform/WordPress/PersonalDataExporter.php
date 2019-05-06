<?php
class Sabai_Platform_WordPress_PersonalDataExporter
{
    protected $_application, $_bundleName, $label, $_fields;
    
    public function __construct(Sabai $application, $bundleName, $label, array $fields)
    {
        $this->_application = $application;
        $this->_bundleName = $bundleName;
        $this->_label = $label;
        $this->_fields = $fields;
    }
    
    public function export($email, $page)
    {
        $ret = array('data' => array(), 'done' => true);
        if (($user = get_user_by('email', $email))
            && $user->ID
        ) {
            if ($personal_data = $this->_application->Entity_PersonalData($this->_bundleName, $this->_fields, $email, $user->ID)) {
                foreach (array_keys($personal_data) as $entity_id) {
                    $ret['data'][] = array(
                        'item_id' => 'post-' . $this->_bundleName . '-' . $entity_id,
                        'group_id' => 'post-' . $this->_bundleName,
                        'group_label' => $this->_label,
                        'data' => $personal_data[$entity_id] + array(
                            'permalink' => array(
                                'name' => __('Permalink URL', 'sabai'),
                                'value' => get_permalink($entity_id),
                            ),
                        ),
                    );
                }
            }
        }
        return $ret;
    }
}
