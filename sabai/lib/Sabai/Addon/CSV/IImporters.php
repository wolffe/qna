<?php
interface Sabai_Addon_CSV_IImporters
{
    public function csvGetImporterNames();
    public function csvGetImporter($importerName);
}