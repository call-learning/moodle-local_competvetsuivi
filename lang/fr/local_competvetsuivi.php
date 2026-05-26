<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     local_competvetsuivi
 * @category    string
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['cohortassigned'] = 'Affectation des cohortes à la matrice mise à jour';
$string['competencies'] = 'Compétences';
$string['competencyfullname'] = 'Nom Complet';
$string['competvetgeneralsettings'] = 'Paramètres Généraux';
$string['competvetmanagement'] = 'Compet Vetsuivi';
$string['competvetuserdatamgmt'] = 'Gestion de l\'importation des données Utilisateur';
$string['contribution:title'] = 'Details de la répartition des connaissances sous-jacentes et des compétences visées par l\'{$a}';
$string['currentprogress'] = 'Avancement';
$string['cvsquestionbankdefaultcategoryname_help'] = 'Il faut que toutes les question concernant l\'autoevaluation soient dans la même banque
 de questions. Et cette banque de question doit être catégorisée. Normalement le nom court de la matrice doit correspondre au nom de la
 banque de question. Si aucune banque de question ne correspond, on regarde la valeur de ce paramètre et on essaie de trouver une banque
 de question avec cette catégorie.
 (voir: https://docs.moodle.org/38/en/Question_categories#Category_Set_Up_and_Management).
 Autre prérequis: l\'idnumber ou le numéro identification unique doit être exactement le nom court de la compétences (par exemple
 COPREV ou COPREV.1).
';
$string['directorydoesnotexist'] = 'Directory does not exist';
$string['doghnutchartheight'] = 'Hauteur du graphe en Doghnut';
$string['doghnutchartheight_desc'] = 'Cela affecte tous les graphes UC vs UE';
$string['doghnutlegend:color'] = 'Couleur';
$string['doghnutlegend:competency'] = 'Compétence';
$string['doghnutlegend:comptype'] = 'Types';
$string['doghnutlegend:value'] = 'Valeur';
$string['enablecompetvetsuivi'] = 'Activer le plugin Compet Vetsuivi';
$string['enablecompetvetsuivi_help'] = 'Activer le plugin Compet Vetsuivi';
$string['foundnomatchingcompetency'] = 'Pas de Compétence trouvées';
$string['foundnomatchingue'] = 'Pas d\'UE/UC trouvées';
$string['foundtoomanymatchingcompetency'] = 'Trop de Compétences correspondent aux critères';
$string['foundtoomanymatchingue'] = 'Trop d\'UE/UC correspondent aux critères';
$string['graphtitle:level0'] = 'Macro-Compétences';
$string['graphtitle:level1'] = 'Compétences';
$string['graphtitle:level2'] = 'Capacités';
$string['home'] = 'Macro-compétences';
$string['importerror'] = 'Erreur d\'importation: {$a}';
$string['legend'] = 'Légende';
$string['managematrix'] = 'Gérer les matrices de compétences';
$string['matrix'] = 'Matrice';
$string['matrix:add'] = 'Ajouter une matrice';
$string['matrix:assigncohorts'] = 'Affecter une cohorte';
$string['matrix:delete'] = "Effacer la matrice";
$string['matrix:edit'] = "Editer la matrice";
$string['matrix:list'] = 'Toutes les matrices';
$string['matrix:view'] = 'Voir la matrice {$a}';
$string['matrix:viewdata'] = 'Voir la matrice pour l\'utilisateur';
$string['matrix:viewtestresults'] = 'Voir les résultats du test';
$string['matrixadded'] = 'Matrice ajoutée';
$string['matrixaddedlog'] = 'Nombre de compétences chargées {$a->compcount}, Nombre de macrocompétences {$a->macrocompcount},
 Nombre d\'UE/UC {$a->uecount}.';
$string['matrixassignedcohorts'] = 'Cohorts';
$string['matrixcohortsassignment'] = 'Affectation d\'une matrice a une cohorte';
$string['matrixcohortsassignment_help'] = 'Permet d\'ajouter un ou plusieurs utilisateurs à une matrice via les cohortes';
$string['matrixcomptype:ability'] = 'Compétences';
$string['matrixcomptype:evaluation'] = 'Evaluations';
$string['matrixcomptype:knowledge'] = 'Connaissances sous-jacentes';
$string['matrixcomptype:objective'] = 'Objectifs';
$string['matrixdeleted'] = 'Matrice effacée';
$string['matrixfileadd'] = 'Fichier de matrice';
$string['matrixfileadd_help'] = 'Uploader un fichier de matrice. Ce doit-être un fichier Excel avec une structure spécifique.';
$string['matrixinfoupdated'] = 'Matrice mise à jour';
$string['matrixname'] = 'Nom complet';
$string['matrixshortname'] = 'Nom court';
$string['matrixuevscomp:viewgraphs'] = 'UC vs Competencies';
$string['matrixuevscompgraphtitle:global'] = 'Contribution de {$a->uename} à la progression générale';
$string['matrixuevscompgraphtitle:semester'] = 'Contribution de {$a->uename} au semestre';
$string['matrixuevscomptitle'] = 'Contribution de l\'UC {$a->uename} dans les compétences et capacités';
$string['matrixupdated'] = 'Contenu de la matrice mis à jour: {$a}.';
$string['matrixviewdatatitle'] = 'Données de la matrice {$a->matrixname} pour l\'utilisateur {$a->username}';
$string['matrixviewtitle'] = 'Voir la matrice {$a}';
$string['milestone'] = 'Niveau à atteindre à la fin du semestre (n)';
$string['nomatrixerror'] = 'Pas de feuille avec le préfixe ${a} dans le fichier uploadé';
$string['pluginname'] = 'Outils CompetVetSuivi';


$string['progresschartheight'] = 'Hauteur du graphe de progression';
$string['progresschartheight_desc'] = 'Cela affecte tous les graphes de progression';
$string['questionbankcategoryname'] = 'Nom de la catégorie de la banque de question';
$string['questionbankcategoryname_desc'] = 'Nom de la catégorie de la banque de question desquelles sont tirées les questions';
$string['readless'] = 'moins';
$string['readmore'] = 'plus';
$string['repartition:title'] = 'Compétences visées par l\'{$a}';
$string['selfassessment'] = 'Auto-évaluation';
$string['semester'] = 'Semestre';
$string['semester:x'] = 'Semestre {$a}';
$string['userdatacsvuploadtask'] = 'Importer les données utilisateurs en CSV';
$string['userdatadirectupload'] = 'Importation immédiate des données utilisateur';
$string['userdatafile'] = 'Fichier des données utilisateur';
$string['userdatafile_desc'] = 'Fichier CSV contentant les données utilisateur';
$string['userdatafile_help'] = 'Fichier CSV contentant les données utilisateur';
$string['userdatafilepath'] = 'Répertoire avec les données utilisateurs en CSV';
$string['userdatafilepath_desc'] = 'Répertoire avec les données utilisateurs en CSV. Les fichiers seront effacés une fois importés';
$string['userdatafilepath_help'] = 'Répertoire avec les données utilisateurs en CSV. Les fichiers seront effacés une fois importés';
$string['userdataimported'] = 'Données utilisateur importées';
$string['userdatainsertednb'] = 'Nombre d\'utilisateurs insérés';
$string['userdataupdatednb'] = 'Nombre d\'utilisateurs mis à jour';
$string['userdatauploaddate'] = 'Date';
$string['usertestresults'] = 'Résultats utilisateurs';
