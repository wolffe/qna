<?php
abstract class Sabai_Addon_Entity_GuestAuthorFieldType extends Sabai_Addon_Field_Type_AbstractType implements Sabai_Addon_Field_IPersonalData, Sabai_Addon_Entity_IPersonalDataAuthorFieldType
{
    protected $_entityType, $_defaultWidget;

    public function __construct(Sabai_Addon $addon, $name, $entityType, $defaultWidget)
    {
        parent::__construct($addon, $name);
        $this->_entityType = $entityType;
        $this->_defaultWidget = $defaultWidget;
    }

    protected function _fieldTypeGetInfo()
    {
        return array(
            'label' => __('Guest Author', 'sabai'),
            'entity_types' => array($this->_entityType),
            'default_widget' => $this->_defaultWidget,
            'creatable' => false,
        );
    }

    public function fieldTypeGetSchema(array $settings)
    {
        return array(
            'columns' => array(
                'email' => array(
                    'type' => Sabai_Addon_Field::COLUMN_TYPE_VARCHAR,
                    'notnull' => true,
                    'length' => 100,
                    'was' => 'email',
                    'default' => '',
                ),
                'name' => array(
                    'type' => Sabai_Addon_Field::COLUMN_TYPE_VARCHAR,
                    'notnull' => true,
                    'length' => 255,
                    'was' => 'name',
                    'default' => '',
                ),
                'url' => array(
                    'type' => Sabai_Addon_Field::COLUMN_TYPE_VARCHAR,
                    'notnull' => true,
                    'length' => 255,
                    'was' => 'url',
                    'default' => '',
                ),
                'guid' => array(
                    'type' => Sabai_Addon_Field::COLUMN_TYPE_VARCHAR,
                    'notnull' => true,
                    'length' => 23,
                    'was' => 'guid',
                    'default' => '',
                ),
            )
        );
    }

    public function fieldTypeOnSave(Sabai_Addon_Field_IField $field, array $values, array $currentValues = null)
    {
        if (is_null($currentValues)
            && !$this->_addon->getApplication()->getUser()->isAnonymous()
        ) {
            return false;
        }
        $ret = array();
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $value['guid'] = empty($currentValues[$key]['guid']) ? uniqid('', true) : $currentValues[$key]['guid'];
                if (!empty($currentValues[$key])) {
                    $value += $currentValues[$key];
                }
                $ret[] = $value;
            } elseif ($value === false) { // deleting explicitly?
                $ret[] = false; 
            }
        }
        return empty($ret) ? false : $ret;
    }

    public function fieldPersonalDataExport(Sabai_Addon_Field_IField $field, Sabai_Addon_Entity_IEntity $entity)
    {
        if (!$value = $entity->getSingleFieldValue($field->getFieldName())) return;

        $ret = array();
        foreach (array(
            'name' => __('Name', 'sabai'),
            'email' => __('E-mail', 'sabai'),
            'url' => __('Website', 'sabai')
        ) as $key => $name) {
            if (!strlen($value[$key])) continue;

            $ret[$key] = array('name' => $name, 'value' => $value[$key]);
        }
        return $ret;
    }

    public function fieldPersonalDataErase(Sabai_Addon_Field_IField $field, Sabai_Addon_Entity_IEntity $entity)
    {
        if (!$value = $entity->getSingleFieldValue($field->getFieldName())) return true; // delete

        return array(
            'name' => $this->_addon->getApplication()->getPlatform()->anonymizeText($value['name']),
            'email' => strlen($value['email']) ? $this->_addon->getApplication()->getPlatform()->anonymizeEmail($value['email']) : '',
            'url' => strlen($value['url']) ? $this->_addon->getApplication()->getPlatform()->anonymizeUrl($value['url']) : '',
        );
    }

    public function fieldPersonalDataQuery(Sabai_Addon_Field_IQuery $query, $fieldName, $email, $userId)
    {
        $query->fieldIs($fieldName, $email, 'email');
    }

    public function fieldPersonalDataAnonymize(Sabai_Addon_Field_IField $field, Sabai_Addon_Entity_IEntity $entity)
    {
        $value = $entity->getSingleFieldValue($field->getFieldName());
        return array(
            'name' => $this->_addon->getApplication()->getPlatform()->anonymizeText($value['name']),
            'email' => strlen($value['email']) ? $this->_addon->getApplication()->getPlatform()->anonymizeEmail($value['email']) : '',
            'url' => strlen($value['url']) ? $this->_addon->getApplication()->getPlatform()->anonymizeUrl($value['url']) : '',
        );
    }
}