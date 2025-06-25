<?php
/**
 * @package   local_quizgenerator 
 * @author    Anna Trofimova
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/vendor/autoload.php'); // PDF parser
require_once(__DIR__ . '/lib/openai.php');
require_once(__DIR__ . '/lib/dbhelpers.php');

require_login();
require_capability('local/quizgenerator:use', context_system::instance());

use Smalot\PdfParser\Parser;

$context = context_user::instance($USER->id);

// Configurar la página
$PAGE->set_url(new moodle_url('/local/quizgenerator/index.php'));
$PAGE->set_context($context);
$PAGE->set_title('AI Quiz Generator');
$PAGE->set_heading('AI Quiz Generator Plugin');

require_once(__DIR__ . '/classes/form/upload_form.php');

$form = new \local_quizgenerator\form\upload_form();

// Recuperar parámetros GET después de redirect
$generated_itemid = optional_param('generated', null, PARAM_INT);
$error = optional_param('error', null, PARAM_INT);
$show_success = false;


// Boton de generar las preguntas 
if (optional_param('generate', false, PARAM_BOOL)) {
    $generated_itemid = required_param('itemid', PARAM_INT);
    $fs = get_file_storage();

    // Obtener el archivo subido según el itemid
    $files = $fs->get_area_files($context->id, 'local_quizgenerator', 'pdfs', $generated_itemid, 'timemodified', false);
    foreach ($files as $file) {
    if (!$file->is_directory()) {

        // Parsear el archivo PDF
        $parser = new Parser();
        $tempfile = $file->copy_content_to_temp();
        $pdf = $parser->parseFile($tempfile);
        $text = $pdf->getText();

        $num_questions = isset($_POST['num_questions']) ? intval($_POST['num_questions']) : 3;
        $question_type = $_POST['question_type'] ?? 'multiple_choice';

        if ($num_questions < 1 || $num_questions > 5) {
            $num_questions = 3;
        }
        if (!in_array($question_type, ['multiple_choice', 'true_false'])) {
            $question_type = 'multiple_choice';
        }

        // Generar las preguntas
        $generated_questions = generate_questions_from_long_pdf($text, 3, $num_questions, $question_type);

           if (!empty($generated_questions)) {
            $questionList = parse_question_output($generated_questions, $num_questions);

            foreach ($questionList as $questionData) {
                save_question_to_db(
                $USER->id,
                $file->get_filename(),
                $questionData['question'],
                $questionData['options'],
                $questionData['correct']
                );
            }

                // Muestrar el mensaje mientras las preguntas estan generando
                echo $OUTPUT->header();
                $url = new moodle_url('/local/quizgenerator/index.php', ['generated' => $file->get_itemid()]);
                ?>
                    <div class="loading-message text-center" style="font-weight: bold; color: #007bff; font-size: 1.2em; margin-top: 2em;">
                        Generando preguntas… por favor, espera unos segundos… </div>

                    <script>
                        setTimeout(() => { window.location.href = '<?php echo $url; ?>'; }, 2000);
                    </script>

                <?php
                echo $OUTPUT->footer();

        } else {

        // En el caso de errores 
            echo $OUTPUT->header();
            echo html_writer::div(
                ' No se pudieron extraer preguntas del texto generado.', 'notifyproblem',
                ['style' => 'font-weight: bold; color: #cc0000; font-size: 1.1em; margin-top: 2em;']
            );
            echo html_writer::script("
                setTimeout(() => {
                window.location.href = '" . new moodle_url('/local/quizgenerator/index.php', ['error' => 1]) . "';}, 2000);
            ");
            echo $OUTPUT->footer();
        }
            break;
        }
    }
}

// Borrar el PDF subido
if (optional_param('delete', false, PARAM_BOOL)) {
    $delete_itemid = required_param('itemid', PARAM_INT);
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'local_quizgenerator', 'pdfs', $delete_itemid);
    redirect($PAGE->url, 'Archivo eliminado correctamente.', 2);
}

// Borrar preguntas generadas antes
if (optional_param('delete_questions', false, PARAM_BOOL)) {
    $filename = required_param('filename', PARAM_FILE);
    $DB->delete_records('quizgenerator_questions', [
        'userid' => $USER->id,
        'filename' => $filename
    ]);
    redirect($PAGE->url, 'Preguntas eliminadas correctamente.', 2);
}

// Subir  nuevo PDF 
if ($form->is_cancelled()) {
    redirect(new moodle_url('/my'));
} else if ($data = $form->get_data()) {
    $draftitemid = file_get_submitted_draft_itemid('userfile');
    $itemid = time();
    file_save_draft_area_files(
        $draftitemid,
        $context->id,
        'local_quizgenerator',
        'pdfs',
        $itemid
    );
    $show_success = true;
}

echo $OUTPUT->header();

// Mostrar notificaciones si es necesario
if (!empty($show_success)) {
    echo $OUTPUT->notification('Archivo subido correctamente.', 'notifysuccess');
}
if ($error) {
    echo $OUTPUT->notification(' No se pudieron extraer preguntas del texto generado.', 'notifyproblem');
}

if ($generated_itemid) {
    echo $OUTPUT->notification('Las preguntas han sido generadas correctamente.', 'notifysuccess');
}

// Mostrar archivos y preguntas generadas
$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'local_quizgenerator', 'pdfs', false, 'timemodified', false);

// Obtener todas las preguntas del usuario actual
$groupedQuestions = [];
$records = $DB->get_records('quizgenerator_questions', ['userid' => $USER->id]);

foreach ($records as $q) {
    $groupedQuestions[$q->filename][] = [
        'question' => $q->questiontext,
        'options' => json_decode($q->options, true),
        'correct' => $q->correctanswer
    ];
}

$parser = new Parser();
$filesdata = [];

foreach ($files as $file) {
    if ($file->is_directory()) {
        continue;
    }

    $tempfile = $file->copy_content_to_temp();
    $text = $parser->parseFile($tempfile)->getText();

    // Leer preguntas generadas desde la base de datos (
    $questions = [];
    $records = $DB->get_records('quizgenerator_questions', [
        'userid' => $USER->id,
        'filename' => $file->get_filename()
    ]);
    foreach ($records as $q) {
        $questions[] = $q->questiontext . "\n\nOpciones:\n" .
            implode("\n", array_map(
                fn($k, $v) => "$k) $v",
                array_keys(json_decode($q->options, true)),
                array_values(json_decode($q->options, true))
            )) . "\nRespuesta correcta: " . $q->correctanswer;
    }

    $filesdata[] = [
        'filename' => $file->get_filename(),
        'itemid' => $file->get_itemid(),
        'excerpt' => s(substr($text, 0, 3000)),
        'url' => $PAGE->url,
        'questions' => $questions
    ];
}


echo $OUTPUT->render_from_template('local_quizgenerator/view', [
    'uploadform' => $form->render(),
    'files' => $filesdata,
    'groupedquestions' => $groupedQuestions,
    'viewquestionsurl' => (new moodle_url('/local/quizgenerator/view.php'))->out()
]);


echo html_writer::script("
    const generateButtons = document.querySelectorAll('input[name=\"generate\"]');
    const loadingMessage = document.querySelector('.loading-message');

    generateButtons.forEach(button => {
        button.addEventListener('click', () => {
            if (loadingMessage) {
                loadingMessage.style.display = 'block';
            }
        });
    });
");

echo $OUTPUT->footer();
