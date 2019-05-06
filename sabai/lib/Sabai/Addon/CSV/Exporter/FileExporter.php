<?php
class Sabai_Addon_CSV_Exporter_FileExporter extends Sabai_Addon_CSV_AbstractExporter
{
    public function csvExporterSettingsForm(Sabai_Addon_Entity_Model_Field $field, array $settings, $column, $enclosure, array $parents = array())
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
        $form += $this->_acceptMultipleValues($enclosure, $parents, array('separator' => $title));
        $form += $this->_getZipFileSettingsForm();
        
        return $form;
    }
    
    public function csvExporterDoExport(Sabai_Addon_Entity_Model_Field $field, array $settings, $value, array $columns, array &$files)
    {
        $ret = array();
        $field_name = $field->getFieldName();
        if (!$this->_doZipFile($settings)
            || (!$zip = $this->_getZipFile($field_name, $settings))
        ) {
            foreach ($value as $file) {
                if (!empty($file['title']) && $file['name'] !== $file['title']) {
                    $ret[] = $file['name'] . $settings['separator'] . $file['title'];
                } else {
                    $ret[] = $file['name'];
                }
            }
        } else {      
            if (!in_array($zip->filename, $files)) {
                $files[] = $zip->filename;
            }
            $upload_dir = $this->_application->getAddon('File')->getUploadDir();
            foreach ($value as $file) {
                if (!empty($file['title']) && $file['name'] !== $file['title']) {
                    $ret[] = $file['name'] . $settings['separator'] . $file['title'];
                } else {
                    $ret[] = $file['name'];
                }
                $file_path = $upload_dir . '/' . $file['name'];
                $zip->addFile($file_path, $file['name']);
            }
            $zip->close();
        }
        
        return isset($settings['_separator']) ? implode($settings['_separator'], $ret) : $ret[0];
    }
}
