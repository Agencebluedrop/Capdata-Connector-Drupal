<?php

namespace Drupal\capdata_connector;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use CapDataOpera\PhpSdk\Graph\Graph;
use CapDataOpera\PhpSdk\Serializer\Serializer;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\node\NodeInterface;
use CapDataOpera\PhpSdk\Model\AdressePostale;
use CapDataOpera\PhpSdk\Model\ArkBnf;
use CapDataOpera\PhpSdk\Model\Auteur;
use CapDataOpera\PhpSdk\Model\CategorieOeuvre;
use CapDataOpera\PhpSdk\Model\Collaboration;
use CapDataOpera\PhpSdk\Model\Collectivite;
use CapDataOpera\PhpSdk\Model\Evenement;
use CapDataOpera\PhpSdk\Model\ExternalThing;
use CapDataOpera\PhpSdk\Model\Fonction;
use CapDataOpera\PhpSdk\Model\GenreOeuvre;
use CapDataOpera\PhpSdk\Model\HistoriqueProduction;
use CapDataOpera\PhpSdk\Model\Image;
use CapDataOpera\PhpSdk\Model\Interpretation;
use CapDataOpera\PhpSdk\Model\Isni;
use CapDataOpera\PhpSdk\Model\Lieu;
use CapDataOpera\PhpSdk\Model\MaitriseOeuvre;
use CapDataOpera\PhpSdk\Model\MentionProduction;
use CapDataOpera\PhpSdk\Model\Oeuvre;
use CapDataOpera\PhpSdk\Model\TypeEvenement;
use CapDataOpera\PhpSdk\Model\TypeOeuvre;
use CapDataOpera\PhpSdk\Model\TypeProduction;
use CapDataOpera\PhpSdk\Model\TypePublic;
use CapDataOpera\PhpSdk\Model\Pays;
use CapDataOpera\PhpSdk\Model\Partenariat;
use CapDataOpera\PhpSdk\Model\Participation;
use CapDataOpera\PhpSdk\Model\Personne;
use CapDataOpera\PhpSdk\Model\Production;
use CapDataOpera\PhpSdk\Model\ProductionPrimaire;
use CapDataOpera\PhpSdk\Model\Programmation;
use CapDataOpera\PhpSdk\Model\Role;
use CapDataOpera\PhpSdk\Model\Saison;
use CapDataOpera\PhpSdk\Model\StatusJuridique;

/**
 * Defines a capData Connector manager.
 */
class CapDataConnectorManager {
  
  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;  

  /**
   * Constructs a CapDataConnectorManager object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, RequestStack $request_stack, FileUrlGeneratorInterface $file_url_generator, ModuleHandlerInterface $module_handler) {
      $this->entityFieldManager = $entity_field_manager;
      $this->entityTypeManager = $entity_type_manager;
      $this->configFactory = $config_factory;
      $this->requestStack = $request_stack;
      $this->fileUrlGenerator = $file_url_generator;
      $this->moduleHandler = $module_handler;
  }

  /**
   * La liste des classes Capdata.
   * ex: capdataopera/php-sdk/src/Model/Lieu.php
   *
   * @return array
   */
  public function getCapDataClassesInfo() {
    $capDataClassesArray = [
      'capdata_typepublic' => [
        'capdata_full_name' => 'CapData Type de public',
        'capdata_short_id' => 'capdatatypepublic',
        'capdata_properties' => [
          'cd_label' => 'Libellé',
          'alt_label' => 'Libellé alternatif',
          'description' => 'Description',
          'image' => 'Image',
          'fr_bnf' => 'Fr Bnf',
          'ark_bnf' => 'Ark Bnf',
          'identifiant_rof' => 'Identifiant Rof',
        ]
      ],
      'capdata_typeevenement' => [
        'capdata_full_name' => 'CapData Type Evénement',
        'capdata_short_id' => 'capdatatypeevenement',
        'capdata_properties' => [
          'cd_label' => 'Libellé',
          'alt_label' => 'Libellé alternatif',
          'description' => 'Description',
          'image' => 'Image',
          'fr_bnf' => 'Fr Bnf',
          'ark_bnf' => 'Ark Bnf',
          'identifiant_rof' => 'Identifiant Rof',
        ]
      ],
      'capdata_typeoeuvre' => [
        'capdata_full_name' => 'CapData Type Oeuvre',
        'capdata_short_id' => 'capdatatypeoeuvre',
        'capdata_properties' => [
          'cd_label' => 'Libellé',
          'alt_label' => 'Libellé alternatif',
          'description' => 'Description',
          'image' => 'Image',
          'fr_bnf' => 'Fr Bnf',
          'ark_bnf' => 'Ark Bnf',
          'identifiant_rof' => 'Identifiant Rof',
        ]
      ],
      'capdata_statutjuridique' => [
        'capdata_full_name' => 'CapData Statut Juridique',
        'capdata_short_id' => 'capdatastatutjuridique',
        'capdata_properties' => [
          'cd_label' => 'Libellé',
          'alt_label' => 'Libellé alternatif',
          'description' => 'Description',
          'image' => 'Image',
          'fr_bnf' => 'Fr Bnf',
          'ark_bnf' => 'Ark Bnf',
          'identifiant_rof' => 'Identifiant Rof',
        ]
      ],
      'capdata_historiqueproduction' => [
        'capdata_full_name' => 'CapData Historique Production',
        'capdata_short_id' => 'capdatahistoriqueproduction',
        'capdata_properties' => [
          'cd_label' => 'Libellé',
          'alt_label' => 'Libellé alternatif',
          'description' => 'Description',
          'image' => 'Image',
          'fr_bnf' => 'Fr Bnf',
          'ark_bnf' => 'Ark Bnf',
          'identifiant_rof' => 'Identifiant Rof',
        ]
      ],
      'capdata_genreoeuvre' => [
        'capdata_full_name' => 'CapData Genre Oeuvre',
        'capdata_short_id' => 'capdatagenreoeuvre',
        'capdata_properties' => [
          'cd_label' => 'Libellé',
          'alt_label' => 'Libellé alternatif',
          'description' => 'Description',
          'image' => 'Image',
          'fr_bnf' => 'Fr Bnf',
          'ark_bnf' => 'Ark Bnf',
          'identifiant_rof' => 'Identifiant Rof',
        ]
      ],
      'capdata_categorieoeuvre' => [
        'capdata_full_name' => 'CapData Catégorie Oeuvre',
        'capdata_short_id' => 'capdatacategorieoeuvre',
        'capdata_properties' => [
          'cd_label' => 'Libellé',
          'alt_label' => 'Libellé alternatif',
          'description' => 'Description',
          'image' => 'Image',
          'fr_bnf' => 'Fr Bnf',
          'ark_bnf' => 'Ark Bnf',
          'identifiant_rof' => 'Identifiant Rof',
        ]
      ],                    
      'capdata_role' => [
        'capdata_full_name' => 'CapData Role',
        'capdata_short_id' => 'capdatarole',
        'capdata_properties' => [
          'cd_label' => 'Libellé',
          'alt_label' => 'Libellé alternatif',
          'description' => 'Description',
          'image' => 'Image',
          'fr_bnf' => 'Fr Bnf',
          'ark_bnf' => 'Ark Bnf',
          'identifiant_rof' => 'Identifiant Rof',
        ]
      ],                          
      'capdata_pays' => [
        'capdata_full_name' => 'CapData Pays',
        'capdata_short_id' => 'capdatapays',
        'capdata_properties' => [
          'cd_label' => 'Libellé',
          'alt_label' => 'Libellé alternatif',
          'description' => 'Description',
          'image' => 'Image',
          'fr_bnf' => 'Fr Bnf',
          'ark_bnf' => 'Ark Bnf',
          'identifiant_rof' => 'Identifiant Rof',
        ]
      ], 
      'capdata_typeproduction' => [
        'capdata_full_name' => 'CapData Type de production',
        'capdata_short_id' => 'capdatatypeproduction',
        'capdata_properties' => [
          'cd_label' => 'Libellé',
          'alt_label' => 'Libellé alternatif',
          'description' => 'Description',
          'image' => 'Image',
          'fr_bnf' => 'Fr Bnf',
          'ark_bnf' => 'Ark Bnf',
          'identifiant_rof' => 'Identifiant Rof',
        ]
      ],           
      'capdata_fonction' => [
        'capdata_full_name' => 'CapData Fonction',
        'capdata_short_id' => 'capdatafonction',
        'capdata_properties' => [
          'cd_label' => 'Libellé',
          'alt_label' => 'Libellé alternatif',
          'description' => 'Description',
          'image' => 'Image',
          'fr_bnf' => 'Fr Bnf',
          'ark_bnf' => 'Ark Bnf',
          'identifiant_rof' => 'Identifiant Rof',
        ]
      ],     
      'capdata_collectivite' => [
        'capdata_full_name' => 'CapData Collectivité',
        'capdata_short_id' => 'capdatacollectivite',
        'capdata_properties' => [
          'cd_label' => 'Nom',
          'siret' => 'Siret',
          'a_pour_fonction' => 'A Pour Fonction',
          'statut_juridique' => 'Statut Juridique',
          'nom_forme_rejet' => 'Nom Forme Rejet',
          'open_agenda_id' => 'Open Agenda',
          'adresse' => 'Adresse',
          'a_pour_lieu' => 'A Pour Lieu',
          'description' => 'Description',
          'isni' => 'ISNI',
          'facebook' => 'Facebook',
          'twitter' => 'Twitter',
          'page_web' => 'Page Web',
          'media' => 'Media',
          'image' => 'Image',
          'date_creation_ressource' => 'Date de création',
          'date_modification_ressource' => 'Date de modification',
          'catalogage_source_date' => 'Catalogage Source Date',
          'catalogage_source_pays' => 'Catalogage Source Pays',
          'catalogage_source_agence' => 'Catalogage Source Agence',
          'identifiant_rof' => 'Identifiant Rof',
        ]
      ],
      'capdata_adressepostale' => [
        'capdata_full_name' => 'CapData Adresse Postale',
        'capdata_short_id' => 'capdataadressepostale',
        'capdata_properties' => [
          'adresse_postale_txt' => 'Adresse Postale',
          'code_postal' => 'Code Postal',
          'commune' => 'Commune',
          'identifiant_rof' => 'Identifiant Rof',
        ]
      ],       
      'capdata_lieu' => [
        'capdata_full_name' => 'CapData Lieu',
        'capdata_short_id' => 'capdatalieu',
        'capdata_properties' => [
          'cd_label' => 'Libellé',
          'date_creation_ressource' => 'Date de création',
          'date_modification_ressource' => 'Date de modification',
          'catalogage_source_date' => 'Catalogage Source Date',
          'catalogage_source_pays' => 'Catalogage Source Pays',
          'catalogage_source_agence' => 'Catalogage Source Agence',
          'description' => 'Description',
          'adresse_postale' => 'Adresse Postale',
          'open_agenda_id' => 'Open Agenda',
          'identifiant_rof' => 'Identifiant Rof',
        ]
      ],                
      'capdata_personne' => [
        'capdata_full_name' => 'CapData Personne (Artiste)',
        'capdata_short_id' => 'capdatapersonne',
        'capdata_properties' => [
          'prenom' => 'Prénom',
          'nom' => 'Nom',
          'nom_forme_rejet' => 'Nom Forme Rejet',
          'biographie' => 'Biographie',
          'a_pour_fonction' => 'A Pour Fonction',
          'a_pour_profession' => 'A Pour Profession',    
          'description' => 'Description',
          'date_creation_ressource' => 'Date de création',
          'date_modification_ressource' => 'Date de modification',
          'catalogage_source_date' => 'Catalogage Source Date',
          'catalogage_source_pays' => 'Catalogage Source Pays',
          'catalogage_source_agence' => 'Catalogage Source Agence',
          'identifiant_rof' => 'Identifiant Rof',
          'isni' => 'ISNI',
          'facebook' => 'Facebook',
          'twitter' => 'Twitter',
          'page_web' => 'Page Web',
          'media' => 'Media',
          'image' => 'Image',
          'fr_bnf' => 'Fr Bnf', 
          'ark_bnf' => 'Ark Bnf',         
        ]
      ],   
      'capdata_saison' => [
        'capdata_full_name' => 'CapData Saison',
        'capdata_short_id' => 'capdatasaison',
        'capdata_properties' => [
          'cd_label' => 'Libellé',
          'alt_label' => 'Libellé alternatif',
          'description' => 'Description',
          'image' => 'Image',
          'fr_bnf' => 'Fr Bnf',
          'ark_bnf' => 'Ark Bnf',
          'media' => 'Media',
          'identifiant_rof' => 'Identifiant Rof',
        ]
      ],  
      'capdata_participation' => [
        'capdata_full_name' => 'CapData Participation',
        'capdata_short_id' => 'capdataparticipation',
        'capdata_properties' => [
          'a_pour_participant' => 'A pour Participant',
          'a_pour_fonction' => 'A pour Fonction',
          'identifiant_rof' => 'Identifiant Rof',
        ]
      ], 
      'capdata_auteur' => [
        'capdata_full_name' => 'CapData Auteur',
        'capdata_short_id' => 'capdataauteur',
        'capdata_properties' => [
          'a_pour_participant' => 'A pour Participant',
          'a_pour_fonction' => 'A pour Fonction',
          'identifiant_rof' => 'Identifiant Rof',
        ]
      ], 
      'capdata_collaboration' => [
        'capdata_full_name' => 'CapData Collaboration',
        'capdata_short_id' => 'capdatacollaboration',
        'capdata_properties' => [
          'a_pour_participant' => 'A pour Participant',
          'a_pour_fonction' => 'A pour Fonction',
          'identifiant_rof' => 'Identifiant Rof',
        ]
      ],  
      'capdata_interpretation' => [
        'capdata_full_name' => 'CapData Interpretation',
        'capdata_short_id' => 'capdatainterpretation',
        'capdata_properties' => [
          'a_pour_participant' => 'A pour Participant',
          'a_pour_fonction' => 'A pour Fonction',
          'identifiant_rof' => 'Identifiant Rof',
        ]
      ],
      'capdata_maitriseoeuvre' => [
        'capdata_full_name' => 'CapData Maitrise Oeuvre',
        'capdata_short_id' => 'capdatamaitriseoeuvre',
        'capdata_properties' => [
          'a_pour_participant' => 'A pour Participant',
          'a_pour_fonction' => 'A pour Fonction',
          'identifiant_rof' => 'Identifiant Rof',
        ]
      ],
      'capdata_mentionproduction' => [
        'capdata_full_name' => 'CapData Mention Production',
        'capdata_short_id' => 'capdatamentionproduction',
        'capdata_properties' => [
          'a_pour_participant' => 'A pour Participant',
          'a_pour_fonction' => 'A pour Fonction',
          'identifiant_rof' => 'Identifiant Rof',
        ]
      ],
      'capdata_partenariat' => [
        'capdata_full_name' => 'CapData Partenariat',
        'capdata_short_id' => 'capdatapartenariat',
        'capdata_properties' => [
          'a_pour_participant' => 'A pour Participant',
          'a_pour_fonction' => 'A pour Fonction',
          'identifiant_rof' => 'Identifiant Rof',
        ]
      ],
      'capdata_programmation' => [
        'capdata_full_name' => 'CapData Programmation',
        'capdata_short_id' => 'capdataprogrammation',
        'capdata_properties' => [
          'a_pour_participant' => 'A pour Participant',
          'a_pour_fonction' => 'A pour Fonction',
          'identifiant_rof' => 'Identifiant Rof',
        ]
      ], 
      'capdata_oeuvre' => [
        'capdata_full_name' => 'CapData Oeuvre',
        'capdata_short_id' => 'capdataoeuvre',
        'capdata_properties' => [
          'titre' => 'Titre',
          'description' => 'Description',
          'identifiant_rof' => 'Identifiant Rof',
          'date_creation_ressource' => 'Date de création de la ressource',
          'date_modification_ressource' => 'Date de modification',
          'catalogage_source_date' => 'Catalogage Source Date',
          'catalogage_source_pays' => 'Catalogage Source Pays',
          'catalogage_source_agence' => 'Catalogage Source Agence',  
          'media' => 'Media',
          'image' => 'Image',
          'fr_bnf' => 'Fr Bnf',
          'ark_bnf' => 'Ark Bnf',
          'a_pour_auteur' => 'A pour auteur',
          'a_pour_mention_production' => 'A pour mention production',
          'a_pour_partenariat' => 'A pour partenariat',
          'a_pour_participation' => 'A pour participation',
          'titre_forme_rejet' => 'Titre Forme Rejet',
          'intrigue' => 'Intrigue',
          'source_livret' => 'Source Livret',
          'date_creation' => 'Date de création',
          'duree' => 'Durée',          
          'a_pour_interpretation' => 'A pour interpretation',
          'categorie_oeuvre' => 'Catégorie Oeuvre',
          'genre_oeuvre' => 'Genre Oeuvre',
          'type_oeuvre' => 'Type Oeuvre',
          'pays_creation' => 'Pays de création',
          'personnage' => 'Personnage',
        ]
      ],  
      'capdata_productionprimaire' => [
        'capdata_full_name' => 'CapData Production Primaire',
        'capdata_short_id' => 'capdataproductionprimaire',
        'capdata_properties' => [
          'titre' => 'Titre',
          'description' => 'Description',
          'identifiant_rof' => 'Identifiant Rof',
          'date_creation_ressource' => 'Date de création',
          'date_modification_ressource' => 'Date de modification',
          'catalogage_source_date' => 'Catalogage Source Date',
          'catalogage_source_pays' => 'Catalogage Source Pays',
          'catalogage_source_agence' => 'Catalogage Source Agence',  
          'media' => 'Media',
          'image' => 'Image',
          'fr_bnf' => 'Fr Bnf',
          'ark_bnf' => 'Ark Bnf',
          'a_pour_auteur' => 'A pour auteur',
          'a_pour_mention_production' => 'A pour mention production',
          'a_pour_partenariat' => 'A pour partenariat',
          'a_pour_participation' => 'A pour participation',
          'a_pour_collaboration' => 'A pour collaboration',
          'a_pour_interpretation' => 'A pour interpretation',
          'a_pour_maitrise_oeuvre' => 'A pour maitrise Oeuvre',
          'a_pour_programmation' => 'A pour programmation',
          'a_pour_saison' => 'A pour saison',
          'a_pour_type_production' => 'A pour type production',
          'a_pour_type_public' => 'A pour type public',
          'historique' => 'Historique',
          'lieu_publication' => 'Lieu publication',
          'oeuvre_representee' => 'Oeuvre representee',
          'date_premiere' => 'Date première',
          'date_publication' => 'Date publication',
          'jeune_public' => 'Jeune public'
        ]
      ],                                                                   
      'capdata_production' => [
        'capdata_full_name' => 'CapData Production',
        'capdata_short_id' => 'capdataproduction',
        'capdata_properties' => [
          'titre' => 'Titre',
          'description' => 'Description',
          'identifiant_rof' => 'Identifiant Rof',
          'date_creation_ressource' => 'Date de création',
          'date_modification_ressource' => 'Date de modification',
          'catalogage_source_date' => 'Catalogage Source Date',
          'catalogage_source_pays' => 'Catalogage Source Pays',
          'catalogage_source_agence' => 'Catalogage Source Agence',  
          'media' => 'Media',
          'image' => 'Image',
          'fr_bnf' => 'Fr Bnf',
          'ark_bnf' => 'Ark Bnf',
          'a_pour_auteur' => 'A pour auteur',
          'a_pour_mention_production' => 'A pour mention production',
          'a_pour_partenariat' => 'A pour partenariat',
          'a_pour_participation' => 'A pour participation',
          'a_pour_collaboration' => 'A pour collaboration',
          'a_pour_interpretation' => 'A pour interpretation',
          'a_pour_maitrise_oeuvre' => 'A pour maitrise Oeuvre',
          'a_pour_programmation' => 'A pour programmation',
          'a_pour_saison' => 'A pour saison',
          'a_pour_type_production' => 'A pour type production',
          'a_pour_type_public' => 'A pour type public',
          'historique' => 'Historique',
          'lieu_publication' => 'Lieu publication',
          'oeuvre_representee' => 'Oeuvre representee',
          'production_primaire' => 'Production primaire',
          'date_premiere' => 'Date première',
          'date_publication' => 'Date publication',
          'jeune_public' => 'Jeune public'
        ]
      ],
      'capdata_evenement' => [
        'capdata_full_name' => 'CapData Evénement',
        'capdata_short_id' => 'capdataevenement',
        'capdata_properties' => [
          'titre' => 'Titre',
          'description' => 'Description',
          'identifiant_rof' => 'Identifiant Rof',
          'date_creation_ressource' => 'Date de création',
          'date_modification_ressource' => 'Date de modification',
          'catalogage_source_date' => 'Catalogage Source Date',
          'catalogage_source_pays' => 'Catalogage Source Pays',
          'catalogage_source_agence' => 'Catalogage Source Agence',  
          'media' => 'Media',
          'image' => 'Image',
          'fr_bnf' => 'Fr Bnf',
          'ark_bnf' => 'Ark Bnf',
          'a_pour_auteur' => 'A pour auteur',
          'a_pour_mention_production' => 'A pour mention production',
          'a_pour_partenariat' => 'A pour partenariat',
          'a_pour_participation' => 'A pour participation',
          'a_pour_collaboration' => 'A pour collaboration',
          'a_pour_interpretation' => 'A pour interpretation',
          'a_pour_maitrise_oeuvre' => 'A pour maitrise Oeuvre',
          'a_pour_programmation' => 'A pour programmation',
          'a_pour_lieu' => 'A Pour Lieu',
          'a_pour_production' => 'A pour production',
          'a_pour_type_public' => 'A pour type public',
          'type_evenement' => 'Type Evenement',
          'annulation' => 'Annulation',
          'date_debut' => 'Date début',
          'date_fin' => 'Date fin',
          'duree' => 'Durée',
          'open_agenda_id' => 'Open Agenda',
        ]
      ],      
    ];
    // Allow other modules to alter the $capDataClassesArray.
    $this->moduleHandler->alter('capdata_classes_info', $capDataClassesArray);
    return $capDataClassesArray;
  }
  /**
   * La liste des champs pour un type de contenu donné.
   * 
   * @param string $selectedContentType
   * 
   * @return array
   */
  public function getFieldsOptionsByContentType($selectedContentType) {
      $entityTypeId = 'node';
      $fields = $this->entityFieldManager->getFieldDefinitions($entityTypeId, $selectedContentType);
      $options = [];
      foreach ($fields as $fieldName => $fieldDefinition) {
        if (!empty($fieldDefinition->getTargetBundle())) {  
          $options[$fieldName] = $fieldDefinition->getLabel();                  
        }
      }
      return $options;
  }

  /**
   * La liste des champs pour une taxonomie donnée.
   * 
   * @param string $selectedTaxonomy
   * 
   * @return array
   */
  public function getFieldsOptionsByTaxonomy($selectedTaxonomy) {
      $entityTypeId = 'taxonomy_term';
      $fields = $this->entityFieldManager->getFieldDefinitions($entityTypeId, $selectedTaxonomy);
      $options = [];
      foreach ($fields as $fieldName => $fieldDefinition) {
        if (!empty($fieldDefinition->getTargetBundle())) {  
          $options[$fieldName] = $fieldDefinition->getLabel();                  
        }
      }
      return $options;
  }

    /**
     * La liste des traitement spéciaux pour un champ donné.
     * 
     * @return array
     */
    public function getSpecialProcessingOptions() {
      $options = [
        'clean_url' => "Nettoyer l'URL",
        'remove_tags' => 'Enlever les balises HTML',
      ];
      return $options;
    }

  /**
   * La liste de types de contenu.
   * 
   * @return array
   */
  public function getContentTypesList() {
    $contentTypes = [];
    $nodeTypes = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    foreach ($nodeTypes as $nodeType) {
      $contentTypes[$nodeType->id()] = $nodeType->label();
    }
    return $contentTypes;
  }

  /**
   * La liste de vocabulaires.
   * 
   * @return array
   */
  public function getVocabularyList() {
    $vocabularies = [];
    $vocabTypes = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->loadMultiple();
    foreach ($vocabTypes as $vocab) {
      $vocabularies[$vocab->id()] = $vocab->label();
    }
    return $vocabularies;
  }

  /**
   * La liste des classes incluses dans l'export 
   * avec leurs informations stockées en configuration.
   * 
   * @return array
   */
  public function getExportCapdataClassesStockedInfo() {
    $exportCapdataClassesStockedInfo = [];
    $config = $this->configFactory->get('capdata_connector.settings');
    $capDataClasses = $this->getCapDataClassesInfo();

    foreach ($capDataClasses as $capDataClassUniqueId => $capDataClassFullData) {
      $capdataClassPropertiesArray = [];
      $includeInExport = $config->get($capDataClassUniqueId.'_include_in_export');
      $mappingType = $config->get($capDataClassUniqueId.'_mapping_type');
      // Si la classe est incluse dans l'export
      if(!empty($includeInExport)){
        foreach ($capDataClassFullData['capdata_properties'] as $propertyKey => $propertyName) { 
          $capdataClassPropertiesArray[$propertyKey] = [
            'property_name' => $propertyName,
            'property_taxo_fields_dropdown' => $config->get($capDataClassUniqueId.'_taxo_'.$propertyKey.'_fields_dropdown'),
            'property_taxo_custom_processing' => $config->get($capDataClassUniqueId.'_taxo_'.$propertyKey.'_custom_processing'),
            'property_taxo_comments' => $config->get($capDataClassUniqueId.'_taxo_'.$propertyKey.'_comments'),
            'property_content_fields_dropdown' => $config->get($capDataClassUniqueId.'_content_'.$propertyKey.'_fields_dropdown'),
            'property_content_custom_processing' => $config->get($capDataClassUniqueId.'_content_'.$propertyKey.'_custom_processing'),
            'property_content_comments' => $config->get($capDataClassUniqueId.'_content_'.$propertyKey.'_comments'),
          ];
        }
        if(!empty($mappingType)){
          if(strpos($mappingType, "_taxo_mapping") !== false){
            $exportCapdataClassesStockedInfo["taxo_mapped_classes"][$capDataClassUniqueId] = [
              'export_class_capdata_full_name' => $capDataClassFullData['capdata_full_name'],
              'export_class_include_in_export' => $includeInExport,
              'export_class_mapping_type' => $config->get($capDataClassUniqueId.'_mapping_type'),
              'export_class_taxonomy_dropdown' => $config->get($capDataClassUniqueId.'_taxonomy_dropdown'),
              'export_class_content_dropdown' => $config->get($capDataClassUniqueId.'_content_dropdown'),
              'export_class_capdata_properties' => $capdataClassPropertiesArray,
            ];
          }elseif(strpos($mappingType, "_content_mapping") !== false){
            $exportCapdataClassesStockedInfo["content_mapped_classes"][$capDataClassUniqueId] = [
              'export_class_capdata_full_name' => $capDataClassFullData['capdata_full_name'],
              'export_class_include_in_export' => $includeInExport,
              'export_class_mapping_type' => $config->get($capDataClassUniqueId.'_mapping_type'),
              'export_class_taxonomy_dropdown' => $config->get($capDataClassUniqueId.'_taxonomy_dropdown'),
              'export_class_content_dropdown' => $config->get($capDataClassUniqueId.'_content_dropdown'),
              'export_class_capdata_properties' => $capdataClassPropertiesArray,
            ];
          }
        }
      }
    }
    return $exportCapdataClassesStockedInfo;
  }

  /**
   * Export RDF-XML Graph.
   * 
   * @return string
   */
  public function dataExport() {
    /**
     * Construction du graph RDF
     */
    $serializer = new Serializer();
    $graph = new Graph();
    $host = !empty($this->configFactory->get('capdata_connector.settings')->get('capdata_connector_host')) ? $this->configFactory->get('capdata_connector.settings')->get('capdata_connector_host') : '';
    $siteName = !empty($this->configFactory->get('system.site')->get('name')) ? $this->configFactory->get('system.site')->get('name') : '';
    $capdataOperaUrl = !empty($this->configFactory->get('capdata_connector.settings')->get('capdata_opera_url')) ? $this->configFactory->get('capdata_connector.settings')->get('capdata_opera_url') : '';
    $ownOrg = new Collectivite($capdataOperaUrl);
    $ownOrg->setNom($siteName)
        ->setSiteWeb($host)
        ->setCatalogageSourceAgence($ownOrg);
    $graph->add($ownOrg);

    $capdataExportData = $this->getExportCapdataClassesStockedInfo();

    // Custom hook, pour l'ajout de données supplémentaires hook_capdata_graph_beginning_alter(&$graph) au début de l'export.
    $this->moduleHandler->alter('capdata_graph_beginning', $graph, $capdataExportData);

    foreach ($capdataExportData["taxo_mapped_classes"] as $capDataClassUniqueId => $capDataClassInfo) {
      /**
       * I. Export de toutes les taxonomies
       */
      $exportClassTaxonomyDropdown = $capDataClassInfo["export_class_taxonomy_dropdown"];
      if(!empty($exportClassTaxonomyDropdown)){
        $taxonomyTerms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($exportClassTaxonomyDropdown); 
        if(!empty($taxonomyTerms)){
          $termIds = array_map(function($term) {
            /** @var \Drupal\taxonomy\Entity\Term $term */
            return $term->tid;
          }, $taxonomyTerms);
          asort($termIds);
          $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($termIds);
          foreach ($terms as $term) {
            // Termes de la taxonomie
            $tid = $term->id();
            $label = $term->label();
            if(isset($capDataClassInfo["export_class_capdata_properties"]["cd_label"])){
              if(!empty($capDataClassInfo["export_class_capdata_properties"]["cd_label"]["property_taxo_fields_dropdown"])){
                $labelFieldName = $capDataClassInfo["export_class_capdata_properties"]["cd_label"]["property_taxo_fields_dropdown"];
                if ($term->hasField($labelFieldName) && !$term->get($labelFieldName)->isEmpty()) {
                  $label = $term->get($labelFieldName)->value;
                  if(!empty($label)){
                    if(!empty($capDataClassInfo["export_class_capdata_properties"]["cd_label"]["property_taxo_custom_processing"])){
                      $customProcessing = $capDataClassInfo["export_class_capdata_properties"]["cd_label"]["property_taxo_custom_processing"];
                      $label = $this->customFieldProcessing($label, $customProcessing);
                    }
                  }
                }
              }
            }

            $termUrl = $host . "/taxonomy/term/" . $tid;

            switch ($capDataClassUniqueId) {
              case 'capdata_typeproduction':
                  $graphItem = new TypeProduction($termUrl);
                  $this->setReferentielCustomProperties($graphItem, 
                                                        $term, 
                                                        $capDataClassInfo["export_class_capdata_properties"],
                                                        "taxo"
                                                      );                    
                  $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");
                  if(!empty($arkBnfUrl)){
                    $arkBnf = new ArkBnf($arkBnfUrl);
                    $graph->add($arkBnf);
                    $graphItem->setArkBnf($arkBnf);
                  }                  
                  break;
              case 'capdata_typepublic':
                  $graphItem = new TypePublic($termUrl);
                  $this->setReferentielCustomProperties($graphItem, 
                                                        $term, 
                                                        $capDataClassInfo["export_class_capdata_properties"],
                                                        "taxo"
                                                      );  
                  $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");                  
                  if(!empty($arkBnfUrl)){
                    $arkBnf = new ArkBnf($arkBnfUrl);
                    $graph->add($arkBnf);
                    $graphItem->setArkBnf($arkBnf);
                  }                   
                  break;
              case 'capdata_typeevenement': 
                  $graphItem = new TypeEvenement($termUrl);
                  $this->setReferentielCustomProperties($graphItem, 
                                                        $term, 
                                                        $capDataClassInfo["export_class_capdata_properties"],
                                                        "taxo"
                                                      );  
                  $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");                  
                  if(!empty($arkBnfUrl)){
                    $arkBnf = new ArkBnf($arkBnfUrl);
                    $graph->add($arkBnf);
                    $graphItem->setArkBnf($arkBnf);
                  }                    
                  break;                  
              case 'capdata_typeoeuvre': 
                  $graphItem = new TypeOeuvre($termUrl);
                  $this->setReferentielCustomProperties($graphItem, 
                                                        $term, 
                                                        $capDataClassInfo["export_class_capdata_properties"],
                                                        "taxo"
                                                      );  
                  $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");                   
                  if(!empty($arkBnfUrl)){
                    $arkBnf = new ArkBnf($arkBnfUrl);
                    $graph->add($arkBnf);
                    $graphItem->setArkBnf($arkBnf);
                  }                  
                  break;                 
              case 'capdata_statutjuridique':  
                  $graphItem = new StatusJuridique($termUrl);
                  $this->setReferentielCustomProperties($graphItem, 
                                                        $term, 
                                                        $capDataClassInfo["export_class_capdata_properties"],
                                                        "taxo"
                                                      );  
                  $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");                   
                  if(!empty($arkBnfUrl)){
                    $arkBnf = new ArkBnf($arkBnfUrl);
                    $graph->add($arkBnf);
                    $graphItem->setArkBnf($arkBnf);
                  }                    
                  break;              
              case 'capdata_historiqueproduction':
                  $graphItem = new HistoriqueProduction($termUrl);
                  $this->setReferentielCustomProperties($graphItem, 
                                                        $term, 
                                                        $capDataClassInfo["export_class_capdata_properties"],
                                                        "taxo"
                                                      );  
                  $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");                   
                  if(!empty($arkBnfUrl)){
                    $arkBnf = new ArkBnf($arkBnfUrl);
                    $graph->add($arkBnf);
                    $graphItem->setArkBnf($arkBnf);
                  }                  
                  break;               
              case 'capdata_genreoeuvre':
                  $graphItem = new GenreOeuvre($termUrl);
                  $this->setReferentielCustomProperties($graphItem, 
                                                        $term, 
                                                        $capDataClassInfo["export_class_capdata_properties"],
                                                        "taxo"
                                                      );  
                  $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");                   
                  if(!empty($arkBnfUrl)){
                    $arkBnf = new ArkBnf($arkBnfUrl);
                    $graph->add($arkBnf);
                    $graphItem->setArkBnf($arkBnf);
                  }                   
                  break;              
              case 'capdata_categorieoeuvre': 
                  $graphItem = new CategorieOeuvre($termUrl);
                  $this->setReferentielCustomProperties($graphItem, 
                                                        $term, 
                                                        $capDataClassInfo["export_class_capdata_properties"],
                                                        "taxo"
                                                      );  
                  $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");                   
                  if(!empty($arkBnfUrl)){
                    $arkBnf = new ArkBnf($arkBnfUrl);
                    $graph->add($arkBnf);
                    $graphItem->setArkBnf($arkBnf);
                  }                  
                  break;              
              case 'capdata_role':
                  $graphItem = new Role($termUrl);
                  $this->setReferentielCustomProperties($graphItem, 
                                                        $term, 
                                                        $capDataClassInfo["export_class_capdata_properties"],
                                                        "taxo"
                                                      );
                  $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");                   
                  if(!empty($arkBnfUrl)){
                    $arkBnf = new ArkBnf($arkBnfUrl);
                    $graph->add($arkBnf);
                    $graphItem->setArkBnf($arkBnf);
                  }                    
                  break;              
              case 'capdata_pays':
                  $graphItem = new Pays($termUrl);
                  $this->setReferentielCustomProperties($graphItem, 
                                                        $term, 
                                                        $capDataClassInfo["export_class_capdata_properties"],
                                                        "taxo"
                                                      );  
                  $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");                   
                  if(!empty($arkBnfUrl)){
                    $arkBnf = new ArkBnf($arkBnfUrl);
                    $graph->add($arkBnf);
                    $graphItem->setArkBnf($arkBnf);
                  }                  
                  break;              
              case 'capdata_fonction':  
                  $graphItem = new Fonction($termUrl);
                  $this->setReferentielCustomProperties($graphItem, 
                                                        $term, 
                                                        $capDataClassInfo["export_class_capdata_properties"],
                                                        "taxo"
                                                      );  
                  $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");                   
                  if(!empty($arkBnfUrl)){
                    $arkBnf = new ArkBnf($arkBnfUrl);
                    $graph->add($arkBnf);
                    $graphItem->setArkBnf($arkBnf);
                  }                   
                  break;
              case 'capdata_collectivite':
                  $graphItem = new Collectivite($termUrl); 
                  $this->setCapdataCollectiviteProperties($graphItem, $term, $capDataClassInfo, "taxo");                                                      
                  $isniUrl = $this->setIsniCapdataProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");  
                  if(!empty($isniUrl)){
                    $isni = new Isni($isniUrl);
                    $graph->add($isni);
                    $graphItem->setIsni($isni);
                  }                  
                  // A pour fonction 
                  $capDataFonctionMappingType = "";
                  if(isset($capdataExportData["content_mapped_classes"]["capdata_fonction"])){
                    $capDataFonctionMappingType = "content";
                  }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_fonction"])){
                    $capDataFonctionMappingType = "taxo";
                  }
                  $capDataFonctionMappingInfo = [
                    "capData_fonction_mappingtype" => $capDataFonctionMappingType,
                  ];                  
                  $currentCapdataClassMappingInfo =[
                    "host" => $host,
                    "mapping_type" =>  "taxo",
                    "mapped_entity" =>  $term,
                    "mapped_entity_properties" => $capDataClassInfo["export_class_capdata_properties"],
                  ]; 
                  $this->setAPourFonctionCapdataProperty($graphItem,
                                                          $capDataFonctionMappingInfo,
                                                          $currentCapdataClassMappingInfo
                                                        );                   
                  $this->setCapdataImageProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");
                  // Media image, sound 
                  $mediaInfo = $this->getCapdataMediaPropertyInfo($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");
                  if(!empty($mediaInfo) && isset($mediaInfo["mediaType"]) && !empty($mediaInfo["mediaUrl"])){
                    if($mediaInfo["mediaType"] == "image"){
                      $refImageUrl = $mediaInfo["mediaUrl"];
                      $capdataImage = new Image($refImageUrl);
                      $capdataImage->setContentUrl($refImageUrl);
                      $capdataImage->setName($graphItem->getNom());
                      $graph->add($capdataImage);
                      $graphItem->setMedia($capdataImage); 
                    }
                    // Media sound to be implemented
                  }                  
                  // A pour lieu (Lieu)
                  $capDataLieuCustomMappingType = "";
                  if(isset($capdataExportData["content_mapped_classes"]["capdata_lieu"])){
                    $capDataLieuCustomMappingType = "content";
                  }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_lieu"])){
                    $capDataLieuCustomMappingType = "taxo";
                  }
                  $capDataLieuCustomMappingInfo = [
                      "capData_lieu_mappingtype" => $capDataLieuCustomMappingType,
                  ];
                  $this->setAPourLieuCapdataProperty($graphItem,
                                                      $capDataLieuCustomMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );
                  // Identifiant ROF                                                     
                  $this->setIdentifiantRofProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");                  
                  break;                                              
              case 'capdata_adressepostale':
                $graphItem = new AdressePostale($termUrl);
                // Commune
                $commune = "";
                if(isset($capDataClassInfo["export_class_capdata_properties"]["commune"])){
                  if(!empty($capDataClassInfo["export_class_capdata_properties"]["commune"]["property_taxo_fields_dropdown"])){
                    $communeFieldName = $capDataClassInfo["export_class_capdata_properties"]["commune"]["property_taxo_fields_dropdown"];
                    if($term->hasField($communeFieldName) && !$term->get($communeFieldName)->isEmpty()) {
                      $commune = $term->get($communeFieldName)->value;
                      if(!empty($commune)){
                        if(!empty($capDataClassInfo["export_class_capdata_properties"]["commune"]["property_taxo_custom_processing"])){
                          $customProcessing = $capDataClassInfo["export_class_capdata_properties"]["commune"]["property_taxo_custom_processing"];
                          $commune = $this->customFieldProcessing($commune, $customProcessing);
                        }
                        $graphItem->setCommune($commune);
                      }
                    }
                  }
                }
                // Adresse postale (texte)
                $adressePostaleTexte = "";
                if(isset($capDataClassInfo["export_class_capdata_properties"]["adresse_postale_txt"])){
                  if(!empty($capDataClassInfo["export_class_capdata_properties"]["adresse_postale_txt"]["property_taxo_fields_dropdown"])){
                    $adressePostaleTxtFieldName = $capDataClassInfo["export_class_capdata_properties"]["adresse_postale_txt"]["property_taxo_fields_dropdown"];
                    if ($term->hasField($adressePostaleTxtFieldName) && !$term->get($adressePostaleTxtFieldName)->isEmpty()) {
                      $adressePostaleTexte = $term->get($adressePostaleTxtFieldName)->value;
                      if(!empty($adressePostaleTexte)){
                        if(!empty($capDataClassInfo["export_class_capdata_properties"]["adresse_postale_txt"]["property_taxo_custom_processing"])){
                          $customProcessing = $capDataClassInfo["export_class_capdata_properties"]["adresse_postale_txt"]["property_taxo_custom_processing"];
                          $adressePostaleTexte = $this->customFieldProcessing($adressePostaleTexte, $customProcessing);
                        }
                        $graphItem->setAdressePostale($adressePostaleTexte);
                      }
                    }
                  }
                }

                // Code postal
                $codePostal = "";
                if(isset($capDataClassInfo["export_class_capdata_properties"]["code_postal"])){
                  if(!empty($capDataClassInfo["export_class_capdata_properties"]["code_postal"]["property_taxo_fields_dropdown"])){
                    $codePostalFieldName = $capDataClassInfo["export_class_capdata_properties"]["code_postal"]["property_taxo_fields_dropdown"];
                    if ($term->hasField($codePostalFieldName) && !$term->get($codePostalFieldName)->isEmpty()) {
                      $codePostal = $term->get($codePostalFieldName)->value;
                      if(!empty($codePostal)){
                        if(!empty($capDataClassInfo["export_class_capdata_properties"]["code_postal"]["property_taxo_custom_processing"])){
                          $customProcessing = $capDataClassInfo["export_class_capdata_properties"]["code_postal"]["property_taxo_custom_processing"];
                          $codePostal = $this->customFieldProcessing($codePostal, $customProcessing);
                        }
                        $graphItem->setCodePostal($codePostal);
                      }
                    }
                  }
                }
                $this->setIdentifiantRofProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");
                break;
              case 'capdata_lieu':
                $graphItem = new Lieu($termUrl);
                // Name
                $this->setNameTraitCapdataProperties($graphItem, $label);
                // Catalog Class Properties
                $this->setCatalogCapdataProperties($graphItem, 
                                                    $term, 
                                                    $capDataClassInfo["export_class_capdata_properties"],
                                                    "taxo"
                                                  );                                  
                $capDataCollectiviteMappingType = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_collectivite"])){
                  $capDataCollectiviteMappingType = "content";
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_collectivite"])){
                  $capDataCollectiviteMappingType = "taxo";
                }
                $capDataCollectiviteMappingInfo = [
                    "capData_collectivite_mappingtype" => $capDataCollectiviteMappingType,
                    "default_collectivite" => $ownOrg,
                ];
                $currentCapdataClassMappingInfo =[
                    "host" => $host,
                    "mapping_type" =>  "taxo",
                    "mapped_entity" =>  $term,
                    "mapped_entity_properties" => $capDataClassInfo["export_class_capdata_properties"],
                ];

                $this->setCatalogageSourceAgenceProperty($graphItem, 
                                                  $capDataCollectiviteMappingInfo,
                                                  $currentCapdataClassMappingInfo
                                                ); 
                // Open agenda                                
                $this->setOpenAgendaCapdataProperty($graphItem, 
                                                  $term, 
                                                  $capDataClassInfo["export_class_capdata_properties"],
                                                  "taxo"
                                                );      
                // Description                                
                $this->setDescriptionCapdataProperty($graphItem, 
                                                      $term, 
                                                      $capDataClassInfo["export_class_capdata_properties"],
                                                      "taxo"
                                                  );                                                   
                // Identifiant Rof
                $this->setIdentifiantRofProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");                
                break;    
              case 'capdata_personne':
                $graphItem = new Personne($termUrl);
                $this->setPersonalDetails($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");     
                $this->setIdentifiantRofProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");
                $this->setSocialsCapdataProperties($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");  
                $isniUrl = $this->setIsniCapdataProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");  
                if(!empty($isniUrl)){
                  $isni = new Isni($isniUrl);
                  $graph->add($isni);
                  $graphItem->setIsni($isni);
                }
                $this->setDescriptionCapdataProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");
                $this->setArkBnfTraitCapdataProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo"); 
                $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo"); 
                if(!empty($arkBnfUrl)){
                  $arkBnf = new ArkBnf($arkBnfUrl);
                  $graph->add($arkBnf);
                  $graphItem->setArkBnf($arkBnf);
                }
                $this->setCapdataImageProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");
                // Media image, sound 
                $mediaInfo = $this->getCapdataMediaPropertyInfo($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");
                if(!empty($mediaInfo) && isset($mediaInfo["mediaType"]) && !empty($mediaInfo["mediaUrl"])){
                  if($mediaInfo["mediaType"] == "image"){
                    $refImageUrl = $mediaInfo["mediaUrl"];
                    $capdataImage = new Image($refImageUrl);
                    $capdataImage->setContentUrl($refImageUrl);
                    $prenom = $graphItem->getPrenom();
                    $nom = $graphItem->getNom();
                    $capdataImage->setName($prenom. ' '.$nom);
                    $graph->add($capdataImage);
                    $graphItem->setMedia($capdataImage); 
                  }
                  // Media sound to be implemented
                }                
                // Catalogage
                $this->setCatalogCapdataProperties($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo"); 
                $capDataCollectiviteMappingType = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_collectivite"])){
                  $capDataCollectiviteMappingType = "content";
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_collectivite"])){
                  $capDataCollectiviteMappingType = "taxo";
                }
                $capDataCollectiviteMappingInfo = [
                    "capData_collectivite_mappingtype" => $capDataCollectiviteMappingType,
                    "default_collectivite" => $ownOrg,
                ];                  
                $currentCapdataClassMappingInfo =[
                    "host" => $host,
                    "mapping_type" =>  "taxo",
                    "mapped_entity" =>  $term,
                    "mapped_entity_properties" => $capDataClassInfo["export_class_capdata_properties"],
                ];                  
                $this->setCatalogageSourceAgenceProperty($graphItem,
                                                          $capDataCollectiviteMappingInfo,
                                                          $currentCapdataClassMappingInfo
                                                        );
                // A pour fonction 
                $capDataFonctionMappingType = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_fonction"])){
                  $capDataFonctionMappingType = "content";
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_fonction"])){
                  $capDataFonctionMappingType = "taxo";
                }
                $capDataFonctionMappingInfo = [
                  "capData_fonction_mappingtype" => $capDataFonctionMappingType,
                ];                  
                $currentCapdataClassMappingInfo =[
                  "host" => $host,
                  "mapping_type" =>  "taxo",
                  "mapped_entity" =>  $term,
                  "mapped_entity_properties" => $capDataClassInfo["export_class_capdata_properties"],
                ]; 
                $this->setAPourFonctionCapdataProperty($graphItem,
                                                        $capDataFonctionMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                                    
                // A pour profession
                $this->setAPourProfessionCapdataProperty($graphItem,
                                                          $capDataFonctionMappingInfo,
                                                          $currentCapdataClassMappingInfo
                                                        );                                                                                          
                break;
              case 'capdata_saison': 
                $graphItem = new Saison($termUrl);
                $this->setReferentielCustomProperties($graphItem, 
                                                      $term, 
                                                      $capDataClassInfo["export_class_capdata_properties"],
                                                      "taxo"
                                                    );  
                $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo"); 
                if(!empty($arkBnfUrl)){
                  $arkBnf = new ArkBnf($arkBnfUrl);
                  $graph->add($arkBnf);
                  $graphItem->setArkBnf($arkBnf);
                }                  
                // Media image, sound 
                $mediaInfo = $this->getCapdataMediaPropertyInfo($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");
                if(!empty($mediaInfo) && isset($mediaInfo["mediaType"]) && !empty($mediaInfo["mediaUrl"])){
                  if($mediaInfo["mediaType"] == "image"){
                    $refImageUrl = $mediaInfo["mediaUrl"];
                    $capdataImage = new Image($refImageUrl);
                    $capdataImage->setContentUrl($refImageUrl);
                    $capdataImage->setName($graphItem->getLabel());
                    $graph->add($capdataImage);
                    $graphItem->setMedia($capdataImage); 
                  }
                  // Media sound to be implemented
                }               
                break;                                
              case 'capdata_participation': 
                $graphItem = new Participation($termUrl);
                $participationTaxonomyMappingInfo = [
                    'capdataExportData' => $capdataExportData,
                    'host' => $host,
                    'capDataClassInfo' => $capDataClassInfo,
                    'ownOrg' => $ownOrg
                ];
                $this->handleParticipationTaxonomyMapping($graphItem, $term, $participationTaxonomyMappingInfo);                
                break;               
              case 'capdata_auteur': 
                $graphItem = new Auteur($termUrl);
                $participationTaxonomyMappingInfo = [
                  'capdataExportData' => $capdataExportData,
                  'host' => $host,
                  'capDataClassInfo' => $capDataClassInfo,
                  'ownOrg' => $ownOrg
                ];
                $this->handleParticipationTaxonomyMapping($graphItem, $term, $participationTaxonomyMappingInfo);                                 
                break;              
              case 'capdata_collaboration': 
                $graphItem = new Collaboration($termUrl);
                $participationTaxonomyMappingInfo = [
                  'capdataExportData' => $capdataExportData,
                  'host' => $host,
                  'capDataClassInfo' => $capDataClassInfo,
                  'ownOrg' => $ownOrg
                ];
                $this->handleParticipationTaxonomyMapping($graphItem, $term, $participationTaxonomyMappingInfo);                
                break;                
              case 'capdata_interpretation': 
                $graphItem = new Interpretation($termUrl);
                $participationTaxonomyMappingInfo = [
                  'capdataExportData' => $capdataExportData,
                  'host' => $host,
                  'capDataClassInfo' => $capDataClassInfo,
                  'ownOrg' => $ownOrg
                ];
                $this->handleParticipationTaxonomyMapping($graphItem, $term, $participationTaxonomyMappingInfo);                 
                break;               
              case 'capdata_maitriseoeuvre': 
                $graphItem = new MaitriseOeuvre($termUrl);
                $participationTaxonomyMappingInfo = [
                  'capdataExportData' => $capdataExportData,
                  'host' => $host,
                  'capDataClassInfo' => $capDataClassInfo,
                  'ownOrg' => $ownOrg
                ];
                $this->handleParticipationTaxonomyMapping($graphItem, $term, $participationTaxonomyMappingInfo);                 
                break;                  
              case 'capdata_mentionproduction': 
                $graphItem = new MentionProduction($termUrl);
                $participationTaxonomyMappingInfo = [
                  'capdataExportData' => $capdataExportData,
                  'host' => $host,
                  'capDataClassInfo' => $capDataClassInfo,
                  'ownOrg' => $ownOrg
                ];
                $this->handleParticipationTaxonomyMapping($graphItem, $term, $participationTaxonomyMappingInfo);                 
                break;                
              case 'capdata_partenariat': 
                $graphItem = new Partenariat($termUrl);
                $participationTaxonomyMappingInfo = [
                  'capdataExportData' => $capdataExportData,
                  'host' => $host,
                  'capDataClassInfo' => $capDataClassInfo,
                  'ownOrg' => $ownOrg
                ];
                $this->handleParticipationTaxonomyMapping($graphItem, $term, $participationTaxonomyMappingInfo);                 
                break;              
              case 'capdata_programmation': 
                $graphItem = new Programmation($termUrl);
                $participationTaxonomyMappingInfo = [
                  'capdataExportData' => $capdataExportData,
                  'host' => $host,
                  'capDataClassInfo' => $capDataClassInfo,
                  'ownOrg' => $ownOrg
                ];
                $this->handleParticipationTaxonomyMapping($graphItem, $term, $participationTaxonomyMappingInfo);                 
                break;
              case 'capdata_oeuvre': 
                $graphItem = new Oeuvre($termUrl);                 
                $this->setCatalogCapdataProperties($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");

                $capDataCollectiviteMappingType = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_collectivite"])){
                  $capDataCollectiviteMappingType = "content";
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_collectivite"])){
                  $capDataCollectiviteMappingType = "taxo";
                }
                $capDataCollectiviteMappingInfo = [
                    "capData_collectivite_mappingtype" => $capDataCollectiviteMappingType,
                    "default_collectivite" => $ownOrg,
                ];
                $currentCapdataClassMappingInfo =[
                    "host" => $host,
                    "mapping_type" =>  "taxo",
                    "mapped_entity" =>  $term,
                    "mapped_entity_properties" => $capDataClassInfo["export_class_capdata_properties"],
                ];
                $this->setCatalogageSourceAgenceProperty($graphItem,
                                                          $capDataCollectiviteMappingInfo,
                                                          $currentCapdataClassMappingInfo
                                                        );                
                // Titre, description ...
                $this->setCapdataTitle($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");    
                $this->setDescriptionCapdataProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo"); 
                $this->setArkBnfTraitCapdataProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");    
                $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo"); 
                if(!empty($arkBnfUrl)){
                  $arkBnf = new ArkBnf($arkBnfUrl);
                  $graph->add($arkBnf);
                  $graphItem->setArkBnf($arkBnf);
                }   
                $this->setIdentifiantRofProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");
                $this->setCapdataImageProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");                
                // Media image, sound 
                $mediaInfo = $this->getCapdataMediaPropertyInfo($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");
                if(!empty($mediaInfo) && isset($mediaInfo["mediaType"]) && !empty($mediaInfo["mediaUrl"])){
                  if($mediaInfo["mediaType"] == "image"){
                    $refImageUrl = $mediaInfo["mediaUrl"];
                    $capdataImage = new Image($refImageUrl);
                    $capdataImage->setContentUrl($refImageUrl);
                    $capdataImage->setName($graphItem->getTitre());
                    $graph->add($capdataImage);
                    $graphItem->setMedia($capdataImage); 
                  }
                  // Media sound to be implemented
                }                
                // Participations diverses
                $capDataParticipationMappingType = "";
                $capDataParticipationCorrespondantEntity = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_participation"])){
                  $capDataParticipationMappingType = "content";
                  if($capdataExportData["content_mapped_classes"]["capdata_participation"]["export_class_mapping_type"] == "capdata_participation_content_mapping"
                    &&
                    !empty($capdataExportData["content_mapped_classes"]["capdata_participation"]["export_class_content_dropdown"])
                  ){
                    $capDataParticipationCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_participation"]["export_class_content_dropdown"];
                  }                
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_participation"])){
                  $capDataParticipationMappingType = "taxo";
                  if($capdataExportData["taxo_mapped_classes"]["capdata_participation"]["export_class_mapping_type"] == "capdata_participation_taxo_mapping"
                    &&
                    !empty($capdataExportData["taxo_mapped_classes"]["capdata_participation"]["export_class_taxonomy_dropdown"])
                  ){
                    $capDataParticipationCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_participation"]["export_class_taxonomy_dropdown"];
                  }                  
                }
                $capDataParticipationMappingInfo = [
                    "capData_participation_mappingtype" => $capDataParticipationMappingType,
                    "capData_participation_correspondance" => $capDataParticipationCorrespondantEntity,
                ];
                $currentCapdataClassMappingInfo =[
                  "host" => $host,
                  "mapping_type" =>  "taxo",
                  "mapped_entity" =>  $term,
                  "mapped_entity_properties" => $capDataClassInfo["export_class_capdata_properties"],
                ];
                // A pour participation
                $this->setAPourParticipationCapdataProperty($graphItem,
                                                        $capDataParticipationMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                
                // A pour auteur (Auteur ou participation)
                $capDataAuteurMappingType = "";
                $capDataAuteurCorrespondantEntity = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_auteur"])){
                  $capDataAuteurMappingType = "content";
                  if($capdataExportData["content_mapped_classes"]["capdata_auteur"]["export_class_mapping_type"] == "capdata_auteur_content_mapping"
                    &&
                    !empty($capdataExportData["content_mapped_classes"]["capdata_auteur"]["export_class_content_dropdown"])
                  ){
                    $capDataAuteurCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_auteur"]["export_class_content_dropdown"];
                  }
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_auteur"])){
                  $capDataAuteurMappingType = "taxo";
                  if($capdataExportData["taxo_mapped_classes"]["capdata_auteur"]["export_class_mapping_type"] == "capdata_auteur_taxo_mapping"
                    &&
                    !empty($capdataExportData["taxo_mapped_classes"]["capdata_auteur"]["export_class_taxonomy_dropdown"])
                  ){
                    $capDataAuteurCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_auteur"]["export_class_taxonomy_dropdown"];
                  }                
                }
                $capDataAuteurMappingInfo = [
                    "capData_auteur_mappingtype" => $capDataAuteurMappingType,
                    "capData_auteur_correspondance" => $capDataAuteurCorrespondantEntity,
                ];              
                $this->setAPourAuteurCapdataProperty($graphItem,
                                                        $capDataAuteurMappingInfo,
                                                        $capDataParticipationMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                 
                // A pour Mention Production (MentionProduction ou Participation)
                $capDataMentionProductionMappingType = "";
                $capDataMentionProductionCorrespondantEntity = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_mentionproduction"])){
                  $capDataMentionProductionMappingType = "content";
                  if($capdataExportData["content_mapped_classes"]["capdata_mentionproduction"]["export_class_mapping_type"] == "capdata_mentionproduction_content_mapping"
                    &&
                    !empty($capdataExportData["content_mapped_classes"]["capdata_mentionproduction"]["export_class_content_dropdown"])
                  ){
                    $capDataMentionProductionCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_mentionproduction"]["export_class_content_dropdown"];
                  }
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"])){
                  $capDataMentionProductionMappingType = "taxo";
                  if($capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"]["export_class_mapping_type"] == "capdata_mentionproduction_taxo_mapping"
                    &&
                    !empty($capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"]["export_class_taxonomy_dropdown"])
                  ){
                    $capDataMentionProductionCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"]["export_class_taxonomy_dropdown"];
                  }                
                }
                $capDataMentionProductionMappingInfo = [
                    "capData_mentionproduction_mappingtype" => $capDataMentionProductionMappingType,
                    "capData_mentionproduction_correspondance" => $capDataMentionProductionCorrespondantEntity,
                ];              
                $this->setAPourMentionProductionCapdataProperty($graphItem,
                                                        $capDataMentionProductionMappingInfo,
                                                        $capDataParticipationMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                
                // A pour partenariat (Partenariat ou participation)
                $capDataPartenariatMappingType = "";
                $capDataPartenariatCorrespondantEntity = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_partenariat"])){
                  $capDataPartenariatMappingType = "content";
                  if($capdataExportData["content_mapped_classes"]["capdata_partenariat"]["export_class_mapping_type"] == "capdata_partenariat_content_mapping"
                    &&
                    !empty($capdataExportData["content_mapped_classes"]["capdata_partenariat"]["export_class_content_dropdown"])
                  ){
                    $capDataPartenariatCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_partenariat"]["export_class_content_dropdown"];
                  }
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_partenariat"])){
                  $capDataPartenariatMappingType = "taxo";
                  if($capdataExportData["taxo_mapped_classes"]["capdata_partenariat"]["export_class_mapping_type"] == "capdata_partenariat_taxo_mapping"
                    &&
                    !empty($capdataExportData["taxo_mapped_classes"]["capdata_partenariat"]["export_class_taxonomy_dropdown"])
                  ){
                    $capDataPartenariatCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_partenariat"]["export_class_taxonomy_dropdown"];
                  }                
                }
                $capDataPartenariatMappingInfo = [
                    "capData_partenariat_mappingtype" => $capDataPartenariatMappingType,
                    "capData_partenariat_correspondance" => $capDataPartenariatCorrespondantEntity,
                ];              
                $this->setAPourPartenariatCapdataProperty($graphItem,
                                                        $capDataPartenariatMappingInfo,
                                                        $capDataParticipationMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                
                // A pour interpretation (Interpretation ou participation)
                $capDataInterpretationMappingType = "";
                $capDataInterpretationCorrespondantEntity = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_interpretation"])){
                  $capDataInterpretationMappingType = "content";
                  if($capdataExportData["content_mapped_classes"]["capdata_interpretation"]["export_class_mapping_type"] == "capdata_interpretation_content_mapping"
                    &&
                    !empty($capdataExportData["content_mapped_classes"]["capdata_interpretation"]["export_class_content_dropdown"])
                  ){
                    $capDataInterpretationCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_interpretation"]["export_class_content_dropdown"];
                  }
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_interpretation"])){
                  $capDataInterpretationMappingType = "taxo";
                  if($capdataExportData["taxo_mapped_classes"]["capdata_interpretation"]["export_class_mapping_type"] == "capdata_interpretation_taxo_mapping"
                    &&
                    !empty($capdataExportData["taxo_mapped_classes"]["capdata_interpretation"]["export_class_taxonomy_dropdown"])
                  ){
                    $capDataInterpretationCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_interpretation"]["export_class_taxonomy_dropdown"];
                  }                
                }
                $capDataInterpretationMappingInfo = [
                    "capData_interpretation_mappingtype" => $capDataInterpretationMappingType,
                    "capData_interpretation_correspondance" => $capDataInterpretationCorrespondantEntity,
                ];              
                $this->setAPourInterpretationCapdataProperty($graphItem,
                                                        $capDataInterpretationMappingInfo,
                                                        $capDataParticipationMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                
                // Categorie Oeuvre
                $capDataCategorieOeuvreMappingType = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_categorieoeuvre"])){
                  $capDataCategorieOeuvreMappingType = "content";
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_categorieoeuvre"])){
                  $capDataCategorieOeuvreMappingType = "taxo";
                }
                $capDataCategorieOeuvreMappingInfo = [
                    "capData_categorieoeuvre_mappingtype" => $capDataCategorieOeuvreMappingType,
                ];
                $this->setCategorieOeuvreCapdataProperty($graphItem,
                                                    $capDataCategorieOeuvreMappingInfo,
                                                    $currentCapdataClassMappingInfo
                                                  );                
                // Genre Oeuvre
                $capDataGenreOeuvreMappingType = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_genreoeuvre"])){
                  $capDataGenreOeuvreMappingType = "content";
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_genreoeuvre"])){
                  $capDataGenreOeuvreMappingType = "taxo";
                }
                $capDataGenreOeuvreMappingInfo = [
                    "capData_genreoeuvre_mappingtype" => $capDataGenreOeuvreMappingType,
                ];
                $this->setGenreOeuvreCapdataProperty($graphItem,
                                                    $capDataGenreOeuvreMappingInfo,
                                                    $currentCapdataClassMappingInfo
                                                  );                
                // Type Oeuvre
                $capDataTypeOeuvreMappingType = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_typeoeuvre"])){
                  $capDataTypeOeuvreMappingType = "content";
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_typeoeuvre"])){
                  $capDataTypeOeuvreMappingType = "taxo";
                }
                $capDataTypeOeuvreMappingInfo = [
                    "capData_typeoeuvre_mappingtype" => $capDataTypeOeuvreMappingType,
                ];
                $this->setTypeOeuvreCapdataProperty($graphItem,
                                                    $capDataTypeOeuvreMappingInfo,
                                                    $currentCapdataClassMappingInfo
                                                  );                
                // Personnage (Role) 
                $capDataRoleMappingType = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_role"])){
                  $capDataRoleMappingType = "content";
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_role"])){
                  $capDataRoleMappingType = "taxo";
                }
                $capDataRoleMappingInfo = [
                    "capData_role_mappingtype" => $capDataRoleMappingType,
                ];
                $this->setPersonnageCapdataProperty($graphItem,
                                                    $capDataRoleMappingInfo,
                                                    $currentCapdataClassMappingInfo
                                                  );
                // Oeuvre titreFormeRejet, sourceLivret,  intrigue ..                                                
                $this->setOeuvreDetails($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");
                // Oeuvre dates
                $this->setOeuvreDatesCapdataProperties($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");                
                break;                               
              case 'capdata_productionprimaire':
                $graphItem = new ProductionPrimaire($termUrl);
                // Catalogage
                $this->setCatalogCapdataProperties($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");

                $capDataCollectiviteMappingType = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_collectivite"])){
                  $capDataCollectiviteMappingType = "content";
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_collectivite"])){
                  $capDataCollectiviteMappingType = "taxo";
                }
                $capDataCollectiviteMappingInfo = [
                    "capData_collectivite_mappingtype" => $capDataCollectiviteMappingType,
                    "default_collectivite" => $ownOrg,
                ];
                $currentCapdataClassMappingInfo =[
                    "host" => $host,
                    "mapping_type" =>  "taxo",
                    "mapped_entity" =>  $term,
                    "mapped_entity_properties" => $capDataClassInfo["export_class_capdata_properties"],
                ];
                $this->setCatalogageSourceAgenceProperty($graphItem,
                                                          $capDataCollectiviteMappingInfo,
                                                          $currentCapdataClassMappingInfo
                                                        );                
                // Titre, description ...
                $this->setCapdataTitle($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");    
                $this->setDescriptionCapdataProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo"); 
                $this->setArkBnfTraitCapdataProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");    
                $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo"); 
                if(!empty($arkBnfUrl)){
                  $arkBnf = new ArkBnf($arkBnfUrl);
                  $graph->add($arkBnf);
                  $graphItem->setArkBnf($arkBnf);
                }   
                $this->setIdentifiantRofProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");
                $this->setProductionDatesProperties($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");
                $this->setCapdataImageProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");                
                // Media image, sound 
                $mediaInfo = $this->getCapdataMediaPropertyInfo($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");
                if(!empty($mediaInfo) && isset($mediaInfo["mediaType"]) && !empty($mediaInfo["mediaUrl"])){
                  if($mediaInfo["mediaType"] == "image"){
                    $refImageUrl = $mediaInfo["mediaUrl"];
                    $capdataImage = new Image($refImageUrl);
                    $capdataImage->setContentUrl($refImageUrl);
                    $capdataImage->setName($graphItem->getTitre());
                    $graph->add($capdataImage);
                    $graphItem->setMedia($capdataImage); 
                  }
                  // Media sound to be implemented
                }
                // Participations diverses
                $capDataParticipationMappingType = "";
                $capDataParticipationCorrespondantEntity = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_participation"])){
                  $capDataParticipationMappingType = "content";
                  if($capdataExportData["content_mapped_classes"]["capdata_participation"]["export_class_mapping_type"] == "capdata_participation_content_mapping"
                    &&
                    !empty($capdataExportData["content_mapped_classes"]["capdata_participation"]["export_class_content_dropdown"])
                  ){
                    $capDataParticipationCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_participation"]["export_class_content_dropdown"];
                  }                
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_participation"])){
                  $capDataParticipationMappingType = "taxo";
                  if($capdataExportData["taxo_mapped_classes"]["capdata_participation"]["export_class_mapping_type"] == "capdata_participation_taxo_mapping"
                    &&
                    !empty($capdataExportData["taxo_mapped_classes"]["capdata_participation"]["export_class_taxonomy_dropdown"])
                  ){
                    $capDataParticipationCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_participation"]["export_class_taxonomy_dropdown"];
                  }                  
                }
                $capDataParticipationMappingInfo = [
                    "capData_participation_mappingtype" => $capDataParticipationMappingType,
                    "capData_participation_correspondance" => $capDataParticipationCorrespondantEntity,
                ];
                $currentCapdataClassMappingInfo =[
                  "host" => $host,
                  "mapping_type" =>  "taxo",
                  "mapped_entity" =>  $term,
                  "mapped_entity_properties" => $capDataClassInfo["export_class_capdata_properties"],
                ];
                // A pour participation
                $this->setAPourParticipationCapdataProperty($graphItem,
                                                        $capDataParticipationMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                
                // A pour auteur (Auteur ou participation)
                $capDataAuteurMappingType = "";
                $capDataAuteurCorrespondantEntity = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_auteur"])){
                  $capDataAuteurMappingType = "content";
                  if($capdataExportData["content_mapped_classes"]["capdata_auteur"]["export_class_mapping_type"] == "capdata_auteur_content_mapping"
                    &&
                    !empty($capdataExportData["content_mapped_classes"]["capdata_auteur"]["export_class_content_dropdown"])
                  ){
                    $capDataAuteurCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_auteur"]["export_class_content_dropdown"];
                  }
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_auteur"])){
                  $capDataAuteurMappingType = "taxo";
                  if($capdataExportData["taxo_mapped_classes"]["capdata_auteur"]["export_class_mapping_type"] == "capdata_auteur_taxo_mapping"
                    &&
                    !empty($capdataExportData["taxo_mapped_classes"]["capdata_auteur"]["export_class_taxonomy_dropdown"])
                  ){
                    $capDataAuteurCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_auteur"]["export_class_taxonomy_dropdown"];
                  }                
                }
                $capDataAuteurMappingInfo = [
                    "capData_auteur_mappingtype" => $capDataAuteurMappingType,
                    "capData_auteur_correspondance" => $capDataAuteurCorrespondantEntity,
                ];              
                $this->setAPourAuteurCapdataProperty($graphItem,
                                                        $capDataAuteurMappingInfo,
                                                        $capDataParticipationMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                 
                // A pour Mention Production (MentionProduction ou Participation)
                $capDataMentionProductionMappingType = "";
                $capDataMentionProductionCorrespondantEntity = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_mentionproduction"])){
                  $capDataMentionProductionMappingType = "content";
                  if($capdataExportData["content_mapped_classes"]["capdata_mentionproduction"]["export_class_mapping_type"] == "capdata_mentionproduction_content_mapping"
                    &&
                    !empty($capdataExportData["content_mapped_classes"]["capdata_mentionproduction"]["export_class_content_dropdown"])
                  ){
                    $capDataMentionProductionCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_mentionproduction"]["export_class_content_dropdown"];
                  }
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"])){
                  $capDataMentionProductionMappingType = "taxo";
                  if($capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"]["export_class_mapping_type"] == "capdata_mentionproduction_taxo_mapping"
                    &&
                    !empty($capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"]["export_class_taxonomy_dropdown"])
                  ){
                    $capDataMentionProductionCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"]["export_class_taxonomy_dropdown"];
                  }                
                }
                $capDataMentionProductionMappingInfo = [
                    "capData_mentionproduction_mappingtype" => $capDataMentionProductionMappingType,
                    "capData_mentionproduction_correspondance" => $capDataMentionProductionCorrespondantEntity,
                ];              
                $this->setAPourMentionProductionCapdataProperty($graphItem,
                                                        $capDataMentionProductionMappingInfo,
                                                        $capDataParticipationMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                
                // A pour partenariat (Partenariat ou participation)
                $capDataPartenariatMappingType = "";
                $capDataPartenariatCorrespondantEntity = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_partenariat"])){
                  $capDataPartenariatMappingType = "content";
                  if($capdataExportData["content_mapped_classes"]["capdata_partenariat"]["export_class_mapping_type"] == "capdata_partenariat_content_mapping"
                    &&
                    !empty($capdataExportData["content_mapped_classes"]["capdata_partenariat"]["export_class_content_dropdown"])
                  ){
                    $capDataPartenariatCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_partenariat"]["export_class_content_dropdown"];
                  }
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_partenariat"])){
                  $capDataPartenariatMappingType = "taxo";
                  if($capdataExportData["taxo_mapped_classes"]["capdata_partenariat"]["export_class_mapping_type"] == "capdata_partenariat_taxo_mapping"
                    &&
                    !empty($capdataExportData["taxo_mapped_classes"]["capdata_partenariat"]["export_class_taxonomy_dropdown"])
                  ){
                    $capDataPartenariatCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_partenariat"]["export_class_taxonomy_dropdown"];
                  }                
                }
                $capDataPartenariatMappingInfo = [
                    "capData_partenariat_mappingtype" => $capDataPartenariatMappingType,
                    "capData_partenariat_correspondance" => $capDataPartenariatCorrespondantEntity,
                ];              
                $this->setAPourPartenariatCapdataProperty($graphItem,
                                                        $capDataPartenariatMappingInfo,
                                                        $capDataParticipationMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                 
                // A pour collaboration (Collaboration ou participation)
                $capDataCollaborationMappingType = "";
                $capDataCollaborationCorrespondantEntity = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_collaboration"])){
                  $capDataCollaborationMappingType = "content";
                  if($capdataExportData["content_mapped_classes"]["capdata_collaboration"]["export_class_mapping_type"] == "capdata_collaboration_content_mapping"
                    &&
                    !empty($capdataExportData["content_mapped_classes"]["capdata_collaboration"]["export_class_content_dropdown"])
                  ){
                    $capDataCollaborationCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_collaboration"]["export_class_content_dropdown"];
                  }
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_collaboration"])){
                  $capDataCollaborationMappingType = "taxo";
                  if($capdataExportData["taxo_mapped_classes"]["capdata_collaboration"]["export_class_mapping_type"] == "capdata_collaboration_taxo_mapping"
                    &&
                    !empty($capdataExportData["taxo_mapped_classes"]["capdata_collaboration"]["export_class_taxonomy_dropdown"])
                  ){
                    $capDataCollaborationCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_collaboration"]["export_class_taxonomy_dropdown"];
                  }                
                }
                $capDataCollaborationMappingInfo = [
                    "capData_collaboration_mappingtype" => $capDataCollaborationMappingType,
                    "capData_collaboration_correspondance" => $capDataCollaborationCorrespondantEntity,
                ];              
                $this->setAPourCollaborationCapdataProperty($graphItem,
                                                        $capDataCollaborationMappingInfo,
                                                        $capDataParticipationMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                 
                // A pour interpretation (Interpretation ou participation)
                $capDataInterpretationMappingType = "";
                $capDataInterpretationCorrespondantEntity = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_interpretation"])){
                  $capDataInterpretationMappingType = "content";
                  if($capdataExportData["content_mapped_classes"]["capdata_interpretation"]["export_class_mapping_type"] == "capdata_interpretation_content_mapping"
                    &&
                    !empty($capdataExportData["content_mapped_classes"]["capdata_interpretation"]["export_class_content_dropdown"])
                  ){
                    $capDataInterpretationCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_interpretation"]["export_class_content_dropdown"];
                  }
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_interpretation"])){
                  $capDataInterpretationMappingType = "taxo";
                  if($capdataExportData["taxo_mapped_classes"]["capdata_interpretation"]["export_class_mapping_type"] == "capdata_interpretation_taxo_mapping"
                    &&
                    !empty($capdataExportData["taxo_mapped_classes"]["capdata_interpretation"]["export_class_taxonomy_dropdown"])
                  ){
                    $capDataInterpretationCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_interpretation"]["export_class_taxonomy_dropdown"];
                  }                
                }
                $capDataInterpretationMappingInfo = [
                    "capData_interpretation_mappingtype" => $capDataInterpretationMappingType,
                    "capData_interpretation_correspondance" => $capDataInterpretationCorrespondantEntity,
                ];              
                $this->setAPourInterpretationCapdataProperty($graphItem,
                                                        $capDataInterpretationMappingInfo,
                                                        $capDataParticipationMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                
                // A pour maitrise oeuvre (MaitriseOeuvre ou participation)
                $capDataMaitriseOeuvreMappingType = "";
                $capDataMaitriseOeuvreCorrespondantEntity = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_maitriseoeuvre"])){
                  $capDataMaitriseOeuvreMappingType = "content";
                  if($capdataExportData["content_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_mapping_type"] == "capdata_maitriseoeuvre_content_mapping"
                    &&
                    !empty($capdataExportData["content_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_content_dropdown"])
                  ){
                    $capDataMaitriseOeuvreCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_content_dropdown"];
                  }
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_maitriseoeuvre"])){
                  $capDataMaitriseOeuvreMappingType = "taxo";
                  if($capdataExportData["taxo_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_mapping_type"] == "capdata_maitriseoeuvre_taxo_mapping"
                    &&
                    !empty($capdataExportData["taxo_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_taxonomy_dropdown"])
                  ){
                    $capDataMaitriseOeuvreCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_taxonomy_dropdown"];
                  }                
                }
                $capDataMaitriseOeuvreMappingInfo = [
                    "capData_maitriseoeuvre_mappingtype" => $capDataMaitriseOeuvreMappingType,
                    "capData_maitriseoeuvre_correspondance" => $capDataMaitriseOeuvreCorrespondantEntity,
                ];              
                $this->setAPourMaitriseOeuvreCapdataProperty($graphItem,
                                                        $capDataMaitriseOeuvreMappingInfo,
                                                        $capDataParticipationMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );               
                // A pour programmation (Programmation ou participation)
                $capDataProgrammationMappingType = "";
                $capDataProgrammationCorrespondantEntity = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_programmation"])){
                  $capDataProgrammationMappingType = "content";
                  if($capdataExportData["content_mapped_classes"]["capdata_programmation"]["export_class_mapping_type"] == "capdata_programmation_content_mapping"
                    &&
                    !empty($capdataExportData["content_mapped_classes"]["capdata_programmation"]["export_class_content_dropdown"])
                  ){
                    $capDataProgrammationCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_programmation"]["export_class_content_dropdown"];
                  }
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_programmation"])){
                  $capDataProgrammationMappingType = "taxo";
                  if($capdataExportData["taxo_mapped_classes"]["capdata_programmation"]["export_class_mapping_type"] == "capdata_programmation_taxo_mapping"
                    &&
                    !empty($capdataExportData["taxo_mapped_classes"]["capdata_programmation"]["export_class_taxonomy_dropdown"])
                  ){
                    $capDataProgrammationCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_programmation"]["export_class_taxonomy_dropdown"];
                  }                
                }
                $capDataProgrammationMappingInfo = [
                    "capData_programmation_mappingtype" => $capDataProgrammationMappingType,
                    "capData_programmation_correspondance" => $capDataProgrammationCorrespondantEntity,
                ];              
                $this->setAPourProgrammationCapdataProperty($graphItem,
                                                        $capDataProgrammationMappingInfo,
                                                        $capDataParticipationMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                 
                // A pour saison (Saison) 
                $capDataSaisonMappingType = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_saison"])){
                  $capDataSaisonMappingType = "content";
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_saison"])){
                  $capDataSaisonMappingType = "taxo";
                }
                $capDataSaisonMappingInfo = [
                    "capData_saison_mappingtype" => $capDataSaisonMappingType,
                ];
                $this->setAPourSaisonCapdataProperty($graphItem,
                                                        $capDataSaisonMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                 
                // A pour type production  (TypeProduction)
                $capDataTypeProductionMappingType = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_typeproduction"])){
                  $capDataTypeProductionMappingType = "content";
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_typeproduction"])){
                  $capDataTypeProductionMappingType = "taxo";
                }
                $capDataTypeProductionMappingInfo = [
                    "capData_typeproduction_mappingtype" => $capDataTypeProductionMappingType,
                ];
                $this->setAPourTypeProductionCapdataProperty($graphItem,
                                                        $capDataTypeProductionMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                
                // A pour type public (TypePublic)
                $capDataTypePublicMappingType = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_typepublic"])){
                  $capDataTypePublicMappingType = "content";
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_typepublic"])){
                  $capDataTypePublicMappingType = "taxo";
                }
                $capDataTypePublicMappingInfo = [
                    "capData_typepublic_mappingtype" => $capDataTypePublicMappingType,
                ];
                $this->setAPourTypePublicCapdataProperty($graphItem,
                                                        $capDataTypePublicMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                
                // Historique (HistoriqueProduction) 
                $capDataHistoriqueProductionMappingType = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_historiqueproduction"])){
                  $capDataHistoriqueProductionMappingType = "content";
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_historiqueproduction"])){
                  $capDataHistoriqueProductionMappingType = "taxo";
                }
                $capDataHistoriqueProductionMappingInfo = [
                    "capData_historiqueproduction_mappingtype" => $capDataHistoriqueProductionMappingType,
                ];
                $this->setHistoriqueCapdataProperty($graphItem,
                                                    $capDataHistoriqueProductionMappingInfo,
                                                    $currentCapdataClassMappingInfo
                                                  );                
                // Lieu Publication (Lieu)
                $capDataLieuPublicationMappingType = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_lieu"])){
                  $capDataLieuPublicationMappingType = "content";
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_lieu"])){
                  $capDataLieuPublicationMappingType = "taxo";
                }
                $capDataLieuPublicationMappingInfo = [
                    "capData_lieu_mappingtype" => $capDataLieuPublicationMappingType,
                ];
                $this->setLieuPublicationCapdataProperty($graphItem,
                                                        $capDataLieuPublicationMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                 
                // Oeuvre representee (Oeuvre) 
                $capDataOeuvreMappingType = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_oeuvre"])){
                  $capDataOeuvreMappingType = "content";
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_oeuvre"])){
                  $capDataOeuvreMappingType = "taxo";
                }
                $capDataOeuvreMappingInfo = [
                    "capData_oeuvre_mappingtype" => $capDataOeuvreMappingType,
                ];
                $this->setOeuvreRepresenteeCapdataProperty($graphItem,
                                                        $capDataOeuvreMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                 
                break;              
              case 'capdata_production':
                $graphItem = new Production($termUrl);
                // Catalogage
                $this->setCatalogCapdataProperties($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");

                $capDataCollectiviteMappingType = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_collectivite"])){
                  $capDataCollectiviteMappingType = "content";
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_collectivite"])){
                  $capDataCollectiviteMappingType = "taxo";
                }
                $capDataCollectiviteMappingInfo = [
                    "capData_collectivite_mappingtype" => $capDataCollectiviteMappingType,
                    "default_collectivite" => $ownOrg,
                ];
                $currentCapdataClassMappingInfo =[
                    "host" => $host,
                    "mapping_type" =>  "taxo",
                    "mapped_entity" =>  $term,
                    "mapped_entity_properties" => $capDataClassInfo["export_class_capdata_properties"],
                ];
                $this->setCatalogageSourceAgenceProperty($graphItem,
                                                          $capDataCollectiviteMappingInfo,
                                                          $currentCapdataClassMappingInfo
                                                        );                
                // Titre, description ...
                $this->setCapdataTitle($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");    
                $this->setDescriptionCapdataProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo"); 
                $this->setArkBnfTraitCapdataProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");    
                $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo"); 
                if(!empty($arkBnfUrl)){
                  $arkBnf = new ArkBnf($arkBnfUrl);
                  $graph->add($arkBnf);
                  $graphItem->setArkBnf($arkBnf);
                }   
                $this->setIdentifiantRofProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");
                $this->setProductionDatesProperties($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");
                $this->setCapdataImageProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");                
                // Media image, sound 
                $mediaInfo = $this->getCapdataMediaPropertyInfo($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");
                if(!empty($mediaInfo) && isset($mediaInfo["mediaType"]) && !empty($mediaInfo["mediaUrl"])){
                  if($mediaInfo["mediaType"] == "image"){
                    $refImageUrl = $mediaInfo["mediaUrl"];
                    $capdataImage = new Image($refImageUrl);
                    $capdataImage->setContentUrl($refImageUrl);
                    $capdataImage->setName($graphItem->getTitre());
                    $graph->add($capdataImage);
                    $graphItem->setMedia($capdataImage); 
                  }
                  // Media sound to be implemented
                }
                // Participations diverses
                $capDataParticipationMappingType = "";
                $capDataParticipationCorrespondantEntity = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_participation"])){
                  $capDataParticipationMappingType = "content";
                  if($capdataExportData["content_mapped_classes"]["capdata_participation"]["export_class_mapping_type"] == "capdata_participation_content_mapping"
                    &&
                    !empty($capdataExportData["content_mapped_classes"]["capdata_participation"]["export_class_content_dropdown"])
                  ){
                    $capDataParticipationCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_participation"]["export_class_content_dropdown"];
                  }                
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_participation"])){
                  $capDataParticipationMappingType = "taxo";
                  if($capdataExportData["taxo_mapped_classes"]["capdata_participation"]["export_class_mapping_type"] == "capdata_participation_taxo_mapping"
                    &&
                    !empty($capdataExportData["taxo_mapped_classes"]["capdata_participation"]["export_class_taxonomy_dropdown"])
                  ){
                    $capDataParticipationCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_participation"]["export_class_taxonomy_dropdown"];
                  }                  
                }
                $capDataParticipationMappingInfo = [
                    "capData_participation_mappingtype" => $capDataParticipationMappingType,
                    "capData_participation_correspondance" => $capDataParticipationCorrespondantEntity,
                ];
                $currentCapdataClassMappingInfo =[
                  "host" => $host,
                  "mapping_type" =>  "taxo",
                  "mapped_entity" =>  $term,
                  "mapped_entity_properties" => $capDataClassInfo["export_class_capdata_properties"],
                ];
                // A pour participation
                $this->setAPourParticipationCapdataProperty($graphItem,
                                                        $capDataParticipationMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                
                // A pour auteur (Auteur ou participation)
                $capDataAuteurMappingType = "";
                $capDataAuteurCorrespondantEntity = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_auteur"])){
                  $capDataAuteurMappingType = "content";
                  if($capdataExportData["content_mapped_classes"]["capdata_auteur"]["export_class_mapping_type"] == "capdata_auteur_content_mapping"
                    &&
                    !empty($capdataExportData["content_mapped_classes"]["capdata_auteur"]["export_class_content_dropdown"])
                  ){
                    $capDataAuteurCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_auteur"]["export_class_content_dropdown"];
                  }
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_auteur"])){
                  $capDataAuteurMappingType = "taxo";
                  if($capdataExportData["taxo_mapped_classes"]["capdata_auteur"]["export_class_mapping_type"] == "capdata_auteur_taxo_mapping"
                    &&
                    !empty($capdataExportData["taxo_mapped_classes"]["capdata_auteur"]["export_class_taxonomy_dropdown"])
                  ){
                    $capDataAuteurCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_auteur"]["export_class_taxonomy_dropdown"];
                  }                
                }
                $capDataAuteurMappingInfo = [
                    "capData_auteur_mappingtype" => $capDataAuteurMappingType,
                    "capData_auteur_correspondance" => $capDataAuteurCorrespondantEntity,
                ];              
                $this->setAPourAuteurCapdataProperty($graphItem,
                                                        $capDataAuteurMappingInfo,
                                                        $capDataParticipationMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                 
                // A pour Mention Production (MentionProduction ou Participation)
                $capDataMentionProductionMappingType = "";
                $capDataMentionProductionCorrespondantEntity = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_mentionproduction"])){
                  $capDataMentionProductionMappingType = "content";
                  if($capdataExportData["content_mapped_classes"]["capdata_mentionproduction"]["export_class_mapping_type"] == "capdata_mentionproduction_content_mapping"
                    &&
                    !empty($capdataExportData["content_mapped_classes"]["capdata_mentionproduction"]["export_class_content_dropdown"])
                  ){
                    $capDataMentionProductionCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_mentionproduction"]["export_class_content_dropdown"];
                  }
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"])){
                  $capDataMentionProductionMappingType = "taxo";
                  if($capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"]["export_class_mapping_type"] == "capdata_mentionproduction_taxo_mapping"
                    &&
                    !empty($capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"]["export_class_taxonomy_dropdown"])
                  ){
                    $capDataMentionProductionCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"]["export_class_taxonomy_dropdown"];
                  }                
                }
                $capDataMentionProductionMappingInfo = [
                    "capData_mentionproduction_mappingtype" => $capDataMentionProductionMappingType,
                    "capData_mentionproduction_correspondance" => $capDataMentionProductionCorrespondantEntity,
                ];              
                $this->setAPourMentionProductionCapdataProperty($graphItem,
                                                        $capDataMentionProductionMappingInfo,
                                                        $capDataParticipationMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                
                // A pour partenariat (Partenariat ou participation)
                $capDataPartenariatMappingType = "";
                $capDataPartenariatCorrespondantEntity = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_partenariat"])){
                  $capDataPartenariatMappingType = "content";
                  if($capdataExportData["content_mapped_classes"]["capdata_partenariat"]["export_class_mapping_type"] == "capdata_partenariat_content_mapping"
                    &&
                    !empty($capdataExportData["content_mapped_classes"]["capdata_partenariat"]["export_class_content_dropdown"])
                  ){
                    $capDataPartenariatCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_partenariat"]["export_class_content_dropdown"];
                  }
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_partenariat"])){
                  $capDataPartenariatMappingType = "taxo";
                  if($capdataExportData["taxo_mapped_classes"]["capdata_partenariat"]["export_class_mapping_type"] == "capdata_partenariat_taxo_mapping"
                    &&
                    !empty($capdataExportData["taxo_mapped_classes"]["capdata_partenariat"]["export_class_taxonomy_dropdown"])
                  ){
                    $capDataPartenariatCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_partenariat"]["export_class_taxonomy_dropdown"];
                  }                
                }
                $capDataPartenariatMappingInfo = [
                    "capData_partenariat_mappingtype" => $capDataPartenariatMappingType,
                    "capData_partenariat_correspondance" => $capDataPartenariatCorrespondantEntity,
                ];              
                $this->setAPourPartenariatCapdataProperty($graphItem,
                                                        $capDataPartenariatMappingInfo,
                                                        $capDataParticipationMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                 
                // A pour collaboration (Collaboration ou participation)
                $capDataCollaborationMappingType = "";
                $capDataCollaborationCorrespondantEntity = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_collaboration"])){
                  $capDataCollaborationMappingType = "content";
                  if($capdataExportData["content_mapped_classes"]["capdata_collaboration"]["export_class_mapping_type"] == "capdata_collaboration_content_mapping"
                    &&
                    !empty($capdataExportData["content_mapped_classes"]["capdata_collaboration"]["export_class_content_dropdown"])
                  ){
                    $capDataCollaborationCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_collaboration"]["export_class_content_dropdown"];
                  }
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_collaboration"])){
                  $capDataCollaborationMappingType = "taxo";
                  if($capdataExportData["taxo_mapped_classes"]["capdata_collaboration"]["export_class_mapping_type"] == "capdata_collaboration_taxo_mapping"
                    &&
                    !empty($capdataExportData["taxo_mapped_classes"]["capdata_collaboration"]["export_class_taxonomy_dropdown"])
                  ){
                    $capDataCollaborationCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_collaboration"]["export_class_taxonomy_dropdown"];
                  }                
                }
                $capDataCollaborationMappingInfo = [
                    "capData_collaboration_mappingtype" => $capDataCollaborationMappingType,
                    "capData_collaboration_correspondance" => $capDataCollaborationCorrespondantEntity,
                ];              
                $this->setAPourCollaborationCapdataProperty($graphItem,
                                                        $capDataCollaborationMappingInfo,
                                                        $capDataParticipationMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                 
                // A pour interpretation (Interpretation ou participation)
                $capDataInterpretationMappingType = "";
                $capDataInterpretationCorrespondantEntity = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_interpretation"])){
                  $capDataInterpretationMappingType = "content";
                  if($capdataExportData["content_mapped_classes"]["capdata_interpretation"]["export_class_mapping_type"] == "capdata_interpretation_content_mapping"
                    &&
                    !empty($capdataExportData["content_mapped_classes"]["capdata_interpretation"]["export_class_content_dropdown"])
                  ){
                    $capDataInterpretationCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_interpretation"]["export_class_content_dropdown"];
                  }
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_interpretation"])){
                  $capDataInterpretationMappingType = "taxo";
                  if($capdataExportData["taxo_mapped_classes"]["capdata_interpretation"]["export_class_mapping_type"] == "capdata_interpretation_taxo_mapping"
                    &&
                    !empty($capdataExportData["taxo_mapped_classes"]["capdata_interpretation"]["export_class_taxonomy_dropdown"])
                  ){
                    $capDataInterpretationCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_interpretation"]["export_class_taxonomy_dropdown"];
                  }                
                }
                $capDataInterpretationMappingInfo = [
                    "capData_interpretation_mappingtype" => $capDataInterpretationMappingType,
                    "capData_interpretation_correspondance" => $capDataInterpretationCorrespondantEntity,
                ];              
                $this->setAPourInterpretationCapdataProperty($graphItem,
                                                        $capDataInterpretationMappingInfo,
                                                        $capDataParticipationMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                
                // A pour maitrise oeuvre (MaitriseOeuvre ou participation)
                $capDataMaitriseOeuvreMappingType = "";
                $capDataMaitriseOeuvreCorrespondantEntity = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_maitriseoeuvre"])){
                  $capDataMaitriseOeuvreMappingType = "content";
                  if($capdataExportData["content_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_mapping_type"] == "capdata_maitriseoeuvre_content_mapping"
                    &&
                    !empty($capdataExportData["content_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_content_dropdown"])
                  ){
                    $capDataMaitriseOeuvreCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_content_dropdown"];
                  }
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_maitriseoeuvre"])){
                  $capDataMaitriseOeuvreMappingType = "taxo";
                  if($capdataExportData["taxo_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_mapping_type"] == "capdata_maitriseoeuvre_taxo_mapping"
                    &&
                    !empty($capdataExportData["taxo_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_taxonomy_dropdown"])
                  ){
                    $capDataMaitriseOeuvreCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_taxonomy_dropdown"];
                  }                
                }
                $capDataMaitriseOeuvreMappingInfo = [
                    "capData_maitriseoeuvre_mappingtype" => $capDataMaitriseOeuvreMappingType,
                    "capData_maitriseoeuvre_correspondance" => $capDataMaitriseOeuvreCorrespondantEntity,
                ];              
                $this->setAPourMaitriseOeuvreCapdataProperty($graphItem,
                                                        $capDataMaitriseOeuvreMappingInfo,
                                                        $capDataParticipationMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );               
                // A pour programmation (Programmation ou participation)
                $capDataProgrammationMappingType = "";
                $capDataProgrammationCorrespondantEntity = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_programmation"])){
                  $capDataProgrammationMappingType = "content";
                  if($capdataExportData["content_mapped_classes"]["capdata_programmation"]["export_class_mapping_type"] == "capdata_programmation_content_mapping"
                    &&
                    !empty($capdataExportData["content_mapped_classes"]["capdata_programmation"]["export_class_content_dropdown"])
                  ){
                    $capDataProgrammationCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_programmation"]["export_class_content_dropdown"];
                  }
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_programmation"])){
                  $capDataProgrammationMappingType = "taxo";
                  if($capdataExportData["taxo_mapped_classes"]["capdata_programmation"]["export_class_mapping_type"] == "capdata_programmation_taxo_mapping"
                    &&
                    !empty($capdataExportData["taxo_mapped_classes"]["capdata_programmation"]["export_class_taxonomy_dropdown"])
                  ){
                    $capDataProgrammationCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_programmation"]["export_class_taxonomy_dropdown"];
                  }                
                }
                $capDataProgrammationMappingInfo = [
                    "capData_programmation_mappingtype" => $capDataProgrammationMappingType,
                    "capData_programmation_correspondance" => $capDataProgrammationCorrespondantEntity,
                ];              
                $this->setAPourProgrammationCapdataProperty($graphItem,
                                                        $capDataProgrammationMappingInfo,
                                                        $capDataParticipationMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                 
                // A pour saison (Saison) 
                $capDataSaisonMappingType = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_saison"])){
                  $capDataSaisonMappingType = "content";
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_saison"])){
                  $capDataSaisonMappingType = "taxo";
                }
                $capDataSaisonMappingInfo = [
                    "capData_saison_mappingtype" => $capDataSaisonMappingType,
                ];
                $this->setAPourSaisonCapdataProperty($graphItem,
                                                        $capDataSaisonMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                 
                // A pour type production  (TypeProduction)
                $capDataTypeProductionMappingType = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_typeproduction"])){
                  $capDataTypeProductionMappingType = "content";
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_typeproduction"])){
                  $capDataTypeProductionMappingType = "taxo";
                }
                $capDataTypeProductionMappingInfo = [
                    "capData_typeproduction_mappingtype" => $capDataTypeProductionMappingType,
                ];
                $this->setAPourTypeProductionCapdataProperty($graphItem,
                                                        $capDataTypeProductionMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                
                // A pour type public (TypePublic)
                $capDataTypePublicMappingType = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_typepublic"])){
                  $capDataTypePublicMappingType = "content";
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_typepublic"])){
                  $capDataTypePublicMappingType = "taxo";
                }
                $capDataTypePublicMappingInfo = [
                    "capData_typepublic_mappingtype" => $capDataTypePublicMappingType,
                ];
                $this->setAPourTypePublicCapdataProperty($graphItem,
                                                        $capDataTypePublicMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                
                // Historique (HistoriqueProduction) 
                $capDataHistoriqueProductionMappingType = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_historiqueproduction"])){
                  $capDataHistoriqueProductionMappingType = "content";
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_historiqueproduction"])){
                  $capDataHistoriqueProductionMappingType = "taxo";
                }
                $capDataHistoriqueProductionMappingInfo = [
                    "capData_historiqueproduction_mappingtype" => $capDataHistoriqueProductionMappingType,
                ];
                $this->setHistoriqueCapdataProperty($graphItem,
                                                    $capDataHistoriqueProductionMappingInfo,
                                                    $currentCapdataClassMappingInfo
                                                  );                
                // Lieu Publication (Lieu)
                $capDataLieuPublicationMappingType = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_lieu"])){
                  $capDataLieuPublicationMappingType = "content";
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_lieu"])){
                  $capDataLieuPublicationMappingType = "taxo";
                }
                $capDataLieuPublicationMappingInfo = [
                    "capData_lieu_mappingtype" => $capDataLieuPublicationMappingType,
                ];
                $this->setLieuPublicationCapdataProperty($graphItem,
                                                        $capDataLieuPublicationMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                 
                // Oeuvre representee (Oeuvre) 
                $capDataOeuvreMappingType = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_oeuvre"])){
                  $capDataOeuvreMappingType = "content";
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_oeuvre"])){
                  $capDataOeuvreMappingType = "taxo";
                }
                $capDataOeuvreMappingInfo = [
                    "capData_oeuvre_mappingtype" => $capDataOeuvreMappingType,
                ];
                $this->setOeuvreRepresenteeCapdataProperty($graphItem,
                                                        $capDataOeuvreMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                 
                // Production Primaire (ProductionPrimaire) 
                $capDataProductionPrimaireMappingType = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_productionprimaire"])){
                  $capDataProductionPrimaireMappingType = "content";
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_productionprimaire"])){
                  $capDataProductionPrimaireMappingType = "taxo";
                }
                $capDataProductionPrimaireMappingInfo = [
                    "capData_productionprimaire_mappingtype" => $capDataProductionPrimaireMappingType,
                ];
                $this->setProductionPrimaireCapdataProperty($graphItem,
                                                        $capDataProductionPrimaireMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                
                break;
              case 'capdata_evenement': 
                $graphItem = new Evenement($termUrl);
                // Catalogage
                $this->setCatalogCapdataProperties($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");

                $capDataCollectiviteMappingType = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_collectivite"])){
                  $capDataCollectiviteMappingType = "content";
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_collectivite"])){
                  $capDataCollectiviteMappingType = "taxo";
                }
                $capDataCollectiviteMappingInfo = [
                    "capData_collectivite_mappingtype" => $capDataCollectiviteMappingType,
                    "default_collectivite" => $ownOrg,
                ];
                $currentCapdataClassMappingInfo =[
                    "host" => $host,
                    "mapping_type" =>  "taxo",
                    "mapped_entity" =>  $term,
                    "mapped_entity_properties" => $capDataClassInfo["export_class_capdata_properties"],
                ];
                $this->setCatalogageSourceAgenceProperty($graphItem,
                                                          $capDataCollectiviteMappingInfo,
                                                          $currentCapdataClassMappingInfo
                                                        ); 
                // Titre, description ...
                $this->setCapdataTitle($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");    
                $this->setDescriptionCapdataProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo"); 
                $this->setArkBnfTraitCapdataProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");    
                $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");
                if(!empty($arkBnfUrl)){
                  $arkBnf = new ArkBnf($arkBnfUrl);
                  $graph->add($arkBnf);
                  $graphItem->setArkBnf($arkBnf);
                }    
                $this->setIdentifiantRofProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");
                $this->setCapdataImageProperty($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");                
                // Media image, sound 
                $mediaInfo = $this->getCapdataMediaPropertyInfo($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");
                if(!empty($mediaInfo) && isset($mediaInfo["mediaType"]) && !empty($mediaInfo["mediaUrl"])){
                  if($mediaInfo["mediaType"] == "image"){
                    $refImageUrl = $mediaInfo["mediaUrl"];
                    $capdataImage = new Image($refImageUrl);
                    $capdataImage->setContentUrl($refImageUrl);
                    $capdataImage->setName($graphItem->getTitre());
                    $graph->add($capdataImage);
                    $graphItem->setMedia($capdataImage); 
                  }
                  // Media sound to be implemented
                }                                                                            
                // Participations diverses
                $capDataParticipationMappingType = "";
                $capDataParticipationCorrespondantEntity = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_participation"])){
                  $capDataParticipationMappingType = "content";
                  if($capdataExportData["content_mapped_classes"]["capdata_participation"]["export_class_mapping_type"] == "capdata_participation_content_mapping"
                    &&
                    !empty($capdataExportData["content_mapped_classes"]["capdata_participation"]["export_class_content_dropdown"])
                  ){
                    $capDataParticipationCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_participation"]["export_class_content_dropdown"];
                  }                
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_participation"])){
                  $capDataParticipationMappingType = "taxo";
                  if($capdataExportData["taxo_mapped_classes"]["capdata_participation"]["export_class_mapping_type"] == "capdata_participation_taxo_mapping"
                    &&
                    !empty($capdataExportData["taxo_mapped_classes"]["capdata_participation"]["export_class_taxonomy_dropdown"])
                  ){
                    $capDataParticipationCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_participation"]["export_class_taxonomy_dropdown"];
                  }                  
                }
                $capDataParticipationMappingInfo = [
                    "capData_participation_mappingtype" => $capDataParticipationMappingType,
                    "capData_participation_correspondance" => $capDataParticipationCorrespondantEntity,
                ];
                $currentCapdataClassMappingInfo =[
                  "host" => $host,
                  "mapping_type" =>  "taxo",
                  "mapped_entity" =>  $term,
                  "mapped_entity_properties" => $capDataClassInfo["export_class_capdata_properties"],
                ];
                // A pour participation
                $this->setAPourParticipationCapdataProperty($graphItem,
                                                        $capDataParticipationMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                
                // A pour auteur (Auteur ou participation)
                $capDataAuteurMappingType = "";
                $capDataAuteurCorrespondantEntity = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_auteur"])){
                  $capDataAuteurMappingType = "content";
                  if($capdataExportData["content_mapped_classes"]["capdata_auteur"]["export_class_mapping_type"] == "capdata_auteur_content_mapping"
                    &&
                    !empty($capdataExportData["content_mapped_classes"]["capdata_auteur"]["export_class_content_dropdown"])
                  ){
                    $capDataAuteurCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_auteur"]["export_class_content_dropdown"];
                  }
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_auteur"])){
                  $capDataAuteurMappingType = "taxo";
                  if($capdataExportData["taxo_mapped_classes"]["capdata_auteur"]["export_class_mapping_type"] == "capdata_auteur_taxo_mapping"
                    &&
                    !empty($capdataExportData["taxo_mapped_classes"]["capdata_auteur"]["export_class_taxonomy_dropdown"])
                  ){
                    $capDataAuteurCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_auteur"]["export_class_taxonomy_dropdown"];
                  }                
                }
                $capDataAuteurMappingInfo = [
                    "capData_auteur_mappingtype" => $capDataAuteurMappingType,
                    "capData_auteur_correspondance" => $capDataAuteurCorrespondantEntity,
                ];              
                $this->setAPourAuteurCapdataProperty($graphItem,
                                                        $capDataAuteurMappingInfo,
                                                        $capDataParticipationMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                 
                // A pour Mention Production (MentionProduction ou Participation)
                $capDataMentionProductionMappingType = "";
                $capDataMentionProductionCorrespondantEntity = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_mentionproduction"])){
                  $capDataMentionProductionMappingType = "content";
                  if($capdataExportData["content_mapped_classes"]["capdata_mentionproduction"]["export_class_mapping_type"] == "capdata_mentionproduction_content_mapping"
                    &&
                    !empty($capdataExportData["content_mapped_classes"]["capdata_mentionproduction"]["export_class_content_dropdown"])
                  ){
                    $capDataMentionProductionCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_mentionproduction"]["export_class_content_dropdown"];
                  }
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"])){
                  $capDataMentionProductionMappingType = "taxo";
                  if($capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"]["export_class_mapping_type"] == "capdata_mentionproduction_taxo_mapping"
                    &&
                    !empty($capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"]["export_class_taxonomy_dropdown"])
                  ){
                    $capDataMentionProductionCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"]["export_class_taxonomy_dropdown"];
                  }                
                }
                $capDataMentionProductionMappingInfo = [
                    "capData_mentionproduction_mappingtype" => $capDataMentionProductionMappingType,
                    "capData_mentionproduction_correspondance" => $capDataMentionProductionCorrespondantEntity,
                ];              
                $this->setAPourMentionProductionCapdataProperty($graphItem,
                                                        $capDataMentionProductionMappingInfo,
                                                        $capDataParticipationMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                
                // A pour partenariat (Partenariat ou participation)
                $capDataPartenariatMappingType = "";
                $capDataPartenariatCorrespondantEntity = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_partenariat"])){
                  $capDataPartenariatMappingType = "content";
                  if($capdataExportData["content_mapped_classes"]["capdata_partenariat"]["export_class_mapping_type"] == "capdata_partenariat_content_mapping"
                    &&
                    !empty($capdataExportData["content_mapped_classes"]["capdata_partenariat"]["export_class_content_dropdown"])
                  ){
                    $capDataPartenariatCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_partenariat"]["export_class_content_dropdown"];
                  }
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_partenariat"])){
                  $capDataPartenariatMappingType = "taxo";
                  if($capdataExportData["taxo_mapped_classes"]["capdata_partenariat"]["export_class_mapping_type"] == "capdata_partenariat_taxo_mapping"
                    &&
                    !empty($capdataExportData["taxo_mapped_classes"]["capdata_partenariat"]["export_class_taxonomy_dropdown"])
                  ){
                    $capDataPartenariatCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_partenariat"]["export_class_taxonomy_dropdown"];
                  }                
                }
                $capDataPartenariatMappingInfo = [
                    "capData_partenariat_mappingtype" => $capDataPartenariatMappingType,
                    "capData_partenariat_correspondance" => $capDataPartenariatCorrespondantEntity,
                ];              
                $this->setAPourPartenariatCapdataProperty($graphItem,
                                                        $capDataPartenariatMappingInfo,
                                                        $capDataParticipationMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                
                // A pour collaboration (Collaboration ou participation)
                $capDataCollaborationMappingType = "";
                $capDataCollaborationCorrespondantEntity = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_collaboration"])){
                  $capDataCollaborationMappingType = "content";
                  if($capdataExportData["content_mapped_classes"]["capdata_collaboration"]["export_class_mapping_type"] == "capdata_collaboration_content_mapping"
                    &&
                    !empty($capdataExportData["content_mapped_classes"]["capdata_collaboration"]["export_class_content_dropdown"])
                  ){
                    $capDataCollaborationCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_collaboration"]["export_class_content_dropdown"];
                  }
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_collaboration"])){
                  $capDataCollaborationMappingType = "taxo";
                  if($capdataExportData["taxo_mapped_classes"]["capdata_collaboration"]["export_class_mapping_type"] == "capdata_collaboration_taxo_mapping"
                    &&
                    !empty($capdataExportData["taxo_mapped_classes"]["capdata_collaboration"]["export_class_taxonomy_dropdown"])
                  ){
                    $capDataCollaborationCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_collaboration"]["export_class_taxonomy_dropdown"];
                  }                
                }
                $capDataCollaborationMappingInfo = [
                    "capData_collaboration_mappingtype" => $capDataCollaborationMappingType,
                    "capData_collaboration_correspondance" => $capDataCollaborationCorrespondantEntity,
                ];              
                $this->setAPourCollaborationCapdataProperty($graphItem,
                                                        $capDataCollaborationMappingInfo,
                                                        $capDataParticipationMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                 
                // A pour interpretation (Interpretation ou participation)
                $capDataInterpretationMappingType = "";
                $capDataInterpretationCorrespondantEntity = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_interpretation"])){
                  $capDataInterpretationMappingType = "content";
                  if($capdataExportData["content_mapped_classes"]["capdata_interpretation"]["export_class_mapping_type"] == "capdata_interpretation_content_mapping"
                    &&
                    !empty($capdataExportData["content_mapped_classes"]["capdata_interpretation"]["export_class_content_dropdown"])
                  ){
                    $capDataInterpretationCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_interpretation"]["export_class_content_dropdown"];
                  }
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_interpretation"])){
                  $capDataInterpretationMappingType = "taxo";
                  if($capdataExportData["taxo_mapped_classes"]["capdata_interpretation"]["export_class_mapping_type"] == "capdata_interpretation_taxo_mapping"
                    &&
                    !empty($capdataExportData["taxo_mapped_classes"]["capdata_interpretation"]["export_class_taxonomy_dropdown"])
                  ){
                    $capDataInterpretationCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_interpretation"]["export_class_taxonomy_dropdown"];
                  }                
                }
                $capDataInterpretationMappingInfo = [
                    "capData_interpretation_mappingtype" => $capDataInterpretationMappingType,
                    "capData_interpretation_correspondance" => $capDataInterpretationCorrespondantEntity,
                ];              
                $this->setAPourInterpretationCapdataProperty($graphItem,
                                                        $capDataInterpretationMappingInfo,
                                                        $capDataParticipationMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                
                // A pour maitrise oeuvre (MaitriseOeuvre ou participation)
                $capDataMaitriseOeuvreMappingType = "";
                $capDataMaitriseOeuvreCorrespondantEntity = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_maitriseoeuvre"])){
                  $capDataMaitriseOeuvreMappingType = "content";
                  if($capdataExportData["content_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_mapping_type"] == "capdata_maitriseoeuvre_content_mapping"
                    &&
                    !empty($capdataExportData["content_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_content_dropdown"])
                  ){
                    $capDataMaitriseOeuvreCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_content_dropdown"];
                  }
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_maitriseoeuvre"])){
                  $capDataMaitriseOeuvreMappingType = "taxo";
                  if($capdataExportData["taxo_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_mapping_type"] == "capdata_maitriseoeuvre_taxo_mapping"
                    &&
                    !empty($capdataExportData["taxo_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_taxonomy_dropdown"])
                  ){
                    $capDataMaitriseOeuvreCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_taxonomy_dropdown"];
                  }                
                }
                $capDataMaitriseOeuvreMappingInfo = [
                    "capData_maitriseoeuvre_mappingtype" => $capDataMaitriseOeuvreMappingType,
                    "capData_maitriseoeuvre_correspondance" => $capDataMaitriseOeuvreCorrespondantEntity,
                ];              
                $this->setAPourMaitriseOeuvreCapdataProperty($graphItem,
                                                        $capDataMaitriseOeuvreMappingInfo,
                                                        $capDataParticipationMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );               
                // A pour programmation (Programmation ou participation)
                $capDataProgrammationMappingType = "";
                $capDataProgrammationCorrespondantEntity = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_programmation"])){
                  $capDataProgrammationMappingType = "content";
                  if($capdataExportData["content_mapped_classes"]["capdata_programmation"]["export_class_mapping_type"] == "capdata_programmation_content_mapping"
                    &&
                    !empty($capdataExportData["content_mapped_classes"]["capdata_programmation"]["export_class_content_dropdown"])
                  ){
                    $capDataProgrammationCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_programmation"]["export_class_content_dropdown"];
                  }
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_programmation"])){
                  $capDataProgrammationMappingType = "taxo";
                  if($capdataExportData["taxo_mapped_classes"]["capdata_programmation"]["export_class_mapping_type"] == "capdata_programmation_taxo_mapping"
                    &&
                    !empty($capdataExportData["taxo_mapped_classes"]["capdata_programmation"]["export_class_taxonomy_dropdown"])
                  ){
                    $capDataProgrammationCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_programmation"]["export_class_taxonomy_dropdown"];
                  }                
                }
                $capDataProgrammationMappingInfo = [
                    "capData_programmation_mappingtype" => $capDataProgrammationMappingType,
                    "capData_programmation_correspondance" => $capDataProgrammationCorrespondantEntity,
                ];              
                $this->setAPourProgrammationCapdataProperty($graphItem,
                                                        $capDataProgrammationMappingInfo,
                                                        $capDataParticipationMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                
                // A pour type public (TypePublic)
                $capDataTypePublicMappingType = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_typepublic"])){
                  $capDataTypePublicMappingType = "content";
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_typepublic"])){
                  $capDataTypePublicMappingType = "taxo";
                }
                $capDataTypePublicMappingInfo = [
                    "capData_typepublic_mappingtype" => $capDataTypePublicMappingType,
                ];
                $this->setAPourTypePublicCapdataProperty($graphItem,
                                                        $capDataTypePublicMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                 
                // Open agenda                                
                $this->setOpenAgendaCapdataProperty($graphItem, 
                                                  $term, 
                                                  $capDataClassInfo["export_class_capdata_properties"],
                                                  "taxo"
                                                );                 
                // A pour type evenement  (typeEvenement)
                $capDataTypeEvenementMappingType = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_typeevenement"])){
                  $capDataTypeEvenementMappingType = "content";
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_typeevenement"])){
                  $capDataTypeEvenementMappingType = "taxo";
                }
                $capDataTypeEvenementMappingInfo = [
                    "capData_typeevenement_mappingtype" => $capDataTypeEvenementMappingType,
                ];
                $this->setAPourTypeEvenementCapdataProperty($graphItem,
                                                        $capDataTypeEvenementMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                
                // A pour lieu (Lieu)
                $capDataLieuCustomMappingType = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_lieu"])){
                  $capDataLieuCustomMappingType = "content";
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_lieu"])){
                  $capDataLieuCustomMappingType = "taxo";
                }
                $capDataLieuCustomMappingInfo = [
                    "capData_lieu_mappingtype" => $capDataLieuCustomMappingType,
                ];
                $this->setAPourLieuCapdataProperty($graphItem,
                                                    $capDataLieuCustomMappingInfo,
                                                    $currentCapdataClassMappingInfo
                                                  );                 
                // A pour production (Production) 
                $capDataProductionCustomMappingType = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_production"])){
                  $capDataProductionCustomMappingType = "content";
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_production"])){
                  $capDataProductionCustomMappingType = "taxo";
                }
                $capDataProductionCustomMappingInfo = [
                    "capData_production_mappingtype" => $capDataProductionCustomMappingType,
                ];
                $this->setAPourProductionCapdataProperty($graphItem,
                                                        $capDataProductionCustomMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                 
                // Dates
                $this->setEventDatesCapdataProperties($graphItem, $term, $capDataClassInfo["export_class_capdata_properties"], "taxo");                
                break;               
              default:
                  $graphItem = null;
                  break;
            }
            if(isset($graphItem) && !empty($graphItem)){
              $this->moduleHandler->alter('capdata_graph_item', $graphItem, $term, $graph);
              $graph->add($graphItem);
            }
          }
        }
      }
    }
    /**
     * II. Export de tous les contenus
     */
    // cas où les classe capdata lieu, capdata type public sont des contenus
    foreach ($capdataExportData["content_mapped_classes"] as $capDataClassUniqueId => $capDataClassInfo) {
      $exportClassContentDropdown = $capDataClassInfo["export_class_content_dropdown"];
      if(!empty($exportClassContentDropdown)){
        $nodeStorage = $this->entityTypeManager->getStorage('node');
        switch ($capDataClassUniqueId) {           
          case 'capdata_typeproduction':
              $query = $nodeStorage->getQuery();
              $query->accessCheck(FALSE);
              $query->condition('type', $exportClassContentDropdown);
              $query->condition('status', 1);
              $results = $query->execute();
              $showTypes = $nodeStorage->loadMultiple($results);
              foreach ($showTypes as $showType) {
                $nid = $showType->id();
                $resourceUrl = $host . "/node/" . $nid;
                $typeProduction = new TypeProduction($resourceUrl);
                $this->setReferentielCustomProperties($typeProduction, 
                                                      $showType, 
                                                      $capDataClassInfo["export_class_capdata_properties"],
                                                      "content"
                                                    );
                $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($typeProduction, $showType, $capDataClassInfo["export_class_capdata_properties"], "content");                                
                if(!empty($arkBnfUrl)){
                  $arkBnf = new ArkBnf($arkBnfUrl);
                  $graph->add($arkBnf);
                  $typeProduction->setArkBnf($arkBnf);
                }                 
                $graph->add($typeProduction);
              }
              break;
          case 'capdata_typepublic':
                $query = $nodeStorage->getQuery();
                $query->accessCheck(FALSE);
                $query->condition('type', $exportClassContentDropdown);
                $query->condition('status', 1);
                $results = $query->execute();
                $publicTypes = $nodeStorage->loadMultiple($results);
              foreach ($publicTypes as $publicType) {
                $nid = $publicType->id();
                $resourceUrl = $host . "/node/" . $nid;
                $typePublic = new TypePublic($resourceUrl);
                $this->setReferentielCustomProperties($typePublic, 
                                                      $publicType, 
                                                      $capDataClassInfo["export_class_capdata_properties"],
                                                      "content"
                                                    );
                $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($typePublic, $publicType, $capDataClassInfo["export_class_capdata_properties"], "content");                                
                if(!empty($arkBnfUrl)){
                  $arkBnf = new ArkBnf($arkBnfUrl);
                  $graph->add($arkBnf);
                  $typePublic->setArkBnf($arkBnf);
                }                
                $graph->add($typePublic);
              }
              break;              
          case 'capdata_typeevenement':
                $query = $nodeStorage->getQuery();
                $query->accessCheck(FALSE);
                $query->condition('type', $exportClassContentDropdown);
                $query->condition('status', 1);
                $results = $query->execute();
                $eventTypes = $nodeStorage->loadMultiple($results);
              foreach ($eventTypes as $eventType) {
                $nid = $eventType->id();
                $resourceUrl = $host . "/node/" . $nid;
                $typeEvenement = new TypeEvenement($resourceUrl);
                $this->setReferentielCustomProperties($typeEvenement, 
                                                      $eventType, 
                                                      $capDataClassInfo["export_class_capdata_properties"],
                                                      "content"
                                                    );
                $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($typeEvenement, $eventType, $capDataClassInfo["export_class_capdata_properties"], "content");                                
                if(!empty($arkBnfUrl)){
                  $arkBnf = new ArkBnf($arkBnfUrl);
                  $graph->add($arkBnf);
                  $typeEvenement->setArkBnf($arkBnf);
                }                 
                $graph->add($typeEvenement);
              }
              break;          
          case 'capdata_typeoeuvre':
                $query = $nodeStorage->getQuery();
                $query->accessCheck(FALSE);
                $query->condition('type', $exportClassContentDropdown);
                $query->condition('status', 1);
                $results = $query->execute();
                $artWorkTypes = $nodeStorage->loadMultiple($results);
              foreach ($artWorkTypes as $artWorkType) {
                $nid = $artWorkType->id();
                $resourceUrl = $host . "/node/" . $nid;
                $typeOeuvre = new TypeOeuvre($resourceUrl);
                $this->setReferentielCustomProperties($typeOeuvre, 
                                                      $artWorkType, 
                                                      $capDataClassInfo["export_class_capdata_properties"],
                                                      "content"
                                                    );
                $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($typeOeuvre, $artWorkType, $capDataClassInfo["export_class_capdata_properties"], "content");                                
                if(!empty($arkBnfUrl)){
                  $arkBnf = new ArkBnf($arkBnfUrl);
                  $graph->add($arkBnf);
                  $typeOeuvre->setArkBnf($arkBnf);
                }                
                $graph->add($typeOeuvre);
              }
              break;          
          case 'capdata_statutjuridique':
                $query = $nodeStorage->getQuery();
                $query->accessCheck(FALSE);
                $query->condition('type', $exportClassContentDropdown);
                $query->condition('status', 1);
                $results = $query->execute();
                $statutJuridiqueNodes = $nodeStorage->loadMultiple($results);
              foreach ($statutJuridiqueNodes as $statutJuridiqueNode) {
                $nid = $statutJuridiqueNode->id();
                $resourceUrl = $host . "/node/" . $nid;
                $statutJuridique = new StatusJuridique($resourceUrl);
                $this->setReferentielCustomProperties($statutJuridique, 
                                                      $statutJuridiqueNode, 
                                                      $capDataClassInfo["export_class_capdata_properties"],
                                                      "content"
                                                    );
                $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($statutJuridique, $statutJuridiqueNode, $capDataClassInfo["export_class_capdata_properties"], "content");                               
                if(!empty($arkBnfUrl)){
                  $arkBnf = new ArkBnf($arkBnfUrl);
                  $graph->add($arkBnf);
                  $statutJuridique->setArkBnf($arkBnf);
                }                 
                $graph->add($statutJuridique);
              }
              break;          
          case 'capdata_genreoeuvre':
                $query = $nodeStorage->getQuery();
                $query->accessCheck(FALSE);
                $query->condition('type', $exportClassContentDropdown);
                $query->condition('status', 1);
                $results = $query->execute();
                $genreOeuvreNodes = $nodeStorage->loadMultiple($results);
              foreach ($genreOeuvreNodes as $genreOeuvreNode) {
                $nid = $genreOeuvreNode->id();
                $resourceUrl = $host . "/node/" . $nid;
                $genreOeuvre = new GenreOeuvre($resourceUrl);
                $this->setReferentielCustomProperties($genreOeuvre, 
                                                      $genreOeuvreNode, 
                                                      $capDataClassInfo["export_class_capdata_properties"],
                                                      "content"
                                                    );
                $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($genreOeuvre, $genreOeuvreNode, $capDataClassInfo["export_class_capdata_properties"], "content");                                
                if(!empty($arkBnfUrl)){
                  $arkBnf = new ArkBnf($arkBnfUrl);
                  $graph->add($arkBnf);
                  $genreOeuvre->setArkBnf($arkBnf);
                }                
                $graph->add($genreOeuvre);
              }
              break;          
          case 'capdata_historiqueproduction':
                $query = $nodeStorage->getQuery();
                $query->accessCheck(FALSE);
                $query->condition('type', $exportClassContentDropdown);
                $query->condition('status', 1);
                $results = $query->execute();
                $historiqueProductionNodes = $nodeStorage->loadMultiple($results);
              foreach ($historiqueProductionNodes as $historiqueProductionNode) {
                $nid = $historiqueProductionNode->id();
                $resourceUrl = $host . "/node/" . $nid;
                $historiqueProduction = new HistoriqueProduction($resourceUrl);
                $this->setReferentielCustomProperties($historiqueProduction, 
                                                      $historiqueProductionNode, 
                                                      $capDataClassInfo["export_class_capdata_properties"],
                                                      "content"
                                                    );
                $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($historiqueProduction, $historiqueProductionNode, $capDataClassInfo["export_class_capdata_properties"], "content");                                
                if(!empty($arkBnfUrl)){
                  $arkBnf = new ArkBnf($arkBnfUrl);
                  $graph->add($arkBnf);
                  $historiqueProduction->setArkBnf($arkBnf);
                }                 
                $graph->add($historiqueProduction);
              }
              break;          
          case 'capdata_categorieoeuvre':
                $query = $nodeStorage->getQuery();
                $query->accessCheck(FALSE);
                $query->condition('type', $exportClassContentDropdown);
                $query->condition('status', 1);
                $results = $query->execute();
                $oeuvreCategories = $nodeStorage->loadMultiple($results);
              foreach ($oeuvreCategories as $oeuvreCategory) {
                $nid = $oeuvreCategory->id();
                $resourceUrl = $host . "/node/" . $nid;
                $categorieOeuvre = new CategorieOeuvre($resourceUrl);
                $this->setReferentielCustomProperties($categorieOeuvre, 
                                                      $oeuvreCategory, 
                                                      $capDataClassInfo["export_class_capdata_properties"],
                                                      "content"
                                                    );
                $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($categorieOeuvre, $oeuvreCategory, $capDataClassInfo["export_class_capdata_properties"], "content");                                
                if(!empty($arkBnfUrl)){
                  $arkBnf = new ArkBnf($arkBnfUrl);
                  $graph->add($arkBnf);
                  $categorieOeuvre->setArkBnf($arkBnf);
                }                 
                $graph->add($categorieOeuvre);
              }
              break;          
          case 'capdata_role':
                $query = $nodeStorage->getQuery();
                $query->accessCheck(FALSE);
                $query->condition('type', $exportClassContentDropdown);
                $query->condition('status', 1);
                $results = $query->execute();
                $roleNodes = $nodeStorage->loadMultiple($results);
              foreach ($roleNodes as $roleNode) {
                $nid = $roleNode->id();
                $resourceUrl = $host . "/node/" . $nid;
                $role = new Role($resourceUrl);
                $this->setReferentielCustomProperties($role, 
                                                      $roleNode, 
                                                      $capDataClassInfo["export_class_capdata_properties"],
                                                      "content"
                                                    );
                $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($role, $roleNode, $capDataClassInfo["export_class_capdata_properties"], "content");                                
                if(!empty($arkBnfUrl)){
                  $arkBnf = new ArkBnf($arkBnfUrl);
                  $graph->add($arkBnf);
                  $role->setArkBnf($arkBnf);
                }                 
                $graph->add($role);
              }
              break;          
          case 'capdata_pays':
                $query = $nodeStorage->getQuery();
                $query->accessCheck(FALSE);
                $query->condition('type', $exportClassContentDropdown);
                $query->condition('status', 1);
                $results = $query->execute();
                $countries = $nodeStorage->loadMultiple($results);
              foreach ($countries as $country) {
                $nid = $country->id();
                $resourceUrl = $host . "/node/" . $nid;
                $pays = new Pays($resourceUrl);
                $this->setReferentielCustomProperties($pays, 
                                                      $country, 
                                                      $capDataClassInfo["export_class_capdata_properties"],
                                                      "content"
                                                    );
                $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($pays, $country, $capDataClassInfo["export_class_capdata_properties"], "content");                                
                if(!empty($arkBnfUrl)){
                  $arkBnf = new ArkBnf($arkBnfUrl);
                  $graph->add($arkBnf);
                  $pays->setArkBnf($arkBnf);
                }                 
                $graph->add($pays);
              }
              break;          
          case 'capdata_fonction':
                $query = $nodeStorage->getQuery();
                $query->accessCheck(FALSE);
                $query->condition('type', $exportClassContentDropdown);
                $query->condition('status', 1);
                $results = $query->execute();
                $refFonctions = $nodeStorage->loadMultiple($results);
              foreach ($refFonctions as $refFonction) {
                $nid = $refFonction->id();
                $resourceUrl = $host . "/node/" . $nid;
                $fonction = new Fonction($resourceUrl);
                $this->setReferentielCustomProperties($fonction, 
                                                      $refFonction, 
                                                      $capDataClassInfo["export_class_capdata_properties"],
                                                      "content"
                                                    );
                $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($fonction, $refFonction, $capDataClassInfo["export_class_capdata_properties"], "content");                                
                if(!empty($arkBnfUrl)){
                  $arkBnf = new ArkBnf($arkBnfUrl);
                  $graph->add($arkBnf);
                  $fonction->setArkBnf($arkBnf);
                }                
                $graph->add($fonction);
              }
              break;
          case 'capdata_collectivite':
                $query = $nodeStorage->getQuery();
                $query->accessCheck(FALSE);
                $query->condition('type', $exportClassContentDropdown);
                $query->condition('status', 1);
                $results = $query->execute();
                $collectivities = $nodeStorage->loadMultiple($results);
              foreach ($collectivities as $collectivity) {
                $nid = $collectivity->id();
                $resourceUrl = $host . "/node/" . $nid;
                $collectivite = new Collectivite($resourceUrl);
                $this->setCapdataCollectiviteProperties($collectivite, $collectivity, $capDataClassInfo, "content");                                                  
                $isniUrl = $this->setIsniCapdataProperty($collectivite, $collectivity, $capDataClassInfo["export_class_capdata_properties"], "content");   
                if(!empty($isniUrl)){
                  $isni = new Isni($isniUrl);
                  $graph->add($isni);
                  $collectivite->setIsni($isni);
                }
                // A pour fonction 
                $capDataFonctionMappingType = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_fonction"])){
                  $capDataFonctionMappingType = "content";
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_fonction"])){
                  $capDataFonctionMappingType = "taxo";
                }
                $capDataFonctionMappingInfo = [
                  "capData_fonction_mappingtype" => $capDataFonctionMappingType,
                ];                  
                $currentCapdataClassMappingInfo =[
                  "host" => $host,
                  "mapping_type" =>  "content",
                  "mapped_entity" =>  $collectivity,
                  "mapped_entity_properties" => $capDataClassInfo["export_class_capdata_properties"],
                ]; 
                $this->setAPourFonctionCapdataProperty($collectivite,
                                                        $capDataFonctionMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );                 
                $this->setCapdataImageProperty($collectivite, $collectivity, $capDataClassInfo["export_class_capdata_properties"], "content");
                // Media image, sound 
                $mediaInfo = $this->getCapdataMediaPropertyInfo($collectivite, $collectivity, $capDataClassInfo["export_class_capdata_properties"], "content");
                if(!empty($mediaInfo) && isset($mediaInfo["mediaType"]) && !empty($mediaInfo["mediaUrl"])){
                  if($mediaInfo["mediaType"] == "image"){
                    $refImageUrl = $mediaInfo["mediaUrl"];
                    $capdataImage = new Image($refImageUrl);
                    $capdataImage->setContentUrl($refImageUrl);
                    $capdataImage->setName($collectivite->getNom());
                    $graph->add($capdataImage);
                    $collectivite->setMedia($capdataImage);
                  }
                  // Media sound to be implemented
                }            
                // A pour lieu (Lieu)
                $capDataLieuCustomMappingType = "";
                if(isset($capdataExportData["content_mapped_classes"]["capdata_lieu"])){
                  $capDataLieuCustomMappingType = "content";
                }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_lieu"])){
                  $capDataLieuCustomMappingType = "taxo";
                }
                $capDataLieuCustomMappingInfo = [
                    "capData_lieu_mappingtype" => $capDataLieuCustomMappingType,
                ];
                $this->setAPourLieuCapdataProperty($collectivite,
                                                    $capDataLieuCustomMappingInfo,
                                                    $currentCapdataClassMappingInfo
                                                  );   
                // Identifiant ROF
                $this->setIdentifiantRofProperty($collectivite, $collectivity, $capDataClassInfo["export_class_capdata_properties"], "content");
                $graph->add($collectivite);
              }
              break;
          case 'capdata_adressepostale':
                $query = $nodeStorage->getQuery();
                $query->accessCheck(FALSE);
                $query->condition('type', $exportClassContentDropdown);
                $query->condition('status', 1);
                $results = $query->execute();
                $adressesPostales = $nodeStorage->loadMultiple($results);
                foreach ($adressesPostales as $adrPostale) {
                  $nid = $adrPostale->id();
                  $resourceUrl = $host . "/node/" . $nid;
                  $capdataAdressePostale = new AdressePostale($resourceUrl);
                  // Commune
                  $commune = "";
                  if(isset($capDataClassInfo["export_class_capdata_properties"]["commune"])){
                    if(!empty($capDataClassInfo["export_class_capdata_properties"]["commune"]["property_content_fields_dropdown"])){
                      $communeFieldName = $capDataClassInfo["export_class_capdata_properties"]["commune"]["property_content_fields_dropdown"];
                      if ($adrPostale->hasField($communeFieldName) && !$adrPostale->get($communeFieldName)->isEmpty()) {
                        $commune = $adrPostale->get($communeFieldName)->value;
                        if(!empty($commune)){
                          if(!empty($capDataClassInfo["export_class_capdata_properties"]["commune"]["property_content_custom_processing"])){
                            $customProcessing = $capDataClassInfo["export_class_capdata_properties"]["commune"]["property_content_custom_processing"];
                            $commune = $this->customFieldProcessing($commune, $customProcessing);
                          }
                          $capdataAdressePostale->setCommune($commune);
                        }
                      }
                    }
                  }
                  // Adresse postale (texte)
                  $adressePostaleTexte = "";
                  if(isset($capDataClassInfo["export_class_capdata_properties"]["adresse_postale_txt"])){
                    if(!empty($capDataClassInfo["export_class_capdata_properties"]["adresse_postale_txt"]["property_content_fields_dropdown"])){
                      $adressePostaleTxtFieldName = $capDataClassInfo["export_class_capdata_properties"]["adresse_postale_txt"]["property_content_fields_dropdown"];
                      if ($adrPostale->hasField($adressePostaleTxtFieldName) && !$adrPostale->get($adressePostaleTxtFieldName)->isEmpty()) {
                        $adressePostaleTexte = $adrPostale->get($adressePostaleTxtFieldName)->value;
                        if(!empty($adressePostaleTexte)){
                          if(!empty($capDataClassInfo["export_class_capdata_properties"]["adresse_postale_txt"]["property_content_custom_processing"])){
                            $customProcessing = $capDataClassInfo["export_class_capdata_properties"]["adresse_postale_txt"]["property_content_custom_processing"];
                            $adressePostaleTexte = $this->customFieldProcessing($adressePostaleTexte, $customProcessing);
                          }
                          $capdataAdressePostale->setAdressePostale($adressePostaleTexte);
                        }
                      }
                    }
                  }
                  // Code postal
                  $codePostal = "";
                  if(isset($capDataClassInfo["export_class_capdata_properties"]["code_postal"])){
                    if(!empty($capDataClassInfo["export_class_capdata_properties"]["code_postal"]["property_content_fields_dropdown"])){
                      $codePostalFieldName = $capDataClassInfo["export_class_capdata_properties"]["code_postal"]["property_content_fields_dropdown"];
                      if ($adrPostale->hasField($codePostalFieldName) && !$adrPostale->get($codePostalFieldName)->isEmpty()) {
                        $codePostal = $adrPostale->get($codePostalFieldName)->value;
                        if(!empty($codePostal)){
                          if(!empty($capDataClassInfo["export_class_capdata_properties"]["code_postal"]["property_content_custom_processing"])){
                            $customProcessing = $capDataClassInfo["export_class_capdata_properties"]["code_postal"]["property_content_custom_processing"];
                            $codePostal = $this->customFieldProcessing($codePostal, $customProcessing);
                          }
                          $capdataAdressePostale->setCodePostal($codePostal);
                        }
                      }
                    }
                  }
                  // Identifiant Rof
                  $this->setIdentifiantRofProperty($capdataAdressePostale, $adrPostale, $capDataClassInfo["export_class_capdata_properties"], "content");
                  $graph->add($capdataAdressePostale);
                }
                break;
          case 'capdata_lieu':
            $query = $nodeStorage->getQuery();
            $query->accessCheck(FALSE);
            $query->condition('type', $exportClassContentDropdown);
            $query->condition('status', 1);
            $results = $query->execute();
            $lieuxGeographiques = $nodeStorage->loadMultiple($results);
            foreach ($lieuxGeographiques as $lieuGeographique) {
              $nid = $lieuGeographique->id();
              $resourceUrl = $host . "/node/" . $nid;
              $lieu = new Lieu($resourceUrl);
              // Label
              $label = $lieuGeographique->getTitle();
              if(isset($capDataClassInfo["export_class_capdata_properties"]["cd_label"])){
                if(!empty($capDataClassInfo["export_class_capdata_properties"]["cd_label"]["property_content_fields_dropdown"])){
                  $labelFieldName = $capDataClassInfo["export_class_capdata_properties"]["cd_label"]["property_content_fields_dropdown"];
                  if ($lieuGeographique->hasField($labelFieldName) && !$lieuGeographique->get($labelFieldName)->isEmpty()) {
                    $label = $lieuGeographique->get($labelFieldName)->value;
                    if(!empty($label)){
                      if(!empty($capDataClassInfo["export_class_capdata_properties"]["cd_label"]["property_content_custom_processing"])){
                        $customProcessing = $capDataClassInfo["export_class_capdata_properties"]["cd_label"]["property_content_custom_processing"];
                        $label = $this->customFieldProcessing($label, $customProcessing);
                      }
                    }
                  }
                }
              }
              $this->setNameTraitCapdataProperties($lieu, $label);
              // Catalogage
              $this->setCatalogCapdataProperties($lieu, 
                                                  $lieuGeographique, 
                                                  $capDataClassInfo["export_class_capdata_properties"],
                                                  "content"
                                                );  
              $capDataCollectiviteMappingType = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_collectivite"])){
                $capDataCollectiviteMappingType = "content";
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_collectivite"])){
                $capDataCollectiviteMappingType = "taxo";
              }
              $capDataCollectiviteMappingInfo = [
                  "capData_collectivite_mappingtype" => $capDataCollectiviteMappingType,
                  "default_collectivite" => $ownOrg,
              ];
              $currentCapdataClassMappingInfo =[
                  "host" => $host,
                  "mapping_type" =>  "content",
                  "mapped_entity" =>  $lieuGeographique,
                  "mapped_entity_properties" => $capDataClassInfo["export_class_capdata_properties"],
              ];
              $this->setCatalogageSourceAgenceProperty($lieu,
                                                        $capDataCollectiviteMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );
              // Open agenda                                  
              $this->setOpenAgendaCapdataProperty($lieu, 
                                                  $lieuGeographique, 
                                                  $capDataClassInfo["export_class_capdata_properties"],
                                                  "content"
                                                  );                                                                          
              // Description
              $this->setDescriptionCapdataProperty($lieu, 
                                                  $lieuGeographique, 
                                                  $capDataClassInfo["export_class_capdata_properties"],
                                                  "content"
                                                ); 
              // Identifiant Rof
              $this->setIdentifiantRofProperty($lieu, $lieuGeographique, $capDataClassInfo["export_class_capdata_properties"], "content");
              $graph->add($lieu);
            }            
            break; 
          case 'capdata_personne':
            $query = $nodeStorage->getQuery();
            $query->accessCheck(FALSE);
            $query->condition('type', $exportClassContentDropdown);
            $query->condition('status', 1);
            $results = $query->execute();
            $artists = $nodeStorage->loadMultiple($results);
            foreach ($artists as $artist) {
              $nid = $artist->id();
              $resourceUrl = $host . "/node/" . $nid;
              $personne = new Personne($resourceUrl);
              $this->setPersonalDetails($personne, $artist, $capDataClassInfo["export_class_capdata_properties"], "content");     
              $this->setIdentifiantRofProperty($personne, $artist, $capDataClassInfo["export_class_capdata_properties"], "content");
              $this->setSocialsCapdataProperties($personne, $artist, $capDataClassInfo["export_class_capdata_properties"], "content");  
              $isniUrl = $this->setIsniCapdataProperty($personne, $artist, $capDataClassInfo["export_class_capdata_properties"], "content");  
              if(!empty($isniUrl)){
                $isni = new Isni($isniUrl);
                $graph->add($isni);
                $personne->setIsni($isni);
              }
              $this->setDescriptionCapdataProperty($personne, $artist, $capDataClassInfo["export_class_capdata_properties"], "content");
              $this->setArkBnfTraitCapdataProperty($personne, $artist, $capDataClassInfo["export_class_capdata_properties"], "content");                 
              $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($personne, $artist, $capDataClassInfo["export_class_capdata_properties"], "content");
              if(!empty($arkBnfUrl)){
                $arkBnf = new ArkBnf($arkBnfUrl);
                $graph->add($arkBnf);
                $personne->setArkBnf($arkBnf);
              }                 
              $this->setCapdataImageProperty($personne, $artist, $capDataClassInfo["export_class_capdata_properties"], "content");
              // Media image, sound 
              $mediaInfo = $this->getCapdataMediaPropertyInfo($personne, $artist, $capDataClassInfo["export_class_capdata_properties"], "content");
              if(!empty($mediaInfo) && isset($mediaInfo["mediaType"]) && !empty($mediaInfo["mediaUrl"])){
                if($mediaInfo["mediaType"] == "image"){
                  $refImageUrl = $mediaInfo["mediaUrl"];
                  $capdataImage = new Image($refImageUrl);
                  $capdataImage->setContentUrl($refImageUrl);
                  $prenom = $personne->getPrenom();
                  $nom = $personne->getNom();
                  $capdataImage->setName($prenom. ' '.$nom);                  
                  $graph->add($capdataImage);
                  $personne->setMedia($capdataImage); 
                }
                // Media sound to be implemented
              }              
              // Catalogage
              $this->setCatalogCapdataProperties($personne, $artist, $capDataClassInfo["export_class_capdata_properties"], "content");

              $capDataCollectiviteMappingType = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_collectivite"])){
                $capDataCollectiviteMappingType = "content";
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_collectivite"])){
                $capDataCollectiviteMappingType = "taxo";
              }
              $capDataCollectiviteMappingInfo = [
                  "capData_collectivite_mappingtype" => $capDataCollectiviteMappingType,
                  "default_collectivite" => $ownOrg,
              ];
              $currentCapdataClassMappingInfo =[
                  "host" => $host,
                  "mapping_type" =>  "content",
                  "mapped_entity" =>  $artist,
                  "mapped_entity_properties" => $capDataClassInfo["export_class_capdata_properties"],
              ];
              $this->setCatalogageSourceAgenceProperty($personne,
                                                        $capDataCollectiviteMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );
              // A pour fonction 
              $capDataFonctionMappingType = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_fonction"])){
                $capDataFonctionMappingType = "content";
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_fonction"])){
                $capDataFonctionMappingType = "taxo";
              }
              $capDataFonctionMappingInfo = [
                  "capData_fonction_mappingtype" => $capDataFonctionMappingType,
              ];
              $currentCapdataClassMappingInfo =[
                "host" => $host,
                "mapping_type" =>  "content",
                "mapped_entity" =>  $artist,
                "mapped_entity_properties" => $capDataClassInfo["export_class_capdata_properties"],
              ];
              $this->setAPourFonctionCapdataProperty($personne,
                                                      $capDataFonctionMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );                
              // A pour profession
              $this->setAPourProfessionCapdataProperty($personne,
                                                      $capDataFonctionMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );                
              $this->moduleHandler->alter('capdata_graph_item', $personne, $artist, $graph);              
              $graph->add($personne);
            }
            break;
          case 'capdata_saison':
            $query = $nodeStorage->getQuery();
            $query->accessCheck(FALSE);
            $query->condition('type', $exportClassContentDropdown);
            $query->condition('status', 1);
            $results = $query->execute();
            $seasons = $nodeStorage->loadMultiple($results);
            foreach ($seasons as $season) {
              $nid = $season->id();
              $resourceUrl = $host . "/node/" . $nid;
              $saison = new Saison($resourceUrl);
              $this->setReferentielCustomProperties($saison,
                                                    $season,
                                                    $capDataClassInfo["export_class_capdata_properties"],
                                                    "content"
                                                  );
              $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($saison, $season, $capDataClassInfo["export_class_capdata_properties"], "content");                              
              if(!empty($arkBnfUrl)){
                $arkBnf = new ArkBnf($arkBnfUrl);
                $graph->add($arkBnf);
                $saison->setArkBnf($arkBnf);
              }                
              // Media image, sound 
              $mediaInfo = $this->getCapdataMediaPropertyInfo($saison, $season, $capDataClassInfo["export_class_capdata_properties"], "content");
              if(!empty($mediaInfo) && isset($mediaInfo["mediaType"]) && !empty($mediaInfo["mediaUrl"])){
                if($mediaInfo["mediaType"] == "image"){
                  $refImageUrl = $mediaInfo["mediaUrl"];
                  $capdataImage = new Image($refImageUrl);
                  $capdataImage->setContentUrl($refImageUrl);
                  $capdataImage->setName($saison->getLabel());
                  $graph->add($capdataImage);
                  $saison->setMedia($capdataImage); 
                }
                // Media sound to be implemented
              }              
              // Alter
              $this->moduleHandler->alter('capdata_graph_item', $saison, $season, $graph);                                    
              $graph->add($saison);
            }
            break;
          case 'capdata_participation':
            $query = $nodeStorage->getQuery();
            $query->accessCheck(FALSE);
            $query->condition('type', $exportClassContentDropdown);
            $query->condition('status', 1);
            $results = $query->execute();
            $allParticipations = $nodeStorage->loadMultiple($results);
            foreach ($allParticipations as $participationItem) {
              $nid = $participationItem->id();
              $resourceUrl = $host . "/node/" . $nid;
              $participation = new Participation($resourceUrl);
              $participationContentMappingInfo = [
                'capdataExportData' => $capdataExportData,
                'host' => $host,
                'capDataClassInfo' => $capDataClassInfo,
                'ownOrg' => $ownOrg
              ];
              $this->handleParticipationContentMapping($participation, $participationItem, $participationContentMappingInfo);
              $graph->add($participation);
            }
            break;          
          case 'capdata_auteur':
            $query = $nodeStorage->getQuery();
            $query->accessCheck(FALSE);
            $query->condition('type', $exportClassContentDropdown);
            $query->condition('status', 1);
            $results = $query->execute();
            $allAuthors = $nodeStorage->loadMultiple($results);
            foreach ($allAuthors as $authorItem) {
              $nid = $authorItem->id();
              $resourceUrl = $host . "/node/" . $nid;
              $auteur = new Auteur($resourceUrl);
              $participationContentMappingInfo = [
                'capdataExportData' => $capdataExportData,
                'host' => $host,
                'capDataClassInfo' => $capDataClassInfo,
                'ownOrg' => $ownOrg
              ];
              $this->handleParticipationContentMapping($auteur, $authorItem, $participationContentMappingInfo);
              $graph->add($auteur);
            }
            break;          
          case 'capdata_collaboration':
            $query = $nodeStorage->getQuery();
            $query->accessCheck(FALSE);
            $query->condition('type', $exportClassContentDropdown);
            $query->condition('status', 1);
            $results = $query->execute();
            $allCollaborations = $nodeStorage->loadMultiple($results);
            foreach ($allCollaborations as $collaborationItem) {
              $nid = $collaborationItem->id();
              $resourceUrl = $host . "/node/" . $nid;
              $collaboration = new Collaboration($resourceUrl);
              $participationContentMappingInfo = [
                'capdataExportData' => $capdataExportData,
                'host' => $host,
                'capDataClassInfo' => $capDataClassInfo,
                'ownOrg' => $ownOrg
              ];
              $this->handleParticipationContentMapping($collaboration, $collaborationItem, $participationContentMappingInfo);
              $graph->add($collaboration);
            }
            break;           
          case 'capdata_interpretation':
            $query = $nodeStorage->getQuery();
            $query->accessCheck(FALSE);
            $query->condition('type', $exportClassContentDropdown);
            $query->condition('status', 1);
            $results = $query->execute();
            $allInterpretations = $nodeStorage->loadMultiple($results);
            foreach ($allInterpretations as $interpretationItem) {
              $nid = $interpretationItem->id();
              $resourceUrl = $host . "/node/" . $nid;
              $interpretation = new Interpretation($resourceUrl);
              $participationContentMappingInfo = [
                'capdataExportData' => $capdataExportData,
                'host' => $host,
                'capDataClassInfo' => $capDataClassInfo,
                'ownOrg' => $ownOrg
              ];
              $this->handleParticipationContentMapping($interpretation, $interpretationItem, $participationContentMappingInfo);
              $graph->add($interpretation);
            }
            break;            
          case 'capdata_maitriseoeuvre':
            $query = $nodeStorage->getQuery();
            $query->accessCheck(FALSE);
            $query->condition('type', $exportClassContentDropdown);
            $query->condition('status', 1);
            $results = $query->execute();
            $allMaitrisesOeuvre = $nodeStorage->loadMultiple($results);
            foreach ($allMaitrisesOeuvre as $maitriseOeuvreItem) {
              $nid = $maitriseOeuvreItem->id();
              $resourceUrl = $host . "/node/" . $nid;
              $maitriseOeuvre = new MaitriseOeuvre($resourceUrl);
              $participationContentMappingInfo = [
                'capdataExportData' => $capdataExportData,
                'host' => $host,
                'capDataClassInfo' => $capDataClassInfo,
                'ownOrg' => $ownOrg
              ];
              $this->handleParticipationContentMapping($maitriseOeuvre, $maitriseOeuvreItem, $participationContentMappingInfo);
              $graph->add($maitriseOeuvre);
            }
            break;             
          case 'capdata_mentionproduction':
            $query = $nodeStorage->getQuery();
            $query->accessCheck(FALSE);
            $query->condition('type', $exportClassContentDropdown);
            $query->condition('status', 1);
            $results = $query->execute();
            $allMentionsProduction = $nodeStorage->loadMultiple($results);
            foreach ($allMentionsProduction as $mentionProductionItem) {
              $nid = $mentionProductionItem->id();
              $resourceUrl = $host . "/node/" . $nid;
              $mentionProduction = new MentionProduction($resourceUrl);
              $participationContentMappingInfo = [
                'capdataExportData' => $capdataExportData,
                'host' => $host,
                'capDataClassInfo' => $capDataClassInfo,
                'ownOrg' => $ownOrg
              ];
              $this->handleParticipationContentMapping($mentionProduction, $mentionProductionItem, $participationContentMappingInfo);
              $graph->add($mentionProduction);
            }
            break;           
          case 'capdata_partenariat':
            $query = $nodeStorage->getQuery();
            $query->accessCheck(FALSE);
            $query->condition('type', $exportClassContentDropdown);
            $query->condition('status', 1);
            $results = $query->execute();
            $allPartenariats = $nodeStorage->loadMultiple($results);
            foreach ($allPartenariats as $partenariatItem) {
              $nid = $partenariatItem->id();
              $resourceUrl = $host . "/node/" . $nid;
              $partenariat = new Partenariat($resourceUrl);
              $participationContentMappingInfo = [
                'capdataExportData' => $capdataExportData,
                'host' => $host,
                'capDataClassInfo' => $capDataClassInfo,
                'ownOrg' => $ownOrg
              ];
              $this->handleParticipationContentMapping($partenariat, $partenariatItem, $participationContentMappingInfo);
              $graph->add($partenariat);
            }
            break;                 
          case 'capdata_programmation':
            $query = $nodeStorage->getQuery();
            $query->accessCheck(FALSE);
            $query->condition('type', $exportClassContentDropdown);
            $query->condition('status', 1);
            $results = $query->execute();
            $allProgrammations = $nodeStorage->loadMultiple($results);
            foreach ($allProgrammations as $programmationItem) {
              $nid = $programmationItem->id();
              $resourceUrl = $host . "/node/" . $nid;
              $programmation = new Programmation($resourceUrl);
              $participationContentMappingInfo = [
                'capdataExportData' => $capdataExportData,
                'host' => $host,
                'capDataClassInfo' => $capDataClassInfo,
                'ownOrg' => $ownOrg
              ];
              $this->handleParticipationContentMapping($programmation, $programmationItem, $participationContentMappingInfo);
              $graph->add($programmation);
            }
            break;           
          case 'capdata_oeuvre':
            $query = $nodeStorage->getQuery();
            $query->accessCheck(FALSE);
            $query->condition('type', $exportClassContentDropdown);
            $query->condition('status', 1);
            $results = $query->execute();
            $allOeuvres = $nodeStorage->loadMultiple($results);
            foreach ($allOeuvres as $oeuvreItem) {
              $nid = $oeuvreItem->id();
              $resourceUrl = $host . "/node/" . $nid;
              $oeuvre = new Oeuvre($resourceUrl);
              // Catalogage
              $this->setCatalogCapdataProperties($oeuvre, $oeuvreItem, $capDataClassInfo["export_class_capdata_properties"], "content");

              $capDataCollectiviteMappingType = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_collectivite"])){
                $capDataCollectiviteMappingType = "content";
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_collectivite"])){
                $capDataCollectiviteMappingType = "taxo";
              }
              $capDataCollectiviteMappingInfo = [
                  "capData_collectivite_mappingtype" => $capDataCollectiviteMappingType,
                  "default_collectivite" => $ownOrg,
              ];
              $currentCapdataClassMappingInfo =[
                  "host" => $host,
                  "mapping_type" =>  "content",
                  "mapped_entity" =>  $oeuvreItem,
                  "mapped_entity_properties" => $capDataClassInfo["export_class_capdata_properties"],
              ];
              $this->setCatalogageSourceAgenceProperty($oeuvre,
                                                        $capDataCollectiviteMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );              
              // Titre, description ...
              $this->setCapdataTitle($oeuvre, $oeuvreItem, $capDataClassInfo["export_class_capdata_properties"], "content");    
              $this->setDescriptionCapdataProperty($oeuvre, $oeuvreItem, $capDataClassInfo["export_class_capdata_properties"], "content"); 
              $this->setArkBnfTraitCapdataProperty($oeuvre, $oeuvreItem, $capDataClassInfo["export_class_capdata_properties"], "content");    
              $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($oeuvre, $oeuvreItem, $capDataClassInfo["export_class_capdata_properties"], "content");    
              if(!empty($arkBnfUrl)){
                $arkBnf = new ArkBnf($arkBnfUrl);
                $graph->add($arkBnf);
                $oeuvre->setArkBnf($arkBnf);
              }               
              $this->setIdentifiantRofProperty($oeuvre, $oeuvreItem, $capDataClassInfo["export_class_capdata_properties"], "content");
              $this->setCapdataImageProperty($oeuvre, $oeuvreItem, $capDataClassInfo["export_class_capdata_properties"], "content");
              // Media image, sound 
              $mediaInfo = $this->getCapdataMediaPropertyInfo($oeuvre, $oeuvreItem, $capDataClassInfo["export_class_capdata_properties"], "content");
              if(!empty($mediaInfo) && isset($mediaInfo["mediaType"]) && !empty($mediaInfo["mediaUrl"])){
                if($mediaInfo["mediaType"] == "image"){
                  $refImageUrl = $mediaInfo["mediaUrl"];
                  $capdataImage = new Image($refImageUrl);
                  $capdataImage->setContentUrl($refImageUrl);
                  $capdataImage->setName($oeuvre->getTitre());
                  $graph->add($capdataImage);
                  $oeuvre->setMedia($capdataImage); 
                }
                // Media sound to be implemented
              }
              // Participations diverses
              $capDataParticipationMappingType = "";
              $capDataParticipationCorrespondantEntity = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_participation"])){
                $capDataParticipationMappingType = "content";
                if($capdataExportData["content_mapped_classes"]["capdata_participation"]["export_class_mapping_type"] == "capdata_participation_content_mapping"
                  &&
                  !empty($capdataExportData["content_mapped_classes"]["capdata_participation"]["export_class_content_dropdown"])
                ){
                  $capDataParticipationCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_participation"]["export_class_content_dropdown"];
                }                
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_participation"])){
                $capDataParticipationMappingType = "taxo";
                if($capdataExportData["taxo_mapped_classes"]["capdata_participation"]["export_class_mapping_type"] == "capdata_participation_taxo_mapping"
                  &&
                  !empty($capdataExportData["taxo_mapped_classes"]["capdata_participation"]["export_class_taxonomy_dropdown"])
                ){
                  $capDataParticipationCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_participation"]["export_class_taxonomy_dropdown"];
                }                  
              }
              $capDataParticipationMappingInfo = [
                  "capData_participation_mappingtype" => $capDataParticipationMappingType,
                  "capData_participation_correspondance" => $capDataParticipationCorrespondantEntity,
              ];
              $currentCapdataClassMappingInfo =[
                "host" => $host,
                "mapping_type" =>  "content",
                "mapped_entity" =>  $oeuvreItem,
                "mapped_entity_properties" => $capDataClassInfo["export_class_capdata_properties"],
              ];
              // A pour participation
              $this->setAPourParticipationCapdataProperty($oeuvre,
                                                      $capDataParticipationMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );
              // A pour auteur (Auteur ou participation)
              $capDataAuteurMappingType = "";
              $capDataAuteurCorrespondantEntity = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_auteur"])){
                $capDataAuteurMappingType = "content";
                if($capdataExportData["content_mapped_classes"]["capdata_auteur"]["export_class_mapping_type"] == "capdata_auteur_content_mapping"
                  &&
                  !empty($capdataExportData["content_mapped_classes"]["capdata_auteur"]["export_class_content_dropdown"])
                ){
                  $capDataAuteurCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_auteur"]["export_class_content_dropdown"];
                }
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_auteur"])){
                $capDataAuteurMappingType = "taxo";
                if($capdataExportData["taxo_mapped_classes"]["capdata_auteur"]["export_class_mapping_type"] == "capdata_auteur_taxo_mapping"
                  &&
                  !empty($capdataExportData["taxo_mapped_classes"]["capdata_auteur"]["export_class_taxonomy_dropdown"])
                ){
                  $capDataAuteurCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_auteur"]["export_class_taxonomy_dropdown"];
                }                
              }
              $capDataAuteurMappingInfo = [
                  "capData_auteur_mappingtype" => $capDataAuteurMappingType,
                  "capData_auteur_correspondance" => $capDataAuteurCorrespondantEntity,
              ];              
              $this->setAPourAuteurCapdataProperty($oeuvre,
                                                      $capDataAuteurMappingInfo,
                                                      $capDataParticipationMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );           
              // A pour Mention Production (MentionProduction ou Participation)
              $capDataMentionProductionMappingType = "";
              $capDataMentionProductionCorrespondantEntity = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_mentionproduction"])){
                $capDataMentionProductionMappingType = "content";
                if($capdataExportData["content_mapped_classes"]["capdata_mentionproduction"]["export_class_mapping_type"] == "capdata_mentionproduction_content_mapping"
                  &&
                  !empty($capdataExportData["content_mapped_classes"]["capdata_mentionproduction"]["export_class_content_dropdown"])
                ){
                  $capDataMentionProductionCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_mentionproduction"]["export_class_content_dropdown"];
                }
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"])){
                $capDataMentionProductionMappingType = "taxo";
                if($capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"]["export_class_mapping_type"] == "capdata_mentionproduction_taxo_mapping"
                  &&
                  !empty($capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"]["export_class_taxonomy_dropdown"])
                ){
                  $capDataMentionProductionCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"]["export_class_taxonomy_dropdown"];
                }                
              }
              $capDataMentionProductionMappingInfo = [
                  "capData_mentionproduction_mappingtype" => $capDataMentionProductionMappingType,
                  "capData_mentionproduction_correspondance" => $capDataMentionProductionCorrespondantEntity,
              ];              
              $this->setAPourMentionProductionCapdataProperty($oeuvre,
                                                      $capDataMentionProductionMappingInfo,
                                                      $capDataParticipationMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );                
              // A pour partenariat (Partenariat ou participation)
              $capDataPartenariatMappingType = "";
              $capDataPartenariatCorrespondantEntity = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_partenariat"])){
                $capDataPartenariatMappingType = "content";
                if($capdataExportData["content_mapped_classes"]["capdata_partenariat"]["export_class_mapping_type"] == "capdata_partenariat_content_mapping"
                  &&
                  !empty($capdataExportData["content_mapped_classes"]["capdata_partenariat"]["export_class_content_dropdown"])
                ){
                  $capDataPartenariatCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_partenariat"]["export_class_content_dropdown"];
                }
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_partenariat"])){
                $capDataPartenariatMappingType = "taxo";
                if($capdataExportData["taxo_mapped_classes"]["capdata_partenariat"]["export_class_mapping_type"] == "capdata_partenariat_taxo_mapping"
                  &&
                  !empty($capdataExportData["taxo_mapped_classes"]["capdata_partenariat"]["export_class_taxonomy_dropdown"])
                ){
                  $capDataPartenariatCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_partenariat"]["export_class_taxonomy_dropdown"];
                }                
              }
              $capDataPartenariatMappingInfo = [
                  "capData_partenariat_mappingtype" => $capDataPartenariatMappingType,
                  "capData_partenariat_correspondance" => $capDataPartenariatCorrespondantEntity,
              ];              
              $this->setAPourPartenariatCapdataProperty($oeuvre,
                                                      $capDataPartenariatMappingInfo,
                                                      $capDataParticipationMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );                
              // A pour interpretation (Interpretation ou participation)
              $capDataInterpretationMappingType = "";
              $capDataInterpretationCorrespondantEntity = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_interpretation"])){
                $capDataInterpretationMappingType = "content";
                if($capdataExportData["content_mapped_classes"]["capdata_interpretation"]["export_class_mapping_type"] == "capdata_interpretation_content_mapping"
                  &&
                  !empty($capdataExportData["content_mapped_classes"]["capdata_interpretation"]["export_class_content_dropdown"])
                ){
                  $capDataInterpretationCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_interpretation"]["export_class_content_dropdown"];
                }
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_interpretation"])){
                $capDataInterpretationMappingType = "taxo";
                if($capdataExportData["taxo_mapped_classes"]["capdata_interpretation"]["export_class_mapping_type"] == "capdata_interpretation_taxo_mapping"
                  &&
                  !empty($capdataExportData["taxo_mapped_classes"]["capdata_interpretation"]["export_class_taxonomy_dropdown"])
                ){
                  $capDataInterpretationCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_interpretation"]["export_class_taxonomy_dropdown"];
                }                
              }
              $capDataInterpretationMappingInfo = [
                  "capData_interpretation_mappingtype" => $capDataInterpretationMappingType,
                  "capData_interpretation_correspondance" => $capDataInterpretationCorrespondantEntity,
              ];              
              $this->setAPourInterpretationCapdataProperty($oeuvre,
                                                      $capDataInterpretationMappingInfo,
                                                      $capDataParticipationMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );               
              // Categorie Oeuvre
              $capDataCategorieOeuvreMappingType = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_categorieoeuvre"])){
                $capDataCategorieOeuvreMappingType = "content";
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_categorieoeuvre"])){
                $capDataCategorieOeuvreMappingType = "taxo";
              }
              $capDataCategorieOeuvreMappingInfo = [
                  "capData_categorieoeuvre_mappingtype" => $capDataCategorieOeuvreMappingType,
              ];
              $this->setCategorieOeuvreCapdataProperty($oeuvre,
                                                  $capDataCategorieOeuvreMappingInfo,
                                                  $currentCapdataClassMappingInfo
                                                );               
              // Genre Oeuvre
              $capDataGenreOeuvreMappingType = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_genreoeuvre"])){
                $capDataGenreOeuvreMappingType = "content";
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_genreoeuvre"])){
                $capDataGenreOeuvreMappingType = "taxo";
              }
              $capDataGenreOeuvreMappingInfo = [
                  "capData_genreoeuvre_mappingtype" => $capDataGenreOeuvreMappingType,
              ];
              $this->setGenreOeuvreCapdataProperty($oeuvre,
                                                  $capDataGenreOeuvreMappingInfo,
                                                  $currentCapdataClassMappingInfo
                                                );              
              // Type Oeuvre
              $capDataTypeOeuvreMappingType = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_typeoeuvre"])){
                $capDataTypeOeuvreMappingType = "content";
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_typeoeuvre"])){
                $capDataTypeOeuvreMappingType = "taxo";
              }
              $capDataTypeOeuvreMappingInfo = [
                  "capData_typeoeuvre_mappingtype" => $capDataTypeOeuvreMappingType,
              ];
              $this->setTypeOeuvreCapdataProperty($oeuvre,
                                                  $capDataTypeOeuvreMappingInfo,
                                                  $currentCapdataClassMappingInfo
                                                );              
              // Personnage (Role) 
              $capDataRoleMappingType = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_role"])){
                $capDataRoleMappingType = "content";
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_role"])){
                $capDataRoleMappingType = "taxo";
              }
              $capDataRoleMappingInfo = [
                  "capData_role_mappingtype" => $capDataRoleMappingType,
              ];
              $this->setPersonnageCapdataProperty($oeuvre,
                                                  $capDataRoleMappingInfo,
                                                  $currentCapdataClassMappingInfo
                                                );              
              // Oeuvre titreFormeRejet, sourceLivret,  intrigue ..                                                
              $this->setOeuvreDetails($oeuvre, $oeuvreItem, $capDataClassInfo["export_class_capdata_properties"], "content");
              // Oeuvre dates
              $this->setOeuvreDatesCapdataProperties($oeuvre, $oeuvreItem, $capDataClassInfo["export_class_capdata_properties"], "content");               
              // Alter
              $this->moduleHandler->alter('capdata_graph_item', $oeuvre, $oeuvreItem, $graph);
              $graph->add($oeuvre);
            }
            break;           
          case 'capdata_productionprimaire':
            $query = $nodeStorage->getQuery();
            $query->accessCheck(FALSE);
            $query->condition('type', $exportClassContentDropdown);
            $query->condition('status', 1);
            $results = $query->execute();
            $shows = $nodeStorage->loadMultiple($results);
            foreach ($shows as $show) {
              $nid = $show->id();
              $resourceUrl = $host . "/node/" . $nid;
              $production = new ProductionPrimaire($resourceUrl);
              // Catalogage
              $this->setCatalogCapdataProperties($production, $show, $capDataClassInfo["export_class_capdata_properties"], "content");

              $capDataCollectiviteMappingType = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_collectivite"])){
                $capDataCollectiviteMappingType = "content";
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_collectivite"])){
                $capDataCollectiviteMappingType = "taxo";
              }
              $capDataCollectiviteMappingInfo = [
                  "capData_collectivite_mappingtype" => $capDataCollectiviteMappingType,
                  "default_collectivite" => $ownOrg,
              ];
              $currentCapdataClassMappingInfo =[
                  "host" => $host,
                  "mapping_type" =>  "content",
                  "mapped_entity" =>  $show,
                  "mapped_entity_properties" => $capDataClassInfo["export_class_capdata_properties"],
              ];
              $this->setCatalogageSourceAgenceProperty($production,
                                                        $capDataCollectiviteMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );
              // Titre, description ...
              $this->setCapdataTitle($production, $show, $capDataClassInfo["export_class_capdata_properties"], "content");    
              $this->setDescriptionCapdataProperty($production, $show, $capDataClassInfo["export_class_capdata_properties"], "content"); 
              $this->setArkBnfTraitCapdataProperty($production, $show, $capDataClassInfo["export_class_capdata_properties"], "content");    
              $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($production, $show, $capDataClassInfo["export_class_capdata_properties"], "content");    
              if(!empty($arkBnfUrl)){
                $arkBnf = new ArkBnf($arkBnfUrl);
                $graph->add($arkBnf);
                $production->setArkBnf($arkBnf);
              }               
              $this->setIdentifiantRofProperty($production, $show, $capDataClassInfo["export_class_capdata_properties"], "content");
              $this->setProductionDatesProperties($production, $show, $capDataClassInfo["export_class_capdata_properties"], "content");
              $this->setCapdataImageProperty($production, $show, $capDataClassInfo["export_class_capdata_properties"], "content");
              // Media image, sound 
              $mediaInfo = $this->getCapdataMediaPropertyInfo($production, $show, $capDataClassInfo["export_class_capdata_properties"], "content");
              if(!empty($mediaInfo) && isset($mediaInfo["mediaType"]) && !empty($mediaInfo["mediaUrl"])){
                if($mediaInfo["mediaType"] == "image"){
                  $refImageUrl = $mediaInfo["mediaUrl"];
                  $capdataImage = new Image($refImageUrl);
                  $capdataImage->setContentUrl($refImageUrl);
                  $capdataImage->setName($production->getTitre());
                  $graph->add($capdataImage);
                  $production->setMedia($capdataImage); 
                }
                // Media sound to be implemented
              }
              // Participations diverses
              $capDataParticipationMappingType = "";
              $capDataParticipationCorrespondantEntity = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_participation"])){
                $capDataParticipationMappingType = "content";
                if($capdataExportData["content_mapped_classes"]["capdata_participation"]["export_class_mapping_type"] == "capdata_participation_content_mapping"
                  &&
                  !empty($capdataExportData["content_mapped_classes"]["capdata_participation"]["export_class_content_dropdown"])
                ){
                  $capDataParticipationCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_participation"]["export_class_content_dropdown"];
                }                
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_participation"])){
                $capDataParticipationMappingType = "taxo";
                if($capdataExportData["taxo_mapped_classes"]["capdata_participation"]["export_class_mapping_type"] == "capdata_participation_taxo_mapping"
                  &&
                  !empty($capdataExportData["taxo_mapped_classes"]["capdata_participation"]["export_class_taxonomy_dropdown"])
                ){
                  $capDataParticipationCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_participation"]["export_class_taxonomy_dropdown"];
                }                  
              }
              $capDataParticipationMappingInfo = [
                  "capData_participation_mappingtype" => $capDataParticipationMappingType,
                  "capData_participation_correspondance" => $capDataParticipationCorrespondantEntity,
              ];
              $currentCapdataClassMappingInfo =[
                "host" => $host,
                "mapping_type" =>  "content",
                "mapped_entity" =>  $show,
                "mapped_entity_properties" => $capDataClassInfo["export_class_capdata_properties"],
              ];
              // A pour participation
              $this->setAPourParticipationCapdataProperty($production,
                                                      $capDataParticipationMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );                                                                  
              // A pour auteur (Auteur ou participation)
              $capDataAuteurMappingType = "";
              $capDataAuteurCorrespondantEntity = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_auteur"])){
                $capDataAuteurMappingType = "content";
                if($capdataExportData["content_mapped_classes"]["capdata_auteur"]["export_class_mapping_type"] == "capdata_auteur_content_mapping"
                  &&
                  !empty($capdataExportData["content_mapped_classes"]["capdata_auteur"]["export_class_content_dropdown"])
                ){
                  $capDataAuteurCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_auteur"]["export_class_content_dropdown"];
                }
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_auteur"])){
                $capDataAuteurMappingType = "taxo";
                if($capdataExportData["taxo_mapped_classes"]["capdata_auteur"]["export_class_mapping_type"] == "capdata_auteur_taxo_mapping"
                  &&
                  !empty($capdataExportData["taxo_mapped_classes"]["capdata_auteur"]["export_class_taxonomy_dropdown"])
                ){
                  $capDataAuteurCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_auteur"]["export_class_taxonomy_dropdown"];
                }                
              }
              $capDataAuteurMappingInfo = [
                  "capData_auteur_mappingtype" => $capDataAuteurMappingType,
                  "capData_auteur_correspondance" => $capDataAuteurCorrespondantEntity,
              ];              
              $this->setAPourAuteurCapdataProperty($production,
                                                      $capDataAuteurMappingInfo,
                                                      $capDataParticipationMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );              
              // A pour Mention Production (MentionProduction ou Participation)
              $capDataMentionProductionMappingType = "";
              $capDataMentionProductionCorrespondantEntity = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_mentionproduction"])){
                $capDataMentionProductionMappingType = "content";
                if($capdataExportData["content_mapped_classes"]["capdata_mentionproduction"]["export_class_mapping_type"] == "capdata_mentionproduction_content_mapping"
                  &&
                  !empty($capdataExportData["content_mapped_classes"]["capdata_mentionproduction"]["export_class_content_dropdown"])
                ){
                  $capDataMentionProductionCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_mentionproduction"]["export_class_content_dropdown"];
                }
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"])){
                $capDataMentionProductionMappingType = "taxo";
                if($capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"]["export_class_mapping_type"] == "capdata_mentionproduction_taxo_mapping"
                  &&
                  !empty($capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"]["export_class_taxonomy_dropdown"])
                ){
                  $capDataMentionProductionCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"]["export_class_taxonomy_dropdown"];
                }                
              }
              $capDataMentionProductionMappingInfo = [
                  "capData_mentionproduction_mappingtype" => $capDataMentionProductionMappingType,
                  "capData_mentionproduction_correspondance" => $capDataMentionProductionCorrespondantEntity,
              ];              
              $this->setAPourMentionProductionCapdataProperty($production,
                                                      $capDataMentionProductionMappingInfo,
                                                      $capDataParticipationMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );                
              // A pour partenariat (Partenariat ou participation)
              $capDataPartenariatMappingType = "";
              $capDataPartenariatCorrespondantEntity = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_partenariat"])){
                $capDataPartenariatMappingType = "content";
                if($capdataExportData["content_mapped_classes"]["capdata_partenariat"]["export_class_mapping_type"] == "capdata_partenariat_content_mapping"
                  &&
                  !empty($capdataExportData["content_mapped_classes"]["capdata_partenariat"]["export_class_content_dropdown"])
                ){
                  $capDataPartenariatCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_partenariat"]["export_class_content_dropdown"];
                }
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_partenariat"])){
                $capDataPartenariatMappingType = "taxo";
                if($capdataExportData["taxo_mapped_classes"]["capdata_partenariat"]["export_class_mapping_type"] == "capdata_partenariat_taxo_mapping"
                  &&
                  !empty($capdataExportData["taxo_mapped_classes"]["capdata_partenariat"]["export_class_taxonomy_dropdown"])
                ){
                  $capDataPartenariatCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_partenariat"]["export_class_taxonomy_dropdown"];
                }                
              }
              $capDataPartenariatMappingInfo = [
                  "capData_partenariat_mappingtype" => $capDataPartenariatMappingType,
                  "capData_partenariat_correspondance" => $capDataPartenariatCorrespondantEntity,
              ];              
              $this->setAPourPartenariatCapdataProperty($production,
                                                      $capDataPartenariatMappingInfo,
                                                      $capDataParticipationMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );              
              // A pour collaboration (Collaboration ou participation)
              $capDataCollaborationMappingType = "";
              $capDataCollaborationCorrespondantEntity = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_collaboration"])){
                $capDataCollaborationMappingType = "content";
                if($capdataExportData["content_mapped_classes"]["capdata_collaboration"]["export_class_mapping_type"] == "capdata_collaboration_content_mapping"
                  &&
                  !empty($capdataExportData["content_mapped_classes"]["capdata_collaboration"]["export_class_content_dropdown"])
                ){
                  $capDataCollaborationCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_collaboration"]["export_class_content_dropdown"];
                }
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_collaboration"])){
                $capDataCollaborationMappingType = "taxo";
                if($capdataExportData["taxo_mapped_classes"]["capdata_collaboration"]["export_class_mapping_type"] == "capdata_collaboration_taxo_mapping"
                  &&
                  !empty($capdataExportData["taxo_mapped_classes"]["capdata_collaboration"]["export_class_taxonomy_dropdown"])
                ){
                  $capDataCollaborationCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_collaboration"]["export_class_taxonomy_dropdown"];
                }                
              }
              $capDataCollaborationMappingInfo = [
                  "capData_collaboration_mappingtype" => $capDataCollaborationMappingType,
                  "capData_collaboration_correspondance" => $capDataCollaborationCorrespondantEntity,
              ];              
              $this->setAPourCollaborationCapdataProperty($production,
                                                      $capDataCollaborationMappingInfo,
                                                      $capDataParticipationMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );               
              // A pour interpretation (Interpretation ou participation)
              $capDataInterpretationMappingType = "";
              $capDataInterpretationCorrespondantEntity = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_interpretation"])){
                $capDataInterpretationMappingType = "content";
                if($capdataExportData["content_mapped_classes"]["capdata_interpretation"]["export_class_mapping_type"] == "capdata_interpretation_content_mapping"
                  &&
                  !empty($capdataExportData["content_mapped_classes"]["capdata_interpretation"]["export_class_content_dropdown"])
                ){
                  $capDataInterpretationCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_interpretation"]["export_class_content_dropdown"];
                }
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_interpretation"])){
                $capDataInterpretationMappingType = "taxo";
                if($capdataExportData["taxo_mapped_classes"]["capdata_interpretation"]["export_class_mapping_type"] == "capdata_interpretation_taxo_mapping"
                  &&
                  !empty($capdataExportData["taxo_mapped_classes"]["capdata_interpretation"]["export_class_taxonomy_dropdown"])
                ){
                  $capDataInterpretationCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_interpretation"]["export_class_taxonomy_dropdown"];
                }                
              }
              $capDataInterpretationMappingInfo = [
                  "capData_interpretation_mappingtype" => $capDataInterpretationMappingType,
                  "capData_interpretation_correspondance" => $capDataInterpretationCorrespondantEntity,
              ];              
              $this->setAPourInterpretationCapdataProperty($production,
                                                      $capDataInterpretationMappingInfo,
                                                      $capDataParticipationMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );               
              // A pour maitrise oeuvre (MaitriseOeuvre ou participation)
              $capDataMaitriseOeuvreMappingType = "";
              $capDataMaitriseOeuvreCorrespondantEntity = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_maitriseoeuvre"])){
                $capDataMaitriseOeuvreMappingType = "content";
                if($capdataExportData["content_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_mapping_type"] == "capdata_maitriseoeuvre_content_mapping"
                  &&
                  !empty($capdataExportData["content_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_content_dropdown"])
                ){
                  $capDataMaitriseOeuvreCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_content_dropdown"];
                }
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_maitriseoeuvre"])){
                $capDataMaitriseOeuvreMappingType = "taxo";
                if($capdataExportData["taxo_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_mapping_type"] == "capdata_maitriseoeuvre_taxo_mapping"
                  &&
                  !empty($capdataExportData["taxo_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_taxonomy_dropdown"])
                ){
                  $capDataMaitriseOeuvreCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_taxonomy_dropdown"];
                }                
              }
              $capDataMaitriseOeuvreMappingInfo = [
                  "capData_maitriseoeuvre_mappingtype" => $capDataMaitriseOeuvreMappingType,
                  "capData_maitriseoeuvre_correspondance" => $capDataMaitriseOeuvreCorrespondantEntity,
              ];              
              $this->setAPourMaitriseOeuvreCapdataProperty($production,
                                                      $capDataMaitriseOeuvreMappingInfo,
                                                      $capDataParticipationMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );                 
              // A pour programmation (Programmation ou participation)
              $capDataProgrammationMappingType = "";
              $capDataProgrammationCorrespondantEntity = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_programmation"])){
                $capDataProgrammationMappingType = "content";
                if($capdataExportData["content_mapped_classes"]["capdata_programmation"]["export_class_mapping_type"] == "capdata_programmation_content_mapping"
                  &&
                  !empty($capdataExportData["content_mapped_classes"]["capdata_programmation"]["export_class_content_dropdown"])
                ){
                  $capDataProgrammationCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_programmation"]["export_class_content_dropdown"];
                }
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_programmation"])){
                $capDataProgrammationMappingType = "taxo";
                if($capdataExportData["taxo_mapped_classes"]["capdata_programmation"]["export_class_mapping_type"] == "capdata_programmation_taxo_mapping"
                  &&
                  !empty($capdataExportData["taxo_mapped_classes"]["capdata_programmation"]["export_class_taxonomy_dropdown"])
                ){
                  $capDataProgrammationCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_programmation"]["export_class_taxonomy_dropdown"];
                }                
              }
              $capDataProgrammationMappingInfo = [
                  "capData_programmation_mappingtype" => $capDataProgrammationMappingType,
                  "capData_programmation_correspondance" => $capDataProgrammationCorrespondantEntity,
              ];              
              $this->setAPourProgrammationCapdataProperty($production,
                                                      $capDataProgrammationMappingInfo,
                                                      $capDataParticipationMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );              
              // A pour saison (Saison) 
              $capDataSaisonMappingType = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_saison"])){
                $capDataSaisonMappingType = "content";
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_saison"])){
                $capDataSaisonMappingType = "taxo";
              }
              $capDataSaisonMappingInfo = [
                  "capData_saison_mappingtype" => $capDataSaisonMappingType,
              ];
              $this->setAPourSaisonCapdataProperty($production,
                                                      $capDataSaisonMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );              
              // A pour type production  (TypeProduction)
              $capDataTypeProductionMappingType = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_typeproduction"])){
                $capDataTypeProductionMappingType = "content";
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_typeproduction"])){
                $capDataTypeProductionMappingType = "taxo";
              }
              $capDataTypeProductionMappingInfo = [
                  "capData_typeproduction_mappingtype" => $capDataTypeProductionMappingType,
              ];
              $this->setAPourTypeProductionCapdataProperty($production,
                                                      $capDataTypeProductionMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );              
              // A pour type public (TypePublic)
              $capDataTypePublicMappingType = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_typepublic"])){
                $capDataTypePublicMappingType = "content";
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_typepublic"])){
                $capDataTypePublicMappingType = "taxo";
              }
              $capDataTypePublicMappingInfo = [
                  "capData_typepublic_mappingtype" => $capDataTypePublicMappingType,
              ];
              $this->setAPourTypePublicCapdataProperty($production,
                                                      $capDataTypePublicMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );               
              // Historique (HistoriqueProduction) 
              $capDataHistoriqueProductionMappingType = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_historiqueproduction"])){
                $capDataHistoriqueProductionMappingType = "content";
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_historiqueproduction"])){
                $capDataHistoriqueProductionMappingType = "taxo";
              }
              $capDataHistoriqueProductionMappingInfo = [
                  "capData_historiqueproduction_mappingtype" => $capDataHistoriqueProductionMappingType,
              ];
              $this->setHistoriqueCapdataProperty($production,
                                                  $capDataHistoriqueProductionMappingInfo,
                                                  $currentCapdataClassMappingInfo
                                                );               
              // Lieu Publication (Lieu)
              $capDataLieuPublicationMappingType = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_lieu"])){
                $capDataLieuPublicationMappingType = "content";
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_lieu"])){
                $capDataLieuPublicationMappingType = "taxo";
              }
              $capDataLieuPublicationMappingInfo = [
                  "capData_lieu_mappingtype" => $capDataLieuPublicationMappingType,
              ];
              $this->setLieuPublicationCapdataProperty($production,
                                                      $capDataLieuPublicationMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );              
              // Oeuvre representee (Oeuvre) 
              $capDataOeuvreMappingType = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_oeuvre"])){
                $capDataOeuvreMappingType = "content";
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_oeuvre"])){
                $capDataOeuvreMappingType = "taxo";
              }
              $capDataOeuvreMappingInfo = [
                  "capData_oeuvre_mappingtype" => $capDataOeuvreMappingType,
              ];
              $this->setOeuvreRepresenteeCapdataProperty($production,
                                                      $capDataOeuvreMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );                                                                   
              $this->moduleHandler->alter('capdata_graph_item', $production, $show, $graph);              
              $graph->add($production);
            }
            break;          
          case 'capdata_production':
            $query = $nodeStorage->getQuery();
            $query->accessCheck(FALSE);
            $query->condition('type', $exportClassContentDropdown);
            $query->condition('status', 1);
            $results = $query->execute();
            $shows = $nodeStorage->loadMultiple($results);
            foreach ($shows as $show) {
              $nid = $show->id();
              $resourceUrl = $host . "/node/" . $nid;
              $production = new Production($resourceUrl);
              // Catalogage
              $this->setCatalogCapdataProperties($production, $show, $capDataClassInfo["export_class_capdata_properties"], "content");

              $capDataCollectiviteMappingType = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_collectivite"])){
                $capDataCollectiviteMappingType = "content";
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_collectivite"])){
                $capDataCollectiviteMappingType = "taxo";
              }
              $capDataCollectiviteMappingInfo = [
                  "capData_collectivite_mappingtype" => $capDataCollectiviteMappingType,
                  "default_collectivite" => $ownOrg,
              ];
              $currentCapdataClassMappingInfo =[
                  "host" => $host,
                  "mapping_type" =>  "content",
                  "mapped_entity" =>  $show,
                  "mapped_entity_properties" => $capDataClassInfo["export_class_capdata_properties"],
              ];
              $this->setCatalogageSourceAgenceProperty($production,
                                                        $capDataCollectiviteMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );
              // Titre, description ...
              $this->setCapdataTitle($production, $show, $capDataClassInfo["export_class_capdata_properties"], "content");    
              $this->setDescriptionCapdataProperty($production, $show, $capDataClassInfo["export_class_capdata_properties"], "content"); 
              $this->setArkBnfTraitCapdataProperty($production, $show, $capDataClassInfo["export_class_capdata_properties"], "content");    
              $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($production, $show, $capDataClassInfo["export_class_capdata_properties"], "content");    
              if(!empty($arkBnfUrl)){
                $arkBnf = new ArkBnf($arkBnfUrl);
                $graph->add($arkBnf);
                $production->setArkBnf($arkBnf);
              } 
              $this->setIdentifiantRofProperty($production, $show, $capDataClassInfo["export_class_capdata_properties"], "content");
              $this->setProductionDatesProperties($production, $show, $capDataClassInfo["export_class_capdata_properties"], "content");
              $this->setCapdataImageProperty($production, $show, $capDataClassInfo["export_class_capdata_properties"], "content");
              // Media image, sound 
              $mediaInfo = $this->getCapdataMediaPropertyInfo($production, $show, $capDataClassInfo["export_class_capdata_properties"], "content");
              if(!empty($mediaInfo) && isset($mediaInfo["mediaType"]) && !empty($mediaInfo["mediaUrl"])){
                if($mediaInfo["mediaType"] == "image"){
                  $refImageUrl = $mediaInfo["mediaUrl"];
                  $capdataImage = new Image($refImageUrl);
                  $capdataImage->setContentUrl($refImageUrl);
                  $capdataImage->setName($production->getTitre());
                  $graph->add($capdataImage);
                  $production->setMedia($capdataImage); 
                }
                // Media sound to be implemented
              }
              // Participations diverses
              $capDataParticipationMappingType = "";
              $capDataParticipationCorrespondantEntity = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_participation"])){
                $capDataParticipationMappingType = "content";
                if($capdataExportData["content_mapped_classes"]["capdata_participation"]["export_class_mapping_type"] == "capdata_participation_content_mapping"
                  &&
                  !empty($capdataExportData["content_mapped_classes"]["capdata_participation"]["export_class_content_dropdown"])
                ){
                  $capDataParticipationCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_participation"]["export_class_content_dropdown"];
                }                
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_participation"])){
                $capDataParticipationMappingType = "taxo";
                if($capdataExportData["taxo_mapped_classes"]["capdata_participation"]["export_class_mapping_type"] == "capdata_participation_taxo_mapping"
                  &&
                  !empty($capdataExportData["taxo_mapped_classes"]["capdata_participation"]["export_class_taxonomy_dropdown"])
                ){
                  $capDataParticipationCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_participation"]["export_class_taxonomy_dropdown"];
                }                  
              }
              $capDataParticipationMappingInfo = [
                  "capData_participation_mappingtype" => $capDataParticipationMappingType,
                  "capData_participation_correspondance" => $capDataParticipationCorrespondantEntity,
              ];
              $currentCapdataClassMappingInfo =[
                "host" => $host,
                "mapping_type" =>  "content",
                "mapped_entity" =>  $show,
                "mapped_entity_properties" => $capDataClassInfo["export_class_capdata_properties"],
              ];
              // A pour participation
              $this->setAPourParticipationCapdataProperty($production,
                                                      $capDataParticipationMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );                                                                  
              // A pour auteur (Auteur ou participation)
              $capDataAuteurMappingType = "";
              $capDataAuteurCorrespondantEntity = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_auteur"])){
                $capDataAuteurMappingType = "content";
                if($capdataExportData["content_mapped_classes"]["capdata_auteur"]["export_class_mapping_type"] == "capdata_auteur_content_mapping"
                  &&
                  !empty($capdataExportData["content_mapped_classes"]["capdata_auteur"]["export_class_content_dropdown"])
                ){
                  $capDataAuteurCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_auteur"]["export_class_content_dropdown"];
                }
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_auteur"])){
                $capDataAuteurMappingType = "taxo";
                if($capdataExportData["taxo_mapped_classes"]["capdata_auteur"]["export_class_mapping_type"] == "capdata_auteur_taxo_mapping"
                  &&
                  !empty($capdataExportData["taxo_mapped_classes"]["capdata_auteur"]["export_class_taxonomy_dropdown"])
                ){
                  $capDataAuteurCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_auteur"]["export_class_taxonomy_dropdown"];
                }                
              }
              $capDataAuteurMappingInfo = [
                  "capData_auteur_mappingtype" => $capDataAuteurMappingType,
                  "capData_auteur_correspondance" => $capDataAuteurCorrespondantEntity,
              ];              
              $this->setAPourAuteurCapdataProperty($production,
                                                      $capDataAuteurMappingInfo,
                                                      $capDataParticipationMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );              
              // A pour Mention Production (MentionProduction ou Participation)
              $capDataMentionProductionMappingType = "";
              $capDataMentionProductionCorrespondantEntity = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_mentionproduction"])){
                $capDataMentionProductionMappingType = "content";
                if($capdataExportData["content_mapped_classes"]["capdata_mentionproduction"]["export_class_mapping_type"] == "capdata_mentionproduction_content_mapping"
                  &&
                  !empty($capdataExportData["content_mapped_classes"]["capdata_mentionproduction"]["export_class_content_dropdown"])
                ){
                  $capDataMentionProductionCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_mentionproduction"]["export_class_content_dropdown"];
                }
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"])){
                $capDataMentionProductionMappingType = "taxo";
                if($capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"]["export_class_mapping_type"] == "capdata_mentionproduction_taxo_mapping"
                  &&
                  !empty($capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"]["export_class_taxonomy_dropdown"])
                ){
                  $capDataMentionProductionCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"]["export_class_taxonomy_dropdown"];
                }                
              }
              $capDataMentionProductionMappingInfo = [
                  "capData_mentionproduction_mappingtype" => $capDataMentionProductionMappingType,
                  "capData_mentionproduction_correspondance" => $capDataMentionProductionCorrespondantEntity,
              ];              
              $this->setAPourMentionProductionCapdataProperty($production,
                                                      $capDataMentionProductionMappingInfo,
                                                      $capDataParticipationMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );                
              // A pour partenariat (Partenariat ou participation)
              $capDataPartenariatMappingType = "";
              $capDataPartenariatCorrespondantEntity = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_partenariat"])){
                $capDataPartenariatMappingType = "content";
                if($capdataExportData["content_mapped_classes"]["capdata_partenariat"]["export_class_mapping_type"] == "capdata_partenariat_content_mapping"
                  &&
                  !empty($capdataExportData["content_mapped_classes"]["capdata_partenariat"]["export_class_content_dropdown"])
                ){
                  $capDataPartenariatCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_partenariat"]["export_class_content_dropdown"];
                }
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_partenariat"])){
                $capDataPartenariatMappingType = "taxo";
                if($capdataExportData["taxo_mapped_classes"]["capdata_partenariat"]["export_class_mapping_type"] == "capdata_partenariat_taxo_mapping"
                  &&
                  !empty($capdataExportData["taxo_mapped_classes"]["capdata_partenariat"]["export_class_taxonomy_dropdown"])
                ){
                  $capDataPartenariatCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_partenariat"]["export_class_taxonomy_dropdown"];
                }                
              }
              $capDataPartenariatMappingInfo = [
                  "capData_partenariat_mappingtype" => $capDataPartenariatMappingType,
                  "capData_partenariat_correspondance" => $capDataPartenariatCorrespondantEntity,
              ];              
              $this->setAPourPartenariatCapdataProperty($production,
                                                      $capDataPartenariatMappingInfo,
                                                      $capDataParticipationMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );              
              // A pour collaboration (Collaboration ou participation)
              $capDataCollaborationMappingType = "";
              $capDataCollaborationCorrespondantEntity = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_collaboration"])){
                $capDataCollaborationMappingType = "content";
                if($capdataExportData["content_mapped_classes"]["capdata_collaboration"]["export_class_mapping_type"] == "capdata_collaboration_content_mapping"
                  &&
                  !empty($capdataExportData["content_mapped_classes"]["capdata_collaboration"]["export_class_content_dropdown"])
                ){
                  $capDataCollaborationCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_collaboration"]["export_class_content_dropdown"];
                }
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_collaboration"])){
                $capDataCollaborationMappingType = "taxo";
                if($capdataExportData["taxo_mapped_classes"]["capdata_collaboration"]["export_class_mapping_type"] == "capdata_collaboration_taxo_mapping"
                  &&
                  !empty($capdataExportData["taxo_mapped_classes"]["capdata_collaboration"]["export_class_taxonomy_dropdown"])
                ){
                  $capDataCollaborationCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_collaboration"]["export_class_taxonomy_dropdown"];
                }                
              }
              $capDataCollaborationMappingInfo = [
                  "capData_collaboration_mappingtype" => $capDataCollaborationMappingType,
                  "capData_collaboration_correspondance" => $capDataCollaborationCorrespondantEntity,
              ];              
              $this->setAPourCollaborationCapdataProperty($production,
                                                      $capDataCollaborationMappingInfo,
                                                      $capDataParticipationMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );               
              // A pour interpretation (Interpretation ou participation)
              $capDataInterpretationMappingType = "";
              $capDataInterpretationCorrespondantEntity = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_interpretation"])){
                $capDataInterpretationMappingType = "content";
                if($capdataExportData["content_mapped_classes"]["capdata_interpretation"]["export_class_mapping_type"] == "capdata_interpretation_content_mapping"
                  &&
                  !empty($capdataExportData["content_mapped_classes"]["capdata_interpretation"]["export_class_content_dropdown"])
                ){
                  $capDataInterpretationCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_interpretation"]["export_class_content_dropdown"];
                }
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_interpretation"])){
                $capDataInterpretationMappingType = "taxo";
                if($capdataExportData["taxo_mapped_classes"]["capdata_interpretation"]["export_class_mapping_type"] == "capdata_interpretation_taxo_mapping"
                  &&
                  !empty($capdataExportData["taxo_mapped_classes"]["capdata_interpretation"]["export_class_taxonomy_dropdown"])
                ){
                  $capDataInterpretationCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_interpretation"]["export_class_taxonomy_dropdown"];
                }                
              }
              $capDataInterpretationMappingInfo = [
                  "capData_interpretation_mappingtype" => $capDataInterpretationMappingType,
                  "capData_interpretation_correspondance" => $capDataInterpretationCorrespondantEntity,
              ];              
              $this->setAPourInterpretationCapdataProperty($production,
                                                      $capDataInterpretationMappingInfo,
                                                      $capDataParticipationMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );               
              // A pour maitrise oeuvre (MaitriseOeuvre ou participation)
              $capDataMaitriseOeuvreMappingType = "";
              $capDataMaitriseOeuvreCorrespondantEntity = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_maitriseoeuvre"])){
                $capDataMaitriseOeuvreMappingType = "content";
                if($capdataExportData["content_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_mapping_type"] == "capdata_maitriseoeuvre_content_mapping"
                  &&
                  !empty($capdataExportData["content_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_content_dropdown"])
                ){
                  $capDataMaitriseOeuvreCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_content_dropdown"];
                }
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_maitriseoeuvre"])){
                $capDataMaitriseOeuvreMappingType = "taxo";
                if($capdataExportData["taxo_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_mapping_type"] == "capdata_maitriseoeuvre_taxo_mapping"
                  &&
                  !empty($capdataExportData["taxo_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_taxonomy_dropdown"])
                ){
                  $capDataMaitriseOeuvreCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_taxonomy_dropdown"];
                }                
              }
              $capDataMaitriseOeuvreMappingInfo = [
                  "capData_maitriseoeuvre_mappingtype" => $capDataMaitriseOeuvreMappingType,
                  "capData_maitriseoeuvre_correspondance" => $capDataMaitriseOeuvreCorrespondantEntity,
              ];              
              $this->setAPourMaitriseOeuvreCapdataProperty($production,
                                                      $capDataMaitriseOeuvreMappingInfo,
                                                      $capDataParticipationMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );                 
              // A pour programmation (Programmation ou participation)
              $capDataProgrammationMappingType = "";
              $capDataProgrammationCorrespondantEntity = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_programmation"])){
                $capDataProgrammationMappingType = "content";
                if($capdataExportData["content_mapped_classes"]["capdata_programmation"]["export_class_mapping_type"] == "capdata_programmation_content_mapping"
                  &&
                  !empty($capdataExportData["content_mapped_classes"]["capdata_programmation"]["export_class_content_dropdown"])
                ){
                  $capDataProgrammationCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_programmation"]["export_class_content_dropdown"];
                }
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_programmation"])){
                $capDataProgrammationMappingType = "taxo";
                if($capdataExportData["taxo_mapped_classes"]["capdata_programmation"]["export_class_mapping_type"] == "capdata_programmation_taxo_mapping"
                  &&
                  !empty($capdataExportData["taxo_mapped_classes"]["capdata_programmation"]["export_class_taxonomy_dropdown"])
                ){
                  $capDataProgrammationCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_programmation"]["export_class_taxonomy_dropdown"];
                }                
              }
              $capDataProgrammationMappingInfo = [
                  "capData_programmation_mappingtype" => $capDataProgrammationMappingType,
                  "capData_programmation_correspondance" => $capDataProgrammationCorrespondantEntity,
              ];              
              $this->setAPourProgrammationCapdataProperty($production,
                                                      $capDataProgrammationMappingInfo,
                                                      $capDataParticipationMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );              
              // A pour saison (Saison) 
              $capDataSaisonMappingType = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_saison"])){
                $capDataSaisonMappingType = "content";
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_saison"])){
                $capDataSaisonMappingType = "taxo";
              }
              $capDataSaisonMappingInfo = [
                  "capData_saison_mappingtype" => $capDataSaisonMappingType,
              ];
              $this->setAPourSaisonCapdataProperty($production,
                                                      $capDataSaisonMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );              
              // A pour type production  (TypeProduction)
              $capDataTypeProductionMappingType = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_typeproduction"])){
                $capDataTypeProductionMappingType = "content";
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_typeproduction"])){
                $capDataTypeProductionMappingType = "taxo";
              }
              $capDataTypeProductionMappingInfo = [
                  "capData_typeproduction_mappingtype" => $capDataTypeProductionMappingType,
              ];
              $this->setAPourTypeProductionCapdataProperty($production,
                                                      $capDataTypeProductionMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );              
              // A pour type public (TypePublic)
              $capDataTypePublicMappingType = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_typepublic"])){
                $capDataTypePublicMappingType = "content";
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_typepublic"])){
                $capDataTypePublicMappingType = "taxo";
              }
              $capDataTypePublicMappingInfo = [
                  "capData_typepublic_mappingtype" => $capDataTypePublicMappingType,
              ];
              $this->setAPourTypePublicCapdataProperty($production,
                                                      $capDataTypePublicMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );               
              // Historique (HistoriqueProduction) 
              $capDataHistoriqueProductionMappingType = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_historiqueproduction"])){
                $capDataHistoriqueProductionMappingType = "content";
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_historiqueproduction"])){
                $capDataHistoriqueProductionMappingType = "taxo";
              }
              $capDataHistoriqueProductionMappingInfo = [
                  "capData_historiqueproduction_mappingtype" => $capDataHistoriqueProductionMappingType,
              ];
              $this->setHistoriqueCapdataProperty($production,
                                                  $capDataHistoriqueProductionMappingInfo,
                                                  $currentCapdataClassMappingInfo
                                                );               
              // Lieu Publication (Lieu)
              $capDataLieuPublicationMappingType = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_lieu"])){
                $capDataLieuPublicationMappingType = "content";
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_lieu"])){
                $capDataLieuPublicationMappingType = "taxo";
              }
              $capDataLieuPublicationMappingInfo = [
                  "capData_lieu_mappingtype" => $capDataLieuPublicationMappingType,
              ];
              $this->setLieuPublicationCapdataProperty($production,
                                                      $capDataLieuPublicationMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );              
              // Oeuvre representee (Oeuvre) 
              $capDataOeuvreMappingType = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_oeuvre"])){
                $capDataOeuvreMappingType = "content";
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_oeuvre"])){
                $capDataOeuvreMappingType = "taxo";
              }
              $capDataOeuvreMappingInfo = [
                  "capData_oeuvre_mappingtype" => $capDataOeuvreMappingType,
              ];
              $this->setOeuvreRepresenteeCapdataProperty($production,
                                                      $capDataOeuvreMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );              
              // Production Primaire (ProductionPrimaire) 
              $capDataProductionPrimaireMappingType = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_productionprimaire"])){
                $capDataProductionPrimaireMappingType = "content";
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_productionprimaire"])){
                $capDataProductionPrimaireMappingType = "taxo";
              }
              $capDataProductionPrimaireMappingInfo = [
                  "capData_productionprimaire_mappingtype" => $capDataProductionPrimaireMappingType,
              ];
              $this->setProductionPrimaireCapdataProperty($production,
                                                      $capDataProductionPrimaireMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );                                                       
              // Alter
              $this->moduleHandler->alter('capdata_graph_item', $production, $show, $graph);                            
              $graph->add($production);
            }
            break;            
          case 'capdata_evenement':
            $query = $nodeStorage->getQuery();
            $query->accessCheck(FALSE);
            $query->condition('type', $exportClassContentDropdown);
            $query->condition('status', 1);
            $results = $query->execute();
            $allEvents = $nodeStorage->loadMultiple($results);
            foreach ($allEvents as $eventItem) {
              $nid = $eventItem->id();
              $resourceUrl = $host . "/node/" . $nid;
              $evenement = new Evenement($resourceUrl);
              // Catalogage
              $this->setCatalogCapdataProperties($evenement, $eventItem, $capDataClassInfo["export_class_capdata_properties"], "content");

              $capDataCollectiviteMappingType = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_collectivite"])){
                $capDataCollectiviteMappingType = "content";
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_collectivite"])){
                $capDataCollectiviteMappingType = "taxo";
              }
              $capDataCollectiviteMappingInfo = [
                  "capData_collectivite_mappingtype" => $capDataCollectiviteMappingType,
                  "default_collectivite" => $ownOrg,
              ];
              $currentCapdataClassMappingInfo =[
                  "host" => $host,
                  "mapping_type" =>  "content",
                  "mapped_entity" =>  $eventItem,
                  "mapped_entity_properties" => $capDataClassInfo["export_class_capdata_properties"],
              ];
              $this->setCatalogageSourceAgenceProperty($evenement,
                                                        $capDataCollectiviteMappingInfo,
                                                        $currentCapdataClassMappingInfo
                                                      );
              // Titre, description ...
              $this->setCapdataTitle($evenement, $eventItem, $capDataClassInfo["export_class_capdata_properties"], "content");    
              $this->setDescriptionCapdataProperty($evenement, $eventItem, $capDataClassInfo["export_class_capdata_properties"], "content"); 
              $this->setArkBnfTraitCapdataProperty($evenement, $eventItem, $capDataClassInfo["export_class_capdata_properties"], "content");    
              $arkBnfUrl = $this->setExternalArkBnfCapdataProperty($evenement, $eventItem, $capDataClassInfo["export_class_capdata_properties"], "content");    
              if(!empty($arkBnfUrl)){
                $arkBnf = new ArkBnf($arkBnfUrl);
                $graph->add($arkBnf);
                $evenement->setArkBnf($arkBnf);
              }              
              $this->setIdentifiantRofProperty($evenement, $eventItem, $capDataClassInfo["export_class_capdata_properties"], "content");
              $this->setCapdataImageProperty($evenement, $eventItem, $capDataClassInfo["export_class_capdata_properties"], "content");
              // Media image, sound 
              $mediaInfo = $this->getCapdataMediaPropertyInfo($evenement, $eventItem, $capDataClassInfo["export_class_capdata_properties"], "content");
              if(!empty($mediaInfo) && isset($mediaInfo["mediaType"]) && !empty($mediaInfo["mediaUrl"])){
                if($mediaInfo["mediaType"] == "image"){
                  $refImageUrl = $mediaInfo["mediaUrl"];
                  $capdataImage = new Image($refImageUrl);
                  $capdataImage->setContentUrl($refImageUrl);
                  $capdataImage->setName($evenement->getTitre());
                  $graph->add($capdataImage);
                  $evenement->setMedia($capdataImage); 
                }
                // Media sound to be implemented
              }              
              // Participations diverses
              $capDataParticipationMappingType = "";
              $capDataParticipationCorrespondantEntity = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_participation"])){
                $capDataParticipationMappingType = "content";
                if($capdataExportData["content_mapped_classes"]["capdata_participation"]["export_class_mapping_type"] == "capdata_participation_content_mapping"
                  &&
                  !empty($capdataExportData["content_mapped_classes"]["capdata_participation"]["export_class_content_dropdown"])
                ){
                  $capDataParticipationCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_participation"]["export_class_content_dropdown"];
                }                
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_participation"])){
                $capDataParticipationMappingType = "taxo";
                if($capdataExportData["taxo_mapped_classes"]["capdata_participation"]["export_class_mapping_type"] == "capdata_participation_taxo_mapping"
                  &&
                  !empty($capdataExportData["taxo_mapped_classes"]["capdata_participation"]["export_class_taxonomy_dropdown"])
                ){
                  $capDataParticipationCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_participation"]["export_class_taxonomy_dropdown"];
                }                  
              }
              $capDataParticipationMappingInfo = [
                  "capData_participation_mappingtype" => $capDataParticipationMappingType,
                  "capData_participation_correspondance" => $capDataParticipationCorrespondantEntity,
              ];
              $currentCapdataClassMappingInfo =[
                "host" => $host,
                "mapping_type" =>  "content",
                "mapped_entity" =>  $eventItem,
                "mapped_entity_properties" => $capDataClassInfo["export_class_capdata_properties"],
              ];              
              // A pour participation
              $this->setAPourParticipationCapdataProperty($evenement,
                                                      $capDataParticipationMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );                                                                  
              // A pour auteur (Auteur ou participation)
              $capDataAuteurMappingType = "";
              $capDataAuteurCorrespondantEntity = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_auteur"])){
                $capDataAuteurMappingType = "content";
                if($capdataExportData["content_mapped_classes"]["capdata_auteur"]["export_class_mapping_type"] == "capdata_auteur_content_mapping"
                  &&
                  !empty($capdataExportData["content_mapped_classes"]["capdata_auteur"]["export_class_content_dropdown"])
                ){
                  $capDataAuteurCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_auteur"]["export_class_content_dropdown"];
                }
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_auteur"])){
                $capDataAuteurMappingType = "taxo";
                if($capdataExportData["taxo_mapped_classes"]["capdata_auteur"]["export_class_mapping_type"] == "capdata_auteur_taxo_mapping"
                  &&
                  !empty($capdataExportData["taxo_mapped_classes"]["capdata_auteur"]["export_class_taxonomy_dropdown"])
                ){
                  $capDataAuteurCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_auteur"]["export_class_taxonomy_dropdown"];
                }                
              }
              $capDataAuteurMappingInfo = [
                  "capData_auteur_mappingtype" => $capDataAuteurMappingType,
                  "capData_auteur_correspondance" => $capDataAuteurCorrespondantEntity,
              ];              
              $this->setAPourAuteurCapdataProperty($evenement,
                                                      $capDataAuteurMappingInfo,
                                                      $capDataParticipationMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );              
              // A pour Mention Production (MentionProduction ou Participation)
              $capDataMentionProductionMappingType = "";
              $capDataMentionProductionCorrespondantEntity = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_mentionproduction"])){
                $capDataMentionProductionMappingType = "content";
                if($capdataExportData["content_mapped_classes"]["capdata_mentionproduction"]["export_class_mapping_type"] == "capdata_mentionproduction_content_mapping"
                  &&
                  !empty($capdataExportData["content_mapped_classes"]["capdata_mentionproduction"]["export_class_content_dropdown"])
                ){
                  $capDataMentionProductionCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_mentionproduction"]["export_class_content_dropdown"];
                }
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"])){
                $capDataMentionProductionMappingType = "taxo";
                if($capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"]["export_class_mapping_type"] == "capdata_mentionproduction_taxo_mapping"
                  &&
                  !empty($capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"]["export_class_taxonomy_dropdown"])
                ){
                  $capDataMentionProductionCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_mentionproduction"]["export_class_taxonomy_dropdown"];
                }                
              }
              $capDataMentionProductionMappingInfo = [
                  "capData_mentionproduction_mappingtype" => $capDataMentionProductionMappingType,
                  "capData_mentionproduction_correspondance" => $capDataMentionProductionCorrespondantEntity,
              ];              
              $this->setAPourMentionProductionCapdataProperty($evenement,
                                                      $capDataMentionProductionMappingInfo,
                                                      $capDataParticipationMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );                
              // A pour partenariat (Partenariat ou participation)
              $capDataPartenariatMappingType = "";
              $capDataPartenariatCorrespondantEntity = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_partenariat"])){
                $capDataPartenariatMappingType = "content";
                if($capdataExportData["content_mapped_classes"]["capdata_partenariat"]["export_class_mapping_type"] == "capdata_partenariat_content_mapping"
                  &&
                  !empty($capdataExportData["content_mapped_classes"]["capdata_partenariat"]["export_class_content_dropdown"])
                ){
                  $capDataPartenariatCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_partenariat"]["export_class_content_dropdown"];
                }
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_partenariat"])){
                $capDataPartenariatMappingType = "taxo";
                if($capdataExportData["taxo_mapped_classes"]["capdata_partenariat"]["export_class_mapping_type"] == "capdata_partenariat_taxo_mapping"
                  &&
                  !empty($capdataExportData["taxo_mapped_classes"]["capdata_partenariat"]["export_class_taxonomy_dropdown"])
                ){
                  $capDataPartenariatCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_partenariat"]["export_class_taxonomy_dropdown"];
                }                
              }
              $capDataPartenariatMappingInfo = [
                  "capData_partenariat_mappingtype" => $capDataPartenariatMappingType,
                  "capData_partenariat_correspondance" => $capDataPartenariatCorrespondantEntity,
              ];              
              $this->setAPourPartenariatCapdataProperty($evenement,
                                                      $capDataPartenariatMappingInfo,
                                                      $capDataParticipationMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );              
              // A pour collaboration (Collaboration ou participation)
              $capDataCollaborationMappingType = "";
              $capDataCollaborationCorrespondantEntity = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_collaboration"])){
                $capDataCollaborationMappingType = "content";
                if($capdataExportData["content_mapped_classes"]["capdata_collaboration"]["export_class_mapping_type"] == "capdata_collaboration_content_mapping"
                  &&
                  !empty($capdataExportData["content_mapped_classes"]["capdata_collaboration"]["export_class_content_dropdown"])
                ){
                  $capDataCollaborationCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_collaboration"]["export_class_content_dropdown"];
                }
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_collaboration"])){
                $capDataCollaborationMappingType = "taxo";
                if($capdataExportData["taxo_mapped_classes"]["capdata_collaboration"]["export_class_mapping_type"] == "capdata_collaboration_taxo_mapping"
                  &&
                  !empty($capdataExportData["taxo_mapped_classes"]["capdata_collaboration"]["export_class_taxonomy_dropdown"])
                ){
                  $capDataCollaborationCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_collaboration"]["export_class_taxonomy_dropdown"];
                }                
              }
              $capDataCollaborationMappingInfo = [
                  "capData_collaboration_mappingtype" => $capDataCollaborationMappingType,
                  "capData_collaboration_correspondance" => $capDataCollaborationCorrespondantEntity,
              ];              
              $this->setAPourCollaborationCapdataProperty($evenement,
                                                      $capDataCollaborationMappingInfo,
                                                      $capDataParticipationMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );               
              // A pour interpretation (Interpretation ou participation)
              $capDataInterpretationMappingType = "";
              $capDataInterpretationCorrespondantEntity = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_interpretation"])){
                $capDataInterpretationMappingType = "content";
                if($capdataExportData["content_mapped_classes"]["capdata_interpretation"]["export_class_mapping_type"] == "capdata_interpretation_content_mapping"
                  &&
                  !empty($capdataExportData["content_mapped_classes"]["capdata_interpretation"]["export_class_content_dropdown"])
                ){
                  $capDataInterpretationCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_interpretation"]["export_class_content_dropdown"];
                }
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_interpretation"])){
                $capDataInterpretationMappingType = "taxo";
                if($capdataExportData["taxo_mapped_classes"]["capdata_interpretation"]["export_class_mapping_type"] == "capdata_interpretation_taxo_mapping"
                  &&
                  !empty($capdataExportData["taxo_mapped_classes"]["capdata_interpretation"]["export_class_taxonomy_dropdown"])
                ){
                  $capDataInterpretationCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_interpretation"]["export_class_taxonomy_dropdown"];
                }                
              }
              $capDataInterpretationMappingInfo = [
                  "capData_interpretation_mappingtype" => $capDataInterpretationMappingType,
                  "capData_interpretation_correspondance" => $capDataInterpretationCorrespondantEntity,
              ];              
              $this->setAPourInterpretationCapdataProperty($evenement,
                                                      $capDataInterpretationMappingInfo,
                                                      $capDataParticipationMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );  
              // A pour maitrise oeuvre (MaitriseOeuvre ou participation)
              $capDataMaitriseOeuvreMappingType = "";
              $capDataMaitriseOeuvreCorrespondantEntity = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_maitriseoeuvre"])){
                $capDataMaitriseOeuvreMappingType = "content";
                if($capdataExportData["content_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_mapping_type"] == "capdata_maitriseoeuvre_content_mapping"
                  &&
                  !empty($capdataExportData["content_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_content_dropdown"])
                ){
                  $capDataMaitriseOeuvreCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_content_dropdown"];
                }
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_maitriseoeuvre"])){
                $capDataMaitriseOeuvreMappingType = "taxo";
                if($capdataExportData["taxo_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_mapping_type"] == "capdata_maitriseoeuvre_taxo_mapping"
                  &&
                  !empty($capdataExportData["taxo_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_taxonomy_dropdown"])
                ){
                  $capDataMaitriseOeuvreCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_maitriseoeuvre"]["export_class_taxonomy_dropdown"];
                }                
              }
              $capDataMaitriseOeuvreMappingInfo = [
                  "capData_maitriseoeuvre_mappingtype" => $capDataMaitriseOeuvreMappingType,
                  "capData_maitriseoeuvre_correspondance" => $capDataMaitriseOeuvreCorrespondantEntity,
              ];              
              $this->setAPourMaitriseOeuvreCapdataProperty($evenement,
                                                      $capDataMaitriseOeuvreMappingInfo,
                                                      $capDataParticipationMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );
              // A pour programmation (Programmation ou participation)
              $capDataProgrammationMappingType = "";
              $capDataProgrammationCorrespondantEntity = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_programmation"])){
                $capDataProgrammationMappingType = "content";
                if($capdataExportData["content_mapped_classes"]["capdata_programmation"]["export_class_mapping_type"] == "capdata_programmation_content_mapping"
                  &&
                  !empty($capdataExportData["content_mapped_classes"]["capdata_programmation"]["export_class_content_dropdown"])
                ){
                  $capDataProgrammationCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_programmation"]["export_class_content_dropdown"];
                }
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_programmation"])){
                $capDataProgrammationMappingType = "taxo";
                if($capdataExportData["taxo_mapped_classes"]["capdata_programmation"]["export_class_mapping_type"] == "capdata_programmation_taxo_mapping"
                  &&
                  !empty($capdataExportData["taxo_mapped_classes"]["capdata_programmation"]["export_class_taxonomy_dropdown"])
                ){
                  $capDataProgrammationCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_programmation"]["export_class_taxonomy_dropdown"];
                }                
              }
              $capDataProgrammationMappingInfo = [
                  "capData_programmation_mappingtype" => $capDataProgrammationMappingType,
                  "capData_programmation_correspondance" => $capDataProgrammationCorrespondantEntity,
              ];              
              $this->setAPourProgrammationCapdataProperty($evenement,
                                                      $capDataProgrammationMappingInfo,
                                                      $capDataParticipationMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );
                                                                   
              // A pour type public (TypePublic)
              $capDataTypePublicMappingType = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_typepublic"])){
                $capDataTypePublicMappingType = "content";
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_typepublic"])){
                $capDataTypePublicMappingType = "taxo";
              }
              $capDataTypePublicMappingInfo = [
                  "capData_typepublic_mappingtype" => $capDataTypePublicMappingType,
              ];
              $this->setAPourTypePublicCapdataProperty($evenement,
                                                      $capDataTypePublicMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );               
              // Open agenda                                
              $this->setOpenAgendaCapdataProperty($evenement, 
                                                $eventItem, 
                                                $capDataClassInfo["export_class_capdata_properties"],
                                                "content"
                                              );                                                             
              // A pour type evenement  (typeEvenement)
              $capDataTypeEvenementMappingType = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_typeevenement"])){
                $capDataTypeEvenementMappingType = "content";
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_typeevenement"])){
                $capDataTypeEvenementMappingType = "taxo";
              }
              $capDataTypeEvenementMappingInfo = [
                  "capData_typeevenement_mappingtype" => $capDataTypeEvenementMappingType,
              ];
              $this->setAPourTypeEvenementCapdataProperty($evenement,
                                                      $capDataTypeEvenementMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );              
              // A pour lieu (Lieu)
              $capDataLieuCustomMappingType = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_lieu"])){
                $capDataLieuCustomMappingType = "content";
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_lieu"])){
                $capDataLieuCustomMappingType = "taxo";
              }
              $capDataLieuCustomMappingInfo = [
                  "capData_lieu_mappingtype" => $capDataLieuCustomMappingType,
              ];
              $this->setAPourLieuCapdataProperty($evenement,
                                                  $capDataLieuCustomMappingInfo,
                                                  $currentCapdataClassMappingInfo
                                                );               
              // A pour production (Production) 
              $capDataProductionCustomMappingType = "";
              if(isset($capdataExportData["content_mapped_classes"]["capdata_production"])){
                $capDataProductionCustomMappingType = "content";
              }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_production"])){
                $capDataProductionCustomMappingType = "taxo";
              }
              $capDataProductionCustomMappingInfo = [
                  "capData_production_mappingtype" => $capDataProductionCustomMappingType,
              ];
              $this->setAPourProductionCapdataProperty($evenement,
                                                      $capDataProductionCustomMappingInfo,
                                                      $currentCapdataClassMappingInfo
                                                    );              
              // Dates
              $this->setEventDatesCapdataProperties($evenement, $eventItem, $capDataClassInfo["export_class_capdata_properties"], "content");
              // Alter
              $this->moduleHandler->alter('capdata_graph_item', $evenement, $eventItem, $graph);              
              $graph->add($evenement);
            }
            break;          
          default:
              break;
        }
      }
    }
    // Custom hook, pour l'ajout de données supplémentaires hook_capdata_graph_alter(&$graph) à la fin de l'export
    $this->moduleHandler->alter('capdata_graph', $graph, $capdataExportData);
    // Finally, output the graph
    $data = $serializer->serialize($graph, 'rdfxml');
    return $data;
  }

  /**
   * Set NameTrait Capdata Properties.
   * 
   * @param mixed $graphItem
   * @param string $name
   */
  public function setNameTraitCapdataProperties($graphItem, $name): void {
    $graphItem->setName($name);
  }

  /**
   * Set Catalog Capdata Properties.
   * 
   * @param mixed $graphItem
   * @param \Drupal\taxonomy\TermInterface|\Drupal\node\NodeInterface $mappedEntity
   * @param array $exportClassCapdataProperties
   * @param string $mappingType
   */
  public function setCatalogCapdataProperties($graphItem, $mappedEntity, $exportClassCapdataProperties, $mappingType): void {
    // Date création ressource
    $creationDateObj ="";
    if ($mappedEntity instanceof \Drupal\taxonomy\TermInterface) {
      // $mappedEntity is a taxonomy term
      $creationDateObj = DrupalDateTime::createFromTimestamp((int)$mappedEntity->changed->value);
    }else{
      $creationDateObj = DrupalDateTime::createFromTimestamp((int)$mappedEntity->created->value);
    }
    if(isset($exportClassCapdataProperties["date_creation_ressource"])){
      if(!empty($exportClassCapdataProperties["date_creation_ressource"]["property_".$mappingType."_fields_dropdown"])){
        $dateCreationFieldName = $exportClassCapdataProperties["date_creation_ressource"]["property_".$mappingType."_fields_dropdown"];
        if ($mappedEntity->hasField($dateCreationFieldName) && !$mappedEntity->get($dateCreationFieldName)->isEmpty()) {
          if(!empty($mappedEntity->get($dateCreationFieldName)->date)){
            $creationDateObj = $mappedEntity->get($dateCreationFieldName)->date;
          }else{
            // timestamp field
            $creationDateObj = DrupalDateTime::createFromTimestamp((int)$mappedEntity->get($dateCreationFieldName)->value);
          }
        }
      }
    }
    // Date modification ressource
    $modifDateObj = DrupalDateTime::createFromTimestamp((int)$mappedEntity->changed->value);
    if(isset($exportClassCapdataProperties["date_modification_ressource"])){
      if(!empty($exportClassCapdataProperties["date_modification_ressource"]["property_".$mappingType."_fields_dropdown"])){
        $dateModifFieldName = $exportClassCapdataProperties["date_modification_ressource"]["property_".$mappingType."_fields_dropdown"];
        if ($mappedEntity->hasField($dateModifFieldName) && !$mappedEntity->get($dateModifFieldName)->isEmpty()) {
          if(!empty($mappedEntity->get($dateModifFieldName)->date)){
            $modifDateObj = $mappedEntity->get($dateModifFieldName)->date;
          }else{
            // timestamp field
            $modifDateObj = DrupalDateTime::createFromTimestamp((int)$mappedEntity->get($dateModifFieldName)->value);
          }
        }
      }
    }

    $creationDate = "";
    if(!empty($creationDateObj)){
      $creationDateObj->setTimezone(new \DateTimeZone('UTC'));
      $creationDate = $creationDateObj->format('Y-m-d\TH:i:s\Z');
    }
    $modifDate = "";
    if(!empty($modifDateObj)){
      $modifDateObj->setTimezone(new \DateTimeZone('UTC'));
      $modifDate = $modifDateObj->format('Y-m-d\TH:i:s\Z');
    }

    // Catalogage source date
    $sourceDate = "";
    if(isset($exportClassCapdataProperties["catalogage_source_date"])){
      if(!empty($exportClassCapdataProperties["catalogage_source_date"]["property_".$mappingType."_fields_dropdown"])){
        $sourceDateFieldName = $exportClassCapdataProperties["catalogage_source_date"]["property_".$mappingType."_fields_dropdown"];
        if ($mappedEntity->hasField($sourceDateFieldName) && !$mappedEntity->get($sourceDateFieldName)->isEmpty()) {
          if(!empty($mappedEntity->get($sourceDateFieldName)->date)){
            $sourceDateObj = $mappedEntity->get($sourceDateFieldName)->date;
          }else{
            // timestamp field
            $sourceDateObj = DrupalDateTime::createFromTimestamp((int)$mappedEntity->get($sourceDateFieldName)->value);
          }
          $sourceDateObj->setTimezone(new \DateTimeZone('UTC'));
          $sourceDate = $sourceDateObj->format('Y-m-d\TH:i:s\Z');
        }
      }
    }

    if(!empty($creationDate)){
      $graphItem->setDateCreationRessource($creationDate);
    }
    if(!empty($modifDate)){
      $graphItem->setDateModificationRessource($modifDate);
    }
    if(!empty($sourceDate)){
      $graphItem->setCatalogageSourceDate($sourceDate);
    }
  }


  /**
   * Set Catalogage source agence property.
   * 
   * @param mixed $graphItem
   * @param array $capDataCollectiviteMappingInfo
   * @param array $currentCapdataClassMappingInfo
   */
  public function setCatalogageSourceAgenceProperty($graphItem, $capDataCollectiviteMappingInfo, $currentCapdataClassMappingInfo): void {
    // Catalogage source agence
    $sourceAgences = null;
    if(!empty($currentCapdataClassMappingInfo)){
      $exportClassCapdataProperties =  $currentCapdataClassMappingInfo["mapped_entity_properties"];
      $mappingType = $currentCapdataClassMappingInfo["mapping_type"];
      $mappedEntity = $currentCapdataClassMappingInfo["mapped_entity"];
      $host = $currentCapdataClassMappingInfo["host"];
      if(!empty($mappedEntity) && !empty($mappingType) && !empty($exportClassCapdataProperties)){
        if(isset($exportClassCapdataProperties["catalogage_source_agence"])){
          if(!empty($exportClassCapdataProperties["catalogage_source_agence"]["property_".$mappingType."_fields_dropdown"])){
            $sourceAgenceFieldName = $exportClassCapdataProperties["catalogage_source_agence"]["property_".$mappingType."_fields_dropdown"];
            if ($mappedEntity->hasField($sourceAgenceFieldName) && !$mappedEntity->get($sourceAgenceFieldName)->isEmpty()) {
              $sourceAgenceItems = $mappedEntity->get($sourceAgenceFieldName);
              foreach ($sourceAgenceItems as $referenceSourceAgence) {
                $sourceAgenceItem = $referenceSourceAgence->entity;
                if(!empty($sourceAgenceItem)){
                  $referenceSourceAgenceId =  $sourceAgenceItem->id();
                  if(!empty($capDataCollectiviteMappingInfo)){
                    if(!empty($capDataCollectiviteMappingInfo["capData_collectivite_mappingtype"])){
                      $collectiviteMappingType = $capDataCollectiviteMappingInfo["capData_collectivite_mappingtype"];
                      $referenceSourceAgenceUrl = "";
                      if($collectiviteMappingType == "taxo" && $sourceAgenceItem instanceof \Drupal\taxonomy\TermInterface){
                        $referenceSourceAgenceUrl = $host . "/taxonomy/term/" . $referenceSourceAgenceId;
                      }elseif($collectiviteMappingType == "content" && $sourceAgenceItem instanceof \Drupal\node\NodeInterface){
                        $referenceSourceAgenceUrl = $host . "/node/" . $referenceSourceAgenceId;
                      }
                      if(!empty($referenceSourceAgenceUrl)){
                        $sourceAgences[] = new ExternalThing($referenceSourceAgenceUrl);
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    if (!empty($sourceAgences)) {
      $graphItem->setCatalogageSourceAgence($sourceAgences);
    }elseif(!empty($capDataCollectiviteMappingInfo["default_collectivite"])){
      $graphItem->setCatalogageSourceAgence($capDataCollectiviteMappingInfo["default_collectivite"]);
    }
  }

  /**
   * Set Collectivite Capdata Properties.
   * 
   * @param mixed $collectivite
   * @param \Drupal\taxonomy\TermInterface|\Drupal\node\NodeInterface $mappedEntity
   * @param array $capDataCollectiviteClassInfo
   * @param string $collectiviteMappingType
   */
  public function setCapdataCollectiviteProperties($collectivite, $mappedEntity, $capDataCollectiviteClassInfo, $collectiviteMappingType): void {
    // Label
    $label = "";
    if($collectiviteMappingType == "content"){
      $label = $mappedEntity->getTitle();
    }else{
      $label = $mappedEntity->label();
    }
    if(isset($capDataCollectiviteClassInfo["export_class_capdata_properties"]["cd_label"])){
      if(!empty($capDataCollectiviteClassInfo["export_class_capdata_properties"]["cd_label"]["property_".$collectiviteMappingType."_fields_dropdown"])){
        $labelFieldName = $capDataCollectiviteClassInfo["export_class_capdata_properties"]["cd_label"]["property_".$collectiviteMappingType."_fields_dropdown"];
        if ($mappedEntity->hasField($labelFieldName) && !$mappedEntity->get($labelFieldName)->isEmpty()) {
          $label = $mappedEntity->get($labelFieldName)->value;
          if(!empty($label)){
            if(!empty($capDataCollectiviteClassInfo["export_class_capdata_properties"]["cd_label"]["property_".$collectiviteMappingType."_custom_processing"])){
              $customProcessing = $capDataCollectiviteClassInfo["export_class_capdata_properties"]["cd_label"]["property_".$collectiviteMappingType."_custom_processing"];
              $label = $this->customFieldProcessing($label, $customProcessing);
            }
          }
        }
      }
    }
    if(!empty($label)){
      $collectivite->setNom($label);
    }
    // Siret 
    $siret = "";
    if(isset($capDataCollectiviteClassInfo["export_class_capdata_properties"]["siret"])){
      if(!empty($capDataCollectiviteClassInfo["export_class_capdata_properties"]["siret"]["property_".$collectiviteMappingType."_fields_dropdown"])){
        $siretFieldName = $capDataCollectiviteClassInfo["export_class_capdata_properties"]["siret"]["property_".$collectiviteMappingType."_fields_dropdown"];
        if($mappedEntity->hasField($siretFieldName) && !$mappedEntity->get($siretFieldName)->isEmpty()) {
          $siret = $mappedEntity->get($siretFieldName)->value;
          if(!empty($siret)){
            if(!empty($capDataCollectiviteClassInfo["export_class_capdata_properties"]["siret"]["property_".$collectiviteMappingType."_custom_processing"])){
              $customProcessing = $capDataCollectiviteClassInfo["export_class_capdata_properties"]["siret"]["property_".$collectiviteMappingType."_custom_processing"];
              $siret = $this->customFieldProcessing($siret, $customProcessing);
            }
            $collectivite->setSiret($siret);
          }
        }
      }
    }
    // Description
    if($collectiviteMappingType == "content"){
        $this->setDescriptionCapdataProperty($collectivite, 
          $mappedEntity, 
          $capDataCollectiviteClassInfo["export_class_capdata_properties"],
          "content"
        );   
    }elseif($collectiviteMappingType == "taxo"){
      $this->setDescriptionCapdataProperty($collectivite, 
          $mappedEntity, 
          $capDataCollectiviteClassInfo["export_class_capdata_properties"],
          "taxo"
      );   
    }
    // Has Socials Trait properties
    if($collectiviteMappingType == "content"){
      $this->setSocialsCapdataProperties($collectivite, 
        $mappedEntity, 
        $capDataCollectiviteClassInfo["export_class_capdata_properties"],
        "content"
      );  
    }elseif($collectiviteMappingType == "taxo"){
      $this->setSocialsCapdataProperties($collectivite, 
        $mappedEntity, 
        $capDataCollectiviteClassInfo["export_class_capdata_properties"],
        "taxo"
      );  
    }
    // Nom forme rejet 
    $nomFormeRejet = "";
    if(isset($capDataCollectiviteClassInfo["export_class_capdata_properties"]["nom_forme_rejet"])){
      if(!empty($capDataCollectiviteClassInfo["export_class_capdata_properties"]["nom_forme_rejet"]["property_".$collectiviteMappingType."_fields_dropdown"])){
        $nomFormeRejetFieldName = $capDataCollectiviteClassInfo["export_class_capdata_properties"]["nom_forme_rejet"]["property_".$collectiviteMappingType."_fields_dropdown"];
        if($mappedEntity->hasField($nomFormeRejetFieldName) && !$mappedEntity->get($nomFormeRejetFieldName)->isEmpty()) {
          $nomFormeRejet = $mappedEntity->get($nomFormeRejetFieldName)->value;
          if(!empty($nomFormeRejet)){
            if(!empty($capDataCollectiviteClassInfo["export_class_capdata_properties"]["nom_forme_rejet"]["property_".$collectiviteMappingType."_custom_processing"])){
              $customProcessing = $capDataCollectiviteClassInfo["export_class_capdata_properties"]["nom_forme_rejet"]["property_".$collectiviteMappingType."_custom_processing"];
              $nomFormeRejet = $this->customFieldProcessing($nomFormeRejet, $customProcessing);
            }
            $collectivite->setNomFormeRejet($nomFormeRejet);
          }
        }
      }
    }
    // Open agenda 
    $openAgenda = "";
    if(isset($capDataCollectiviteClassInfo["export_class_capdata_properties"]["open_agenda_id"])){
      if(!empty($capDataCollectiviteClassInfo["export_class_capdata_properties"]["open_agenda_id"]["property_".$collectiviteMappingType."_fields_dropdown"])){
        $openAgendaFieldName = $capDataCollectiviteClassInfo["export_class_capdata_properties"]["open_agenda_id"]["property_".$collectiviteMappingType."_fields_dropdown"];
        if($mappedEntity->hasField($openAgendaFieldName) && !$mappedEntity->get($openAgendaFieldName)->isEmpty()) {
          $openAgenda = $mappedEntity->get($openAgendaFieldName)->value;
          if(!empty($openAgenda)){
            if(!empty($capDataCollectiviteClassInfo["export_class_capdata_properties"]["open_agenda_id"]["property_".$collectiviteMappingType."_custom_processing"])){
              $customProcessing = $capDataCollectiviteClassInfo["export_class_capdata_properties"]["open_agenda_id"]["property_".$collectiviteMappingType."_custom_processing"];
              $openAgenda = $this->customFieldProcessing($openAgenda, $customProcessing);
            }
            $collectivite->setOpenAgenda("https://openagenda.com/" . $openAgenda);
          }
        }
      }
    }
    // Catalog Class properties
    if($collectiviteMappingType == "content"){
        $this->setCatalogCapdataProperties($collectivite, 
          $mappedEntity, 
          $capDataCollectiviteClassInfo["export_class_capdata_properties"],
          "content"
        );  
    }elseif($collectiviteMappingType == "taxo"){
      $this->setCatalogCapdataProperties($collectivite, 
        $mappedEntity, 
        $capDataCollectiviteClassInfo["export_class_capdata_properties"],
        "taxo"
      );  
    }
  }

  /**
   * Set Socials Capdata Properties.
   * 
   * @param mixed $graphItem
   * @param \Drupal\taxonomy\TermInterface|\Drupal\node\NodeInterface $mappedEntity
   * @param array $exportClassCapdataProperties
   * @param string $mappingType
   */
  public function setSocialsCapdataProperties($graphItem, $mappedEntity, $exportClassCapdataProperties, $mappingType): void {
    // Facebook
    $facebook = "";
    if(isset($exportClassCapdataProperties["facebook"])){
      if(!empty($exportClassCapdataProperties["facebook"]["property_".$mappingType."_fields_dropdown"])){
        $facebookFieldName = $exportClassCapdataProperties["facebook"]["property_".$mappingType."_fields_dropdown"];
        if($mappedEntity->hasField($facebookFieldName) && !$mappedEntity->get($facebookFieldName)->isEmpty()) {
          $facebookFieldDefinition = $mappedEntity->getFieldDefinition($facebookFieldName);
          if($facebookFieldDefinition->getType() == 'link'){
            $facebookUri = $mappedEntity->get($facebookFieldName)->uri;
            $facebookUrl =  Url::fromUri($facebookUri, ['absolute' => TRUE]);
            $facebook = $facebookUrl->toString();
          }else{
            $facebook = $mappedEntity->get($facebookFieldName)->value;
          }
          if(!empty($facebook)){
            if(!empty($exportClassCapdataProperties["facebook"]["property_".$mappingType."_custom_processing"])){
              $customProcessing = $exportClassCapdataProperties["facebook"]["property_".$mappingType."_custom_processing"];
              $facebook = $this->customFieldProcessing($facebook, $customProcessing);
            }
            $facebook = $this->cleanUrl($facebook);
            $graphItem->setFacebook($facebook);
          }
        }
      }
    }
    // Twitter
    $twitter = "";
    if(isset($exportClassCapdataProperties["twitter"])){
      if(!empty($exportClassCapdataProperties["twitter"]["property_".$mappingType."_fields_dropdown"])){
        $twitterFieldName = $exportClassCapdataProperties["twitter"]["property_".$mappingType."_fields_dropdown"];
        if($mappedEntity->hasField($twitterFieldName) && !$mappedEntity->get($twitterFieldName)->isEmpty()) {
          $twitterFieldDefinition = $mappedEntity->getFieldDefinition($twitterFieldName);
          if($twitterFieldDefinition->getType() == 'link'){
            $twitterUri = $mappedEntity->get($twitterFieldName)->uri;
            $twitterUrl =  Url::fromUri($twitterUri, ['absolute' => TRUE]);
            $twitter = $twitterUrl->toString();
          }else{
            $twitter = $mappedEntity->get($twitterFieldName)->value;
          }
          if(!empty($twitter)){
            if(!empty($exportClassCapdataProperties["twitter"]["property_".$mappingType."_custom_processing"])){
              $customProcessing = $exportClassCapdataProperties["twitter"]["property_".$mappingType."_custom_processing"];
              $twitter = $this->customFieldProcessing($twitter, $customProcessing);
            }
            $twitter = $this->cleanUrl($twitter);
            $graphItem->setTwitter($twitter);
          }
        }
      }
    }
    // Site web
    $siteWeb = "";
    if(isset($exportClassCapdataProperties["page_web"])){
      if(!empty($exportClassCapdataProperties["page_web"]["property_".$mappingType."_fields_dropdown"])){
        $siteWebFieldName = $exportClassCapdataProperties["page_web"]["property_".$mappingType."_fields_dropdown"];
        if($mappedEntity->hasField($siteWebFieldName) && !$mappedEntity->get($siteWebFieldName)->isEmpty()) {
          $siteWebFieldDefinition = $mappedEntity->getFieldDefinition($siteWebFieldName);
          if($siteWebFieldDefinition->getType() == 'link'){
            $siteWebUri = $mappedEntity->get($siteWebFieldName)->uri;
            $siteWebUrl =  Url::fromUri($siteWebUri, ['absolute' => TRUE]);
            $siteWeb = $siteWebUrl->toString();
          }else{
            $siteWeb = $mappedEntity->get($siteWebFieldName)->value;
          }
          if(!empty($siteWeb)){
            if(!empty($exportClassCapdataProperties["page_web"]["property_".$mappingType."_custom_processing"])){
              $customProcessing = $exportClassCapdataProperties["page_web"]["property_".$mappingType."_custom_processing"];
              $siteWeb = $this->customFieldProcessing($siteWeb, $customProcessing);
            }
            $siteWeb = $this->cleanUrl($siteWeb);
            $graphItem->setSiteWeb($siteWeb);
          }
        }
      }
    }
  }

  /**
   * Set Image capdata Property.
   * 
   * @param mixed $graphItem
   * @param \Drupal\taxonomy\TermInterface|\Drupal\node\NodeInterface $mappedEntity
   * @param array $exportClassCapdataProperties
   * @param string $mappingType
   */
  public function setCapdataImageProperty($graphItem, $mappedEntity, $exportClassCapdataProperties, $mappingType): void {  
    // Image
    $refImageUrl = "";
    if(isset($exportClassCapdataProperties["image"])){
      if(!empty($exportClassCapdataProperties["image"]["property_".$mappingType."_fields_dropdown"])){
        $imageTraitFieldName = $exportClassCapdataProperties["image"]["property_".$mappingType."_fields_dropdown"];
        if($mappedEntity->hasField($imageTraitFieldName) && !$mappedEntity->get($imageTraitFieldName)->isEmpty()) {
          $imgFieldDefinition = $mappedEntity->getFieldDefinition($imageTraitFieldName);
          if($imgFieldDefinition->getType() == 'image'){
            /** @var File $refImage */
            $refImage = $mappedEntity->get($imageTraitFieldName)->entity;
            $uriRefImage = $refImage->getFileUri();
            $refImageUrl = $this->fileUrlGenerator->generateAbsoluteString($uriRefImage);
          }elseif($imgFieldDefinition->getType() == "entity_reference" && $imgFieldDefinition->getSetting('target_type') == 'media'){
            $handlerSettings = $imgFieldDefinition->getSetting('handler_settings');
            $targetBundles = $handlerSettings['target_bundles'];
            if(in_array('image', $targetBundles)){
              /** @var Media $refImage */
              $refImage = $mappedEntity->get($imageTraitFieldName)->entity;
              $uriRefImage = $refImage->get('field_media_image')->entity->getFileUri();
              $refImageUrl = $this->fileUrlGenerator->generateAbsoluteString($uriRefImage);
            }
          }
          if(!empty($refImageUrl)){
            $graphItem->setImage($refImageUrl);
          }
        }
      }
    }
  }  

  /**
   * Get Media Property Info.
   * 
   * @param mixed $graphItem
   * @param \Drupal\taxonomy\TermInterface|\Drupal\node\NodeInterface $mappedEntity
   * @param array $exportClassCapdataProperties
   * @param string $mappingType
   * 
   * @return array
   */
  public function getCapdataMediaPropertyInfo($graphItem, $mappedEntity, $exportClassCapdataProperties, $mappingType){  
    // Image, Sound ...
    $refImageUrl = "";
    $refSoundUrl = "";
    $mediaInfo = [];
    if(isset($exportClassCapdataProperties["media"])){
      if(!empty($exportClassCapdataProperties["media"]["property_".$mappingType."_fields_dropdown"])){
        $mediaTraitFieldName = $exportClassCapdataProperties["media"]["property_".$mappingType."_fields_dropdown"];
        if($mappedEntity->hasField($mediaTraitFieldName) && !$mappedEntity->get($mediaTraitFieldName)->isEmpty()) {
          $mediaFieldDefinition = $mappedEntity->getFieldDefinition($mediaTraitFieldName);
          if($mediaFieldDefinition->getType() == 'image'){
            /** @var File $refImage */
            $refImage = $mappedEntity->get($mediaTraitFieldName)->entity;
            $uriRefImage = $refImage->getFileUri();
            $refImageUrl = $this->fileUrlGenerator->generateAbsoluteString($uriRefImage);
          }elseif($mediaFieldDefinition->getType() == "entity_reference" && $mediaFieldDefinition->getSetting('target_type') == 'media'){
            $handlerSettings = $mediaFieldDefinition->getSetting('handler_settings');
            $targetBundles = $handlerSettings['target_bundles'];
            if(in_array('image', $targetBundles)){
              /** @var Media $refImage */
              $refImage = $mappedEntity->get($mediaTraitFieldName)->entity;
              $uriRefImage = $refImage->get('field_media_image')->entity->getFileUri();
              $refImageUrl = $this->fileUrlGenerator->generateAbsoluteString($uriRefImage);
            }
          }
          if(!empty($refImageUrl)){
            $mediaInfo["mediaType"] = "image";
            $mediaInfo["mediaUrl"] = $refImageUrl;
          }
          // $refSoundUrl to be implemented - $mediaInfo["mediaType"] = "sound";
        }
      }
    }
    return $mediaInfo;
  }

  /**
   * Set Identifiant Rof.
   * 
   * @param mixed $graphItem
   * @param \Drupal\taxonomy\TermInterface|\Drupal\node\NodeInterface $mappedEntity
   * @param array $exportClassCapdataProperties
   * @param string $mappingType
   */
  public function setIdentifiantRofProperty($graphItem, $mappedEntity, $exportClassCapdataProperties, $mappingType): void {
    $identifiantRof = "";
    if(isset($exportClassCapdataProperties["identifiant_rof"])){
      if(!empty($exportClassCapdataProperties["identifiant_rof"]["property_".$mappingType."_fields_dropdown"])){
        $identifiantRofFieldName = $exportClassCapdataProperties["identifiant_rof"]["property_".$mappingType."_fields_dropdown"];
        if($mappedEntity->hasField($identifiantRofFieldName) && !$mappedEntity->get($identifiantRofFieldName)->isEmpty()){
          $identifiantRof = $mappedEntity->get($identifiantRofFieldName)->value;
          if(!empty($identifiantRof)){
            if(!empty($exportClassCapdataProperties["identifiant_rof"]["property_".$mappingType."_custom_processing"])){
              $customProcessing = $exportClassCapdataProperties["identifiant_rof"]["property_".$mappingType."_custom_processing"];
              $identifiantRof = $this->customFieldProcessing($identifiantRof, $customProcessing);
            }
            $graphItem->setIdentifiantRof($identifiantRof);
          }
        }
      }
    }
  }

  /**
   * Set OpenAgenda Capdata Properties.
   * 
   * @param mixed $graphItem
   * @param \Drupal\taxonomy\TermInterface|\Drupal\node\NodeInterface $mappedEntity
   * @param array $exportClassCapdataProperties
   * @param string $mappingType
   */
  public function setOpenAgendaCapdataProperty($graphItem, $mappedEntity, $exportClassCapdataProperties, $mappingType): void {
    // Open agenda 
    $openAgenda = "";
    if(isset($exportClassCapdataProperties["open_agenda_id"])){
      if(!empty($exportClassCapdataProperties["open_agenda_id"]["property_".$mappingType."_fields_dropdown"])){
        $openAgendaFieldName = $exportClassCapdataProperties["open_agenda_id"]["property_".$mappingType."_fields_dropdown"];
        if($mappedEntity->hasField($openAgendaFieldName) && !$mappedEntity->get($openAgendaFieldName)->isEmpty()) {
          $openAgenda = $mappedEntity->get($openAgendaFieldName)->value;
          if(!empty($openAgenda)){
            if(!empty($exportClassCapdataProperties["open_agenda_id"]["property_".$mappingType."_custom_processing"])){
              $customProcessing = $exportClassCapdataProperties["open_agenda_id"]["property_".$mappingType."_custom_processing"];
              $openAgenda = $this->customFieldProcessing($openAgenda, $customProcessing);
            }
            $graphItem->setOpenAgenda("https://openagenda.com/" . $openAgenda);
          }
        }
      }
    }
  }

  /**
   * Set Description Capdata Properties.
   * 
   * @param mixed $graphItem
   * @param \Drupal\taxonomy\TermInterface|\Drupal\node\NodeInterface $mappedEntity
   * @param array $exportClassCapdataProperties
   * @param string $mappingType
   */
  public function setDescriptionCapdataProperty($graphItem, $mappedEntity, $exportClassCapdataProperties, $mappingType): void {
    // Description
    $description = "";
    if(isset($exportClassCapdataProperties["description"])){
      if(!empty($exportClassCapdataProperties["description"]["property_".$mappingType."_fields_dropdown"])){
        $descriptionFieldName = $exportClassCapdataProperties["description"]["property_".$mappingType."_fields_dropdown"];
        if($mappedEntity->hasField($descriptionFieldName) && !$mappedEntity->get($descriptionFieldName)->isEmpty()) {
          $description = $mappedEntity->get($descriptionFieldName)->value;
          if(!empty($description)){
            if(!empty($exportClassCapdataProperties["description"]["property_".$mappingType."_custom_processing"])){
              $customProcessing = $exportClassCapdataProperties["description"]["property_".$mappingType."_custom_processing"];
              $description = $this->customFieldProcessing($description, $customProcessing);
            }
            $description = str_replace(["\x08", "\x03"], " ", $description);
            $graphItem->setDescription($description);
          }
        }
      }
    }
  }

  /**
   * Set ArkBnfTrait Capdata Properties.
   * 
   * @param mixed $graphItem
   * @param \Drupal\taxonomy\TermInterface|\Drupal\node\NodeInterface $mappedEntity
   * @param array $exportClassCapdataProperties
   * @param string $mappingType
   */
  public function setArkBnfTraitCapdataProperty($graphItem, $mappedEntity, $exportClassCapdataProperties, $mappingType): void {
    // frBnf
    $frBnf = "";
    if(isset($exportClassCapdataProperties["fr_bnf"])){
      if(!empty($exportClassCapdataProperties["fr_bnf"]["property_".$mappingType."_fields_dropdown"])){
        $frBnfFieldName = $exportClassCapdataProperties["fr_bnf"]["property_".$mappingType."_fields_dropdown"];
        if($mappedEntity->hasField($frBnfFieldName) && !$mappedEntity->get($frBnfFieldName)->isEmpty()) {
          $frBnf = $mappedEntity->get($frBnfFieldName)->value;
          if(!empty($frBnf)){
            if(!empty($exportClassCapdataProperties["fr_bnf"]["property_".$mappingType."_custom_processing"])){
              $customProcessing = $exportClassCapdataProperties["fr_bnf"]["property_".$mappingType."_custom_processing"];
              $frBnf = $this->customFieldProcessing($frBnf, $customProcessing);
            }
            $graphItem->setFrBnf($frBnf);
          }
        }
      }
    }   
  }


  /**
   * Set ArkBnfTrait ArkBnf Capdata Property.
   * 
   * @param mixed $graphItem
   * @param \Drupal\taxonomy\TermInterface|\Drupal\node\NodeInterface $mappedEntity
   * @param array $exportClassCapdataProperties
   * @param string $mappingType
   * 
   * @return string
   */
  public function setExternalArkBnfCapdataProperty($graphItem, $mappedEntity, $exportClassCapdataProperties, $mappingType){
    // ArkBnf
    $arkBnf = "";
    if(isset($exportClassCapdataProperties["ark_bnf"])){
      if(!empty($exportClassCapdataProperties["ark_bnf"]["property_".$mappingType."_fields_dropdown"])){
        $arkBnfFieldName = $exportClassCapdataProperties["ark_bnf"]["property_".$mappingType."_fields_dropdown"];
        if($mappedEntity->hasField($arkBnfFieldName) && !$mappedEntity->get($arkBnfFieldName)->isEmpty()) {
          $arkBnfFieldDefinition = $mappedEntity->getFieldDefinition($arkBnfFieldName);
          if($arkBnfFieldDefinition->getType() == 'link'){
            $arkBnfUri = $mappedEntity->get($arkBnfFieldName)->uri;
            $arkBnfUrl =  Url::fromUri($arkBnfUri, ['absolute' => TRUE]);
            $arkBnf = $arkBnfUrl->toString();
          }else{
            $arkBnf = $mappedEntity->get($arkBnfFieldName)->value;
          }
          if(!empty($arkBnf)){
            if(!empty($exportClassCapdataProperties["ark_bnf"]["property_".$mappingType."_custom_processing"])){
              $customProcessing = $exportClassCapdataProperties["ark_bnf"]["property_".$mappingType."_custom_processing"];
              $arkBnf = $this->customFieldProcessing($arkBnf, $customProcessing);
            }
            $arkBnf = $this->cleanUrl($arkBnf);
          }
        }
      }
    }  
    return $arkBnf;  
  }  


  /**
   * Set ISNI Capdata Property.
   * 
   * @param mixed $graphItem
   * @param \Drupal\taxonomy\TermInterface|\Drupal\node\NodeInterface $mappedEntity
   * @param array $exportClassCapdataProperties
   * @param string $mappingType
   * 
   * @return string
   */
  public function setIsniCapdataProperty($graphItem, $mappedEntity, $exportClassCapdataProperties, $mappingType){
    // ISNI
    $isni = "";
    if(isset($exportClassCapdataProperties["isni"])){
      if(!empty($exportClassCapdataProperties["isni"]["property_".$mappingType."_fields_dropdown"])){
        $isniFieldName = $exportClassCapdataProperties["isni"]["property_".$mappingType."_fields_dropdown"];
        if($mappedEntity->hasField($isniFieldName) && !$mappedEntity->get($isniFieldName)->isEmpty()) {
          $isniFieldDefinition = $mappedEntity->getFieldDefinition($isniFieldName);
          if($isniFieldDefinition->getType() == 'link'){
            $isniUri = $mappedEntity->get($isniFieldName)->uri;
            $isniUrl =  Url::fromUri($isniUri, ['absolute' => TRUE]);
            $isni = $isniUrl->toString();
          }else{
            $isni = $mappedEntity->get($isniFieldName)->value;
          }
          if(!empty($isni)){
            if(!empty($exportClassCapdataProperties["isni"]["property_".$mappingType."_custom_processing"])){
              $customProcessing = $exportClassCapdataProperties["isni"]["property_".$mappingType."_custom_processing"];
              $isni = $this->customFieldProcessing($isni, $customProcessing);
            }
            $isni = $this->cleanUrl($isni);
          }
        }
      }
    }  
    return $isni;  
  }  

  /**
   * Set Custom Referentiel Capdata Properties.
   * 
   * @param mixed $graphItem
   * @param \Drupal\taxonomy\TermInterface|\Drupal\node\NodeInterface $mappedEntity
   * @param array $exportClassCapdataProperties
   * @param string $mappingType
   */
  public function setReferentielCustomProperties($graphItem, $mappedEntity, $exportClassCapdataProperties, $mappingType): void {  
    // Label
    $label = "";
    if($mappingType == "content"){
      $label = $mappedEntity->getTitle();
    }else{
      $label = $mappedEntity->label();
    }
    if(isset($exportClassCapdataProperties["cd_label"])){
      if(!empty($exportClassCapdataProperties["cd_label"]["property_".$mappingType."_fields_dropdown"])){
        $labelFieldName = $exportClassCapdataProperties["cd_label"]["property_".$mappingType."_fields_dropdown"];
        if($mappedEntity->hasField($labelFieldName) && !$mappedEntity->get($labelFieldName)->isEmpty()) {
          $label = $mappedEntity->get($labelFieldName)->value;
          if(!empty($label)){
            if(!empty($exportClassCapdataProperties["cd_label"]["property_".$mappingType."_custom_processing"])){
              $customProcessing = $exportClassCapdataProperties["cd_label"]["property_".$mappingType."_custom_processing"];
              $label = $this->customFieldProcessing($label, $customProcessing);
            }
          }
        }
      }
    }
    if(!empty($label)){
      $label = trim($label, " \t\n\r\0\v\xc2\xa0");
      $graphItem->setLabel($label);
    }
    // alt Label
    $altLabel = "";
    if(isset($exportClassCapdataProperties["alt_label"])){
      if(!empty($exportClassCapdataProperties["alt_label"]["property_".$mappingType."_fields_dropdown"])){
        $altLabelFieldName = $exportClassCapdataProperties["alt_label"]["property_".$mappingType."_fields_dropdown"];
        if($mappedEntity->hasField($altLabelFieldName) && !$mappedEntity->get($altLabelFieldName)->isEmpty()) {
          $altLabel = $mappedEntity->get($altLabelFieldName)->value;
          if(!empty($altLabel)){
            if(!empty($exportClassCapdataProperties["alt_label"]["property_".$mappingType."_custom_processing"])){
              $customProcessing = $exportClassCapdataProperties["alt_label"]["property_".$mappingType."_custom_processing"];
              $altLabel = $this->customFieldProcessing($altLabel, $customProcessing);
            }
            $graphItem->setAltLabel($altLabel);
          }
        }
      }
    }
    // Image
    $refImageUrl = "";
    if(isset($exportClassCapdataProperties["image"])){
      if(!empty($exportClassCapdataProperties["image"]["property_".$mappingType."_fields_dropdown"])){
        $imageTraitFieldName = $exportClassCapdataProperties["image"]["property_".$mappingType."_fields_dropdown"];
        if($mappedEntity->hasField($imageTraitFieldName) && !$mappedEntity->get($imageTraitFieldName)->isEmpty()) {
          $imgFieldDefinition = $mappedEntity->getFieldDefinition($imageTraitFieldName);
          if($imgFieldDefinition->getType() == 'image'){
            /** @var File $refImage */
            $refImage = $mappedEntity->get($imageTraitFieldName)->entity;
            $uriRefImage = $refImage->getFileUri();
            $refImageUrl = $this->fileUrlGenerator->generateAbsoluteString($uriRefImage);
          }elseif($imgFieldDefinition->getType() == "entity_reference" && $imgFieldDefinition->getSetting('target_type') == 'media'){
            $handlerSettings = $imgFieldDefinition->getSetting('handler_settings');
            $targetBundles = $handlerSettings['target_bundles'];
            if(in_array('image', $targetBundles)){
              /** @var Media $refImage */
              $refImage = $mappedEntity->get($imageTraitFieldName)->entity;
              $uriRefImage = $refImage->get('field_media_image')->entity->getFileUri();
              $refImageUrl = $this->fileUrlGenerator->generateAbsoluteString($uriRefImage);
            }
          }
          if(!empty($refImageUrl)){
            $graphItem->setImage($refImageUrl);
          }
        }
      }
    }
    // Description
    if($mappingType == "content"){
          $this->setDescriptionCapdataProperty($graphItem, 
            $mappedEntity, 
            $exportClassCapdataProperties,
            "content"
          );   
    }elseif($mappingType == "taxo"){
          $this->setDescriptionCapdataProperty($graphItem,
              $mappedEntity, 
              $exportClassCapdataProperties,
              "taxo"
          );
    }
    // ArkBnf Trait
    if($mappingType == "content"){
          $this->setArkBnfTraitCapdataProperty($graphItem, 
            $mappedEntity, 
            $exportClassCapdataProperties,
            "content"
          );   
    }elseif($mappingType == "taxo"){
          $this->setArkBnfTraitCapdataProperty($graphItem,
              $mappedEntity, 
              $exportClassCapdataProperties,
              "taxo"
          );
    }
    // Identifiant Rof
    if($mappingType == "content"){
          $this->setIdentifiantRofProperty($graphItem,
            $mappedEntity,
            $exportClassCapdataProperties,
            "content"
          );
    }elseif($mappingType == "taxo"){
          $this->setIdentifiantRofProperty($graphItem,
            $mappedEntity,
            $exportClassCapdataProperties,
            "taxo"
          );
    }
  }

  /**
   * Set Personal info Capdata Properties.
   * 
   * @param mixed $graphItem
   * @param \Drupal\taxonomy\TermInterface|\Drupal\node\NodeInterface $mappedEntity
   * @param array $exportClassCapdataProperties
   * @param string $mappingType
   */
  public function setPersonalDetails($graphItem, $mappedEntity, $exportClassCapdataProperties, $mappingType): void {  
    // Prenom
    $prenom = "";
    if(isset($exportClassCapdataProperties["prenom"])){
      if(!empty($exportClassCapdataProperties["prenom"]["property_".$mappingType."_fields_dropdown"])){
        $prenomFieldName = $exportClassCapdataProperties["prenom"]["property_".$mappingType."_fields_dropdown"];
        if($mappedEntity->hasField($prenomFieldName) && !$mappedEntity->get($prenomFieldName)->isEmpty()){
          $prenom = $mappedEntity->get($prenomFieldName)->value;
          if(!empty($prenom)){
            if(!empty($exportClassCapdataProperties["prenom"]["property_".$mappingType."_custom_processing"])){
              $customProcessing = $exportClassCapdataProperties["prenom"]["property_".$mappingType."_custom_processing"];
              $prenom = $this->customFieldProcessing($prenom, $customProcessing);
            }
            $graphItem->setPrenom($prenom);
          }
        }
      }
    }

    // Nom
    $nom = "";
    if(isset($exportClassCapdataProperties["nom"])){
      if(!empty($exportClassCapdataProperties["nom"]["property_".$mappingType."_fields_dropdown"])){
        $nomFieldName = $exportClassCapdataProperties["nom"]["property_".$mappingType."_fields_dropdown"];
        if($mappedEntity->hasField($nomFieldName) && !$mappedEntity->get($nomFieldName)->isEmpty()){
          $nom = $mappedEntity->get($nomFieldName)->value;
          if(!empty($nom)){
            if(!empty($exportClassCapdataProperties["nom"]["property_".$mappingType."_custom_processing"])){
              $customProcessing = $exportClassCapdataProperties["nom"]["property_".$mappingType."_custom_processing"];
              $nom = $this->customFieldProcessing($nom, $customProcessing);
            }
            $graphItem->setNom($nom);
          }
        }
      }
    }

    // Nom forme rejet
    $nomFormeRejet = "";
    if(isset($exportClassCapdataProperties["nom_forme_rejet"])){
      if(!empty($exportClassCapdataProperties["nom_forme_rejet"]["property_".$mappingType."_fields_dropdown"])){
        $nomFormeRejetFieldName = $exportClassCapdataProperties["nom_forme_rejet"]["property_".$mappingType."_fields_dropdown"];
        if($mappedEntity->hasField($nomFormeRejetFieldName) && !$mappedEntity->get($nomFormeRejetFieldName)->isEmpty()){
          $nomFormeRejet = $mappedEntity->get($nomFormeRejetFieldName)->value;
          if(!empty($nomFormeRejet)){
            if(!empty($exportClassCapdataProperties["nom_forme_rejet"]["property_".$mappingType."_custom_processing"])){
              $customProcessing = $exportClassCapdataProperties["nom_forme_rejet"]["property_".$mappingType."_custom_processing"];
              $nomFormeRejet = $this->customFieldProcessing($nomFormeRejet, $customProcessing);
            }
            $graphItem->setNomFormeRejet($nomFormeRejet);
          }
        }
      }
    }    

    // Biographie
    $biographie = "";
    if(isset($exportClassCapdataProperties["biographie"])){
      if(!empty($exportClassCapdataProperties["biographie"]["property_".$mappingType."_fields_dropdown"])){
        $biographieFieldName = $exportClassCapdataProperties["biographie"]["property_".$mappingType."_fields_dropdown"];
        if($mappedEntity->hasField($biographieFieldName) && !$mappedEntity->get($biographieFieldName)->isEmpty()){
          $biographie = $mappedEntity->get($biographieFieldName)->value;
          if(!empty($biographie)){
            if(!empty($exportClassCapdataProperties["biographie"]["property_".$mappingType."_custom_processing"])){
              $customProcessing = $exportClassCapdataProperties["biographie"]["property_".$mappingType."_custom_processing"];
              $biographie = $this->customFieldProcessing($biographie, $customProcessing);
            }
            $graphItem->setBiographie($biographie);
          }
        }
      }
    }
  }

  /**
   * Set A Pour Fonction property.
   * 
   * @param mixed $graphItem
   * @param array $capDataFonctionMappingInfo
   * @param array $currentCapdataClassMappingInfo
   */
  public function setAPourFonctionCapdataProperty($graphItem, $capDataFonctionMappingInfo, $currentCapdataClassMappingInfo): void {
    $fonctionsArray = null;
    if(!empty($currentCapdataClassMappingInfo)){
      $exportClassCapdataProperties =  $currentCapdataClassMappingInfo["mapped_entity_properties"];
      $mappingType = $currentCapdataClassMappingInfo["mapping_type"];
      $mappedEntity = $currentCapdataClassMappingInfo["mapped_entity"];
      $host = $currentCapdataClassMappingInfo["host"];
      if(!empty($mappedEntity) && !empty($mappingType) && !empty($exportClassCapdataProperties)){
        if(isset($exportClassCapdataProperties["a_pour_fonction"])){
          if(!empty($exportClassCapdataProperties["a_pour_fonction"]["property_".$mappingType."_fields_dropdown"])){
            $aPourFonctionFieldName = $exportClassCapdataProperties["a_pour_fonction"]["property_".$mappingType."_fields_dropdown"];
            if ($mappedEntity->hasField($aPourFonctionFieldName) && !$mappedEntity->get($aPourFonctionFieldName)->isEmpty()) {
              $fonctionItems = $mappedEntity->get($aPourFonctionFieldName);
              foreach ($fonctionItems as $referenceFonction) {
                $fonctionItem = $referenceFonction->entity;
                if(!empty($fonctionItem)){
                  $referenceFonctionId =  $fonctionItem->id();
                  if(!empty($capDataFonctionMappingInfo)){
                    if(!empty($capDataFonctionMappingInfo["capData_fonction_mappingtype"])){
                      $fonctionMappingType = $capDataFonctionMappingInfo["capData_fonction_mappingtype"];
                      $referenceFonctionUrl = "";
                      if($fonctionMappingType == "taxo" && $fonctionItem instanceof \Drupal\taxonomy\TermInterface){
                        $referenceFonctionUrl = $host . "/taxonomy/term/" . $referenceFonctionId;
                      }elseif($fonctionMappingType == "content" && $fonctionItem instanceof \Drupal\node\NodeInterface){
                        $referenceFonctionUrl = $host . "/node/" . $referenceFonctionId;
                      }
                      if(!empty($referenceFonctionUrl)){
                        $fonctionsArray[] = new ExternalThing($referenceFonctionUrl);
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    if (!empty($fonctionsArray)) {
      $graphItem->setAPourFonction($fonctionsArray);
    }
  }

  /**
   * Set A Pour Participant property.
   * 
   * @param mixed $graphItem
   * @param array $capDataPersonneMappingInfo
   * @param array $capDataCollectiviteMappingInfo
   * @param array $currentCapdataClassMappingInfo
   */
  public function setAPourParticipantCapdataProperty($graphItem, $capDataPersonneMappingInfo, $capDataCollectiviteMappingInfo, $currentCapdataClassMappingInfo): void {
    $participantsArray = null;
    if(!empty($currentCapdataClassMappingInfo)){
      $exportClassCapdataProperties =  $currentCapdataClassMappingInfo["mapped_entity_properties"];
      $mappingType = $currentCapdataClassMappingInfo["mapping_type"];
      $mappedEntity = $currentCapdataClassMappingInfo["mapped_entity"];
      $host = $currentCapdataClassMappingInfo["host"];
      if(!empty($mappedEntity) && !empty($mappingType) && !empty($exportClassCapdataProperties)){
        if(isset($exportClassCapdataProperties["a_pour_participant"])){
          if(!empty($exportClassCapdataProperties["a_pour_participant"]["property_".$mappingType."_fields_dropdown"])){
            $aPourParticipantFieldName = $exportClassCapdataProperties["a_pour_participant"]["property_".$mappingType."_fields_dropdown"];
            if ($mappedEntity->hasField($aPourParticipantFieldName) && !$mappedEntity->get($aPourParticipantFieldName)->isEmpty()) {
              $participantItems = $mappedEntity->get($aPourParticipantFieldName);
              foreach ($participantItems as $referenceParticipant) {
                $participantItem = $referenceParticipant->entity;
                if(!empty($participantItem)){
                  $referenceParticipantId =  $participantItem->id();
                  // Le participant est une personne ou une collectivite?
                  if(!empty($capDataPersonneMappingInfo) && !empty($capDataCollectiviteMappingInfo)){
                    if(!empty($capDataPersonneMappingInfo["capData_personne_correspondance"])
                      && $participantItem->bundle() == $capDataPersonneMappingInfo["capData_personne_correspondance"]
                    ){
                      // Le participant est une personne
                      if(!empty($capDataPersonneMappingInfo["capData_personne_mappingtype"])){
                        $personneMappingType = $capDataPersonneMappingInfo["capData_personne_mappingtype"];
                        $referenceParticipantUrl = "";
                        if($personneMappingType == "taxo" && $participantItem instanceof \Drupal\taxonomy\TermInterface){
                          $referenceParticipantUrl = $host . "/taxonomy/term/" . $referenceParticipantId;
                        }elseif($personneMappingType == "content" && $participantItem instanceof \Drupal\node\NodeInterface){
                          $referenceParticipantUrl = $host . "/node/" . $referenceParticipantId;
                        }
                        if(!empty($referenceParticipantUrl)){
                          $participantsArray[] = new ExternalThing($referenceParticipantUrl);
                        }
                      }
                    }elseif(!empty($capDataCollectiviteMappingInfo["capData_collectivite_correspondance"])
                      && $participantItem->bundle() == $capDataCollectiviteMappingInfo["capData_collectivite_correspondance"] 
                    ){
                      // Le participant est une collectivite
                      if(!empty($capDataCollectiviteMappingInfo["capData_collectivite_mappingtype"])){
                        $collectiviteMappingType = $capDataCollectiviteMappingInfo["capData_collectivite_mappingtype"];
                        $referenceParticipantUrl = "";
                        if($collectiviteMappingType == "taxo" && $participantItem instanceof \Drupal\taxonomy\TermInterface){
                          $referenceParticipantUrl = $host . "/taxonomy/term/" . $referenceParticipantId;
                        }elseif($collectiviteMappingType == "content" && $participantItem instanceof \Drupal\node\NodeInterface){
                          $referenceParticipantUrl = $host . "/node/" . $referenceParticipantId;
                        }
                        if(!empty($referenceParticipantUrl)){
                          $participantsArray[] = new ExternalThing($referenceParticipantUrl);
                        }   
                      }                
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    if (!empty($participantsArray)) {
      $graphItem->setAPourParticipant($participantsArray);
    }
  }  

  /**
   * Set A Pour Profession property.
   * 
   * @param mixed $graphItem
   * @param array $capDataFonctionMappingInfo
   * @param array $currentCapdataClassMappingInfo
   */
  public function setAPourProfessionCapdataProperty($graphItem, $capDataFonctionMappingInfo, $currentCapdataClassMappingInfo): void {
    $professionsArray = null;
    if(!empty($currentCapdataClassMappingInfo)){
      $exportClassCapdataProperties =  $currentCapdataClassMappingInfo["mapped_entity_properties"];
      $mappingType = $currentCapdataClassMappingInfo["mapping_type"];
      $mappedEntity = $currentCapdataClassMappingInfo["mapped_entity"];
      $host = $currentCapdataClassMappingInfo["host"];
      if(!empty($mappedEntity) && !empty($mappingType) && !empty($exportClassCapdataProperties)){
        if(isset($exportClassCapdataProperties["a_pour_profession"])){
          if(!empty($exportClassCapdataProperties["a_pour_profession"]["property_".$mappingType."_fields_dropdown"])){
            $aPourProfessionFieldName = $exportClassCapdataProperties["a_pour_profession"]["property_".$mappingType."_fields_dropdown"];
            if ($mappedEntity->hasField($aPourProfessionFieldName) && !$mappedEntity->get($aPourProfessionFieldName)->isEmpty()) {
              $professionItems = $mappedEntity->get($aPourProfessionFieldName);
              foreach ($professionItems as $referenceProfession) {
                $professionItem = $referenceProfession->entity;
                if(!empty($professionItem)){
                  $referenceProfessionId =  $professionItem->id();
                  if(!empty($capDataFonctionMappingInfo)){
                    if(!empty($capDataFonctionMappingInfo["capData_fonction_mappingtype"])){
                      $fonctionMappingType = $capDataFonctionMappingInfo["capData_fonction_mappingtype"];
                      $referenceProfessionUrl = "";
                      if($fonctionMappingType == "taxo" && $professionItem instanceof \Drupal\taxonomy\TermInterface){
                        $referenceProfessionUrl = $host . "/taxonomy/term/" . $referenceProfessionId;
                      }elseif($fonctionMappingType == "content" && $professionItem instanceof \Drupal\node\NodeInterface){
                        $referenceProfessionUrl = $host . "/node/" . $referenceProfessionId;
                      }
                      if(!empty($referenceProfessionUrl)){
                        $professionsArray[] = new ExternalThing($referenceProfessionUrl);
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    if (!empty($professionsArray)) {
      $graphItem->setAPourProfession($professionsArray);
    }
  }

  /**
   * Set Capdata Title property.
   * 
   * @param mixed $graphItem
   * @param \Drupal\taxonomy\TermInterface|\Drupal\node\NodeInterface $mappedEntity
   * @param array $exportClassCapdataProperties
   * @param string $mappingType
   */
  public function setCapdataTitle($graphItem, $mappedEntity, $exportClassCapdataProperties, $mappingType): void {
    $title = "";
    if($mappingType == "content"){
      $title = $mappedEntity->getTitle();
    }else{
      $title = $mappedEntity->label();
    }
    if(isset($exportClassCapdataProperties["titre"])){
      if(!empty($exportClassCapdataProperties["titre"]["property_".$mappingType."_fields_dropdown"])){
        $titleFieldName = $exportClassCapdataProperties["titre"]["property_".$mappingType."_fields_dropdown"];
        if($mappedEntity->hasField($titleFieldName) && !$mappedEntity->get($titleFieldName)->isEmpty()){
          $title = $mappedEntity->get($titleFieldName)->value;
          if(!empty($title)){
            if(!empty($exportClassCapdataProperties["titre"]["property_".$mappingType."_custom_processing"])){
              $customProcessing = $exportClassCapdataProperties["titre"]["property_".$mappingType."_custom_processing"];
              $title = $this->customFieldProcessing($title, $customProcessing);
            }
          }
        }
      }
    }
    if(!empty($title)){
      $graphItem->setTitre($title);
    }
  }

  /**
   * Set A Pour Auteur property.
   * 
   * @param mixed $graphItem
   * @param array $capDataAuteurMappingInfo
   * @param array $capDataParticipationMappingInfo
   * @param array $currentCapdataClassMappingInfo
   */
  public function setAPourAuteurCapdataProperty($graphItem, $capDataAuteurMappingInfo, $capDataParticipationMappingInfo, $currentCapdataClassMappingInfo): void {
    $auteursArray = null;
    if(!empty($currentCapdataClassMappingInfo)){
      $exportClassCapdataProperties =  $currentCapdataClassMappingInfo["mapped_entity_properties"];
      $mappingType = $currentCapdataClassMappingInfo["mapping_type"];
      $mappedEntity = $currentCapdataClassMappingInfo["mapped_entity"];
      $host = $currentCapdataClassMappingInfo["host"];
      if(!empty($mappedEntity) && !empty($mappingType) && !empty($exportClassCapdataProperties)){
        if(isset($exportClassCapdataProperties["a_pour_auteur"])){
          if(!empty($exportClassCapdataProperties["a_pour_auteur"]["property_".$mappingType."_fields_dropdown"])){
            $aPourAuteurFieldName = $exportClassCapdataProperties["a_pour_auteur"]["property_".$mappingType."_fields_dropdown"];
            if ($mappedEntity->hasField($aPourAuteurFieldName) && !$mappedEntity->get($aPourAuteurFieldName)->isEmpty()) {
              $auteurItems = $mappedEntity->get($aPourAuteurFieldName);
              foreach ($auteurItems as $referenceAuteur) {
                $auteurItem = $referenceAuteur->entity;
                if(!empty($auteurItem)){
                  $referenceAuteurId =  $auteurItem->id();
                  // L'auteur est de la classe Auteur ou de la classe Participation?
                  if(!empty($capDataAuteurMappingInfo) && !empty($capDataParticipationMappingInfo)){
                    if(!empty($capDataAuteurMappingInfo["capData_auteur_correspondance"])
                      && $auteurItem->bundle() == $capDataAuteurMappingInfo["capData_auteur_correspondance"]
                    ){
                      // L'auteur est de la classe  Auteur qui herite ses proprietes de la classe Participation
                      if(!empty($capDataAuteurMappingInfo["capData_auteur_mappingtype"])){
                        $auteurMappingType = $capDataAuteurMappingInfo["capData_auteur_mappingtype"];
                        $referenceAuteurUrl = "";
                        if($auteurMappingType == "taxo" && $auteurItem instanceof \Drupal\taxonomy\TermInterface){
                          $referenceAuteurUrl = $host . "/taxonomy/term/" . $referenceAuteurId;
                        }elseif($auteurMappingType == "content" && $auteurItem instanceof \Drupal\node\NodeInterface){
                          $referenceAuteurUrl = $host . "/node/" . $referenceAuteurId;
                        }
                        if(!empty($referenceAuteurUrl)){
                          $auteursArray[] = new ExternalThing($referenceAuteurUrl);
                        }
                      }
                    }elseif(!empty($capDataParticipationMappingInfo["capData_participation_correspondance"])
                      && $auteurItem->bundle() == $capDataParticipationMappingInfo["capData_participation_correspondance"] 
                    ){
                      // L'auteur est de la classe Participation
                      if(!empty($capDataParticipationMappingInfo["capData_participation_mappingtype"])){
                        $participationMappingType = $capDataParticipationMappingInfo["capData_participation_mappingtype"];
                        $referenceAuteurUrl = "";
                        if($participationMappingType == "taxo" && $auteurItem instanceof \Drupal\taxonomy\TermInterface){
                          $referenceAuteurUrl = $host . "/taxonomy/term/" . $referenceAuteurId;
                        }elseif($participationMappingType == "content" && $auteurItem instanceof \Drupal\node\NodeInterface){
                          $referenceAuteurUrl = $host . "/node/" . $referenceAuteurId;
                        }
                        if(!empty($referenceAuteurUrl)){
                          $auteursArray[] = new ExternalThing($referenceAuteurUrl);
                        }   
                      }                
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    if (!empty($auteursArray)) {
      $graphItem->setAPourAuteur($auteursArray);
    }
  }
  
  /**
   * Set A Pour Mention Production property.
   * 
   * @param mixed $graphItem
   * @param array $capDataMentionProductionMappingInfo
   * @param array $capDataParticipationMappingInfo
   * @param array $currentCapdataClassMappingInfo
   */
  public function setAPourMentionProductionCapdataProperty($graphItem, $capDataMentionProductionMappingInfo, $capDataParticipationMappingInfo, $currentCapdataClassMappingInfo): void {
    $mentionsProductionArray = null;
    if(!empty($currentCapdataClassMappingInfo)){
      $exportClassCapdataProperties =  $currentCapdataClassMappingInfo["mapped_entity_properties"];
      $mappingType = $currentCapdataClassMappingInfo["mapping_type"];
      $mappedEntity = $currentCapdataClassMappingInfo["mapped_entity"];
      $host = $currentCapdataClassMappingInfo["host"];
      if(!empty($mappedEntity) && !empty($mappingType) && !empty($exportClassCapdataProperties)){
        if(isset($exportClassCapdataProperties["a_pour_mention_production"])){
          if(!empty($exportClassCapdataProperties["a_pour_mention_production"]["property_".$mappingType."_fields_dropdown"])){
            $aPourMentionProductionFieldName = $exportClassCapdataProperties["a_pour_mention_production"]["property_".$mappingType."_fields_dropdown"];
            if ($mappedEntity->hasField($aPourMentionProductionFieldName) && !$mappedEntity->get($aPourMentionProductionFieldName)->isEmpty()) {
              $mentionProductionItems = $mappedEntity->get($aPourMentionProductionFieldName);
              foreach ($mentionProductionItems as $referenceMentionProduction) {
                $mentionProductionItem = $referenceMentionProduction->entity;
                if(!empty($mentionProductionItem)){
                  $referenceMentionProductionId =  $mentionProductionItem->id();
                  // La mention production est de la classe MentionProduction ou de la classe Participation?
                  if(!empty($capDataMentionProductionMappingInfo) && !empty($capDataParticipationMappingInfo)){
                    if(!empty($capDataMentionProductionMappingInfo["capData_mentionproduction_correspondance"])
                      && $mentionProductionItem->bundle() == $capDataMentionProductionMappingInfo["capData_mentionproduction_correspondance"]
                    ){
                      // La mention production est de la classe  MentionProduction qui herite ses proprietes de la classe Participation
                      if(!empty($capDataMentionProductionMappingInfo["capData_mentionproduction_mappingtype"])){
                        $mentionProductionMappingType = $capDataMentionProductionMappingInfo["capData_mentionproduction_mappingtype"];
                        $referenceMentionProductionUrl = "";
                        if($mentionProductionMappingType == "taxo" && $mentionProductionItem instanceof \Drupal\taxonomy\TermInterface){
                          $referenceMentionProductionUrl = $host . "/taxonomy/term/" . $referenceMentionProductionId;
                        }elseif($mentionProductionMappingType == "content" && $mentionProductionItem instanceof \Drupal\node\NodeInterface){
                          $referenceMentionProductionUrl = $host . "/node/" . $referenceMentionProductionId;
                        }
                        if(!empty($referenceMentionProductionUrl)){
                          $mentionsProductionArray[] = new ExternalThing($referenceMentionProductionUrl);
                        }
                      }
                    }elseif(!empty($capDataParticipationMappingInfo["capData_participation_correspondance"])
                      && $mentionProductionItem->bundle() == $capDataParticipationMappingInfo["capData_participation_correspondance"] 
                    ){
                      //  La mention production est de la classe Participation
                      if(!empty($capDataParticipationMappingInfo["capData_participation_mappingtype"])){
                        $participationMappingType = $capDataParticipationMappingInfo["capData_participation_mappingtype"];
                        $referenceMentionProductionUrl = "";
                        if($participationMappingType == "taxo" && $mentionProductionItem instanceof \Drupal\taxonomy\TermInterface){
                          $referenceMentionProductionUrl = $host . "/taxonomy/term/" . $referenceMentionProductionId;
                        }elseif($participationMappingType == "content" && $mentionProductionItem instanceof \Drupal\node\NodeInterface){
                          $referenceMentionProductionUrl = $host . "/node/" . $referenceMentionProductionId;
                        }
                        if(!empty($referenceMentionProductionUrl)){
                          $mentionsProductionArray[] = new ExternalThing($referenceMentionProductionUrl);
                        }   
                      }                
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    if (!empty($mentionsProductionArray)) {
      $graphItem->setAPourMentionProduction($mentionsProductionArray);
    }
  }  

  /**
   * Set A Pour Partenariat property.
   * 
   * @param mixed $graphItem
   * @param array $capDataPartenariatMappingInfo
   * @param array $capDataParticipationMappingInfo
   * @param array $currentCapdataClassMappingInfo
   */
  public function setAPourPartenariatCapdataProperty($graphItem, $capDataPartenariatMappingInfo, $capDataParticipationMappingInfo, $currentCapdataClassMappingInfo): void {
    $partenariatsArray = null;
    if(!empty($currentCapdataClassMappingInfo)){
      $exportClassCapdataProperties =  $currentCapdataClassMappingInfo["mapped_entity_properties"];
      $mappingType = $currentCapdataClassMappingInfo["mapping_type"];
      $mappedEntity = $currentCapdataClassMappingInfo["mapped_entity"];
      $host = $currentCapdataClassMappingInfo["host"];
      if(!empty($mappedEntity) && !empty($mappingType) && !empty($exportClassCapdataProperties)){
        if(isset($exportClassCapdataProperties["a_pour_partenariat"])){
          if(!empty($exportClassCapdataProperties["a_pour_partenariat"]["property_".$mappingType."_fields_dropdown"])){
            $aPourPartenariatFieldName = $exportClassCapdataProperties["a_pour_partenariat"]["property_".$mappingType."_fields_dropdown"];
            if ($mappedEntity->hasField($aPourPartenariatFieldName) && !$mappedEntity->get($aPourPartenariatFieldName)->isEmpty()) {
              $partenariatItems = $mappedEntity->get($aPourPartenariatFieldName);
              foreach ($partenariatItems as $referencePartenariat) {
                $partenariatItem = $referencePartenariat->entity;
                if(!empty($partenariatItem)){
                  $referencePartenariatId =  $partenariatItem->id();
                  if(!empty($capDataPartenariatMappingInfo) && !empty($capDataParticipationMappingInfo)){
                    if(!empty($capDataPartenariatMappingInfo["capData_partenariat_correspondance"])
                      && $partenariatItem->bundle() == $capDataPartenariatMappingInfo["capData_partenariat_correspondance"]
                    ){
                      if(!empty($capDataPartenariatMappingInfo["capData_partenariat_mappingtype"])){
                        $partenariatMappingType = $capDataPartenariatMappingInfo["capData_partenariat_mappingtype"];
                        $referencePartenariatUrl = "";
                        if($partenariatMappingType == "taxo" && $partenariatItem instanceof \Drupal\taxonomy\TermInterface){
                          $referencePartenariatUrl = $host . "/taxonomy/term/" . $referencePartenariatId;
                        }elseif($partenariatMappingType == "content" && $partenariatItem instanceof \Drupal\node\NodeInterface){
                          $referencePartenariatUrl = $host . "/node/" . $referencePartenariatId;
                        }
                        if(!empty($referencePartenariatUrl)){
                          $partenariatsArray[] = new ExternalThing($referencePartenariatUrl);
                        }
                      }
                    }elseif(!empty($capDataParticipationMappingInfo["capData_participation_correspondance"])
                      && $partenariatItem->bundle() == $capDataParticipationMappingInfo["capData_participation_correspondance"] 
                    ){
                      if(!empty($capDataParticipationMappingInfo["capData_participation_mappingtype"])){
                        $participationMappingType = $capDataParticipationMappingInfo["capData_participation_mappingtype"];
                        $referencePartenariatUrl = "";
                        if($participationMappingType == "taxo" && $partenariatItem instanceof \Drupal\taxonomy\TermInterface){
                          $referencePartenariatUrl = $host . "/taxonomy/term/" . $referencePartenariatId;
                        }elseif($participationMappingType == "content" && $partenariatItem instanceof \Drupal\node\NodeInterface){
                          $referencePartenariatUrl = $host . "/node/" . $referencePartenariatId;
                        }
                        if(!empty($referencePartenariatUrl)){
                          $partenariatsArray[] = new ExternalThing($referencePartenariatUrl);
                        }   
                      }                
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    if (!empty($partenariatsArray)) {
      $graphItem->setAPourPartenariat($partenariatsArray);
    }
  }

  /**
   * Set A Pour Collaboration property.
   * 
   * @param mixed $graphItem
   * @param array $capDataCollaborationMappingInfo
   * @param array $capDataParticipationMappingInfo
   * @param array $currentCapdataClassMappingInfo
   */
  public function setAPourCollaborationCapdataProperty($graphItem, $capDataCollaborationMappingInfo, $capDataParticipationMappingInfo, $currentCapdataClassMappingInfo): void {
    $collaborationsArray = null;
    if(!empty($currentCapdataClassMappingInfo)){
      $exportClassCapdataProperties =  $currentCapdataClassMappingInfo["mapped_entity_properties"];
      $mappingType = $currentCapdataClassMappingInfo["mapping_type"];
      $mappedEntity = $currentCapdataClassMappingInfo["mapped_entity"];
      $host = $currentCapdataClassMappingInfo["host"];
      if(!empty($mappedEntity) && !empty($mappingType) && !empty($exportClassCapdataProperties)){
        if(isset($exportClassCapdataProperties["a_pour_collaboration"])){
          if(!empty($exportClassCapdataProperties["a_pour_collaboration"]["property_".$mappingType."_fields_dropdown"])){
            $aPourCollaborationFieldName = $exportClassCapdataProperties["a_pour_collaboration"]["property_".$mappingType."_fields_dropdown"];
            if ($mappedEntity->hasField($aPourCollaborationFieldName) && !$mappedEntity->get($aPourCollaborationFieldName)->isEmpty()) {
              $collaborationItems = $mappedEntity->get($aPourCollaborationFieldName);
              foreach ($collaborationItems as $referenceCollaboration) {
                $collaborationItem = $referenceCollaboration->entity;
                if(!empty($collaborationItem)){
                  $referenceCollaborationId =  $collaborationItem->id();
                  if(!empty($capDataCollaborationMappingInfo) && !empty($capDataParticipationMappingInfo)){
                    if(!empty($capDataCollaborationMappingInfo["capData_collaboration_correspondance"])
                      && $collaborationItem->bundle() == $capDataCollaborationMappingInfo["capData_collaboration_correspondance"]
                    ){
                      if(!empty($capDataCollaborationMappingInfo["capData_collaboration_mappingtype"])){
                        $collaborationMappingType = $capDataCollaborationMappingInfo["capData_collaboration_mappingtype"];
                        $referenceCollaborationUrl = "";
                        if($collaborationMappingType == "taxo" && $collaborationItem instanceof \Drupal\taxonomy\TermInterface){
                          $referenceCollaborationUrl = $host . "/taxonomy/term/" . $referenceCollaborationId;
                        }elseif($collaborationMappingType == "content" && $collaborationItem instanceof \Drupal\node\NodeInterface){
                          $referenceCollaborationUrl = $host . "/node/" . $referenceCollaborationId;
                        }
                        if(!empty($referenceCollaborationUrl)){
                          $collaborationsArray[] = new ExternalThing($referenceCollaborationUrl);
                        }
                      }
                    }elseif(!empty($capDataParticipationMappingInfo["capData_participation_correspondance"])
                      && $collaborationItem->bundle() == $capDataParticipationMappingInfo["capData_participation_correspondance"] 
                    ){
                      if(!empty($capDataParticipationMappingInfo["capData_participation_mappingtype"])){
                        $participationMappingType = $capDataParticipationMappingInfo["capData_participation_mappingtype"];
                        $referenceCollaborationUrl = "";
                        if($participationMappingType == "taxo" && $collaborationItem instanceof \Drupal\taxonomy\TermInterface){
                          $referenceCollaborationUrl = $host . "/taxonomy/term/" . $referenceCollaborationId;
                        }elseif($participationMappingType == "content" && $collaborationItem instanceof \Drupal\node\NodeInterface){
                          $referenceCollaborationUrl = $host . "/node/" . $referenceCollaborationId;
                        }
                        if(!empty($referenceCollaborationUrl)){
                          $collaborationsArray[] = new ExternalThing($referenceCollaborationUrl);
                        }   
                      }                
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    if (!empty($collaborationsArray)) {
      $graphItem->setAPourCollaboration($collaborationsArray);
    }
  }  

  /**
   * Set A Pour Interpretation property.
   * 
   * @param mixed $graphItem
   * @param array $capDataInterpretationMappingInfo
   * @param array $capDataParticipationMappingInfo
   * @param array $currentCapdataClassMappingInfo
   */
  public function setAPourInterpretationCapdataProperty($graphItem, $capDataInterpretationMappingInfo, $capDataParticipationMappingInfo, $currentCapdataClassMappingInfo): void {
    $interpretationsArray = null;
    if(!empty($currentCapdataClassMappingInfo)){
      $exportClassCapdataProperties =  $currentCapdataClassMappingInfo["mapped_entity_properties"];
      $mappingType = $currentCapdataClassMappingInfo["mapping_type"];
      $mappedEntity = $currentCapdataClassMappingInfo["mapped_entity"];
      $host = $currentCapdataClassMappingInfo["host"];
      if(!empty($mappedEntity) && !empty($mappingType) && !empty($exportClassCapdataProperties)){
        if(isset($exportClassCapdataProperties["a_pour_interpretation"])){
          if(!empty($exportClassCapdataProperties["a_pour_interpretation"]["property_".$mappingType."_fields_dropdown"])){
            $aPourInterpretationFieldName = $exportClassCapdataProperties["a_pour_interpretation"]["property_".$mappingType."_fields_dropdown"];
            if ($mappedEntity->hasField($aPourInterpretationFieldName) && !$mappedEntity->get($aPourInterpretationFieldName)->isEmpty()) {
              $interpretationItems = $mappedEntity->get($aPourInterpretationFieldName);
              foreach ($interpretationItems as $referenceIntepretation) {
                $interpretationItem = $referenceIntepretation->entity;
                if(!empty($interpretationItem)){
                  $referenceInterpretationId =  $interpretationItem->id();
                  if(!empty($capDataInterpretationMappingInfo) && !empty($capDataParticipationMappingInfo)){
                    if(!empty($capDataInterpretationMappingInfo["capData_interpretation_correspondance"])
                      && $interpretationItem->bundle() == $capDataInterpretationMappingInfo["capData_interpretation_correspondance"]
                    ){
                      if(!empty($capDataInterpretationMappingInfo["capData_interpretation_mappingtype"])){
                        $interpretationMappingType = $capDataInterpretationMappingInfo["capData_interpretation_mappingtype"];
                        $referenceInterpretationUrl = "";
                        if($interpretationMappingType == "taxo" && $interpretationItem instanceof \Drupal\taxonomy\TermInterface){
                          $referenceInterpretationUrl = $host . "/taxonomy/term/" . $referenceInterpretationId;
                        }elseif($interpretationMappingType == "content" && $interpretationItem instanceof \Drupal\node\NodeInterface){
                          $referenceInterpretationUrl = $host . "/node/" . $referenceInterpretationId;
                        }
                        if(!empty($referenceInterpretationUrl)){
                          $interpretationsArray[] = new ExternalThing($referenceInterpretationUrl);
                        }
                      }
                    }elseif(!empty($capDataParticipationMappingInfo["capData_participation_correspondance"])
                      && $interpretationItem->bundle() == $capDataParticipationMappingInfo["capData_participation_correspondance"] 
                    ){
                      if(!empty($capDataParticipationMappingInfo["capData_participation_mappingtype"])){
                        $participationMappingType = $capDataParticipationMappingInfo["capData_participation_mappingtype"];
                        $referenceInterpretationUrl = "";
                        if($participationMappingType == "taxo" && $interpretationItem instanceof \Drupal\taxonomy\TermInterface){
                          $referenceInterpretationUrl = $host . "/taxonomy/term/" . $referenceInterpretationId;
                        }elseif($participationMappingType == "content" && $interpretationItem instanceof \Drupal\node\NodeInterface){
                          $referenceInterpretationUrl = $host . "/node/" . $referenceInterpretationId;
                        }
                        if(!empty($referenceInterpretationUrl)){
                          $interpretationsArray[] = new ExternalThing($referenceInterpretationUrl);
                        }   
                      }                
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    if (!empty($interpretationsArray)) {
      $graphItem->setAPourInterpretation($interpretationsArray);
    }
  }

  /**
   * Set A Pour Maitrise Oeuvre property.
   * 
   * @param mixed $graphItem
   * @param array $capDataMaitriseOeuvreMappingInfo
   * @param array $capDataParticipationMappingInfo
   * @param array $currentCapdataClassMappingInfo
   */
  public function setAPourMaitriseOeuvreCapdataProperty($graphItem, $capDataMaitriseOeuvreMappingInfo, $capDataParticipationMappingInfo, $currentCapdataClassMappingInfo): void {
    $maitriseOeuvresArray = null;
    if(!empty($currentCapdataClassMappingInfo)){
      $exportClassCapdataProperties =  $currentCapdataClassMappingInfo["mapped_entity_properties"];
      $mappingType = $currentCapdataClassMappingInfo["mapping_type"];
      $mappedEntity = $currentCapdataClassMappingInfo["mapped_entity"];
      $host = $currentCapdataClassMappingInfo["host"];
      if(!empty($mappedEntity) && !empty($mappingType) && !empty($exportClassCapdataProperties)){
        if(isset($exportClassCapdataProperties["a_pour_maitrise_oeuvre"])){
          if(!empty($exportClassCapdataProperties["a_pour_maitrise_oeuvre"]["property_".$mappingType."_fields_dropdown"])){
            $aPourMaitriseOeuvreFieldName = $exportClassCapdataProperties["a_pour_maitrise_oeuvre"]["property_".$mappingType."_fields_dropdown"];
            if ($mappedEntity->hasField($aPourMaitriseOeuvreFieldName) && !$mappedEntity->get($aPourMaitriseOeuvreFieldName)->isEmpty()) {
              $maitriseOeuvreItems = $mappedEntity->get($aPourMaitriseOeuvreFieldName);
              foreach ($maitriseOeuvreItems as $referenceMaitriseOeuvre) {
                $maitriseOeuvreItem = $referenceMaitriseOeuvre->entity;
                if(!empty($maitriseOeuvreItem)){
                  $referenceMaitriseOeuvreId =  $maitriseOeuvreItem->id();
                  if(!empty($capDataMaitriseOeuvreMappingInfo) && !empty($capDataParticipationMappingInfo)){
                    if(!empty($capDataMaitriseOeuvreMappingInfo["capData_maitriseoeuvre_correspondance"])
                      && $maitriseOeuvreItem->bundle() == $capDataMaitriseOeuvreMappingInfo["capData_maitriseoeuvre_correspondance"]
                    ){
                      if(!empty($capDataMaitriseOeuvreMappingInfo["capData_maitriseoeuvre_mappingtype"])){
                        $maitriseOeuvreMappingType = $capDataMaitriseOeuvreMappingInfo["capData_maitriseoeuvre_mappingtype"];
                        $referenceMaitriseOeuvreUrl = "";
                        if($maitriseOeuvreMappingType == "taxo" && $maitriseOeuvreItem instanceof \Drupal\taxonomy\TermInterface){
                          $referenceMaitriseOeuvreUrl = $host . "/taxonomy/term/" . $referenceMaitriseOeuvreId;
                        }elseif($maitriseOeuvreMappingType == "content" && $maitriseOeuvreItem instanceof \Drupal\node\NodeInterface){
                          $referenceMaitriseOeuvreUrl = $host . "/node/" . $referenceMaitriseOeuvreId;
                        }
                        if(!empty($referenceMaitriseOeuvreUrl)){
                          $maitriseOeuvresArray[] = new ExternalThing($referenceMaitriseOeuvreUrl);
                        }
                      }
                    }elseif(!empty($capDataParticipationMappingInfo["capData_participation_correspondance"])
                      && $maitriseOeuvreItem->bundle() == $capDataParticipationMappingInfo["capData_participation_correspondance"] 
                    ){
                      if(!empty($capDataParticipationMappingInfo["capData_participation_mappingtype"])){
                        $participationMappingType = $capDataParticipationMappingInfo["capData_participation_mappingtype"];
                        $referenceMaitriseOeuvreUrl = "";
                        if($participationMappingType == "taxo" && $maitriseOeuvreItem instanceof \Drupal\taxonomy\TermInterface){
                          $referenceMaitriseOeuvreUrl = $host . "/taxonomy/term/" . $referenceMaitriseOeuvreId;
                        }elseif($participationMappingType == "content" && $maitriseOeuvreItem instanceof \Drupal\node\NodeInterface){
                          $referenceMaitriseOeuvreUrl = $host . "/node/" . $referenceMaitriseOeuvreId;
                        }
                        if(!empty($referenceMaitriseOeuvreUrl)){
                          $maitriseOeuvresArray[] = new ExternalThing($referenceMaitriseOeuvreUrl);
                        }   
                      }                
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    if (!empty($maitriseOeuvresArray)) {
      $graphItem->setAPourMaitriseOeuvre($maitriseOeuvresArray);
    }
  }
  
  /**
   * Set A Pour Programmation property.
   * 
   * @param mixed $graphItem
   * @param array $capDataProgrammationMappingInfo
   * @param array $capDataParticipationMappingInfo
   * @param array $currentCapdataClassMappingInfo
   */
  public function setAPourProgrammationCapdataProperty($graphItem, $capDataProgrammationMappingInfo, $capDataParticipationMappingInfo, $currentCapdataClassMappingInfo): void {
    $programmationsArray = null;
    if(!empty($currentCapdataClassMappingInfo)){
      $exportClassCapdataProperties =  $currentCapdataClassMappingInfo["mapped_entity_properties"];
      $mappingType = $currentCapdataClassMappingInfo["mapping_type"];
      $mappedEntity = $currentCapdataClassMappingInfo["mapped_entity"];
      $host = $currentCapdataClassMappingInfo["host"];
      if(!empty($mappedEntity) && !empty($mappingType) && !empty($exportClassCapdataProperties)){
        if(isset($exportClassCapdataProperties["a_pour_programmation"])){
          if(!empty($exportClassCapdataProperties["a_pour_programmation"]["property_".$mappingType."_fields_dropdown"])){
            $aPourProgrammationFieldName = $exportClassCapdataProperties["a_pour_programmation"]["property_".$mappingType."_fields_dropdown"];
            if ($mappedEntity->hasField($aPourProgrammationFieldName) && !$mappedEntity->get($aPourProgrammationFieldName)->isEmpty()) {
              $programmationItems = $mappedEntity->get($aPourProgrammationFieldName);
              foreach ($programmationItems as $referenceProgrammation) {
                $programmationItem = $referenceProgrammation->entity;
                if(!empty($programmationItem)){
                  $referenceProgrammationId =  $programmationItem->id();
                  if(!empty($capDataProgrammationMappingInfo) && !empty($capDataParticipationMappingInfo)){
                    if(!empty($capDataProgrammationMappingInfo["capData_programmation_correspondance"])
                      && $programmationItem->bundle() == $capDataProgrammationMappingInfo["capData_programmation_correspondance"]
                    ){
                      if(!empty($capDataProgrammationMappingInfo["capData_programmation_mappingtype"])){
                        $programmationMappingType = $capDataProgrammationMappingInfo["capData_programmation_mappingtype"];
                        $referenceProgrammationUrl = "";
                        if($programmationMappingType == "taxo" && $programmationItem instanceof \Drupal\taxonomy\TermInterface){
                          $referenceProgrammationUrl = $host . "/taxonomy/term/" . $referenceProgrammationId;
                        }elseif($programmationMappingType == "content" && $programmationItem instanceof \Drupal\node\NodeInterface){
                          $referenceProgrammationUrl = $host . "/node/" . $referenceProgrammationId;
                        }
                        if(!empty($referenceProgrammationUrl)){
                          $programmationsArray[] = new ExternalThing($referenceProgrammationUrl);
                        }
                      }
                    }elseif(!empty($capDataParticipationMappingInfo["capData_participation_correspondance"])
                      && $programmationItem->bundle() == $capDataParticipationMappingInfo["capData_participation_correspondance"] 
                    ){
                      if(!empty($capDataParticipationMappingInfo["capData_participation_mappingtype"])){
                        $participationMappingType = $capDataParticipationMappingInfo["capData_participation_mappingtype"];
                        $referenceProgrammationUrl = "";
                        if($participationMappingType == "taxo" && $programmationItem instanceof \Drupal\taxonomy\TermInterface){
                          $referenceProgrammationUrl = $host . "/taxonomy/term/" . $referenceProgrammationId;
                        }elseif($participationMappingType == "content" && $programmationItem instanceof \Drupal\node\NodeInterface){
                          $referenceProgrammationUrl = $host . "/node/" . $referenceProgrammationId;
                        }
                        if(!empty($referenceProgrammationUrl)){
                          $programmationsArray[] = new ExternalThing($referenceProgrammationUrl);
                        }   
                      }                
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    if (!empty($programmationsArray)) {
      $graphItem->setAPourProgrammation($programmationsArray);
    }
  }

  /**
   * Set A Pour Participation property.
   * 
   * @param mixed $graphItem
   * @param array $capDataParticipationMappingInfo
   * @param array $currentCapdataClassMappingInfo
   */
  public function setAPourParticipationCapdataProperty($graphItem, $capDataParticipationMappingInfo, $currentCapdataClassMappingInfo): void {
    $participationsArray = null;
    if(!empty($currentCapdataClassMappingInfo)){
      $exportClassCapdataProperties =  $currentCapdataClassMappingInfo["mapped_entity_properties"];
      $mappingType = $currentCapdataClassMappingInfo["mapping_type"];
      $mappedEntity = $currentCapdataClassMappingInfo["mapped_entity"];
      $host = $currentCapdataClassMappingInfo["host"];
      if(!empty($mappedEntity) && !empty($mappingType) && !empty($exportClassCapdataProperties)){
        if(isset($exportClassCapdataProperties["a_pour_participation"])){
          if(!empty($exportClassCapdataProperties["a_pour_participation"]["property_".$mappingType."_fields_dropdown"])){
            $aPourParticipationFieldName = $exportClassCapdataProperties["a_pour_participation"]["property_".$mappingType."_fields_dropdown"];
            if ($mappedEntity->hasField($aPourParticipationFieldName) && !$mappedEntity->get($aPourParticipationFieldName)->isEmpty()) {
              $participationItems = $mappedEntity->get($aPourParticipationFieldName);
              foreach ($participationItems as $referenceParticipation) {
                $participationItem = $referenceParticipation->entity;
                if(!empty($participationItem)){
                  $referenceParticipationId =  $participationItem->id();
                  if(!empty($capDataParticipationMappingInfo)){
                    if(!empty($capDataParticipationMappingInfo["capData_participation_mappingtype"])){
                      $participationMappingType = $capDataParticipationMappingInfo["capData_participation_mappingtype"];
                      $referenceParticipationUrl = "";
                      if($participationMappingType == "taxo" && $participationItem instanceof \Drupal\taxonomy\TermInterface){
                        $referenceParticipationUrl = $host . "/taxonomy/term/" . $referenceParticipationId;
                      }elseif($participationMappingType == "content" && $participationItem instanceof \Drupal\node\NodeInterface){
                        $referenceParticipationUrl = $host . "/node/" . $referenceParticipationId;
                      }
                      if(!empty($referenceParticipationUrl)){
                        $participationsArray[] = new ExternalThing($referenceParticipationUrl);
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    if (!empty($participationsArray)) {
      $graphItem->setAPourParticipation($participationsArray);
    }
  }

  /**
   * Set A Pour Saison property.
   * 
   * @param mixed $graphItem
   * @param array $capDataSaisonMappingInfo
   * @param array $currentCapdataClassMappingInfo
   */
  public function setAPourSaisonCapdataProperty($graphItem, $capDataSaisonMappingInfo, $currentCapdataClassMappingInfo): void {
    $saisonsArray = null;
    if(!empty($currentCapdataClassMappingInfo)){
      $exportClassCapdataProperties =  $currentCapdataClassMappingInfo["mapped_entity_properties"];
      $mappingType = $currentCapdataClassMappingInfo["mapping_type"];
      $mappedEntity = $currentCapdataClassMappingInfo["mapped_entity"];
      $host = $currentCapdataClassMappingInfo["host"];
      if(!empty($mappedEntity) && !empty($mappingType) && !empty($exportClassCapdataProperties)){
        if(isset($exportClassCapdataProperties["a_pour_saison"])){
          if(!empty($exportClassCapdataProperties["a_pour_saison"]["property_".$mappingType."_fields_dropdown"])){
            $aPourSaisonFieldName = $exportClassCapdataProperties["a_pour_saison"]["property_".$mappingType."_fields_dropdown"];
            if ($mappedEntity->hasField($aPourSaisonFieldName) && !$mappedEntity->get($aPourSaisonFieldName)->isEmpty()) {
              $saisonItems = $mappedEntity->get($aPourSaisonFieldName);
              foreach ($saisonItems as $referenceSaison) {
                $saisonItem = $referenceSaison->entity;
                if(!empty($saisonItem)){
                  $referenceSaisonId =  $saisonItem->id();
                  if(!empty($capDataSaisonMappingInfo)){
                    if(!empty($capDataSaisonMappingInfo["capData_saison_mappingtype"])){
                      $saisonMappingType = $capDataSaisonMappingInfo["capData_saison_mappingtype"];
                      $referenceSaisonUrl = "";
                      if($saisonMappingType == "taxo" && $saisonItem instanceof \Drupal\taxonomy\TermInterface){
                        $referenceSaisonUrl = $host . "/taxonomy/term/" . $referenceSaisonId;
                      }elseif($saisonMappingType == "content" && $saisonItem instanceof \Drupal\node\NodeInterface){
                        $referenceSaisonUrl = $host . "/node/" . $referenceSaisonId;
                      }
                      if(!empty($referenceSaisonUrl)){
                        $saisonsArray[] = new ExternalThing($referenceSaisonUrl);
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    if (!empty($saisonsArray)) {
      $graphItem->setAPourSaison($saisonsArray);
    }
  }

  /**
   * Set A Pour Type Production property.
   * 
   * @param mixed $graphItem
   * @param array $capDataTypeProductionMappingInfo
   * @param array $currentCapdataClassMappingInfo
   */
  public function setAPourTypeProductionCapdataProperty($graphItem, $capDataTypeProductionMappingInfo, $currentCapdataClassMappingInfo): void {
    $typesProductionArray = null;
    if(!empty($currentCapdataClassMappingInfo)){
      $exportClassCapdataProperties =  $currentCapdataClassMappingInfo["mapped_entity_properties"];
      $mappingType = $currentCapdataClassMappingInfo["mapping_type"];
      $mappedEntity = $currentCapdataClassMappingInfo["mapped_entity"];
      $host = $currentCapdataClassMappingInfo["host"];
      if(!empty($mappedEntity) && !empty($mappingType) && !empty($exportClassCapdataProperties)){
        if(isset($exportClassCapdataProperties["a_pour_type_production"])){
          if(!empty($exportClassCapdataProperties["a_pour_type_production"]["property_".$mappingType."_fields_dropdown"])){
            $aPourTypeProductionFieldName = $exportClassCapdataProperties["a_pour_type_production"]["property_".$mappingType."_fields_dropdown"];
            if ($mappedEntity->hasField($aPourTypeProductionFieldName) && !$mappedEntity->get($aPourTypeProductionFieldName)->isEmpty()) {
              $typeProductionItems = $mappedEntity->get($aPourTypeProductionFieldName);
              foreach ($typeProductionItems as $referenceTypeProduction) {
                $typeProductionItem = $referenceTypeProduction->entity;
                if(!empty($typeProductionItem)){
                  $referenceTypeProductionId =  $typeProductionItem->id();
                  if(!empty($capDataTypeProductionMappingInfo)){
                    if(!empty($capDataTypeProductionMappingInfo["capData_typeproduction_mappingtype"])){
                      $typeProductionMappingType = $capDataTypeProductionMappingInfo["capData_typeproduction_mappingtype"];
                      $referenceTypeProductionUrl = "";
                      if($typeProductionMappingType == "taxo" && $typeProductionItem instanceof \Drupal\taxonomy\TermInterface){
                        $referenceTypeProductionUrl = $host . "/taxonomy/term/" . $referenceTypeProductionId;
                      }elseif($typeProductionMappingType == "content" && $typeProductionItem instanceof \Drupal\node\NodeInterface){
                        $referenceTypeProductionUrl = $host . "/node/" . $referenceTypeProductionId;
                      }
                      if(!empty($referenceTypeProductionUrl)){
                        $typesProductionArray[] = new ExternalThing($referenceTypeProductionUrl);
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    if (!empty($typesProductionArray)) {
      $graphItem->setAPourTypeProduction($typesProductionArray);
    }
  }

  /**
   * Set A Pour Type Public property.
   * 
   * @param mixed $graphItem
   * @param array $capDataTypePublicMappingInfo
   * @param array $currentCapdataClassMappingInfo
   */
  public function setAPourTypePublicCapdataProperty($graphItem, $capDataTypePublicMappingInfo, $currentCapdataClassMappingInfo): void {
    $typesPublicArray = null;
    if(!empty($currentCapdataClassMappingInfo)){
      $exportClassCapdataProperties =  $currentCapdataClassMappingInfo["mapped_entity_properties"];
      $mappingType = $currentCapdataClassMappingInfo["mapping_type"];
      $mappedEntity = $currentCapdataClassMappingInfo["mapped_entity"];
      $host = $currentCapdataClassMappingInfo["host"];
      if(!empty($mappedEntity) && !empty($mappingType) && !empty($exportClassCapdataProperties)){
        if(isset($exportClassCapdataProperties["a_pour_type_public"])){
          if(!empty($exportClassCapdataProperties["a_pour_type_public"]["property_".$mappingType."_fields_dropdown"])){
            $aPourTypePublicFieldName = $exportClassCapdataProperties["a_pour_type_public"]["property_".$mappingType."_fields_dropdown"];
            if ($mappedEntity->hasField($aPourTypePublicFieldName) && !$mappedEntity->get($aPourTypePublicFieldName)->isEmpty()) {
              $typePublicItems = $mappedEntity->get($aPourTypePublicFieldName);
              foreach ($typePublicItems as $referenceTypePublic) {
                $typePublicItem = $referenceTypePublic->entity;
                if(!empty($typePublicItem)){
                  $referenceTypePublicId =  $typePublicItem->id();
                  if(!empty($capDataTypePublicMappingInfo)){
                    if(!empty($capDataTypePublicMappingInfo["capData_typepublic_mappingtype"])){
                      $typePublicMappingType = $capDataTypePublicMappingInfo["capData_typepublic_mappingtype"];
                      $referenceTypePublicUrl = "";
                      if($typePublicMappingType == "taxo" && $typePublicItem instanceof \Drupal\taxonomy\TermInterface){
                        $referenceTypePublicUrl = $host . "/taxonomy/term/" . $referenceTypePublicId;
                      }elseif($typePublicMappingType == "content" && $typePublicItem instanceof \Drupal\node\NodeInterface){
                        $referenceTypePublicUrl = $host . "/node/" . $referenceTypePublicId;
                      }
                      if(!empty($referenceTypePublicUrl)){
                        $typesPublicArray[] = new ExternalThing($referenceTypePublicUrl);
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    if (!empty($typesPublicArray)) {
      $graphItem->setAPourTypePublic($typesPublicArray);
    }
  }

  /**
   * Set Historique Capdata property.
   * 
   * @param mixed $graphItem
   * @param array $capDataHistoriqueProductionMappingInfo
   * @param array $currentCapdataClassMappingInfo
   */
  public function setHistoriqueCapdataProperty($graphItem, $capDataHistoriqueProductionMappingInfo, $currentCapdataClassMappingInfo): void {
    $historiquesArray = null;
    if(!empty($currentCapdataClassMappingInfo)){
      $exportClassCapdataProperties =  $currentCapdataClassMappingInfo["mapped_entity_properties"];
      $mappingType = $currentCapdataClassMappingInfo["mapping_type"];
      $mappedEntity = $currentCapdataClassMappingInfo["mapped_entity"];
      $host = $currentCapdataClassMappingInfo["host"];
      if(!empty($mappedEntity) && !empty($mappingType) && !empty($exportClassCapdataProperties)){
        if(isset($exportClassCapdataProperties["historique"])){
          if(!empty($exportClassCapdataProperties["historique"]["property_".$mappingType."_fields_dropdown"])){
            $historiqueFieldName = $exportClassCapdataProperties["historique"]["property_".$mappingType."_fields_dropdown"];
            if ($mappedEntity->hasField($historiqueFieldName) && !$mappedEntity->get($historiqueFieldName)->isEmpty()) {
              $historiqueItems = $mappedEntity->get($historiqueFieldName);
              foreach ($historiqueItems as $referenceHistorique) {
                $historiqueItem = $referenceHistorique->entity;
                if(!empty($historiqueItem)){
                  $referenceHistoriqueId =  $historiqueItem->id();
                  if(!empty($capDataHistoriqueProductionMappingInfo)){
                    if(!empty($capDataHistoriqueProductionMappingInfo["capData_historiqueproduction_mappingtype"])){
                      $historiqueMappingType = $capDataHistoriqueProductionMappingInfo["capData_historiqueproduction_mappingtype"];
                      $referenceHistoriqueUrl = "";
                      if($historiqueMappingType == "taxo" && $historiqueItem instanceof \Drupal\taxonomy\TermInterface){
                        $referenceHistoriqueUrl = $host . "/taxonomy/term/" . $referenceHistoriqueId;
                      }elseif($historiqueMappingType == "content" && $historiqueItem instanceof \Drupal\node\NodeInterface){
                        $referenceHistoriqueUrl = $host . "/node/" . $referenceHistoriqueId;
                      }
                      if(!empty($referenceHistoriqueUrl)){
                        $historiquesArray[] = new ExternalThing($referenceHistoriqueUrl);
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    if (!empty($historiquesArray)) {
      $graphItem->setHistorique($historiquesArray);
    }
  }

  /**
   * Set Lieu Publication property.
   * 
   * @param mixed $graphItem
   * @param array $capDataLieuPublicationMappingInfo
   * @param array $currentCapdataClassMappingInfo
   */
  public function setLieuPublicationCapdataProperty($graphItem, $capDataLieuPublicationMappingInfo, $currentCapdataClassMappingInfo): void {
    $lieuPublicationArray = null;
    if(!empty($currentCapdataClassMappingInfo)){
      $exportClassCapdataProperties =  $currentCapdataClassMappingInfo["mapped_entity_properties"];
      $mappingType = $currentCapdataClassMappingInfo["mapping_type"];
      $mappedEntity = $currentCapdataClassMappingInfo["mapped_entity"];
      $host = $currentCapdataClassMappingInfo["host"];
      if(!empty($mappedEntity) && !empty($mappingType) && !empty($exportClassCapdataProperties)){
        if(isset($exportClassCapdataProperties["lieu_publication"])){
          if(!empty($exportClassCapdataProperties["lieu_publication"]["property_".$mappingType."_fields_dropdown"])){
            $lieuPublicationFieldName = $exportClassCapdataProperties["lieu_publication"]["property_".$mappingType."_fields_dropdown"];
            if ($mappedEntity->hasField($lieuPublicationFieldName) && !$mappedEntity->get($lieuPublicationFieldName)->isEmpty()) {
              $lieuPublicationItems = $mappedEntity->get($lieuPublicationFieldName);
              foreach ($lieuPublicationItems as $referenceLieuPublication) {
                $lieuPublicationItem = $referenceLieuPublication->entity;
                if(!empty($lieuPublicationItem)){
                  $referenceLieuPublicationId =  $lieuPublicationItem->id();
                  if(!empty($capDataLieuPublicationMappingInfo)){
                    if(!empty($capDataLieuPublicationMappingInfo["capData_lieu_mappingtype"])){
                      $lieuPublicationMappingType = $capDataLieuPublicationMappingInfo["capData_lieu_mappingtype"];
                      $referenceLieuPublicationUrl = "";
                      if($lieuPublicationMappingType == "taxo" && $lieuPublicationItem instanceof \Drupal\taxonomy\TermInterface){
                        $referenceLieuPublicationUrl = $host . "/taxonomy/term/" . $referenceLieuPublicationId;
                      }elseif($lieuPublicationMappingType == "content" && $lieuPublicationItem instanceof \Drupal\node\NodeInterface){
                        $referenceLieuPublicationUrl = $host . "/node/" . $referenceLieuPublicationId;
                      }
                      if(!empty($referenceLieuPublicationUrl)){
                        $lieuPublicationArray[] = new ExternalThing($referenceLieuPublicationUrl);
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    if (!empty($lieuPublicationArray)) {
      $graphItem->setLieuPublication($lieuPublicationArray);
    }
  }

  /**
   * Set Oeuvre Representee Capdata property.
   * 
   * @param mixed $graphItem
   * @param array $capDataOeuvreMappingInfo
   * @param array $currentCapdataClassMappingInfo
   */
  public function setOeuvreRepresenteeCapdataProperty($graphItem, $capDataOeuvreMappingInfo, $currentCapdataClassMappingInfo): void {
    $oeuvresArray = null;
    if(!empty($currentCapdataClassMappingInfo)){
      $exportClassCapdataProperties =  $currentCapdataClassMappingInfo["mapped_entity_properties"];
      $mappingType = $currentCapdataClassMappingInfo["mapping_type"];
      $mappedEntity = $currentCapdataClassMappingInfo["mapped_entity"];
      $host = $currentCapdataClassMappingInfo["host"];
      if(!empty($mappedEntity) && !empty($mappingType) && !empty($exportClassCapdataProperties)){
        if(isset($exportClassCapdataProperties["oeuvre_representee"])){
          if(!empty($exportClassCapdataProperties["oeuvre_representee"]["property_".$mappingType."_fields_dropdown"])){
            $oeuvreRepresenteeFieldName = $exportClassCapdataProperties["oeuvre_representee"]["property_".$mappingType."_fields_dropdown"];
            if ($mappedEntity->hasField($oeuvreRepresenteeFieldName) && !$mappedEntity->get($oeuvreRepresenteeFieldName)->isEmpty()) {
              $oeuvreItems = $mappedEntity->get($oeuvreRepresenteeFieldName);
              foreach ($oeuvreItems as $referenceOeuvre) {
                $oeuvreItem = $referenceOeuvre->entity;
                if(!empty($oeuvreItem)){
                  $referenceOeuvreId =  $oeuvreItem->id();
                  if(!empty($capDataOeuvreMappingInfo)){
                    if(!empty($capDataOeuvreMappingInfo["capData_oeuvre_mappingtype"])){
                      $oeuvreMappingType = $capDataOeuvreMappingInfo["capData_oeuvre_mappingtype"];
                      $referenceOeuvreUrl = "";
                      if($oeuvreMappingType == "taxo" && $oeuvreItem instanceof \Drupal\taxonomy\TermInterface){
                        $referenceOeuvreUrl = $host . "/taxonomy/term/" . $referenceOeuvreId;
                      }elseif($oeuvreMappingType == "content" && $oeuvreItem instanceof \Drupal\node\NodeInterface){
                        $referenceOeuvreUrl = $host . "/node/" . $referenceOeuvreId;
                      }
                      if(!empty($referenceOeuvreUrl)){
                        $oeuvresArray[] = new ExternalThing($referenceOeuvreUrl);
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    if (!empty($oeuvresArray)) {
      $graphItem->setOeuvreRepresentee($oeuvresArray);
    }
  }

  /**
   * Set Production Primaire property.
   * 
   * @param mixed $graphItem
   * @param array $capDataProductionPrimaireMappingInfo
   * @param array $currentCapdataClassMappingInfo
   */
  public function setProductionPrimaireCapdataProperty($graphItem, $capDataProductionPrimaireMappingInfo, $currentCapdataClassMappingInfo): void {
    $productionsPrimairesArray = null;
    if(!empty($currentCapdataClassMappingInfo)){
      $exportClassCapdataProperties =  $currentCapdataClassMappingInfo["mapped_entity_properties"];
      $mappingType = $currentCapdataClassMappingInfo["mapping_type"];
      $mappedEntity = $currentCapdataClassMappingInfo["mapped_entity"];
      $host = $currentCapdataClassMappingInfo["host"];
      if(!empty($mappedEntity) && !empty($mappingType) && !empty($exportClassCapdataProperties)){
        if(isset($exportClassCapdataProperties["production_primaire"])){
          if(!empty($exportClassCapdataProperties["production_primaire"]["property_".$mappingType."_fields_dropdown"])){
            $productionPrimaireFieldName = $exportClassCapdataProperties["production_primaire"]["property_".$mappingType."_fields_dropdown"];
            if ($mappedEntity->hasField($productionPrimaireFieldName) && !$mappedEntity->get($productionPrimaireFieldName)->isEmpty()) {
              $productionPrimaireItems = $mappedEntity->get($productionPrimaireFieldName);
              foreach ($productionPrimaireItems as $referenceProductionPrimaire) {
                $productionPrimaireItem = $referenceProductionPrimaire->entity;
                if(!empty($productionPrimaireItem)){
                  $referenceProductionPrimaireId =  $productionPrimaireItem->id();
                  if(!empty($capDataProductionPrimaireMappingInfo)){
                    if(!empty($capDataProductionPrimaireMappingInfo["capData_productionprimaire_mappingtype"])){
                      $productionPrimaireMappingType = $capDataProductionPrimaireMappingInfo["capData_productionprimaire_mappingtype"];
                      $referenceProductionPrimaireUrl = "";
                      if($productionPrimaireMappingType == "taxo" && $productionPrimaireItem instanceof \Drupal\taxonomy\TermInterface){
                        $referenceProductionPrimaireUrl = $host . "/taxonomy/term/" . $referenceProductionPrimaireId;
                      }elseif($productionPrimaireMappingType == "content" && $productionPrimaireItem instanceof \Drupal\node\NodeInterface){
                        $referenceProductionPrimaireUrl = $host . "/node/" . $referenceProductionPrimaireId;
                      }
                      if(!empty($referenceProductionPrimaireUrl)){
                        $productionsPrimairesArray[] = new ExternalThing($referenceProductionPrimaireUrl);
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    if (!empty($productionsPrimairesArray)) {
      $graphItem->setProductionPrimaire($productionsPrimairesArray);
    }
  }

  /**
   * Handle participation content mapping.
   * 
   * @param mixed $graphItem
   * @param \Drupal\node\NodeInterface $node
   * @param array $participationContentMappingInfo
   */
  public function handleParticipationContentMapping($graphItem, $node, $participationContentMappingInfo): void {
    if(!empty($participationContentMappingInfo)){
      $capdataExportData = $participationContentMappingInfo['capdataExportData'];
      $host = $participationContentMappingInfo['host'];
      $capDataClassInfo = $participationContentMappingInfo['capDataClassInfo'];
      $ownOrg = $participationContentMappingInfo['ownOrg'];  
      if(!empty($capdataExportData) && !empty($host) && !empty($node) && !empty($capDataClassInfo) && !empty($ownOrg) && !empty($graphItem)){
        // A pour fonction (Fonction)
        $capDataFonctionMappingType = "";
        if(isset($capdataExportData["content_mapped_classes"]["capdata_fonction"])){
          $capDataFonctionMappingType = "content";
        }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_fonction"])){
          $capDataFonctionMappingType = "taxo";
        }
        $capDataFonctionMappingInfo = [
            "capData_fonction_mappingtype" => $capDataFonctionMappingType,
        ];
        $currentCapdataClassMappingInfo =[
          "host" => $host,
          "mapping_type" =>  "content",
          "mapped_entity" =>  $node,
          "mapped_entity_properties" => $capDataClassInfo["export_class_capdata_properties"],
        ];
        $this->setAPourFonctionCapdataProperty($graphItem,
                                                $capDataFonctionMappingInfo,
                                                $currentCapdataClassMappingInfo
                                              );               
        // A pour participant (Personne ou Collectivite)
        $capDataPersonneMappingType = "";
        $capDataPersonneCorrespondantEntity = "";
        if(isset($capdataExportData["content_mapped_classes"]["capdata_personne"])){
          $capDataPersonneMappingType = "content";
          if($capdataExportData["content_mapped_classes"]["capdata_personne"]["export_class_mapping_type"] == "capdata_personne_content_mapping"
            &&
            !empty($capdataExportData["content_mapped_classes"]["capdata_personne"]["export_class_content_dropdown"])
          ){
            $capDataPersonneCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_personne"]["export_class_content_dropdown"];
          }
        }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_personne"])){
          $capDataPersonneMappingType = "taxo";
          if($capdataExportData["taxo_mapped_classes"]["capdata_personne"]["export_class_mapping_type"] == "capdata_personne_taxo_mapping"
            &&
            !empty($capdataExportData["taxo_mapped_classes"]["capdata_personne"]["export_class_taxonomy_dropdown"])
          ){
            $capDataPersonneCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_personne"]["export_class_taxonomy_dropdown"];
          }                
        }
        $capDataPersonneMappingInfo = [
            "capData_personne_mappingtype" => $capDataPersonneMappingType,
            "capData_personne_correspondance" => $capDataPersonneCorrespondantEntity,
        ];

        $capDataCollectiviteMappingType = "";
        $capDataCollectiviteCorrespondantEntity = "";
        if(isset($capdataExportData["content_mapped_classes"]["capdata_collectivite"])){
          $capDataCollectiviteMappingType = "content";
          if($capdataExportData["content_mapped_classes"]["capdata_collectivite"]["export_class_mapping_type"] == "capdata_collectivite_content_mapping"
            &&
            !empty($capdataExportData["content_mapped_classes"]["capdata_collectivite"]["export_class_content_dropdown"])
          ){
            $capDataCollectiviteCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_collectivite"]["export_class_content_dropdown"];
          }                
        }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_collectivite"])){
          $capDataCollectiviteMappingType = "taxo";
          if($capdataExportData["taxo_mapped_classes"]["capdata_collectivite"]["export_class_mapping_type"] == "capdata_collectivite_taxo_mapping"
            &&
            !empty($capdataExportData["taxo_mapped_classes"]["capdata_collectivite"]["export_class_taxonomy_dropdown"])
          ){
            $capDataCollectiviteCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_collectivite"]["export_class_taxonomy_dropdown"];
          }                  
        }
        $capDataCollectiviteMappingInfo = [
            "capData_collectivite_mappingtype" => $capDataCollectiviteMappingType,
            "capData_collectivite_correspondance" => $capDataCollectiviteCorrespondantEntity,
            "default_collectivite" => $ownOrg,
        ];
        $this->setAPourParticipantCapdataProperty($graphItem,
                                                $capDataPersonneMappingInfo,
                                                $capDataCollectiviteMappingInfo,
                                                $currentCapdataClassMappingInfo
                                              );
        // Identifiant Rof
        $this->setIdentifiantRofProperty($graphItem,
            $node,
            $capDataClassInfo["export_class_capdata_properties"],
            "content"
          );                                              
      }                        
    }                                  
  }  

  /**
   * Handle participation taxonomy mapping.
   * 
   * @param mixed $graphItem
   * @param \Drupal\taxonomy\TermInterface $term
   * @param array $participationTaxonomyMappingInfo
   */
  public function handleParticipationTaxonomyMapping($graphItem, $term, $participationTaxonomyMappingInfo): void {
    if(!empty($participationTaxonomyMappingInfo)){
      $capdataExportData = $participationTaxonomyMappingInfo['capdataExportData'];
      $host = $participationTaxonomyMappingInfo['host'];
      $capDataClassInfo = $participationTaxonomyMappingInfo['capDataClassInfo'];
      $ownOrg = $participationTaxonomyMappingInfo['ownOrg'];
      if(!empty($capdataExportData) && !empty($host) && !empty($term) && !empty($capDataClassInfo) && !empty($ownOrg) && !empty($graphItem)){
        // A pour fonction (Fonction)
        $capDataFonctionMappingType = "";
        if(isset($capdataExportData["content_mapped_classes"]["capdata_fonction"])){
          $capDataFonctionMappingType = "content";
        }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_fonction"])){
          $capDataFonctionMappingType = "taxo";
        }
        $capDataFonctionMappingInfo = [
          "capData_fonction_mappingtype" => $capDataFonctionMappingType,
        ];
        $currentCapdataClassMappingInfo =[
          "host" => $host,
          "mapping_type" =>  "taxo",
          "mapped_entity" =>  $term,
          "mapped_entity_properties" => $capDataClassInfo["export_class_capdata_properties"],
        ];
        $this->setAPourFonctionCapdataProperty($graphItem,
                                                $capDataFonctionMappingInfo,
                                                $currentCapdataClassMappingInfo
                                              );
        // A pour participant (Personne ou Collectivite)
        $capDataPersonneMappingType = "";
        $capDataPersonneCorrespondantEntity = "";
        if(isset($capdataExportData["content_mapped_classes"]["capdata_personne"])){
          $capDataPersonneMappingType = "content";
          if($capdataExportData["content_mapped_classes"]["capdata_personne"]["export_class_mapping_type"] == "capdata_personne_content_mapping"
          &&
          !empty($capdataExportData["content_mapped_classes"]["capdata_personne"]["export_class_content_dropdown"])
          ){
            $capDataPersonneCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_personne"]["export_class_content_dropdown"];
          }
        }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_personne"])){
          $capDataPersonneMappingType = "taxo";
          if($capdataExportData["taxo_mapped_classes"]["capdata_personne"]["export_class_mapping_type"] == "capdata_personne_taxo_mapping"
          &&
          !empty($capdataExportData["taxo_mapped_classes"]["capdata_personne"]["export_class_taxonomy_dropdown"])
          ){
            $capDataPersonneCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_personne"]["export_class_taxonomy_dropdown"];
          }                
        }
        $capDataPersonneMappingInfo = [
            "capData_personne_mappingtype" => $capDataPersonneMappingType,
            "capData_personne_correspondance" => $capDataPersonneCorrespondantEntity,
        ];

        $capDataCollectiviteMappingType = "";
        $capDataCollectiviteCorrespondantEntity = "";
        if(isset($capdataExportData["content_mapped_classes"]["capdata_collectivite"])){
            $capDataCollectiviteMappingType = "content";
            if($capdataExportData["content_mapped_classes"]["capdata_collectivite"]["export_class_mapping_type"] == "capdata_collectivite_content_mapping"
              &&
              !empty($capdataExportData["content_mapped_classes"]["capdata_collectivite"]["export_class_content_dropdown"])
            ){
              $capDataCollectiviteCorrespondantEntity = $capdataExportData["content_mapped_classes"]["capdata_collectivite"]["export_class_content_dropdown"];
            }
        }elseif(isset($capdataExportData["taxo_mapped_classes"]["capdata_collectivite"])){
            $capDataCollectiviteMappingType = "taxo";
            if($capdataExportData["taxo_mapped_classes"]["capdata_collectivite"]["export_class_mapping_type"] == "capdata_collectivite_taxo_mapping"
              &&
              !empty($capdataExportData["taxo_mapped_classes"]["capdata_collectivite"]["export_class_taxonomy_dropdown"])
            ){
              $capDataCollectiviteCorrespondantEntity = $capdataExportData["taxo_mapped_classes"]["capdata_collectivite"]["export_class_taxonomy_dropdown"];
            }    
        }
        $capDataCollectiviteMappingInfo = [
            "capData_collectivite_mappingtype" => $capDataCollectiviteMappingType,
            "capData_collectivite_correspondance" => $capDataCollectiviteCorrespondantEntity,
            "default_collectivite" => $ownOrg,
        ];
        $this->setAPourParticipantCapdataProperty($graphItem,
                                                $capDataPersonneMappingInfo,
                                                $capDataCollectiviteMappingInfo,
                                                $currentCapdataClassMappingInfo
                                                );
        // Identifiant Rof
        $this->setIdentifiantRofProperty($graphItem,
            $term,
            $capDataClassInfo["export_class_capdata_properties"],
            "taxo"
          );
      }
    }
  }

  /**
   * Set Date Premiere, Date publication Properties.
   * 
   * @param mixed $graphItem
   * @param \Drupal\taxonomy\TermInterface|\Drupal\node\NodeInterface $mappedEntity
   * @param array $exportClassCapdataProperties
   * @param string $mappingType
   */
  public function setProductionDatesProperties($graphItem, $mappedEntity, $exportClassCapdataProperties, $mappingType): void {
    // Date premiere
    $datePremiere = "";
    if(isset($exportClassCapdataProperties["date_premiere"])){
      if(!empty($exportClassCapdataProperties["date_premiere"]["property_".$mappingType."_fields_dropdown"])){
        $datePremiereFieldName = $exportClassCapdataProperties["date_premiere"]["property_".$mappingType."_fields_dropdown"];
        if ($mappedEntity->hasField($datePremiereFieldName) && !$mappedEntity->get($datePremiereFieldName)->isEmpty()) {
          if(!empty($mappedEntity->get($datePremiereFieldName)->date)){
            $datePremiereObj = $mappedEntity->get($datePremiereFieldName)->date;
          }else{
            // timestamp field
            $datePremiereObj = DrupalDateTime::createFromTimestamp((int)$mappedEntity->get($datePremiereFieldName)->value);
          }
          $datePremiereObj->setTimezone(new \DateTimeZone('UTC'));
          $datePremiere = $datePremiereObj->format('Y-m-d\TH:i:s\Z');
        }
      }
    }
    if(!empty($datePremiere)){
      $graphItem->setDatePremiere($datePremiere);
    }
    // Date publication
    $datePublication = "";
    if(isset($exportClassCapdataProperties["date_publication"])){
      if(!empty($exportClassCapdataProperties["date_publication"]["property_".$mappingType."_fields_dropdown"])){
        $datePublicationFieldName = $exportClassCapdataProperties["date_publication"]["property_".$mappingType."_fields_dropdown"];
        if ($mappedEntity->hasField($datePublicationFieldName) && !$mappedEntity->get($datePublicationFieldName)->isEmpty()) {
          if(!empty($mappedEntity->get($datePublicationFieldName)->date)){
            $datePublicationObj = $mappedEntity->get($datePublicationFieldName)->date;
          }else{
            // timestamp field
            $datePublicationObj = DrupalDateTime::createFromTimestamp((int)$mappedEntity->get($datePublicationFieldName)->value);
          }
          $datePublicationObj->setTimezone(new \DateTimeZone('UTC'));
          $datePublication = $datePublicationObj->format('Y-m-d\TH:i:s\Z');
        }
      }
    }
    if(!empty($datePublication)){
      $graphItem->setDatePublication($datePublication);
    }
  }

  /**
   * Set A Pour Type Evenement property.
   * 
   * @param mixed $graphItem
   * @param array $capDataTypeEvenementMappingInfo
   * @param array $currentCapdataClassMappingInfo
   */
  public function setAPourTypeEvenementCapdataProperty($graphItem, $capDataTypeEvenementMappingInfo, $currentCapdataClassMappingInfo): void {
    $typesEvenementArray = null;
    if(!empty($currentCapdataClassMappingInfo)){
      $exportClassCapdataProperties =  $currentCapdataClassMappingInfo["mapped_entity_properties"];
      $mappingType = $currentCapdataClassMappingInfo["mapping_type"];
      $mappedEntity = $currentCapdataClassMappingInfo["mapped_entity"];
      $host = $currentCapdataClassMappingInfo["host"];
      if(!empty($mappedEntity) && !empty($mappingType) && !empty($exportClassCapdataProperties)){
        if(isset($exportClassCapdataProperties["type_evenement"])){
          if(!empty($exportClassCapdataProperties["type_evenement"]["property_".$mappingType."_fields_dropdown"])){
            $typeEvenementFieldName = $exportClassCapdataProperties["type_evenement"]["property_".$mappingType."_fields_dropdown"];
            if ($mappedEntity->hasField($typeEvenementFieldName) && !$mappedEntity->get($typeEvenementFieldName)->isEmpty()) {
              $typeEvenementItems = $mappedEntity->get($typeEvenementFieldName);
              foreach ($typeEvenementItems as $referenceTypeEvenement) {
                $typeEvenementItem = $referenceTypeEvenement->entity;
                if(!empty($typeEvenementItem)){
                  $referenceTypeEvenementId =  $typeEvenementItem->id();
                  if(!empty($capDataTypeEvenementMappingInfo)){
                    if(!empty($capDataTypeEvenementMappingInfo["capData_typeevenement_mappingtype"])){
                      $typeEvenementMappingType = $capDataTypeEvenementMappingInfo["capData_typeevenement_mappingtype"];
                      $referenceTypeEvenementUrl = "";
                      if($typeEvenementMappingType == "taxo" && $typeEvenementItem instanceof \Drupal\taxonomy\TermInterface){
                        $referenceTypeEvenementUrl = $host . "/taxonomy/term/" . $referenceTypeEvenementId;
                      }elseif($typeEvenementMappingType == "content" && $typeEvenementItem instanceof \Drupal\node\NodeInterface){
                        $referenceTypeEvenementUrl = $host . "/node/" . $referenceTypeEvenementId;
                      }
                      if(!empty($referenceTypeEvenementUrl)){
                        $typesEvenementArray[] = new ExternalThing($referenceTypeEvenementUrl);
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    if (!empty($typesEvenementArray)) {
      $graphItem->setTypeEvenement($typesEvenementArray);
    }
  }

  /**
   * Set A pour lieu property.
   * 
   * @param mixed $graphItem
   * @param array $capDataLieuCustomMappingInfo
   * @param array $currentCapdataClassMappingInfo
   */
  public function setAPourLieuCapdataProperty($graphItem, $capDataLieuCustomMappingInfo, $currentCapdataClassMappingInfo): void {
    $lieuGeoArray = null;
    if(!empty($currentCapdataClassMappingInfo)){
      $exportClassCapdataProperties =  $currentCapdataClassMappingInfo["mapped_entity_properties"];
      $mappingType = $currentCapdataClassMappingInfo["mapping_type"];
      $mappedEntity = $currentCapdataClassMappingInfo["mapped_entity"];
      $host = $currentCapdataClassMappingInfo["host"];
      if(!empty($mappedEntity) && !empty($mappingType) && !empty($exportClassCapdataProperties)){
        if(isset($exportClassCapdataProperties["a_pour_lieu"])){
          if(!empty($exportClassCapdataProperties["a_pour_lieu"]["property_".$mappingType."_fields_dropdown"])){
            $lieuGeoFieldName = $exportClassCapdataProperties["a_pour_lieu"]["property_".$mappingType."_fields_dropdown"];
            if ($mappedEntity->hasField($lieuGeoFieldName) && !$mappedEntity->get($lieuGeoFieldName)->isEmpty()) {
              $lieuGeoItems = $mappedEntity->get($lieuGeoFieldName);
              foreach ($lieuGeoItems as $referenceLieuGeo) {
                $lieuGeoItem = $referenceLieuGeo->entity;
                if(!empty($lieuGeoItem)){
                  $referenceLieuGeoId =  $lieuGeoItem->id();
                  if(!empty($capDataLieuCustomMappingInfo)){
                    if(!empty($capDataLieuCustomMappingInfo["capData_lieu_mappingtype"])){
                      $lieuGeoMappingType = $capDataLieuCustomMappingInfo["capData_lieu_mappingtype"];
                      $referenceLieuGeoUrl = "";
                      if($lieuGeoMappingType == "taxo" && $lieuGeoItem instanceof \Drupal\taxonomy\TermInterface){
                        $referenceLieuGeoUrl = $host . "/taxonomy/term/" . $referenceLieuGeoId;
                      }elseif($lieuGeoMappingType == "content" && $lieuGeoItem instanceof \Drupal\node\NodeInterface){
                        $referenceLieuGeoUrl = $host . "/node/" . $referenceLieuGeoId;
                      }
                      if(!empty($referenceLieuGeoUrl)){
                        $lieuGeoArray[] = new ExternalThing($referenceLieuGeoUrl);
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    if (!empty($lieuGeoArray)) {
      $graphItem->setAPourLieu($lieuGeoArray);
    }
  }

  /**
   * Set A Pour Production property.
   * 
   * @param mixed $graphItem
   * @param array $capDataProductionCustomMappingInfo
   * @param array $currentCapdataClassMappingInfo
   */
  public function setAPourProductionCapdataProperty($graphItem, $capDataProductionCustomMappingInfo, $currentCapdataClassMappingInfo): void {
    $productionsArray = null;
    if(!empty($currentCapdataClassMappingInfo)){
      $exportClassCapdataProperties =  $currentCapdataClassMappingInfo["mapped_entity_properties"];
      $mappingType = $currentCapdataClassMappingInfo["mapping_type"];
      $mappedEntity = $currentCapdataClassMappingInfo["mapped_entity"];
      $host = $currentCapdataClassMappingInfo["host"];
      if(!empty($mappedEntity) && !empty($mappingType) && !empty($exportClassCapdataProperties)){
        if(isset($exportClassCapdataProperties["a_pour_production"])){
          if(!empty($exportClassCapdataProperties["a_pour_production"]["property_".$mappingType."_fields_dropdown"])){
            $aPourProductionFieldName = $exportClassCapdataProperties["a_pour_production"]["property_".$mappingType."_fields_dropdown"];
            if ($mappedEntity->hasField($aPourProductionFieldName) && !$mappedEntity->get($aPourProductionFieldName)->isEmpty()) {
              $productionItems = $mappedEntity->get($aPourProductionFieldName);
              foreach ($productionItems as $referenceProduction) {
                $productionItem = $referenceProduction->entity;
                if(!empty($productionItem)){
                  $referenceProductionId =  $productionItem->id();
                  if(!empty($capDataProductionCustomMappingInfo)){
                    if(!empty($capDataProductionCustomMappingInfo["capData_production_mappingtype"])){
                      $productionMappingType = $capDataProductionCustomMappingInfo["capData_production_mappingtype"];
                      $referenceProductionUrl = "";
                      if($productionMappingType == "taxo" && $productionItem instanceof \Drupal\taxonomy\TermInterface){
                        $referenceProductionUrl = $host . "/taxonomy/term/" . $referenceProductionId;
                      }elseif($productionMappingType == "content" && $productionItem instanceof \Drupal\node\NodeInterface){
                        $referenceProductionUrl = $host . "/node/" . $referenceProductionId;
                      }
                      if(!empty($referenceProductionUrl)){
                        $productionsArray[] = new ExternalThing($referenceProductionUrl);
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    if (!empty($productionsArray)) {
      $graphItem->setAPourProduction($productionsArray);
    }
  }


  /**
   * Set Date debut, Date fin, Annulation Properties.
   * 
   * @param mixed $graphItem
   * @param \Drupal\taxonomy\TermInterface|\Drupal\node\NodeInterface $mappedEntity
   * @param array $exportClassCapdataProperties
   * @param string $mappingType
   */
  public function setEventDatesCapdataProperties($graphItem, $mappedEntity, $exportClassCapdataProperties, $mappingType): void {
    // Date debut
    $dateDebut = "";
    if(isset($exportClassCapdataProperties["date_debut"])){
      if(!empty($exportClassCapdataProperties["date_debut"]["property_".$mappingType."_fields_dropdown"])){
        $dateDebutFieldName = $exportClassCapdataProperties["date_debut"]["property_".$mappingType."_fields_dropdown"];
        if ($mappedEntity->hasField($dateDebutFieldName) && !$mappedEntity->get($dateDebutFieldName)->isEmpty()) {
          if(!empty($mappedEntity->get($dateDebutFieldName)->date)){
            $dateDebutObj = $mappedEntity->get($dateDebutFieldName)->date;
          }else{
            // timestamp field
            $dateDebutObj = DrupalDateTime::createFromTimestamp((int)$mappedEntity->get($dateDebutFieldName)->value);
          }
          $dateDebutObj->setTimezone(new \DateTimeZone('UTC'));
          $dateDebut = $dateDebutObj->format('Y-m-d\TH:i:s\Z');
        }
      }
    }
    if(!empty($dateDebut)){
      $graphItem->setDateDebut($dateDebut);
    }
    // Date fin
    $dateFin = "";
    if(isset($exportClassCapdataProperties["date_fin"])){
      if(!empty($exportClassCapdataProperties["date_fin"]["property_".$mappingType."_fields_dropdown"])){
        $dateFinFieldName = $exportClassCapdataProperties["date_fin"]["property_".$mappingType."_fields_dropdown"];
        if ($mappedEntity->hasField($dateFinFieldName) && !$mappedEntity->get($dateFinFieldName)->isEmpty()) {
          if(!empty($mappedEntity->get($dateFinFieldName)->date)){
            $dateFinObj = $mappedEntity->get($dateFinFieldName)->date;
          }else{
            // timestamp field
            $dateFinObj = DrupalDateTime::createFromTimestamp((int)$mappedEntity->get($dateFinFieldName)->value);
          }
          $dateFinObj->setTimezone(new \DateTimeZone('UTC'));
          $dateFin = $dateFinObj->format('Y-m-d\TH:i:s\Z');
        }
      }
    }
    if(!empty($dateFin)){
      $graphItem->setDateFin($dateFin);
    }
    // Annulation
    $bAnnulation = false;
    if(isset($exportClassCapdataProperties["annulation"])){
      if(!empty($exportClassCapdataProperties["annulation"]["property_".$mappingType."_fields_dropdown"])){
        $bAnnulationFieldName = $exportClassCapdataProperties["annulation"]["property_".$mappingType."_fields_dropdown"];
        if($mappedEntity->hasField($bAnnulationFieldName) && !$mappedEntity->get($bAnnulationFieldName)->isEmpty()){
          $bAnnulation = $mappedEntity->get($bAnnulationFieldName)->value;
          if(!empty($bAnnulation)){
            if(!empty($exportClassCapdataProperties["annulation"]["property_".$mappingType."_custom_processing"])){
              $customProcessing = $exportClassCapdataProperties["annulation"]["property_".$mappingType."_custom_processing"];
              $bAnnulation = $this->customFieldProcessing($bAnnulation, $customProcessing);
            }
            $graphItem->setAnnulation(true);
          }
        }
      }
    }    
  }

  /**
   * Set Categorie Oeuvre property.
   * 
   * @param mixed $graphItem
   * @param array $capDataCategorieOeuvreMappingInfo
   * @param array $currentCapdataClassMappingInfo
   */
  public function setCategorieOeuvreCapdataProperty($graphItem, $capDataCategorieOeuvreMappingInfo, $currentCapdataClassMappingInfo): void {
    $categoriesOeuvreArray = null;
    if(!empty($currentCapdataClassMappingInfo)){
      $exportClassCapdataProperties =  $currentCapdataClassMappingInfo["mapped_entity_properties"];
      $mappingType = $currentCapdataClassMappingInfo["mapping_type"];
      $mappedEntity = $currentCapdataClassMappingInfo["mapped_entity"];
      $host = $currentCapdataClassMappingInfo["host"];
      if(!empty($mappedEntity) && !empty($mappingType) && !empty($exportClassCapdataProperties)){
        if(isset($exportClassCapdataProperties["categorie_oeuvre"])){
          if(!empty($exportClassCapdataProperties["categorie_oeuvre"]["property_".$mappingType."_fields_dropdown"])){
            $categorieOeuvreFieldName = $exportClassCapdataProperties["categorie_oeuvre"]["property_".$mappingType."_fields_dropdown"];
            if ($mappedEntity->hasField($categorieOeuvreFieldName) && !$mappedEntity->get($categorieOeuvreFieldName)->isEmpty()) {
              $categorieOeuvreItems = $mappedEntity->get($categorieOeuvreFieldName);
              foreach ($categorieOeuvreItems as $referenceCategorieOeuvre) {
                $categorieOeuvreItem = $referenceCategorieOeuvre->entity;
                if(!empty($categorieOeuvreItem)){
                  $referenceCategorieOeuvreId =  $categorieOeuvreItem->id();
                  if(!empty($capDataCategorieOeuvreMappingInfo)){
                    if(!empty($capDataCategorieOeuvreMappingInfo["capData_categorieoeuvre_mappingtype"])){
                      $categorieOeuvreMappingType = $capDataCategorieOeuvreMappingInfo["capData_categorieoeuvre_mappingtype"];
                      $referenceCategorieOeuvreUrl = "";
                      if($categorieOeuvreMappingType == "taxo" && $categorieOeuvreItem instanceof \Drupal\taxonomy\TermInterface){
                        $referenceCategorieOeuvreUrl = $host . "/taxonomy/term/" . $referenceCategorieOeuvreId;
                      }elseif($categorieOeuvreMappingType == "content" && $categorieOeuvreItem instanceof \Drupal\node\NodeInterface){
                        $referenceCategorieOeuvreUrl = $host . "/node/" . $referenceCategorieOeuvreId;
                      }
                      if(!empty($referenceCategorieOeuvreUrl)){
                        $categoriesOeuvreArray[] = new ExternalThing($referenceCategorieOeuvreUrl);
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    if (!empty($categoriesOeuvreArray)) {
      $graphItem->setCategorieOeuvre($categoriesOeuvreArray);
    }
  }

  /**
   * Set Genre Oeuvre property.
   * 
   * @param mixed $graphItem
   * @param array $capDataGenreOeuvreMappingInfo
   * @param array $currentCapdataClassMappingInfo
   */
  public function setGenreOeuvreCapdataProperty($graphItem, $capDataGenreOeuvreMappingInfo, $currentCapdataClassMappingInfo): void {
    $genresOeuvreArray = null;
    if(!empty($currentCapdataClassMappingInfo)){
      $exportClassCapdataProperties =  $currentCapdataClassMappingInfo["mapped_entity_properties"];
      $mappingType = $currentCapdataClassMappingInfo["mapping_type"];
      $mappedEntity = $currentCapdataClassMappingInfo["mapped_entity"];
      $host = $currentCapdataClassMappingInfo["host"];
      if(!empty($mappedEntity) && !empty($mappingType) && !empty($exportClassCapdataProperties)){
        if(isset($exportClassCapdataProperties["genre_oeuvre"])){
          if(!empty($exportClassCapdataProperties["genre_oeuvre"]["property_".$mappingType."_fields_dropdown"])){
            $genreOeuvreFieldName = $exportClassCapdataProperties["genre_oeuvre"]["property_".$mappingType."_fields_dropdown"];
            if ($mappedEntity->hasField($genreOeuvreFieldName) && !$mappedEntity->get($genreOeuvreFieldName)->isEmpty()) {
              $genreOeuvreItems = $mappedEntity->get($genreOeuvreFieldName);
              foreach ($genreOeuvreItems as $referenceGenreOeuvre) {
                $genreOeuvreItem = $referenceGenreOeuvre->entity;
                if(!empty($genreOeuvreItem)){
                  $referenceGenreOeuvreId =  $genreOeuvreItem->id();
                  if(!empty($capDataGenreOeuvreMappingInfo)){
                    if(!empty($capDataGenreOeuvreMappingInfo["capData_genreoeuvre_mappingtype"])){
                      $genreOeuvreMappingType = $capDataGenreOeuvreMappingInfo["capData_genreoeuvre_mappingtype"];
                      $referenceGenreOeuvreUrl = "";
                      if($genreOeuvreMappingType == "taxo" && $genreOeuvreItem instanceof \Drupal\taxonomy\TermInterface){
                        $referenceGenreOeuvreUrl = $host . "/taxonomy/term/" . $referenceGenreOeuvreId;
                      }elseif($genreOeuvreMappingType == "content" && $genreOeuvreItem instanceof \Drupal\node\NodeInterface){
                        $referenceGenreOeuvreUrl = $host . "/node/" . $referenceGenreOeuvreId;
                      }
                      if(!empty($referenceGenreOeuvreUrl)){
                        $genresOeuvreArray[] = new ExternalThing($referenceGenreOeuvreUrl);
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    if (!empty($genresOeuvreArray)) {
      $graphItem->setGenreOeuvre($genresOeuvreArray);
    }
  }  

  /**
   * Set Type Oeuvre property.
   * 
   * @param mixed $graphItem
   * @param array $capDataTypeOeuvreMappingInfo
   * @param array $currentCapdataClassMappingInfo
   */
  public function setTypeOeuvreCapdataProperty($graphItem, $capDataTypeOeuvreMappingInfo, $currentCapdataClassMappingInfo): void {
    $typesOeuvreArray = null;
    if(!empty($currentCapdataClassMappingInfo)){
      $exportClassCapdataProperties =  $currentCapdataClassMappingInfo["mapped_entity_properties"];
      $mappingType = $currentCapdataClassMappingInfo["mapping_type"];
      $mappedEntity = $currentCapdataClassMappingInfo["mapped_entity"];
      $host = $currentCapdataClassMappingInfo["host"];
      if(!empty($mappedEntity) && !empty($mappingType) && !empty($exportClassCapdataProperties)){
        if(isset($exportClassCapdataProperties["type_oeuvre"])){
          if(!empty($exportClassCapdataProperties["type_oeuvre"]["property_".$mappingType."_fields_dropdown"])){
            $typeOeuvreFieldName = $exportClassCapdataProperties["type_oeuvre"]["property_".$mappingType."_fields_dropdown"];
            if ($mappedEntity->hasField($typeOeuvreFieldName) && !$mappedEntity->get($typeOeuvreFieldName)->isEmpty()) {
              $typeOeuvreItems = $mappedEntity->get($typeOeuvreFieldName);
              foreach ($typeOeuvreItems as $referenceTypeOeuvre) {
                $typeOeuvreItem = $referenceTypeOeuvre->entity;
                if(!empty($typeOeuvreItem)){
                  $referenceTypeOeuvreId =  $typeOeuvreItem->id();
                  if(!empty($capDataTypeOeuvreMappingInfo)){
                    if(!empty($capDataTypeOeuvreMappingInfo["capData_typeoeuvre_mappingtype"])){
                      $typeOeuvreMappingType = $capDataTypeOeuvreMappingInfo["capData_typeoeuvre_mappingtype"];
                      $referenceTypeOeuvreUrl = "";
                      if($typeOeuvreMappingType == "taxo" && $typeOeuvreItem instanceof \Drupal\taxonomy\TermInterface){
                        $referenceTypeOeuvreUrl = $host . "/taxonomy/term/" . $referenceTypeOeuvreId;
                      }elseif($typeOeuvreMappingType == "content" && $typeOeuvreItem instanceof \Drupal\node\NodeInterface){
                        $referenceTypeOeuvreUrl = $host . "/node/" . $referenceTypeOeuvreId;
                      }
                      if(!empty($referenceTypeOeuvreUrl)){
                        $typesOeuvreArray[] = new ExternalThing($referenceTypeOeuvreUrl);
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    if (!empty($typesOeuvreArray)) {
      $graphItem->setTypeOeuvre($typesOeuvreArray);
    }
  }

  /**
   * Set Personnage Capdata property.
   * 
   * @param mixed $graphItem
   * @param array $capDataRoleMappingInfo
   * @param array $currentCapdataClassMappingInfo
   */
  public function setPersonnageCapdataProperty($graphItem, $capDataRoleMappingInfo, $currentCapdataClassMappingInfo): void {
    $personnagesArray = null;
    if(!empty($currentCapdataClassMappingInfo)){
      $exportClassCapdataProperties =  $currentCapdataClassMappingInfo["mapped_entity_properties"];
      $mappingType = $currentCapdataClassMappingInfo["mapping_type"];
      $mappedEntity = $currentCapdataClassMappingInfo["mapped_entity"];
      $host = $currentCapdataClassMappingInfo["host"];
      if(!empty($mappedEntity) && !empty($mappingType) && !empty($exportClassCapdataProperties)){
        if(isset($exportClassCapdataProperties["personnage"])){
          if(!empty($exportClassCapdataProperties["personnage"]["property_".$mappingType."_fields_dropdown"])){
            $personnageFieldName = $exportClassCapdataProperties["personnage"]["property_".$mappingType."_fields_dropdown"];
            if ($mappedEntity->hasField($personnageFieldName) && !$mappedEntity->get($personnageFieldName)->isEmpty()) {
              $personnageItems = $mappedEntity->get($personnageFieldName);
              foreach ($personnageItems as $referencePersonnage) {
                $personnageItem = $referencePersonnage->entity;
                if(!empty($personnageItem)){
                  $referencePersonnageId =  $personnageItem->id();
                  if(!empty($capDataRoleMappingInfo)){
                    if(!empty($capDataRoleMappingInfo["capData_role_mappingtype"])){
                      $roleMappingType = $capDataRoleMappingInfo["capData_role_mappingtype"];
                      $referencePersonnageUrl = "";
                      if($roleMappingType == "taxo" && $personnageItem instanceof \Drupal\taxonomy\TermInterface){
                        $referencePersonnageUrl = $host . "/taxonomy/term/" . $referencePersonnageId;
                      }elseif($roleMappingType == "content" && $personnageItem instanceof \Drupal\node\NodeInterface){
                        $referencePersonnageUrl = $host . "/node/" . $referencePersonnageId;
                      }
                      if(!empty($referencePersonnageUrl)){
                        $personnagesArray[] = new ExternalThing($referencePersonnageUrl);
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    if (!empty($personnagesArray)) {
      $graphItem->setPersonnage($personnagesArray);
    }
  }

  /**
   * Set Oeuvre titreFormeRejet, intrigue, source livret Capdata Properties.
   * 
   * @param mixed $graphItem
   * @param \Drupal\taxonomy\TermInterface|\Drupal\node\NodeInterface $mappedEntity
   * @param array $exportClassCapdataProperties
   * @param string $mappingType
   */
  public function setOeuvreDetails($graphItem, $mappedEntity, $exportClassCapdataProperties, $mappingType): void {  
    // Titre form rejet
    $titreFormeRejet = "";
    if(isset($exportClassCapdataProperties["titre_forme_rejet"])){
      if(!empty($exportClassCapdataProperties["titre_forme_rejet"]["property_".$mappingType."_fields_dropdown"])){
        $titreFormeRejetFieldName = $exportClassCapdataProperties["titre_forme_rejet"]["property_".$mappingType."_fields_dropdown"];
        if($mappedEntity->hasField($titreFormeRejetFieldName) && !$mappedEntity->get($titreFormeRejetFieldName)->isEmpty()){
          $titreFormeRejet = $mappedEntity->get($titreFormeRejetFieldName)->value;
          if(!empty($titreFormeRejet)){
            if(!empty($exportClassCapdataProperties["titre_forme_rejet"]["property_".$mappingType."_custom_processing"])){
              $customProcessing = $exportClassCapdataProperties["titre_forme_rejet"]["property_".$mappingType."_custom_processing"];
              $titreFormeRejet = $this->customFieldProcessing($titreFormeRejet, $customProcessing);
            }
            $graphItem->setTitreFormeRejet($titreFormeRejet);
          }
        }
      }
    }

    // Intrigue
    $intrigue = "";
    if(isset($exportClassCapdataProperties["intrigue"])){
      if(!empty($exportClassCapdataProperties["intrigue"]["property_".$mappingType."_fields_dropdown"])){
        $intrigueFieldName = $exportClassCapdataProperties["intrigue"]["property_".$mappingType."_fields_dropdown"];
        if($mappedEntity->hasField($intrigueFieldName) && !$mappedEntity->get($intrigueFieldName)->isEmpty()){
          $intrigue = $mappedEntity->get($intrigueFieldName)->value;
          if(!empty($intrigue)){
            if(!empty($exportClassCapdataProperties["intrigue"]["property_".$mappingType."_custom_processing"])){
              $customProcessing = $exportClassCapdataProperties["intrigue"]["property_".$mappingType."_custom_processing"];
              $intrigue = $this->customFieldProcessing($intrigue, $customProcessing);
            }
            $graphItem->setIntrigue($intrigue);
          }
        }
      }
    }
  }

  /**
   * Set Date de creation, duree capdata Properties.
   * 
   * @param mixed $graphItem
   * @param \Drupal\taxonomy\TermInterface|\Drupal\node\NodeInterface $mappedEntity
   * @param array $exportClassCapdataProperties
   * @param string $mappingType
   */
  public function setOeuvreDatesCapdataProperties($graphItem, $mappedEntity, $exportClassCapdataProperties, $mappingType): void {
    // Date de création
    $creationDateObj ="";
    if ($mappedEntity instanceof \Drupal\taxonomy\TermInterface) {
      // $mappedEntity is a taxonomy term
      $creationDateObj = DrupalDateTime::createFromTimestamp((int)$mappedEntity->changed->value);
    }else{
      $creationDateObj = DrupalDateTime::createFromTimestamp((int)$mappedEntity->created->value);
    }
    if(isset($exportClassCapdataProperties["date_creation"])){
      if(!empty($exportClassCapdataProperties["date_creation"]["property_".$mappingType."_fields_dropdown"])){
        $dateCreationFieldName = $exportClassCapdataProperties["date_creation"]["property_".$mappingType."_fields_dropdown"];
        if ($mappedEntity->hasField($dateCreationFieldName) && !$mappedEntity->get($dateCreationFieldName)->isEmpty()) {
          if(!empty($mappedEntity->get($dateCreationFieldName)->date)){
            $creationDateObj = $mappedEntity->get($dateCreationFieldName)->date;
          }else{
            // timestamp field
            $creationDateObj = DrupalDateTime::createFromTimestamp((int)$mappedEntity->get($dateCreationFieldName)->value);
          }
        }
      }
    }
    $creationDate = "";
    if(!empty($creationDateObj)){
      $creationDateObj->setTimezone(new \DateTimeZone('UTC'));
      $creationDate = $creationDateObj->format('Y-m-d\TH:i:s\Z');
      if(!empty($creationDate)){
        $graphItem->setDateDeCreation($creationDate);
      }      
    }
  }

  /**
   * Clean URL.
   * 
   * @param string $url
   * 
   * @return string
   */
  private function cleanUrl($url){
      $cleanUrl = '';

      if (UrlHelper::isValid($url, true)) {
          $cleanUrl = $url;
      } else {
          $url = "https://" . $url;
          if (UrlHelper::isValid($url, true)) {
              $cleanUrl = $url;
          }
      }
      return $cleanUrl;
  }

  /**
   * Custom Field Processing.
   * 
   * @param string $fieldValue
   * @param string $customProcessing
   * 
   * @return string
   */
  private function customFieldProcessing($fieldValue, $customProcessing){
    $processedValue = $fieldValue;
    switch ($customProcessing) {
      case 'remove_tags':
          $processedValue = Html::decodeEntities(strip_tags($fieldValue));
          break;
      default:
          break;
    }
    return $processedValue;
  }

}