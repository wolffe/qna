<?php
class Sabai_Addon_CSV_Exporter_TimeExporter extends Sabai_Addon_CSV_AbstractExporter
{
    public function csvExporterSettingsForm(Sabai_Addon_Entity_Model_Field $field, array $settings, $column, $enclosure, array $parents = array())
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
        $form += $this->_getDateFormatSettingsForm($parents, array('separator' => $form['separator']['#title']), 'H:i');
        if ($field->isCustomField()) {
            $form += $this->_acceptMultipleValues($enclosure, $parents, array('separator' => $form['separator']['#title']));
        }
        return $form;
    }
    
    public function csvExporterDoExport(Sabai_Addon_Entity_Model_Field $field, array $settings, $value, array $columns, array &$files)
    {
        $ret = array();
        switch ($settings['date_format']) {
            case 'string':
                foreach ($value as $_value) {
                    if (false === $start = @date($settings['date_format_php'], $_value['start'])) {
                        $start = (string)$_value['start'];
                    }
                    if (empty($_value['end'])
                        || false === ($end = @date($settings['date_format_php'], $_value['end']))
                    ) {
                        $end = (string)$_value['end'];
                    }
                    $ret[] = implode($settings['separator'], array($start, $end, (string)@$_value['day']));
                }
                break;
            default:
                foreach ($value as $_value) {
                    if (!empty($_value['start'])) {
                        $ret[] = implode($settings['separator'], array($_value['start'], (string)@$_value['end'], (string)@$_value['day']));
                    }
                }
        }
             
        return isset($settings['_separator']) ? implode($settings['_separator'], $ret) : $ret[0];
    }
}