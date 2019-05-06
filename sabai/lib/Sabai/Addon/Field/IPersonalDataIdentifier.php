<?php
interface Sabai_Addon_Field_IPersonalDataIdentifier
{
    public function fieldPersonalDataQuery(Sabai_Addon_Field_IQuery $query, $fieldName, $email, $userId);
    public function fieldPersonalDataAnonymize(Sabai_Addon_Field_IField $field, Sabai_Addon_Entity_IEntity $entity);
}