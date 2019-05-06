<?php
interface Sabai_Addon_CSV_IImporter
{
    public function csvImporterInfo($key = null);
    public function csvImporterSettingsForm(Sabai_Addon_Entity_Model_Field $field, array $settings, $column, $enclosure, array $parents = array());
    public function csvImporterDoImport(Sabai_Addon_Entity_Model_Field $field, array $settings, $column, $value);
    public function csvImporterClean(Sabai_Addon_Entity_Model_Field $field, array $settings, $column);
    public function csvImporterSupports(Sabai_Addon_Entity_Model_Bundle $bundle, Sabai_Addon_Entity_Model_Field $field);
}