<?php
require_once('../../config.php');
require_login();

$context = context_user::instance($USER->id);
require_capability('local/quizgenerator:use', $context);

$PAGE->set_url(new moodle_url('/local/quizgenerator/view.php'));
$PAGE->set_context($context);
$PAGE->set_title('Preguntas generadas');
$PAGE->set_heading('Preguntas generadas');

echo $OUTPUT->header();

// Título con icono y espaciado
echo html_writer::tag('h2', 'Tus preguntas generadas', ['class' => 'mb-4']);

global $DB;
$questions = $DB->get_records('quizgenerator_questions', ['userid' => $USER->id], 'timecreated DESC');

if ($questions) {
    echo html_writer::start_tag('div', ['class' => 'table-responsive']); 

    echo html_writer::start_tag('table', [
        'class' => 'table table-bordered table-striped table-hover table-sm',
        'style' => 'table-layout: fixed; word-wrap: break-word;'
    ]);

    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', 'Archivo');
    echo html_writer::tag('th', 'Pregunta');
    echo html_writer::tag('th', 'Opciones');
    echo html_writer::tag('th', 'Respuesta correcta');
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');

    echo html_writer::start_tag('tbody');

    foreach ($questions as $q) {
        $options = json_decode($q->options, true);

        $formatted_options = '';
        if (is_array($options)) {
            foreach ($options as $key => $val) {
                $formatted_options .= s($key) . ') ' . s($val) . '<br>';
            }
        }

        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', s($q->filename));
        echo html_writer::tag('td', s($q->questiontext));
        echo html_writer::tag('td', $formatted_options);
        echo html_writer::tag('td', s($q->correctanswer));
        echo html_writer::end_tag('tr');
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_tag('div'); 
} else {
    echo $OUTPUT->notification('No hay preguntas guardadas aún.', 'notifyinfo');
}



echo html_writer::start_div('d-flex gap-2 mt-4');

// Botón para volver
echo html_writer::link(
    new moodle_url('/local/quizgenerator/index.php'),
    'Volver al generador',
    ['class' => 'btn btn-secondary']
);

// Botón para descargar tabla 
echo html_writer::link(
    new moodle_url('/local/quizgenerator/export.php'),
    'Exportar como CSV',
    ['class' => 'btn btn-success']
);

echo html_writer::end_div();


echo $OUTPUT->footer();
