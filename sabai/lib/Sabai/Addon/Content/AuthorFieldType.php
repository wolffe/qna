<?php
class Sabai_Addon_Content_AuthorFieldType extends Sabai_Addon_Field_Type_AbstractType implements Sabai_Addon_Entity_IPersonalDataAuthorFieldType
{
    protected function _fieldTypeGetInfo()
    {
        return array(
            'label' => 'Author',
            'entity_types' => array('content'),
            'creatable' => false,
        );
    }

    public function fieldPersonalDataQuery(Sabai_Addon_Field_IQuery $query, $fieldName, $email, $userId)
    {
        $query->propertyIs($fieldName, $userId);
    }

    public function fieldPersonalDataAnonymize(Sabai_Addon_Field_IField $field, Sabai_Addon_Entity_IEntity $entity)
    {
        return 0;
    }
}