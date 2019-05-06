<?php 
class Sabai_Addon_CSV extends Sabai_Addon
    implements Sabai_Addon_System_IAdminRouter,
               Sabai_Addon_CSV_IExporters,
               Sabai_Addon_CSV_IImporters
{
    const VERSION = '1.4.6', PACKAGE = 'sabai';
    
    public function systemGetAdminRoutes()
    {
        $routes = array();
        foreach ($this->_application->getModel('Bundle', 'Entity')->fetch() as $bundle) {
            if (!$this->_application->isAddonLoaded($bundle->addon)
                || !$this->_isCsvEnabled($bundle)
            ) continue;
            
            $routes[$bundle->getAdminPath() . '/import'] = array(
                'controller' => 'Import',
                'title' => __('Import', 'sabai'),
                'callback_path' => 'import',
                'weight' => 10,
                'priority' => 6,
            );
            $routes[$bundle->getAdminPath() . '/export'] = array(
                'controller' => 'Export',
                'title' => __('Export', 'sabai'),
                'callback_path' => 'export',
                'weight' => 11,
                'priority' => 6,
            );
            $routes[$bundle->getAdminPath() . '/export/download'] = array(
                'controller' => 'DownloadExported',
                'type' => Sabai::ROUTE_CALLBACK,
            );
        }

        return $routes;
    }

    public function systemOnAccessAdminRoute(Sabai_Context $context, $path, $accessType, array &$route){}

    public function systemGetAdminRouteTitle(Sabai_Context $context, $path, $title, $titleType, array $route){}
    
    protected function _isCsvEnabled($bundle)
    {
        return empty($bundle->info['csv_disable']);
    }

    private function _onEntityBundlesChange()
    {
        $this->_application->getAddon('System')->reloadRoutes($this, true);
    }
    
    public function onEntityCreateBundlesSuccess($bundles)
    {
        $this->_onEntityBundlesChange();
    }
    
    public function onEntityUpdateBundlesSuccess($bundles)
    {
        $this->_onEntityBundlesChange();
    }
    
    public function onEntityDeleteBundlesSuccess($bundles)
    {
        $this->_onEntityBundlesChange();
    }

    public function getExportDir()
    {
        return isset($this->_config['export_dir']) ? $this->_config['export_dir'] : $this->getVarDir('export');
    }
        
    public function getImportDir()
    {
        return isset($this->_config['import_dir']) ? $this->_config['import_dir'] : $this->getVarDir('import');
    }
    
    public function hasVarDir()
    {
        return array('export', 'import');
    }
    
    public function csvGetExporterNames()
    {
        return array(
            'content_activity', 'content_children_count', 'content_featured', 'content_guest_author', 'content_parent',
            'content_post_id', 'content_post_published', 'content_post_slug', 'content_post_title', 'content_post_user_id',
            'content_post_status', 'content_post_views', 'content_reference',
            'date_timestamp',
            'field_string', 'field_text', 'field_boolean', 'field_number', 'field_choice', 'field_email', 'field_phone',
            'field_user', 'field_link', 'field_video', 'field_range',
            'social_accounts',
            'time_time',
            'voting_rating', 'voting_favorite', 'voting_updown', 'voting_helpful',
            'file_image', 'file_file',
            'taxonomy_terms', 'taxonomy_content_count', 'taxonomy_term_id', 'taxonomy_term_name', 'taxonomy_term_parent', 'taxonomy_term_title'
        );
    }
    
    public function csvGetExporter($name)
    {
        if (strpos($name, 'content_') === 0) {
            return new Sabai_Addon_CSV_Exporter_ContentExporter($this->_application, $name);
        }
        if (strpos($name, 'taxonomy_') === 0) {
            return new Sabai_Addon_CSV_Exporter_TaxonomyExporter($this->_application, $name);
        }
        if (strpos($name, 'field_') === 0) {
            return new Sabai_Addon_CSV_Exporter_FieldExporter($this->_application, $name);
        }
        if (strpos($name, 'voting_') === 0) {
            return new Sabai_Addon_CSV_Exporter_VotingExporter($this->_application, $name);
        }
        if (strpos($name, 'file_') === 0) {
            return new Sabai_Addon_CSV_Exporter_FileExporter($this->_application, $name);
        }
        if (strpos($name, 'date_') === 0) {
            return new Sabai_Addon_CSV_Exporter_DateExporter($this->_application, $name);
        }
        if (strpos($name, 'social_') === 0) {
            return new Sabai_Addon_CSV_Exporter_SocialExporter($this->_application, $name);
        }
        if (strpos($name, 'time_') === 0) {
            return new Sabai_Addon_CSV_Exporter_TimeExporter($this->_application, $name);
        }
    }
    
    public function csvGetImporterNames()
    {
        return array(
            'content_activity', 'content_children_count', 'content_featured', 'content_guest_author', 'content_parent',
            'content_post_id', 'content_post_published', 'content_post_slug', 'content_post_title', 'content_post_user_id',
            'content_post_status', 'content_post_views', 'content_reference',
            'date_timestamp',
            'field_string', 'field_text', 'field_boolean', 'field_number', 'field_choice', 'field_email', 'field_phone',
            'field_user', 'field_link', 'field_video', 'field_range',
            'social_accounts',
            'time_time',
            'voting_rating', 'voting_favorite', 'voting_updown', 'voting_helpful',
            'file_image', 'file_file',
            'taxonomy_terms', 'taxonomy_content_count', 'taxonomy_term_id', 'taxonomy_term_name', 'taxonomy_term_parent', 'taxonomy_term_title'
        );
    }
    
    public function csvGetImporter($name)
    {
        if (strpos($name, 'content_') === 0) {
            return new Sabai_Addon_CSV_Importer_ContentImporter($this->_application, $name);
        }
        if (strpos($name, 'taxonomy_') === 0) {
            return new Sabai_Addon_CSV_Importer_TaxonomyImporter($this->_application, $name);
        }
        if (strpos($name, 'field_') === 0) {
            return new Sabai_Addon_CSV_Importer_FieldImporter($this->_application, $name);
        }
        if (strpos($name, 'voting_') === 0) {
            return new Sabai_Addon_CSV_Importer_VotingImporter($this->_application, $name);
        }
        if (strpos($name, 'file_') === 0) {
            return new Sabai_Addon_CSV_Importer_FileImporter($this->_application, $name);
        }
        if (strpos($name, 'date_') === 0) {
            return new Sabai_Addon_CSV_Importer_DateImporter($this->_application, $name);
        }
        if (strpos($name, 'social_') === 0) {
            return new Sabai_Addon_CSV_Importer_SocialImporter($this->_application, $name);
        }
        if (strpos($name, 'time_') === 0) {
            return new Sabai_Addon_CSV_Importer_TimeImporter($this->_application, $name);
        }
    }

    public function onContentAdminPostsLinksFilter(&$links, $bundle)
    {
        $this->_addCsvLinks($links, $bundle);
    }
    
    public function onTaxonomyAdminTermsLinksFilter(&$links, $bundle)
    {
        $this->_addCsvLinks($links, $bundle);
    }
    
    protected function _addCsvLinks(&$links, $bundle)
    {
        if (!$this->_isCsvEnabled($bundle)) return;
        
        foreach (array('import' => __('Import', 'sabai'), 'export' => __('Export', 'sabai')) as $key => $label) {
            $links[] = $this->_application->LinkTo(
                $label,
                $this->_application->Url($bundle->getAdminPath() . '/' . $key),
                array('icon' => 'table'),
                array('class' => 'sabai-btn sabai-btn-primary sabai-btn-sm')
            );
        }
    }
}