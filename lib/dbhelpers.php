<?php

// Guarda las preguntas a BD 
function save_question_to_db($userid, $filename, $questiontext, $options, $correctanswer) {
    global $DB;

    $new_question = new stdClass();
    $new_question->userid = $userid;
    $new_question->filename = $filename;
    $new_question->questiontext = $questiontext;
    $new_question->options = $options;
    $new_question->correctanswer = $correctanswer;
    $new_question->timecreated = time();

    return $DB->insert_record('quizgenerator_questions', $new_question);
}

function parse_question_output($rawText, $limit = 3) {
    $results = [];

    // Dividir en bloques por pregunta
    $blocks = preg_split('/\n*Pregunta\s*\d+:/i', $rawText, -1, PREG_SPLIT_NO_EMPTY);

    foreach ($blocks as $block) {
        $block = trim($block);

        // Tipo test
        if (preg_match('/^(.*?)\n*a\)\s*(.*?)\n*b\)\s*(.*?)\n*c\)\s*(.*?)\n*d\)\s*(.*?)\n*Respuesta correcta:\s*([a-d])/is', $block, $matches)) {
            $results[] = [
                'question' => trim($matches[1]),
                'options' => json_encode([
                    'a' => trim($matches[2]),
                    'b' => trim($matches[3]),
                    'c' => trim($matches[4]),
                    'd' => trim($matches[5])
                ]),
                'correct' => trim($matches[6])
            ];
        }
        // Tipo verdadero/falso
        elseif (preg_match('/^(.*?)\n*Respuesta:\s*(Verdadero|Falso)/i', $block, $matches)) {
            $results[] = [
                'question' => trim($matches[1]),
                'options' => json_encode([
                    'a' => 'Verdadero',
                    'b' => 'Falso'
                ]),
                'correct' => strtolower($matches[2]) === 'verdadero' ? 'a' : 'b'
            ];
        }
    }

    return array_slice($results, 0, $limit);
}
