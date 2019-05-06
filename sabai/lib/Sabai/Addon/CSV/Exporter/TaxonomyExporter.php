<?php
class Sabai_Addon_CSV_Exporter_TaxonomyExporter extends Sabai_Addon_CSV_AbstractExporter
{    
    public function csvExporterSupports(Sabai_Addon_Entity_Model_Bundle $bundle, Sabai_Addon_Entity_Model_Field $field)
    {
        switch ($this->_name) {
            case 'taxonomy_terms':
                return (bool)$this->_application->getModel('Bundle', 'Entity')
                    ->entitytypeName_is('taxonomy')
                    ->id_is($field->getFieldData('bundle_id'))
                    ->fetchOne();
            case 'taxonomy_term_parent':
                return !empty($bundle->info['taxonomy_hierarchical']);
        }
        return true;
    }
    
    public function csvExporterSettingsForm(Sabai_Addon_Entity_Model_Field $field, array $settings, $column, $enclosure, array $parents = array())
    {
        switch ($this->_name) {
            case 'taxonomy_term_parent':
                return array(
                    'type' => array(
                        '#type' => 'select',
                        '#title' => __('Parent term data type', 'sabai'),
                        '#description' => __('Select the type of data used to specify terms.', 'sabai'),
                        '#options' => array(
                            'id' => __('ID', 'sabai'),
                            'slug' => __('Slug', 'sabai'),
                            'title' => __('Title', 'sabai'),
                        ),
                        '#default_value' => 'slug',
                        '#horizontal' => true,
                    ),
                );  
            case 'taxonomy_terms':
                return array(
                    'type' => array(
                        '#type' => 'select',
                        '#title' => __('Taxonomy term data type', 'sabai'),
                        '#description' => __('Select the type of data used to specify terms.', 'sabai'),
                        '#options' => array(
                            'id' => __('ID', 'sabai'),
                            'slug' => __('Slug', 'sabai'),
                            'title' => __('Title', 'sabai'),
                        ),
                        '#default_value' => 'slug',
                        '#horizontal' => true,
                    ),
                ) + $this->_acceptMultipleValues($enclosure, $parents);
            case 'taxonomy_content_count':
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
                    ),
                ) + $this->_acceptMultipleValues($enclosure, $parents, array('separator' => $title));
        }
    }
    
    public function csvExporterDoExport(Sabai_Addon_Entity_Model_Field $field, array $settings, $value, array $columns, array &$files)
    {
        switch ($this->_name) {
            case 'taxonomy_term_parent':
                if (empty($value)
                    || (!$parent = $this->_application->Entity_Entity($field->Bundle->entitytype_name, $value, false))
                ) return '';
                
                switch ($settings['type']) {
                    case 'slug':
                        $method = 'getSlug';
                        break;
                    case 'title':
                        $method = 'getTitle';
                        break;
                    case 'id':
                    default:
                        $method = 'getId';
                        break;
                }
                return $parent->$method();
            case 'taxonomy_terms':
                switch ($settings['type']) {
                    case 'slug':
                        $method = 'getSlug';
                        break;
                    case 'title':
                        $method = 'getTitle';
                        break;
                    case 'id':
                    default:
                        $method = 'getId';
                        break;
                }
                $ret = array();
                foreach ($value as $_value) {
                    $ret[] = $_value->$method();
                }
                return isset($settings['_separator']) ? implode($settings['_separator'], $ret) : $ret[0];
            case 'taxonomy_content_count':
                $ret = array();
                foreach ($value[0] as $content_bundle_name => $count) {
                    if (strpos($content_bundle_name, '_') === 0) continue;
                    
                    $ret[] = $content_bundle_name . $settings['separator'] . $count;
                }
                return isset($settings['_separator']) ? implode($settings['_separator'], $ret) : $ret[0];
            default:
                return $value;
        }
    }
}