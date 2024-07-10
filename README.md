# Capdata RDF Connector

CapData RDF est un composant clé du projet CapData Opéra - France 2030,
porté par la Réunion des Opéras de France dans le cadre du programme France 2030. 
Ce module vise à faciliter l'alignement et l'exposition des données au format RDF, 
essentiel pour l'interopérabilité et le partage des données culturelles.

Pour une description complète du module, veuillez visiter la
[page du projet](https://www.drupal.org/project/capdata_connector).

## Configuration

Page de configuration propre au module: `admin/config/services/capdata-mapping`.

## Génération du fichier d'export

Commande drush pour générer le fichier d'export: `drush capdata-rdf-export --uri=https://opera-exemple.com`