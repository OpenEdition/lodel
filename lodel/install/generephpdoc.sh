#!/bin/bash
#
# LODEL - Logiciel d'Édition ÉLectronique.
# @license    GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
# @authors    See COPYRIGHT file

# Script de creation/mise à jour de la documentation

# Racine du dépôt CVS
LODELROOT=../..


# Titre de la documentation
TITLE="Lodel - Documentation Code Source"
# Nom du paquet par defaut
PACKAGES=lodel
# Repertoires qui seront analyses pour generer la doc
PATH_PROJECT=$PWD/$LODELROOT/lodel,$PWD/$LODELROOT/lodeladmin
# Chemin vers phpdoc (ou nom de l'executable phpdoc)
PATH_PHPDOC=phpdoc
# Repertoire où sera stocké la documentation
PATH_DOCS=$PWD/$LODELROOT/documentation

# Format de sortie
OUTPUTFORMAT=HTML
CONVERTER=Smarty
TEMPLATE=PHP

# Analyse les blocs private
PRIVATE=on

# Liste des fichiers ignorÃs
IGNORE="*.html"

# make documentation
phpdoc -q -i $IGNORE -d $PATH_PROJECT -t $PATH_DOCS -ti "$TITLE" -dn $PACKAGES -o $OUTPUTFORMAT:$CONVERTER:$TEMPLATE -pp $PRIVATE -s


