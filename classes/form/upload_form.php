<?php

namespace local_quizgenerator\form;

require_once("$CFG->libdir/formslib.php");

// Se usa un form de moodle que existe ya para subir pdf

class upload_form extends \moodleform {
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'fileheader', get_string('uploadpdf', 'local_quizgenerator'));

        $mform->addElement('filepicker', 'userfile', get_string('uploadpdf', 'local_quizgenerator'), null, [
            'accepted_types' => ['.pdf'],
            'maxbytes' => 10485760, 
        ]);
        $mform->addRule('userfile', null, 'required', null, 'client');

        $this->add_action_buttons(true, get_string('submit', 'local_quizgenerator'));
    }
}
