<?php
class Sabai_Addon_CSV_Helper_Exporters extends Sabai_Helper
{
    /**
     * Returns all available exporters
     * @param Sabai $application
     */
    public function help(Sabai $application, $byFieldType = false, $useCache = true)
    {
        $cache_id = $byFieldType ? 'csv_exporters_by_field_type' : 'csv_exporters';
        if (!$useCache
            || (!$ret = $application->getPlatform()->getCache($cache_id))
        ) {
            $csv_config = $application->getAddon('CSV')->getConfig();
            $exporters = $exporters_by_field_type = array();
            foreach ($application->getInstalledAddonsByInterface('Sabai_Addon_CSV_IExporters') as $addon_name) {
                if (!$application->isAddonLoaded($addon_name)) continue;
                
                foreach ($application->getAddon($addon_name)->csvGetExporterNames() as $exporter_name) {
                    if (!$exporter = $application->getAddon($addon_name)->csvGetExporter($exporter_name)) {
                        continue;
                    }
                    $exporters[$exporter_name] = $addon_name;
                    
                    foreach ((array)$exporter->csvExporterInfo('field_types') as $field_type) {
                        if (!isset($exporters_by_field_type[$field_type])) {
                            $exporters_by_field_type[$field_type] = $exporter_name;
                        } else {
                            // More than one exporter for the field type
                            if (isset($csv_config['default_exporters'][$field_type])
                                && $csv_config['default_exporters'][$field_type] === $exporter_name
                            ) {
                                $exporters_by_field_type[$field_type] = $exporter_name;
                            }
                        }
                    }
                }
            }
            $application->getPlatform()->setCache($exporters, 'csv_exporters')
                ->setCache($exporters_by_field_type, 'csv_exporters_by_field_type');
            
            $ret = $byFieldType ? $exporters_by_field_type : $exporters;
        }

        return $ret;
    }
    
    private $_impls = array();

    /**
     * Gets an implementation of Sabai_Addon_CSV_IExporter interface for a given exporter type
     * @param Sabai $application
     * @param string $exporter
     */
    public function impl(Sabai $application, $exporter, $returnFalse = false)
    {
        if (!isset($this->_impls[$exporter])) {
            $exporters = $this->help($application);
            // Valid exporter type?
            if (!isset($exporters[$exporter])
                || (!$application->isAddonLoaded($exporters[$exporter]))
            ) {                
                if ($returnFalse) return false;
                throw new Sabai_UnexpectedValueException(sprintf('Invalid exporter type: %s', $exporter));
            }
            $this->_impls[$exporter] = $application->getAddon($exporters[$exporter])->csvGetExporter($exporter);
        }

        return $this->_impls[$exporter];
    }
}