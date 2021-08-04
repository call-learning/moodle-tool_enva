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
 * Tools for ENVA
 *
 * @package    tool_enva
 * @copyright  2020 CALL Learning
 * @author     Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Outils ENVA';

// Tools.
$string['managesurvey'] = 'Outils questionnaire avenir professionnel';
$string['managecohortsync'] = 'Synchronisation des cohortes et cours';
$string['managegroupsync'] = 'Synchronisation des groupes dans les cours';
$string['downloademptysurvey'] = 'Télécharger les étudiants avec une réponse vide à l\'enquête';
$string['deletesurveyinfo'] = 'Effacer les données d\'enquête';
$string['deleteyearoneemptysurvey'] = 'Effacer les résultats d\'enquêtes vides (année 1)';
$string['deletesurveyinfoconfirm'] = 'Confirmer l\'effacement';
$string['deleteyearoneemptysurveyconfirm'] = 'Confirmer l\'effacement';
$string['emptyyearonesurveydatatask'] = 'ENVA: Tâche de purge des résultats d\'enquête vides';

$string['groupsyncfile:def'] = 'Fichier de défition des groupes';
$string['groupsyncfile:def_help'] = 'Fichier de défition des synchronisation, doit contenir la correspondance entre un course et
 un ensemble de groupes. 2 colunnes à minima: courseid, groups. Groups contient une liste séparée par une virgule, contenant
 les groupes qui doivent être créés dans ce cours';
$string['cohortsyncfile:def'] = 'Fichier de défition des synchronisation cohorte';
$string['cohortsyncfile:def_help'] = 'Fichier de défition des synchronisation, doit contenir la correspondance entre
les cours, cohortes et roles. Il a au minimum 3 colonnes courseid, cohort_idnumber et role_shortname';
$string['tool/enva:managesurvey'] = 'Peut gerer les outils divers';
$string['tool/enva:managegroupsync'] = 'Peut gérer la synchronisation des groupes';
$string['tool/enva:managecohortsync'] = 'Peut gérer la synchronisation des cohortes';
$string['csvdelimiter'] = 'Délimiteur CSV';
$string['encoding'] = 'Encodage CSV';
$string['import'] = 'Importation';
$string['syncallcohortcourses'] = 'Synchronise toutes les cohortes de cours';
$string['invalidimportfile'] = 'Fichier d\'importation invalide ({$a})';
$string['headernotpresent'] = 'Entête non présent ({$a})';
$string['currentimportprogress'] = 'Progression';
$string['cannotopenimporter'] = 'Ne peux ouvrir l\'outil d\'importation';
$string['importgroupsync:error:cannotaddinstance'] = 'Ne peux ajouter une instance de groupe (Line:{$a})';
$string['importgroupsync:error:wrongcourse'] = 'Mauvais cours (Line:{$a})';
$string['importgroupsync:error:wronggroups'] = 'Mauvais groupe (Line:{$a})';
$string['importcohortsync:error:cannotaddinstance'] = 'Ne peut ajouter une instance de synchronisation cohorte (Line:{$a})';
$string['importcohortsync:error:cannotupdateinstance'] = 'Ne peut modifier une instance de synchronisation cohorte (Line:{$a})';
$string['importcohortsync:error:wrongcourse'] = 'Mauvais cours (Line:{$a})';
$string['importcohortsync:error:wrongcohort'] = 'Mauvaise cohorte (Line:{$a})';
$string['importcohortsync:error:wrongrole'] = 'Mauvais role (Line:{$a})';
$string['messageprovider:syncfinished'] = 'Synchronisation des cohortes terminée';
$string['settings:cohortstoreset'] = 'Cohortes a remettre à zéro pour les questionnaires avenir professionnel';
$string['settings:cohortstoreset_help'] = 'Identifiants numériques séparés par des virgules qui permettent de spécifier les
 cohortes concernées par les questionnaires avenir professionnel';
$string['sync:enrolmentname'] = 'outilenva::{$a->cohortname}({$a->rolename})';
$string['surveyparameters'] = 'Paramètres questionnaire avenir professionnel';
