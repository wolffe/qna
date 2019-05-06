<?php
class Sabai_Addon_CSV_Importer_TimeImporter extends Sabai_Addon_CSV_AbstractImporter
{
    public function csvImporterSettingsForm(Sabai_Addon_Entity_Model_Field $field, array $settings, $column, $enclosure, array $parents = array())
    {
        $form = array(
            'separator' => array(
                '#type' => 'textfield',
                '#title' => __('Start/End/Day separator', 'sabai'),
                '#description' => __('Enter the character used to separate the starting time, ending time, and day of week.', 'sabai'),
                '#default_value' => '|',
                '#horizontal' => true,
                '#min_length' => 1,
                '#required' => true,
            ),
        );
        $form += $this->_getDateFormatSettingsForm();
        if ($field->isCustomField()) {
            $form += $this->_acceptMultipleValues($enclosure, $parents, array('separator' => $form['separator']['#title']));
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
                $value = explode($settings['separator'], $value);
                if (!$value[0]) continue;
                
                $ret[] = array(
                    'start' => $value[0],
                    'end' => false !== ($value[1] = strtotime($value[1])) ? $value[1] : null,
                    'day' => (string)@$value[2],
                );
            }
        } else {
            foreach ($values as $value) {
                $value = explode($settings['separator'], $value);
                if (!$value[0]) continue;
                
                $ret[] = array(
                    'start' => $value[0],
                    'end' => strlen($value[1]) ? $value[1] : null,
                    'day' => (string)@$value[2],
                );
            }
        }
        return $ret;
    }
}