<?php
class Sabai_Addon_File_Controller_Admin_RegenerateImages extends Sabai_Addon_Form_MultiStepController
{    
    protected $_flush;       
        
    protected function _doExecute(Sabai_Context $context)
    {
        parent::_doExecute($context);
        $this->LoadCss('sabai-file-results-table.min.css', 'sabai-file-results-table', null, 'sabai');
    }
    
    protected function _getSteps(Sabai_Context $context, array &$formStorage)
    {
        return array('settings', 'regenerate');
    }
    
    public function _getFormForStepSettings(Sabai_Context $context, array &$formStorage)
    {
        $form = array(
            '#tree' => true,
            'fields' => array(),
        );
        
        foreach ($this->getModel('FieldConfig', 'Entity')->type_is('file_image')->fetch()->with('Fields')->with('Bundle') as $field_config) {
            $field_labels = array();
            foreach ($field_config->Fields as $field) {
                $field_labels[] = $this->Translate($field->Bundle->label) . ' (' . $field->Bundle->addon . ') - ' . $field->getFieldLabel();
            }
            $form['fields'][$field_config->name] = array(
                '#title' => implode(', ', $field_labels),
                '#collapsible' => false,
                'regenerate' => array(
                    '#type' => 'checkbox',
                    '#default_value' => true,
                    '#title' => __('Regenerate image files for the field(s)', 'sabai'),
                    '#horizontal' => true,
                ),
            );
        }

        return $form;
    }
    
    public function _getFormForStepRegenerate(Sabai_Context $context, array &$formStorage)
    {      
        $file_count = 0;
        foreach ((array)$formStorage['values']['settings']['fields'] as $field_name => $field_settings) {
            if (empty($field_settings['regenerate'])) continue;

            $sql = sprintf(
                'SELECT COUNT(_file.file_id) FROM %1$sentity_field_%2$s _field LEFT JOIN %1$sfile_file _file ON _field.file_id = _file.file_id GROUP BY _file.file_id',
                $this->getDB()->getResourcePrefix(),
                $field_name
            );
            $file_count += (int)$this->getDB()->query($sql)->rowCount();    
        }
        
        if (empty($file_count)) {
            $this->_submitable = false;
            return array(
                '#header' => array('<div class="sabai-alert sabai-alert-warning">' . Sabai::h(__('There are no image files to regenerate!', 'sabai')) . '</div>'),
            );
        }

        $context->addTemplate($this->getPlatform()->getAssetsDir('sabai') . '/templates/file_results_table');
        $this->_ajaxSubmit = true;
        $this->_ajaxOnReadyState = 'function (result, target, trigger, count) {
    var results = target.find(".sabai-file-results"), table = results.find("table");
    if (!result.id) {
        SABAI.ajaxLoader(null, true, table);
        if (result.success) {
            SABAI.flash(result.success, "success", 0);
        }
        if (result.error) {
            SABAI.flash(result.error, "danger", 0);
        }
        return;
    }
    if (count === 1) {
        target.find(".sabai-form, .sabai-form-headers").hide();
        results.slideDown();
        SABAI.scrollTo(results);
        SABAI.ajaxLoader(null, false, table);
    }
    var thumbnail = result.thumbnail ? "<img src=\'" + result.thumbnail + "\' height=\'50\'/>" : "";
    $("<tr class=\'sabai-" + result.status + "\'><th>" + count + "</th><td>" + result.id + "</td><td>" + thumbnail + (result.title.length ? result.title : "' . Sabai::h(__('(no title)', 'sabai')) . '") + "</td><td>" + result.message + "</td></tr>")
        .appendTo(table.find("tbody"));
    if (count % 3 === 0) {
        SABAI.scrollTo(results.find(".sabai-file-results-footer"));
    }
}';
        $this->_submitButtons[] = array('#btn_label' => __('Regenerate Now', 'sabai'), '#btn_type' => 'primary', '#btn_size' => 'lg');
        
