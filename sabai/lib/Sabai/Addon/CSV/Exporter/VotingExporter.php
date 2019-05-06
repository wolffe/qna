<?php
class Sabai_Addon_CSV_Exporter_VotingExporter extends Sabai_Addon_CSV_AbstractExporter
{
    public function csvExporterSupports(Sabai_Addon_Entity_Model_Bundle $bundle, Sabai_Addon_Entity_Model_Field $field)
    {
        return !empty($bundle->info[$this->_name])
            && (!isset($bundle->info[$this->_name]['csv']) || !empty($bundle->info[$this->_name]['csv']));
    }
    
    public function csvExporterDoExport(Sabai_Addon_Entity_Model_Field $field, array $settings, $value, array $columns, array &$files)
    {
        switch ($this->_name) {
            case 'voting_default':
            case 'voting_rating':
                foreach (array_keys($value) as $name) {
                    // Save current count/sum as count_init/sum_init to preserve stats
                    if (isset($value[$name]['count_init'])) {
                        $value[$name]['count_init'] = $value[$name]['count'];
                    }
                    if (isset($value[$name]['sum_init'])) {
                        $value[$name]['sum_init'] = $value[$name]['sum'];
                    }
                }
                break;
            default:
                unset($value[0]); // remove var compat with <=1.3
        
                // Save current count/sum as count_init/sum_init to preserve stats
                if (isset($value['count_init'])) {
                    $value['count_init'] = $value['count'];
                }
                if (isset($value['sum_init'])) {
                    $value['sum_init'] = $value['sum'];
                }
        }
        
        return serialize($value);
    }
}