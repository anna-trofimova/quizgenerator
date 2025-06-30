<?php

//Llama API de OpenAI
function call_openai_api($prompt, $apiKey) {
    $data = [
        "model" => "gpt-4.1",
        "messages" => [
            ["role" => "user", "content" => $prompt]
        ],
        "temperature" => 0.3
    ];

    $headers = [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/chat/completions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    curl_close($ch);

    $decoded = json_decode($response, true);
    return $decoded['choices'][0]['message']['content'] ?? '';
}


function generate_multiple_choice_questions($inputText, $numQuestions = 3) {
    $apiKey = "_API_";

    $prompt = "Lee el siguiente texto y genera EXACTAMENTE $numQuestions preguntas tipo test con 4 opciones (a, b, c, d), en español. Usa este formato:

Pregunta 1: ...
a) ...
b) ...
c) ...
d) ...
Respuesta correcta: ...

Pregunta 2: ...
a) ...
b) ...
c) ...
d) ...
Respuesta correcta: ...

NO EXPLIQUES NADA.

=== CONTENIDO ===
" . substr($inputText, 0, 3000);

    return call_openai_api($prompt, $apiKey);
}

function generate_true_false_questions($inputText, $numQuestions = 3) {
    $apiKey = "_API_";
    $prompt = "Lee el siguiente texto y genera EXACTAMENTE $numQuestions preguntas de tipo Verdadero o Falso en español. Usa este formato:

Pregunta 1: [enunciado]
Respuesta: Verdadero

Pregunta 2: [enunciado]
Respuesta: Falso

NO EXPLIQUES NADA. NO CAMBIES EL FORMATO.

=== CONTENIDO ===
" . substr($inputText, 0, 3000);

    return call_openai_api($prompt, $apiKey);
}

function generate_questions_from_long_pdf($longtext, $max_parts = 3, $numQuestions = 3, $questionType = 'multiple_choice') {
    $paragraphs = explode("\n\n", wordwrap($longtext, 1500, "\n\n"));
    $parts = array_chunk($paragraphs, 5);
    $questions = '';
    $count = 0;

    foreach ($parts as $part) {
        if ($count >= $max_parts) break;
        $textblock = trim(implode("\n\n", $part));
        if (strlen($textblock) < 200) continue;

        if ($questionType === 'true_false') {
            $questions .= generate_true_false_questions($textblock, $numQuestions) . "\n\n";
        } else {
            $questions .= generate_multiple_choice_questions($textblock, $numQuestions) . "\n\n";
        }

        $count++;
    }

    return trim($questions);
}
