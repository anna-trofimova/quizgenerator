<?php
require_once('../../config.php');
require_login();

$context = context_user::instance($USER->id);
require_capability('local/quizgenerator:use', $context);

global $DB;

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=preguntas_generadas.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['Archivo', 'Pregunta', 'Opciones', 'Respuesta Correcta']);

$questions = $DB->get_records('quizgenerator_questions', ['userid' => $USER->id], 'timecreated DESC');

foreach ($questions as $q) {
    $options = json_decode($q->options, true);
    $formatted = '';

    if (is_array($options)) {
        foreach ($options as $key => $val) {
            $formatted .= "$key) $val; ";
        }
    }

    fputcsv($output, [$q->filename, $q->questiontext, $formatted, $q->correctanswer]);
}

fclose($output);
exit;
