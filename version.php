<?php

/**
 * @package   local_quizgenerator 
 * @copyright 2020, Anna Trofimova a.trofimova.2020@alumnos.urjc.es
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_quizgenerator';
$plugin->version   = 2025050204; 
$plugin->requires  = 2024041900;                // Moodle 4.5.0 release (April 19, 2024)
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = 'v0.1 (Alpha)';

$plugin->dependencies = [
    'mod_forum' => 2022041900,
    'mod_data' => 2022041900,
];