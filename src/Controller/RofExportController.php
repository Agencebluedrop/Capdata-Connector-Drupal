<?php

namespace Drupal\capdata_connector\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\capdata_connector\CapDataConnectorManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * Returns responses for rof export routes.
 */
class RofExportController extends ControllerBase {

    /**
     * The CapData Connector manager.
     *
     * @var \Drupal\capdata_connector\CapDataConnectorManager
     */
    protected $capdataConnectorManager;

    /**
     * RofExportController constructor.
     *
     * @param \Drupal\capdata_connector\CapDataConnectorManager $capdata_connector_manager
     *   The CapData Connector manager.
     */
    public function __construct(CapDataConnectorManager $capdata_connector_manager) {
        $this->capdataConnectorManager = $capdata_connector_manager;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     *   The Drupal service container.
     *
     * @return static
     */
    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('capdata_connector.capdata_manager')
        );
    }

    /**
     * Builds the RDF export response.
     */
    public function capdataRdfExport() {
        $data = '';
        $file = getcwd() . '/.well-known/capdata-export.rdf';

        if (file_exists($file)) {
            $data = file_get_contents($file);
        }

        if (empty($data)) {
            $data = $this->capdataConnectorManager->dataExport();
        }

        $response = new Response();
        $response->setContent($data);

        $response->headers->set('Content-Type', 'application/rdf+xml');
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            'capdata-export.rdf'
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }
}