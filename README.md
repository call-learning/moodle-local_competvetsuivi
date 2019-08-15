# Compet Vet Suivi Local Plugin

Ce plugin permet de calculer les compétences acquises pour un utilisateur en fonction des UE complététees et d'une matrice de compétences.

Les données concernant les utilisateurs (completion des UEs) peuvent être synchronées en pointant sur un répertoire dans
lequel la liste des utilisateurs et UE complétées sont stockées.

Pour les matrices de compétence, il suffit de les télécharger dans la section de paramétrage du plugin. Chaque matrice pourra être appliquée
à une cohorte d'utilisateur.

Les paramètres du plugin se situent dans le menu adminstration ("Administration du Site") dans la partie générale (Notifications, ...)

# Synchronisation des données utilisateurs

La synchronisation se fait de manière périodique par une tâche Moodle. Dans les paramètre du plugin on spécifie
un répertoire dans lequel on versera le fichier contenant la liste des utilisateur et la complétion de leur UE.
Le fichier une fois traité est effacé. Ce qui permettra d'en verser un autre. Si aucun fichier n'est présent dans
le répertoire, aucune action n'est menée.
Cette tâche se déroule toute les 5 minutes. Cela conviendra si le volume de données n'est pas trop grand.
Dans tous les cas cela peut être changé dans les paramètres de la tâche. 

Alternativement on peut synchroniser la liste des utilisateurs par le script suivant:

php local/competvetsuivi/cli/uploaduserdata.php --file=<csv file>

# Gestion des matrices de compétences

Une matrice peut être rajoutée au Système dans la section de paramétrage du plugin
Elle pourra:
 * être mise à jour en chargeant un nouveau fichier
 * assignée à une ou plusieurs cohortes d'utilisateurs
 * Affichée : afin de voir les compétences 
 * effacée du système
 
 
# Format des données d'entrée

## Importation des utilisateurs

## Importation des données de matrice



## License ##

2019 CALL Learning <laurent@call-learning.fr>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <http://www.gnu.org/licenses/>.
