<?php
interface Sabai_Addon_CSV_IExporters
{
    public function csvGetExporterNames();
    public function csvGetExporter($exporterName);
}