<?php
class Sabai_Addon_CSV_Exporter_DateExporter extends Sabai_Addon_CSV_AbstractExporter
{
    public function csvExporterSettingsForm(Sabai_Addon_Entity_Model_Field $field, array $settings, $column, $enclosure, array $parents = array())
    {
        $form = $this->_getDateFormatSettingsForm($parents);
        if ($field->isCustomField()) {
            $form += $this->_acceptMultipleValues($enclosure, $parents);
        }
        return $form;
    }
    
    public function csvExporterDoExport(Sabai_Addon_Entity_Model_Field $field, array $settings, $value, array $columns, array &$files)
    {
        $ret = array();
        switch ($settings['date_format']) {
            case 'string':
                foreach ($value as $_value) {
                    if (false !== $__value = @date($settings['date_format_php'], $_value)) {
                        $ret[] = $__value;
                    } else {
                        $ret[] = $_value;
                    }
                }
                break;
            default:
                foreach ($value as $_value) {
                    $ret[] = $_value;
                }
        }
             
        return isset($settings['_separator']) ? implode($settings['_separator'], $ret) : $ret[0];
    }
}