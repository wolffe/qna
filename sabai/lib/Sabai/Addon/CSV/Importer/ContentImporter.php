<?php
class Sabai_Addon_CSV_Importer_ContentImporter extends Sabai_Addon_CSV_AbstractImporter
{
    protected function _csvImporterInfo()
    {
        switch ($this->_name) {
            case 'content_featured':
                $columns = array(
                    'value' => __('Priority', '@@sabai_package_name'),
                    'featured_at' => __('Featured Date', '@@sabai_package_name'),
                    'expires_at' => __('End Date', '@@sabai_package_name'),
                );
                break;
            case 'content_activity':
                $columns = array(
                    'active_at' => __('Last Active Date', '@@sabai_package_name'),
                    'edited_at' => __('Edited Date', '@@sabai_package_name'),
                );
                break;
            case 'content_guest_author':
                $columns = array(
                    'name' => __('Name', '@@sabai_package_name'),
                    'email' => __('E-mail Address', '@@sabai_package_name'),
                    'url' => __('Website URL', '@@sabai_package_name'),
                    'guid' => __('GUID', '@@sabai_package_name'),
                );
                break;
            default:
                $columns = null;
        }
        return array(
            'field_types' => array($this->_name),
            'columns' => $columns,
        );
    }


    public function csvImporterSupports(Sabai_Addon_Entity_Model_Bundle $bundle, Sabai_Addon_Entity_Model_Field $field)
    {
        switch ($this->_name) {
            case 'content_post_views':
                return empty($bundle->info['parent']) && (!isset($bundle->info['public']) || $bundle->info['public'] !== false);
            case 'content_post_slug':
                return empty($bundle->info['parent']);
        }
        return true;
    }

    public function csvImporterSettingsForm(Sabai_Addon_Entity_Model_Field $field, array $settings, $column, $enclosure, array $parents = array())
    {
        switch ($this->_name) {
            case 'content_post_published':
                return $this->_getDateFormatSettingsForm();
            case 'content_featured':
                return in_array($column, array('featured_at', 'expires_at')) ? $this->_getDateFormatSettingsForm() : null;
            case 'content_activity':
                return in_array($column, array('active_at', 'edited_at')) ? $this->_getDateFormatSettingsForm() : null;
            case 'content_children_count':
                return array(
                    'separator' => array(
                        '#type' => 'textfield',
                        '#title' => $title = __('Content type/count separator', 'sabai'),
                        '#description' => __('Enter the character used to separate the content type and count.', 'sabai'),
                        '#default_value' => '|',
                        '#horizontal' => true,
                        '#min_length' => 1,
                        '#required' => true,
                        '#weight' => 1,
                        '#size' => 5,
                    ),
                ) + $this->_acceptMultipleValues($enclosure, $parents, array('separator' => $title));
            case 'content_parent':
                return array(
                    'type' => array(
                        '#type' => 'select',
                        '#title' => __('Parent content ID type', 'sabai'),
                        '#description' => __('Select the type of data used to specify parent content items.', 'sabai'),
                        '#options' => array(
                            'id' => __('ID', 'sabai'),
                            'slug' => __('Slug', 'sabai'),
                        ),
                        '#default_value' => 'slug',
                        '#horizontal' => true,
                    ),
                );
        }
    }
    
    public function csvImporterDoImport(Sabai_Addon_Entity_Model_Field $field, array $settings, $column, $value)
    {
        switch ($this->_name) {
            case 'content_post_published':
                if ($settings['date_format'] === 'string') {
                    return false !== ($value = strtotime($value)) ? $value : null;
                }
                return $value;
            case 'content_featured':
                if (in_array($column, array('featured_at', 'expires_at'))) {
                    if ($settings['date_format'] === 'string'
                        && false === ($value = strtotime($value))
                    ) {
                        return null;
                    }
                }
                return array(array($column => $value));
            case 'content_activity':
                if (in_array($column, array('active_at', 'edited_at'))) {
                    if ($settings['date_format'] === 'string'
                        && false === ($value = strtotime($value))
                    ) {
                        return null;
                    }
                }
                return array(array($column => $value));
            case 'content_children_count':
                if (!empty($settings['_multiple'])) {
                    if (!$values = explode($settings['_separator'], $value)) {
                        return;
                    }
                } else {
                    $values = array($value);
                }
                $ret = array();
                foreach ($values as $value) {
                    if ($value = explode($settings['separator'], $value)) {
                        $ret[] = array(
                            'child_bundle_name' => $value[0],
                            'value' => $value[1],
                        );
                    }
                }
                return $ret;
            case 'content_parent':
                if ($settings['type'] === 'slug') {
                    if (!isset($this->_parentBundle)
                        && (!$this->_parentBundle = $this->_application->Entity_Bundle($field->Bundle->info['parent']))
                    ) return false;
                    
                    if (!$post = $this->_application->Entity_TypeImpl($this->_parentBundle->entitytype_name)->entityTypeGetEntityBySlug($this->_parentBundle->name, $value)) return;
                    return $post->getId();
                } else {
                    return $value;
                }   
            default:
                return $value;
        }
    }
}