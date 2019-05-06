<?php
class Sabai_Addon_CSV_Helper_Importers extends Sabai_Helper
{
    /**
     * Returns all available importers
     * @param Sabai $application
     */
    public function help(Sabai $application, $byFieldType = false, $useCache = true)
    {
        $cache_id = $byFieldType ? 'csv_importers_by_field_type' : 'csv_importers';
        if (!$useCache
            || (!$ret = $application->getPlatform()->getCache($cache_id))
        ) {
            $csv_config = $application->getAddon('CSV')->getConfig();
            $importers = $importers_by_field_type = array();
            foreach ($application->getInstalledAddonsByInterface('Sabai_Addon_CSV_IImporters') as $addon_name) {
                if (!$application->isAddonLoaded($addon_name)) continue;
                
                foreach ($application->getAddon($addon_name)->csvGetImporterNames() as $importer_name) {
                    if (!$importer = $application->getAddon($addon_name)->csvGetImporter($importer_name)) {
                        continue;
                    }
                    $importers[$importer_name] = $addon_name;
                    
                    foreach ((array)$importer->csvImporterInfo('field_types') as $field_type) {
                        if (!isset($importers_by_field_type[$field_type])) {
                            $importers_by_field_type[$field_type] = $importer_name;
                        } else {
                            // More than one importer for the field type
                            if (isset($csv_config['default_importers'][$field_type])
                                && $csv_config['default_importers'][$field_type] === $importer_name
                            ) {
                                $importers_by_field_type[$field_type] = $importer_name;
                            }
                        }
                    }
                }
            }
            $application->getPlatform()->setCache($importers, 'csv_importers')
                ->setCache($importers_by_field_type, 'csv_importers_by_field_type');
            
            $ret = $byFieldType ? $importers_by_field_type : $importers;
        }

        return $ret;
    }
    
    private $_impls = array();

    /**
     * Gets an implementation of Sabai_Addon_CSV_IImporter interface for a given importer type
     * @param Sabai $application
     * @param string $importer
     */
    public function impl(Sabai $application, $importer, $returnFalse = false)
    {
        if (!isset($this->_impls[$importer])) {
            $importers = $this->help($application);
            // Valid importer type?
            if (!isset($importers[$importer])
                || (!$application->isAddonLoaded($importers[$importer]))
            ) {                
                if ($returnFalse) return false;
                throw new Sabai_UnexpectedValueException(sprintf('Invalid importer type: %s', $importer));
            }
            $this->_impls[$importer] = $application->getAddon($importers[$importer])->csvGetImporter($importer);
        }

        return $this->_impls[$importer];
    }
}