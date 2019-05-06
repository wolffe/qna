<?php
class Sabai_Addon_CSV_Importer_TaxonomyImporter extends Sabai_Addon_CSV_AbstractImporter
{
    protected $_parentBundle, $_levelIds = array(), $_termTitles = array();
    
    protected function _csvImporterInfo()
    {
        return array(
            'field_types' => array($this->_name),
            'columns' => null,
        );
    }
    
    public function csvImporterSupports(Sabai_Addon_Entity_Model_Bundle $bundle, Sabai_Addon_Entity_Model_Field $field)
    {
        switch ($this->_name) {
            case 'taxonomy_term_name':
                return empty($bundle->info['parent']);
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
    
    public function csvImporterSettingsForm(Sabai_Addon_Entity_Model_Field $field, array $settings, $column, $enclosure, array $parents = array())
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
                    'create' => array(
                        '#type' => 'checkbox',
                        '#title' => __('Create non-existent terms', 'sabai'),
                        '#default_value' => true,
                        '#horizontal' => true,
                        '#states' => array(
                            'visible' => array(
                                sprintf('select[name="%s[type]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'value', 'value' => 'title'),
                            ),
                        ),
                    ),
                ) + $this->_acceptMultipleValues($enclosure, $parents);
            case 'taxonomy_content_count':
                $form = array(
                    'separator' => array(
                        '#type' => 'textfield',
                        '#title' => __('Content type/count separator', 'sabai'),
                        '#description' => __('Enter the character used to separate the content type and count.', 'sabai'),
                        '#default_value' => '|',
                        '#horizontal' => true,
                        '#min_length' => 1,
                        '#required' => true,
                        '#weight' => 1,
                    ),
                );
                $form += $this->_acceptMultipleValues($enclosure, $parents, array('separator' => $form['separator']['#title']));
                return $form;
        }
    }
    
    public function csvImporterDoImport(Sabai_Addon_Entity_Model_Field $field, array $settings, $column, $value)
    {
        switch ($this->_name) {
            case 'taxonomy_terms':
                $value = str_replace('&amp;', '&', $value);
                if (!empty($settings['_multiple'])) {
                    if (!$values = explode($settings['_separator'], $value)) {
                        return;
                    }
                } else {
                    $values = array($value);
                }
                $ret = array();
                switch ($settings['type']) {
                    case 'title':
                        if (!$bundle = $this->_application->Entity_Bundle($field->getFieldData('bundle_id'))) return;

                        if (!empty($this->_termTitles)) {
                            foreach (array_keys($values) as $i) {
                                if ($term_id = array_search(strtolower($values[$i]), $this->_termTitles)) {
                                    $ret[] = $term_id;
                                    unset($values[$i]);
                                }
                            }
                        }
                        if (!empty($values)) {
                            $terms = $this->_application->Entity_TypeImpl($bundle->entitytype_name)->entityTypeGetEntitiesByTitles($bundle->name, $values);
                            foreach ($terms as $term) {
                                $this->_termTitles[$term->getId()] = strtolower($term->getTitle());
                                $ret[] = $term->getId();
                            }
                            if ($settings['create']) {
                                foreach ($values as $title) {
                                    if (!in_array(strtolower($title), $this->_termTitles)) {
                                        try { 
                                            $term = $this->_application->Entity_Save($bundle, array('taxonomy_term_title' => $title, 'taxonomy_term_parent' => 0));
                                        } catch (Sabai_IException $e) {
                                            $this->_application->LogError($e);
                                            continue;
                                        }
                                        $this->_termTitles[$term->getId()] = strtolower($term->getTitle());
                                        $ret[] = $term->getId();
                                    }
                                }
                            }
                        }
                        break;
                    case 'slug':
                        if (!$bundle = $this->_application->Entity_Bundle($field->getFieldData('bundle_id'))) return;
                        
                        $terms = $this->_application->Entity_TypeImpl($bundle->entitytype_name)->entityTypeGetEntitiesBySlugs($bundle->name, $values);
                        foreach ($terms as $term) {
                            $ret[] = $term->getId();
                        }
                        break;
                    case 'id':
                        $ret = $values;
                        break;
                }
                return $ret;  
            case 'taxonomy_term_parent':
                switch ($settings['type']) {
                    case 'slug':
                        $bundle = $field->Bundle;
                        if (!$term = $this->_application->Entity_TypeImpl($bundle->entitytype_name)->entityTypeGetEntityBySlug($bundle->name, $value)) return;

                        return $term->getId();
                    case 'title':
                        $bundle = $field->Bundle;
                        if (!$term = $this->_application->Entity_TypeImpl($bundle->entitytype_name)->entityTypeGetEntityByTitle($bundle->name, $value)) return;

                        return $term->getId();
                    default:
                        return $value;
                }
            case 'taxonomy_content_count':
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
                            'content_bundle_name' => $value[0],
                            'value' => $value[1],
                        );
                    }
                }
                return $ret;
            default:
                return $value;
        }
    }
}