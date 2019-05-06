<?php
interface Sabai_Addon_Field_IPersonalData
{
    public function fieldPersonalDataExport(Sabai_Addon_Field_IField $field, Sabai_Addon_Entity_IEntity $entity);
    public function fieldPersonalDataErase(Sabai_Addon_Field_IField $field, Sabai_Addon_Entity_IEntity $entity);
}