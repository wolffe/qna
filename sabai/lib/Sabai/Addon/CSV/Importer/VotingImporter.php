<?php
class Sabai_Addon_CSV_Importer_VotingImporter extends Sabai_Addon_CSV_AbstractImporter
{
    public function csvImporterSupports(Sabai_Addon_Entity_Model_Bundle $bundle, Sabai_Addon_Entity_Model_Field $field)
    {
        return !empty($bundle->info[$this->_name])
            && (!isset($bundle->info[$this->_name]['csv']) || !empty($bundle->info[$this->_name]['csv']));
    }
    
    public function csvImporterDoImport(Sabai_Addon_Entity_Model_Field $field, array $settings, $column, $value)
    {
        if (false === $value = unserialize($value)) return;
        
        switch ($this->_name) {
            case 'voting_default':
            case 'voting_rating':
                $ret = array();
                foreach ($value as $name => $_value) {
                    $ret[] = $_value + array('name' => $name);
                }
                return $ret;
            default:
                return array($value);
        }
    }
}