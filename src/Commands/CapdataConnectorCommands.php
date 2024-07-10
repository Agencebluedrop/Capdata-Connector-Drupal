<?php

namespace Drupal\capdata_connector\Commands;

use Drush\Commands\DrushCommands;
use Drupal\capdata_connector\CapDataConnectorManager;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class CapdataConnectorCommands extends DrushCommands
{

    /**
     * The CapData Connector manager.
     *
     * @var \Drupal\capdata_connector\CapDataConnectorManager
     */
    protected $capdataConnectorManager;

    /**
     * CapdataConnectorCommands constructor.
     *
     * @param \Drupal\capdata_connector\CapDataConnectorManager $capdata_connector_manager
     *   The CapData Connector manager.
     */
    public function __construct(CapDataConnectorManager $capdata_connector_manager) {
        $this->capdataConnectorManager = $capdata_connector_manager;
    }

    /**
     * Generate RDF data export file (capdata-export.rdf).
     *
     * @command capdata_connector:rdf-export
     * @aliases capdata-rdf-export
     */
    public function rdfExport()
    {
        $this->logger()->notice('Starting export.');

        $data = $this->capdataConnectorManager->dataExport();
        $file = getcwd() . '/.well-known/capdata-export.rdf';
        $compressedFile = $file . '.gz';

        if (file_put_contents($file, $data)) {
            $this->logger()->notice('File capdata-export.rdf generated.');
            
            // Open the gz file (w9 is the highest compression)
            $fp = gzopen($compressedFile, 'w9');
            
            // Compress the file
            gzwrite($fp, file_get_contents($file));
            
            // Close the gz file
            gzclose($fp);

            $this->logger()->notice('File capdata-export.rdf compressed.');
        } else {
            $this->logger()->error('File capdata-export.rdf could not be generated.');
        }
    }
}
