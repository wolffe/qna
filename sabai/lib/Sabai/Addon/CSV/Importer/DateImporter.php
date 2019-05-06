<?php
class Sabai_Addon_CSV_Importer_DateImporter extends Sabai_Addon_CSV_AbstractImporter
{
    public function csvImporterSettingsForm(Sabai_Addon_Entity_Model_Field $field, array $settings, $column, $enclosure, array $parents = array())
    {
        $form = $this->_getDateFormatSettingsForm();
        if ($field->isCustomField()) {
            $form += $this->_acceptMultipleValues($enclosure, $parents);
        }
        return $form;
    }
    
    public function csvImporterDoImport(Sabai_Addon_Entity_Model_Field $field, array $settings, $column, $value)
    {
        if (!empty($settings['_multiple'])) {
            if (!$values = explode($settings['_separator'], $value)) {
                return;
            }
        } else {
            $values = array($value);
        }
        $ret = array();
        if ($settings['date_format'] === 'string') {
            foreach ($values as $value) {
                if (false !== $value = strtotime($value)) {
                    $ret[] = $value;
                }
            }
        } else {
            foreach ($values as $value) {
                $ret[] = $value;
            }
        }
        
        return $ret;
    }
}