Lodel
=====

Logiciel d'édition Électronique

Home page: http://www.lodel.org

Download: http://openedition.github.io/lodel/

E-Mail: lodel@lodel.org

Résumé
-------

Lodel est un logiciel d'édition électronique. Il permet de publier en ligne des
articles issus d'un traitement de texte.


Licence
-------

Lodel est un logiciel libre sous licence GPL Version 2. Lisez la licence dans le fichier
COPYING.


Lodel - Logiciel d'édition Électronique
----------------------------------------

Lodel est un logiciel d'édition électronique. Il est simple d'utilisation et
facile à adapter à des usages particuliers.

Les documents à publier peuvent être préparés dans un traitement de texte (Word,
OpenOffice.org, etc) ou édités directement en ligne.

Le design du site est défini par des gabarits écrits dans le langage Lodelscript.

Installation
------------

Pré-requis:
  - Utiliser son propre serveur linux, Lodel n'est pas utilisable sur hébergement dédié.
  - Serveur HTTP (nginx, apache) avec PHP
  - Serveur MYSQL
    - pour être utilisé avec OTX, il faut une valeur de max_allowed_packet et key_buffer très grande (16 M)


Marche à suivre:
  - Cloner de préférence la dernière version tagguée
  - Faire pointer le virtual host sur la racine de l'installation lodel.
  - L'utilisateur du serveur HTTP doit avoir les droits de lecture sur tous les fichiers.
  - Créer une base de donnée et un utilisateur ayant les droits de modification sur cette base.
  - Aller à l'adresse configurée avec un navigateur web, suivre les instructions.
  - Il faudra donner temporairement les droits d'écriture sur le dossier d'une instance de site.
  - Vérifer qu'à l'intérieur du dossier d'un site l'utilisateur du serveur HTTP a bien les droits d'écriture sur les dossiers:
      upload, docannexe, docannexe/file, docannexe/image, lodel/sources, lodel/icons
