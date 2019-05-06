<?php
class Sabai_Addon_File_Helper_LocationSettingsForm extends Sabai_Helper
{
    public function help(Sabai $application, array $parents)
    {        
        $form = array(
            'location' => array(
                '#type' => 'radios',
                '#title' => __('File location', 'sabai'),
                '#options' => array(
                    'upload' => __('Upload zip archive', 'sabai'),
                    'local' => __('Local folder', 'sabai'),
                    'none' => __('No upload', 'sabai'),
                ),
                '#options_description' => array(
                    'upload' => __('Upload a zip archive file containing all files.', 'sabai'),
                    'local' => __('Specify the path to the directory where all files are located.', 'sabai'),
                    'none' => sprintf(
                        __('Use existing files located under %s.', 'sabai'),
                        $application->getAddon('File')->getUploadDir()
                    ),
                ),
                '#default_value' => 'none',
            ),
            'file' => array(
                '#type' => 'file',
                '#title' => __('Upload zip archive', 'sabai'),
                '#upload_dir' => $application->getAddon('File')->getTmpDir(),
                '#allowed_extensions' => array('zip'),
                '#states' => array(
                    'visible' => array(
                        sprintf('[name="%s[location]"]', $application->Form_FieldName($parents)) => array('type' => 'value', 'value' => 'upload'),
                    ),
                ),
                '#required' => create_function('$form', sprintf('return $form->getValue(array(\'%s\', \'location\')) === \'upload\';', implode("', '", $parents))),
            ),
            'local' => array(
                '#title' => __('Local folder', 'sabai'),
                '#type' => 'textfield',
                '#states' => array(
                    'visible' => array(
                        sprintf('[name="%s[location]"]', $application->Form_FieldName($parents)) => array('type' => 'value', 'value' => 'local'),
                    ),
                ),
                '#placeholder' => '/path/to/local/folder',
                '#required' => create_function('$form', sprintf('return $form->getValue(array(\'%s\', \'location\')) === \'local\';', implode("', '", $parents))),
            ),
        );
        
        return $form;
    }
    
    public function uploadDir(Sabai $application, array $settings)
    {   
        if ($settings['location'] === 'local') {
            return @is_dir($settings['local']) ? rtrim($settings['local'], '/') : null;
        }
            
        if ($settings['location'] !== 'upload') return;        
        
        $ret = null;
        if ($archive = @$settings['file']['saved_file_path']) {
            $application->getPlatform()->unzip($archive, dirname($archive));
            $possible_file_dir = array(
                dirname($archive) . '/' . substr($settings['file']['name'], 0, -1 * (strlen($settings['file']['file_ext']) + 1)), // check sub directory with folder name
                dirname($archive)
            );
            foreach ($possible_file_dir as $file_dir) {
                if (@is_dir($file_dir)) {
                    $ret = $file_dir;
                    break;
                }
            }
        }
        @unlink($settings['file']['saved_file_path']);
        
        return rtrim($ret, '/');
    }
    
    public function saveFiles(Sabai $application, array $settings, array $values = null, array $uploadOptions = array(), $tmpDir = null)
    {     
        $ret = array();
        if ($settings['location'] === 'none') {
            if (!isset($values)) {
                $files = $application->getModel('File', 'File');
                if (!empty($uploadOptions['image_only'])) {
                    $files->isImage_is(true);
                }
                foreach ($files->fetch(100, 0, 'id', 'DESC') as $file) {
                    $ret[$file->id] = array('id' => $file->id);
                }
            } else {            
                foreach ((array)$values as $value) {
                    if (empty($value)) continue;
                    
                    $file_data = $value; // invalid file data
                    $file_data['file_ext'] = $file_data['extension']; // uploader expects file_ext
                    $file = $application->File_Save($file_data, null, false);
                    $ret[$file->id] = array('id' => $file->id);
                }
            }
        } else {
            if (!isset($tmpDir)) return $ret;
            
            if (!isset($values)) {
                $values = array();
                foreach (new DirectoryIterator($tmpDir) as $file_info) {
                    if (!$file_info->isFile()) continue;
                    
                    $values[] = $file_info->getFilename();
                }
            }

            foreach ($values as $value) {
                $file_size = $file_width = $file_height = $file_title = null;
                if (is_array($value)) {
                    $file_data = $value;
                    $value = $file_data['name']; // original file name
                    if (!empty($file_data['size'])) {
                        $file_size = $file_data['size'];
                    }
                    if (!empty($file_data['title'])) {
                        $file_title = $file_data['title'];
                    }
                }
                $file_path = $tmpDir . '/' . $value;
                if (!file_exists($file_path)) continue;
                
                $file_data = array(
                    'name' => isset($file_title) ? $file_title : $value,
                    'tmp_name' => $file_path,
                    'size' => isset($file_size) ? $file_size : @filesize($file_path),
                );
                try {
                    $file = $application->File_Save($application->Upload(
                        $file_data,
                        array('check_tmp_name' => false, 'max_file_size' => 0) + $uploadOptions
                    ));
                } catch (Sabai_IException $e) {
                    $application->LogError($e);
                    continue;
                }
                
                $ret[$file->id] = array('id' => $file->id);
            }
        }
        
        return array_values($ret);
    }
}