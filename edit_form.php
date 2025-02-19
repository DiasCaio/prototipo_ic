<?php
require_once($CFG->dirroot.'/blocks/edit_form.php');

class block_descricao_imagens_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
        $mform->addElement('text', 'title', get_string('pluginname', 'block_descricao_imagens'));
        $mform->setType('title', PARAM_TEXT);
    }
}
