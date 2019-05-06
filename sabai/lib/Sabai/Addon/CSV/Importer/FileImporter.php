<?php
class Sabai_Addon_CSV_Importer_FileImporter extends Sabai_Addon_CSV_AbstractImporter
{
    protected $_fileDir = array();
    
    public function csvImporterSettingsForm(Sabai_Addon_Entity_Model_Field $field, array $settings, $column, $enclosure, array $parents = array())
    {
        $form = array(
            'separator' => array(
                '#type' => 'textfield',
                '#title' => $title = __('File name/title separator', 'sabai'),
                '#description' => __('Enter the character used to separate the file name and title.', 'sabai'),
                '#default_value' => '|',
                '#horizontal' => true,
                '#min_length' => 1,
                '#required' => true,
                '#weight' => 1,
                '#size' => 5,
            ),
        );
        $form += $this->_acceptMultipleValues($enclosure, $parents, array('separator' => $form['separator']['#title']));
        $form += $this->_application->File_LocationSettingsForm($parents);
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
        $files = array();
        foreach ($values as $value) {
            if ($value = explode($settings['separator'], $value)) {
                $files[] = array(
                    'name' => $value[0],
                    'title' => isset($value[1]) ? $value[1] : '',
                );
            }
        }
        if (empty($files)) return;

        if ($settings['location'] === 'none') {
            return $this->_application->File_LocationSettingsForm_saveFiles($settings, $files);
        }
        
        $field_name = $field->getFieldName();
        if (!isset($this->_fileDir[$field_name])) {
            if (!$this->_fileDir[$field_name] = $this->_application->File_LocationSettingsForm_uploadDir($settings)) {
                $this->_fileDir[$field_name] = false;
            }
         }
        if (!$this->_fileDir[$field_name]) return;
            
        return $this->_application->File_LocationSettingsForm_saveFiles(
            $settings,
            $files,
            array('image_only' => $this->_name === 'file_image'),
            $this->_fileDir[$field_name]
        );
    }
}
