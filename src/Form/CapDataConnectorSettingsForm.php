<?php

namespace Drupal\capdata_connector\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\capdata_connector\CapDataConnectorManager;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Configure CapData Connector settings.
 */
class CapDataConnectorSettingsForm extends ConfigFormBase {

  /**
   * The CapData Connector manager.
   *
   * @var \Drupal\capdata_connector\CapDataConnectorManager
   */
  protected $capdataConnectorManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;  

  /**
   * CapData Connector Settings form constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\capdata_connector\CapDataConnectorManager $capdata_connector_manager
   *   The CapData Connector manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CapDataConnectorManager $capdata_connector_manager, RequestStack $request_stack) {
    parent::__construct($config_factory);
    $this->capdataConnectorManager = $capdata_connector_manager;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('capdata_connector.capdata_manager'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'capdata_connector_settings_form';
  }

  /**
   * {@inheritdoc}
   *
   * @return string[]
   *   The editable config names.
   */
  protected function getEditableConfigNames() {
    return [
      'capdata_connector.settings',
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @param mixed[] $form
   *   The settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return mixed[]
   *   The settings form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('capdata_connector.settings');
    $taxonomiesArray =  $this->capdataConnectorManager->getVocabularyList();
    $contentTypesArray =  $this->capdataConnectorManager->getContentTypesList();
    $capDataClasses = $this->capdataConnectorManager->getCapDataClassesInfo();

    $form['capdata_opera_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Capdata url de l'opéra"),
      '#default_value' => $config->get('capdata_opera_url'),
      '#description' => $this->t('ex: https://capdataculture.fr/graph/identifier/xxxx'),
    ];

    foreach ($capDataClasses as $capDataClassUniqueId => $capDataClassFullData) {
      $form[$capDataClassUniqueId.'_info'] = [
        '#type' => 'details',
        '#title' => $this->t($capDataClassFullData['capdata_full_name']),
        '#weight' => 1,
        '#collapsible' => TRUE,
      ];
      $form[$capDataClassUniqueId.'_info'][$capDataClassUniqueId.'_include_in_export'] = [
        '#type' => 'checkbox',
        '#title' => $this->t("Inclure cette classe dans l'export"),
        '#default_value' => $config->get($capDataClassUniqueId.'_include_in_export'),
        '#description' => $this->t("Cocher cette case pour inclure cette classe dans l'export au format RDF")
      ];
      $form[$capDataClassUniqueId.'_info'][$capDataClassUniqueId.'_mapping_type'] = [
        '#type' => 'radios',
        '#title' => $this->t('Mapper cette classe à une entité Drupal:'),
        '#options' => [
          $capDataClassUniqueId.'_taxo_mapping' => $this->t('Taxonomie'),
          $capDataClassUniqueId.'_content_mapping' => $this->t('Type de contenu'),
        ],
        '#default_value' => $config->get($capDataClassUniqueId.'_mapping_type'),
        '#attributes' => ['data-capdata-class-identifier' => $capDataClassUniqueId, 'data-capdata-trigger-identifier' => $capDataClassUniqueId."_mapping_type_trigger"],
        '#ajax' => [
          'callback' => '::capdataWrapperInfoCallback',
          'wrapper'  => $capDataClassFullData['capdata_short_id'].'-info-wrapper',
          'progress' => [
            'type' => 'throbber',
            'message' => $this->t('Chargement en cours...'),
          ],
        ],
      ];

      $selectedMappingType = "";
      if (!empty($form_state->getValues())){
        $selectedMappingType = $form_state->getValue($capDataClassUniqueId.'_mapping_type');
      }else{
        $selectedMappingType = $config->get($capDataClassUniqueId.'_mapping_type');
      }

      $form[$capDataClassUniqueId.'_info'][$capDataClassUniqueId.'_info_container'] = [
        '#type' => 'container',
        '#attributes' => ['id' => $capDataClassFullData['capdata_short_id'].'-info-wrapper'],
      ];
      if(!empty($selectedMappingType)){
        if($selectedMappingType == $capDataClassUniqueId."_taxo_mapping"){
          $form[$capDataClassUniqueId.'_info'][$capDataClassUniqueId.'_info_container'][$capDataClassUniqueId.'_taxonomy_dropdown'] = [
            '#type' => 'select',
            '#title' => $this->t('Choisir la taxonomie correspondante:'), 
            '#options' => $taxonomiesArray,
            '#empty_option' => $this->t('-Sélectionner-'),
            '#default_value' => !empty($form_state->getValue($capDataClassUniqueId.'_taxonomy_dropdown')) ? $form_state->getValue($capDataClassUniqueId.'_taxonomy_dropdown') : $config->get($capDataClassUniqueId.'_taxonomy_dropdown'),
            '#attributes' => ['data-capdata-class-identifier' => $capDataClassUniqueId, 'data-capdata-trigger-identifier' => $capDataClassUniqueId."_taxonomy_dropdown_trigger"],
            // Il va falloir charger les champs de la taxonomie sélectionnée
            '#ajax' => [
              'callback' => '::capdataTaxonomyFieldsDropdownCallback',
              'wrapper'  => $capDataClassFullData['capdata_short_id'].'-properties-mappingcontainer',
            ],
          ];
          // Mapping des propriétés de la classe CapData aux champs de la taxonomie sélectionnée.
          $form[$capDataClassUniqueId.'_info'][$capDataClassUniqueId.'_info_container'][$capDataClassUniqueId.'_properties_mapping'] = [
            '#type' => 'details',
            '#title' => $this->t("Mapping des champs de la classe ".$capDataClassFullData['capdata_full_name']),
            '#collapsible' => TRUE,
          ];

          $selectedTaxonomy = "";
          if (!empty($form_state->getValues())){
            $selectedTaxonomy = $form_state->getValue($capDataClassUniqueId.'_taxonomy_dropdown');
            $triggering_element = $form_state->getTriggeringElement();
            if(!empty($triggering_element)){
              $triggeringElementIdentifier = $triggering_element['#attributes']['data-capdata-trigger-identifier'];
              if(!empty($triggeringElementIdentifier)){
                if($triggeringElementIdentifier == $capDataClassUniqueId."_mapping_type_trigger" && empty($selectedTaxonomy)){
                  $selectedTaxonomy = $config->get($capDataClassUniqueId.'_taxonomy_dropdown');
                }
              }
            }
          }else{
            $selectedTaxonomy = $config->get($capDataClassUniqueId.'_taxonomy_dropdown');
          }

          $form[$capDataClassUniqueId.'_info'][$capDataClassUniqueId.'_info_container'][$capDataClassUniqueId.'_properties_mapping'][$capDataClassUniqueId.'_properties_mapping_container'] = [
            '#type' => 'container',
            '#attributes' => ['id' => $capDataClassFullData['capdata_short_id'].'-properties-mappingcontainer'],
          ];
          if (!empty($selectedTaxonomy)) {
            foreach ($capDataClassFullData['capdata_properties'] as $propertyKey => $propertyName) {            
              // Propriété 1, Propriété 2, etc.
              $form[$capDataClassUniqueId.'_info'][$capDataClassUniqueId.'_info_container'][$capDataClassUniqueId.'_properties_mapping'][$capDataClassUniqueId.'_properties_mapping_container'][$capDataClassUniqueId.'_'.$propertyKey.'_fieldset'] = [
                '#type' => 'fieldset',
                '#attributes' => [
                  'class' => ['single-capdata-property-fieldset']
                ],
              ];
              $form[$capDataClassUniqueId.'_info'][$capDataClassUniqueId.'_info_container'][$capDataClassUniqueId.'_properties_mapping'][$capDataClassUniqueId.'_properties_mapping_container'][$capDataClassUniqueId.'_'.$propertyKey.'_fieldset'][$capDataClassUniqueId.'_taxo_'.$propertyKey.'_name'] = [
                '#type' => 'markup',
                '#markup' => '<strong>'.$this->t($propertyName).': </strong>',
              ];
              $form[$capDataClassUniqueId.'_info'][$capDataClassUniqueId.'_info_container'][$capDataClassUniqueId.'_properties_mapping'][$capDataClassUniqueId.'_properties_mapping_container'][$capDataClassUniqueId.'_'.$propertyKey.'_fieldset'][$capDataClassUniqueId.'_taxo_'.$propertyKey.'_fields_dropdown'] = [
                '#type' => 'select',
                '#title' => $this->t('Choisir un champ de la taxonomie  <br>'). $taxonomiesArray[$selectedTaxonomy],
                '#options' => $this->capdataConnectorManager->getFieldsOptionsByTaxonomy($selectedTaxonomy),
                '#default_value' => !empty($form_state->getValue($capDataClassUniqueId.'_taxo_'.$propertyKey.'_fields_dropdown')) ? $form_state->getValue($capDataClassUniqueId.'_taxo_'.$propertyKey.'_fields_dropdown') : $config->get($capDataClassUniqueId.'_taxo_'.$propertyKey.'_fields_dropdown'),
                '#empty_option' => $this->t('-Sélectionner-'),
              ];
              $form[$capDataClassUniqueId.'_info'][$capDataClassUniqueId.'_info_container'][$capDataClassUniqueId.'_properties_mapping'][$capDataClassUniqueId.'_properties_mapping_container'][$capDataClassUniqueId.'_'.$propertyKey.'_fieldset'][$capDataClassUniqueId.'_taxo_'.$propertyKey.'_custom_processing'] = [
                '#type' => 'select',
                '#title' => $this->t('Choisir un traitement spécifique <br>  pour la valeur de ce champ'),
                '#options' => $this->capdataConnectorManager->getSpecialProcessingOptions(),
                '#default_value' => !empty($form_state->getValue($capDataClassUniqueId.'_taxo_'.$propertyKey.'_custom_processing')) ? $form_state->getValue($capDataClassUniqueId.'_taxo_'.$propertyKey.'_custom_processing') : $config->get($capDataClassUniqueId.'_taxo_'.$propertyKey.'_custom_processing'),
                '#empty_option' => $this->t('-Sélectionner-'),
              ];
              $form[$capDataClassUniqueId.'_info'][$capDataClassUniqueId.'_info_container'][$capDataClassUniqueId.'_properties_mapping'][$capDataClassUniqueId.'_properties_mapping_container'][$capDataClassUniqueId.'_'.$propertyKey.'_fieldset'][$capDataClassUniqueId.'_taxo_'.$propertyKey.'_comments'] = [
                '#type' => 'textarea',
                '#title' => $this->t('Commentaire'),
                '#default_value' => !empty($form_state->getValue($capDataClassUniqueId.'_taxo_'.$propertyKey.'_comments')) ? $form_state->getValue($capDataClassUniqueId.'_taxo_'.$propertyKey.'_comments') : $config->get($capDataClassUniqueId.'_taxo_'.$propertyKey.'_comments'),
                '#description' => $this->t('Commentaires supplémentaires'),
              ];
            }
          }
        }elseif($selectedMappingType == $capDataClassUniqueId."_content_mapping"){
          $form[$capDataClassUniqueId.'_info'][$capDataClassUniqueId.'_info_container'][$capDataClassUniqueId.'_content_dropdown'] = [
            '#type' => 'select',
            '#title' => $this->t('Choisir le type de contenu correspondant:'), 
            '#options' => $contentTypesArray,
            '#empty_option' => $this->t('-Sélectionner-'),
            '#default_value' => !empty($form_state->getValue($capDataClassUniqueId.'_content_dropdown')) ? $form_state->getValue($capDataClassUniqueId.'_content_dropdown') : $config->get($capDataClassUniqueId.'_content_dropdown'),
            '#attributes' => ['data-capdata-class-identifier' => $capDataClassUniqueId, 'data-capdata-trigger-identifier' => $capDataClassUniqueId."_content_dropdown_trigger"],
            // Il va falloir charger les champs du type de contenu sélectionné
            '#ajax' => [
              'callback' => '::capdataContentFieldsDropdownCallback',
              'wrapper'  => $capDataClassFullData['capdata_short_id'].'-properties-mappingcontainer',
            ],
          ];
          // Mapping des propriétés de la classe CapData aux champs du type de contenu sélectionné.
          $form[$capDataClassUniqueId.'_info'][$capDataClassUniqueId.'_info_container'][$capDataClassUniqueId.'_properties_mapping'] = [
            '#type' => 'details',
            '#title' => $this->t("Mapping des champs de la classe ".$capDataClassFullData['capdata_full_name']),
            '#collapsible' => TRUE,
          ];

          $selectedContentType = "";
          if (!empty($form_state->getValues())){
            $selectedContentType = $form_state->getValue($capDataClassUniqueId.'_content_dropdown');
            $triggering_element = $form_state->getTriggeringElement();
            if(!empty($triggering_element)){
              $triggeringElementIdentifier = $triggering_element['#attributes']['data-capdata-trigger-identifier'];
              if(!empty($triggeringElementIdentifier)){
                if($triggeringElementIdentifier == $capDataClassUniqueId."_mapping_type_trigger" && empty($selectedContentType)){
                  $selectedContentType = $config->get($capDataClassUniqueId.'_content_dropdown');
                }
              }
            }
          }else{
            $selectedContentType = $config->get($capDataClassUniqueId.'_content_dropdown');
          }

          $form[$capDataClassUniqueId.'_info'][$capDataClassUniqueId.'_info_container'][$capDataClassUniqueId.'_properties_mapping'][$capDataClassUniqueId.'_properties_mapping_container'] = [
            '#type' => 'container',
            '#attributes' => ['id' => $capDataClassFullData['capdata_short_id'].'-properties-mappingcontainer'],
          ];
          if (!empty($selectedContentType)) {
            foreach ($capDataClassFullData['capdata_properties'] as $propertyKey => $propertyName) {            
              // Propriété 1, Propriété 2, etc.
              $form[$capDataClassUniqueId.'_info'][$capDataClassUniqueId.'_info_container'][$capDataClassUniqueId.'_properties_mapping'][$capDataClassUniqueId.'_properties_mapping_container'][$capDataClassUniqueId.'_'.$propertyKey.'_fieldset'] = [
                '#type' => 'fieldset',
                '#attributes' => [
                  'class' => ['single-capdata-property-fieldset']
                ],
              ];
              $form[$capDataClassUniqueId.'_info'][$capDataClassUniqueId.'_info_container'][$capDataClassUniqueId.'_properties_mapping'][$capDataClassUniqueId.'_properties_mapping_container'][$capDataClassUniqueId.'_'.$propertyKey.'_fieldset'][$capDataClassUniqueId.'_content_'.$propertyKey.'_name'] = [
                '#type' => 'markup',
                '#markup' => '<strong>'.$this->t($propertyName).': </strong>',
              ];
              $form[$capDataClassUniqueId.'_info'][$capDataClassUniqueId.'_info_container'][$capDataClassUniqueId.'_properties_mapping'][$capDataClassUniqueId.'_properties_mapping_container'][$capDataClassUniqueId.'_'.$propertyKey.'_fieldset'][$capDataClassUniqueId.'_content_'.$propertyKey.'_fields_dropdown'] = [
                '#type' => 'select',
                '#title' => $this->t('Choisir un champ du type de contenu <br> '). $contentTypesArray[$selectedContentType],
                '#options' => $this->capdataConnectorManager->getFieldsOptionsByContentType($selectedContentType),
                '#default_value' => !empty($form_state->getValue($capDataClassUniqueId.'_content_'.$propertyKey.'_fields_dropdown')) ? $form_state->getValue($capDataClassUniqueId.'_content_'.$propertyKey.'_fields_dropdown') : $config->get($capDataClassUniqueId.'_content_'.$propertyKey.'_fields_dropdown'),
                '#empty_option' => $this->t('-Sélectionner-'),
              ];
              $form[$capDataClassUniqueId.'_info'][$capDataClassUniqueId.'_info_container'][$capDataClassUniqueId.'_properties_mapping'][$capDataClassUniqueId.'_properties_mapping_container'][$capDataClassUniqueId.'_'.$propertyKey.'_fieldset'][$capDataClassUniqueId.'_content_'.$propertyKey.'_custom_processing'] = [
                '#type' => 'select',
                '#title' => $this->t('Choisir un traitement spécifique <br> pour la valeur de ce champ'),
                '#options' => $this->capdataConnectorManager->getSpecialProcessingOptions(),
                '#default_value' => !empty($form_state->getValue($capDataClassUniqueId.'_content_'.$propertyKey.'_custom_processing')) ? $form_state->getValue($capDataClassUniqueId.'_content_'.$propertyKey.'_custom_processing') : $config->get($capDataClassUniqueId.'_content_'.$propertyKey.'_custom_processing'),
                '#empty_option' => $this->t('-Sélectionner-'),
              ];
              $form[$capDataClassUniqueId.'_info'][$capDataClassUniqueId.'_info_container'][$capDataClassUniqueId.'_properties_mapping'][$capDataClassUniqueId.'_properties_mapping_container'][$capDataClassUniqueId.'_'.$propertyKey.'_fieldset'][$capDataClassUniqueId.'_content_'.$propertyKey.'_comments'] = [
                '#type' => 'textarea',
                '#title' => $this->t('Commentaire'),
                '#default_value' => !empty($form_state->getValue($capDataClassUniqueId.'_content_'.$propertyKey.'_comments')) ? $form_state->getValue($capDataClassUniqueId.'_content_'.$propertyKey.'_comments') : $config->get($capDataClassUniqueId.'_content_'.$propertyKey.'_comments'),
                '#description' => $this->t('Commentaires supplémentaires'),
              ];
            }
          }
        }
      }
    }
    $form['#attached']['library'][] = 'capdata_connector/capdata_settingsstyles';
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @param mixed[] $form
   *   The settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('capdata_connector.settings');
    $capDataClasses = $this->capdataConnectorManager->getCapDataClassesInfo();
    foreach ($capDataClasses as $capDataClassUniqueId => $capDataClassFullData) {
      $config
      ->set($capDataClassUniqueId.'_include_in_export', $form_state->getValue($capDataClassUniqueId.'_include_in_export'))
      ->set($capDataClassUniqueId.'_mapping_type', $form_state->getValue($capDataClassUniqueId.'_mapping_type'))
      ->set($capDataClassUniqueId.'_taxonomy_dropdown', $form_state->getValue($capDataClassUniqueId.'_taxonomy_dropdown'))
      ->set($capDataClassUniqueId.'_content_dropdown', $form_state->getValue($capDataClassUniqueId.'_content_dropdown'));
      foreach ($capDataClassFullData['capdata_properties'] as $propertyKey => $propertyName) { 
        $config   
        ->set($capDataClassUniqueId.'_taxo_'.$propertyKey.'_fields_dropdown', $form_state->getValue($capDataClassUniqueId.'_taxo_'.$propertyKey.'_fields_dropdown'))
        ->set($capDataClassUniqueId.'_taxo_'.$propertyKey.'_custom_processing', $form_state->getValue($capDataClassUniqueId.'_taxo_'.$propertyKey.'_custom_processing'))
        ->set($capDataClassUniqueId.'_taxo_'.$propertyKey.'_comments', $form_state->getValue($capDataClassUniqueId.'_taxo_'.$propertyKey.'_comments'))
        ->set($capDataClassUniqueId.'_content_'.$propertyKey.'_fields_dropdown', $form_state->getValue($capDataClassUniqueId.'_content_'.$propertyKey.'_fields_dropdown'))
        ->set($capDataClassUniqueId.'_content_'.$propertyKey.'_custom_processing', $form_state->getValue($capDataClassUniqueId.'_content_'.$propertyKey.'_custom_processing'))
        ->set($capDataClassUniqueId.'_content_'.$propertyKey.'_comments', $form_state->getValue($capDataClassUniqueId.'_content_'.$propertyKey.'_comments'));
      }
    }
    $host = "";
    $currentRequest = $this->requestStack->getCurrentRequest();
    if(!empty($currentRequest)){
      $host = $currentRequest->getSchemeAndHttpHost();
    }
    $config->set('capdata_connector_host', $host);
    $config->set('capdata_opera_url', $form_state->getValue('capdata_opera_url'));
    $config->save();
    parent::submitForm($form, $form_state);
  }



  /**
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   */
  public function capdataWrapperInfoCallback(array $form, FormStateInterface $form_state) {
    $triggeringElement = $form_state->getTriggeringElement();
    if(!empty($triggeringElement)){
      $capdataClassIdAttr = $triggeringElement['#attributes']['data-capdata-class-identifier'];
      $capdataClassId = $capdataClassIdAttr."_info";
      $capdataClassInfoContainer = $capdataClassIdAttr."_info_container";
      return $form[$capdataClassId][$capdataClassInfoContainer];
    }else{
      return [];
    }
  }

  /**
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   */
  public function capdataTaxonomyFieldsDropdownCallback(array $form, FormStateInterface $form_state) {
    $triggeringElement = $form_state->getTriggeringElement();
    if(!empty($triggeringElement)){
      $capdataClassIdAttr = $triggeringElement['#attributes']['data-capdata-class-identifier'];
      $capdataClassId = $capdataClassIdAttr."_info";
      $capdataClassInfoContainer = $capdataClassIdAttr."_info_container";
      $capdataClassPropertiesMapping = $capdataClassIdAttr."_properties_mapping";
      $capdataClassPropertiesMappingContainer = $capdataClassIdAttr."_properties_mapping_container";
      return $form[$capdataClassId][$capdataClassInfoContainer][$capdataClassPropertiesMapping][$capdataClassPropertiesMappingContainer];
    }else{
      return [];
    }
  }

  /**
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   */
  public function capdataContentFieldsDropdownCallback(array $form, FormStateInterface $form_state) {
    $triggeringElement = $form_state->getTriggeringElement();
    if(!empty($triggeringElement)){
      $capdataClassIdAttr = $triggeringElement['#attributes']['data-capdata-class-identifier'];
      $capdataClassId = $capdataClassIdAttr."_info";
      $capdataClassInfoContainer = $capdataClassIdAttr."_info_container";
      $capdataClassPropertiesMapping = $capdataClassIdAttr."_properties_mapping";
      $capdataClassPropertiesMappingContainer = $capdataClassIdAttr."_properties_mapping_container";
      return $form[$capdataClassId][$capdataClassInfoContainer][$capdataClassPropertiesMapping][$capdataClassPropertiesMappingContainer];
    }else{
      return [];
    }
  }
}