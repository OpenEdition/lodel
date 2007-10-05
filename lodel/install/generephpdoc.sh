#!/bin/bash
#
#  LODEL - Logiciel d'Edition ELectronique.
#
#  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
#  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
#  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
#  Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
#  Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
#  Copyright (c) 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
#
#  Home page: http://www.lodel.org
#
#  E-Mail: lodel@lodel.org
#
#                            All Rights Reserved
#
#     This program is free software; you can redistribute it and/or modify
#     it under the terms of the GNU General Public License as published by
#     the Free Software Foundation; either version 2 of the License, or
#     (at your option) any later version.
#
#     This program is distributed in the hope that it will be useful,
#     but WITHOUT ANY WARRANTY; without even the implied warranty of
#     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#     GNU General Public License for more details.
#
#     You should have received a copy of the GNU General Public License
#     along with this program; if not, write to the Free Software
#     Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

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