        return array(
            '#header' => array('<div class="sabai-alert sabai-alert-info">' . Sabai::h(sprintf(__('There are %d image(s) in total that can be regenerated. Press the regenerate button below to start regeneration. Do not close the browser window until all images are processed!', 'sabai'), $file_count)) . '</div>'),
            'show_progress' => array(
                '#type' => 'checkbox',
                '#title' => __('Show progress', 'sabai'),
                '#description' => __('Check this option to show the progress of the process. This may not work with some servers.', 'sabai'),
                '#default_value' => false,
                '#horizontal' => true,
            ),
        );
    }
    
    public function _submitFormForStepRegenerate(Sabai_Context $context, Sabai_Addon_Form_Form $form)
    {
        @set_time_limit(0);
        if ($this->_flush = $context->getRequest()->isAjax() && $context->getRequest()->asBool('show_progress', false)) {
            while(@ob_end_clean());
            @ini_set('zlib.output_compression', 0);
            @ini_set('implicit_flush', 1);
            header('Content-type: application/json; charset=utf-8');
            ob_start();
        }
        
        if (empty($form->storage['values']['settings']['fields'])) return;
        
        $fields = $form->storage['values']['settings']['fields'];
        
        $upload_dir = $this->getAddon('File')->getUploadDir();
        $thumbnail_dir = $this->getAddon('File')->getThumbnailDir();
        $file_config = $this->getAddon('File')->getConfig();
        
        $files_regenerated = $files_failed = 0;
        $file_ids = array();

        foreach ($fields as $field_name => $field_settings) {
            if (empty($field_settings['regenerate'])) continue;
            
            $sql = sprintf(
                'SELECT _file.file_id, _file.file_name, _file.file_title FROM %1$sentity_field_%2$s _field LEFT JOIN %1$sfile_file _file ON _field.file_id = _file.file_id GROUP BY _file.file_id ORDER BY _file.file_id',
                $this->getDB()->getResourcePrefix(),
                $field_name
            );
            $rs = $this->getDB()->query($sql)->getIterator();
            $rs->rewind();
            while ($rs->valid()) {
                list($file_id, $file_name, $file_title) = $rs->row();
                
                if (!isset($file_ids[$file_id])) {
                    $source_file = null;
                    foreach (array(
                        $upload_dir . '/' . $file_name, // original file
                        $upload_dir . '/l_' . $file_name // large-sized file
                    ) as $_source_file) {
                        if (file_exists($_source_file)) {
                            $source_file = $_source_file;
                            break;
                        }
                    }
                
                    if ($source_file && file_exists($source_file)) {
                        try {
                            $this->getPlatform()->resizeImage(
                                $source_file,
                                $thumbnail_dir . '/' . $file_name,
                                $file_config['thumbnail_width'],
                                $file_config['thumbnail_height'],
                                true, // crop
                                false // enlarge
                            );
                            $this->getPlatform()->resizeImage(
                                $source_file,
                                $upload_dir . '/m_' . $file_name,
                                $file_config['image_medium_width'],
                                null,
                                false,
                                true
                            );
                            $this->getPlatform()->resizeImage(
                                $source_file,
                                $upload_dir . '/l_' . $file_name,
                                $file_config['image_large_width'],
                                null,
                                false,
                                true
                            );
                    
                            ++$files_regenerated;
                            $ret_msg = __('Regenerate successful', 'sabai');
                            $ret_status = 'success';
                            $thumbnail_url = $this->File_ThumbnailUrl($file_name, true);
                        } catch (Exception $e) {
                            ++$files_failed;
                            $ret_msg = $e->getMessage();
                            $ret_status = 'danger';
                            $thumbnail_url = null;
                        }
                    } else {    
                        ++$files_failed;
                        $ret_msg = __('File does not exist', 'sabai');
                        $ret_status = 'danger';
                        $thumbnail_url = null;
                        // Delete record from the field table
                        $sql = sprintf(
                            'DELETE FROM %sentity_field_%s WHERE file_id = %d',
                            $this->getDB()->getResourcePrefix(),
                            $field_name,
                            $file_id
                        );
                        $this->getDB()->exec($sql);
                    }
                
                    $file_ids[$file_id] = true;
                } else {
                    // Already regenerated
                    ++$files_regenerated;
                    $ret_msg = __('Regenerate successful', 'sabai') . ' *';
                    $ret_status = 'success';
                    $thumbnail_url = $this->File_ThumbnailUrl($file_name);
                }
                
                if ($this->_flush) {
                    echo json_encode(array(
                        'id' => $file_id,
                        'title' => Sabai::h($file_title),
                        'message' => Sabai::h($ret_msg),
                        'status' => $ret_status,
                        'name' => $file_name,
                        'thumbnail' => $thumbnail_url,
                    ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                    ob_flush();
                    flush();
                    sleep(1);
                }

                $rs->next();
            }
        }
        
        $form->storage['files_regenerated'] = $files_regenerated;
        $form->storage['files_failed'] = $files_failed;
    }

    protected function _complete(Sabai_Context $context, array $formStorage)
    {
        $success = sprintf(__('%d image(s) regenerated successfullly.', 'sabai'), @$formStorage['files_regenerated']);
        $error = null;
        if (!empty($formStorage['files_failed'])) {
            $error = sprintf(__('Faield regenerating %d image(s).', 'sabai'), $formStorage['files_failed']);
        }
        if (!$this->_flush) {
            $context->addTemplate('form_results');
            $context->success = $success; 
            $context->error = $error;
        } else {            
            $context->setSuccess()->setSuccessAttributes(array(
                'id' => null,
                'success' => $success,
                'error' => $error,
            ));
        }
    }
}