<?php
class Sabai_Addon_CSV_Exporter_FieldExporter extends Sabai_Addon_CSV_AbstractExporter
{
    protected function _csvExporterInfo()
    {
        return array(
            'field_types' => array(substr($this->_name, 6)), // remove field_ part
        );
    }
    
    public function csvExporterSettingsForm(Sabai_Addon_Entity_Model_Field $field, array $settings, $column, $enclosure, array $parents = array())
    {
        if ($this->_name === 'field_boolean') return;
        
        $form = $reserved_separator = array();
        
        switch ($this->_name) {                
            case 'field_link':
                $form += array(
                    'separator' => array(
                        '#type' => 'textfield',
                        '#title' => __('Link URL/title separator', 'sabai'),
                        '#description' => __('Enter the character used to separate the link URL and title.', 'sabai'),
                        '#default_value' => '|',
                        '#horizontal' => true,
                        '#min_length' => 1,
                        '#required' => true,
                        '#weight' => 1,
                    ),
                );
                $reserved_separator['separator'] = $form['separator']['#title'];
                break;
            case 'field_video':
                $form += array(
                    'separator' => array(
                        '#type' => 'textfield',
                        '#title' => __('Video provider/ID separator', 'sabai'),
                        '#description' => __('Enter the character used to separate the video provider and ID.', 'sabai'),
                        '#default_value' => '|',
                        '#horizontal' => true,
                        '#min_length' => 1,
                        '#required' => true,
                        '#weight' => 1,
                    ),
                );
                $reserved_separator['separator'] = $form['separator']['#title'];
                break;
            case 'field_range':
                $form += array(
                    'separator' => array(
                        '#type' => 'textfield',
                        '#title' => __('Field min/max separator', 'sabai'),
                        '#description' => __('Enter the character used to separate the minimum and maximum values.', 'sabai'),
                        '#default_value' => '|',
                        '#horizontal' => true,
                        '#min_length' => 1,
                        '#required' => true,
                        '#weight' => 1,
                    ),
                );
                $reserved_separator['separator'] = $form['separator']['#title'];
                break;
            default:
        }
        
        if ($field->isCustomField()) {
            $form += $this->_acceptMultipleValues($enclosure, $parents, $reserved_separator);
        }
        
        return $form;
    }
    
    public function csvExporterDoExport(Sabai_Addon_Entity_Model_Field $field, array $settings, $value, array $columns, array &$files)
    {
        if ($this->_name === 'field_boolean') return parent::csvExporterDoExport($field, $settings, $value, $columns, $files);

        $ret = array();        
        switch ($this->_name) {
            case 'field_text':
                foreach ($value as $_value) {
                    $ret[] = $_value['value'];
                }
                break;
            case 'field_link':
                foreach ($value as $_value) {
                    $ret[] = $_value['url'] . $settings['separator'] . $_value['title'];
                }
                break;
            case 'field_video':
                foreach ($value as $_value) {
                    $ret[] = $_value['provider'] . $settings['separator'] . $_value['id'];
                }
                break;
            case 'field_range':
                foreach ($value as $_value) {
                    $ret[] = $_value['min'] . $settings['separator'] . $_value['max'];
                }
                break;
            case 'field_user':
                foreach ($value as $_value) {
                    $ret[] = $_value->id;
                }
                break;
            default:
                $ret = $value;
        }
        
        return isset($settings['_separator']) ? implode($settings['_separator'], $ret) : $ret[0];
    }
}