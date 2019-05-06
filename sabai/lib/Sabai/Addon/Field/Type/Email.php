<?php
class Sabai_Addon_Field_Type_Email extends Sabai_Addon_Field_Type_String implements Sabai_Addon_Field_IPersonalDataIdentifier
{
    protected function _fieldTypeGetInfo()
    {
        return array(
            'label' => __('Email', 'sabai'),
            'default_widget' => $this->_name,
            'default_renderer' => $this->_name,
            'default_settings' => array(
                'min_length' => null,
                'max_length' => null,
                'char_validation' => 'email',
            ),
        );
    }

    public function fieldTypeGetSettingsForm(array $settings, array $parents = array())
    {
        $form = parent::fieldTypeGetSettingsForm($settings, $parents);
        $form['char_validation']['#type'] = 'hidden';
        $form['char_validation']['#value'] = 'email';
        return $form;
    }

    public function fieldPersonalDataQuery(Sabai_Addon_Field_IQuery $query, $fieldName, $email, $userId)
    {
        $query->fieldIs($fieldName, $email);
    }

    public function fieldPersonalDataAnonymize(Sabai_Addon_Field_IField $field, Sabai_Addon_Entity_IEntity $entity)
    {
        return $this->_addon->getApplication()->getPlatform()->anonymizeEmail($entity->getSingleFieldValue($field->getFieldName()));
    }
}