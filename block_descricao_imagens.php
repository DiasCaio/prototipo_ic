<?php
defined('MOODLE_INTERNAL') || die();

class block_descricao_imagens extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_descricao_imagens');
    }

    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        global $CFG;
        $form_url = new moodle_url('/blocks/descricao_imagens/processa_imagem.php');

        $this->content = new stdClass();
        $this->content->text = '
            <form action="' . $form_url . '" method="post" enctype="multipart/form-data">
                <label for="imagem">Envie uma imagem:</label>
                <input type="file" name="imagem" accept="image/*" required>
                <button type="submit">Enviar</button>
            </form>
        ';

        return $this->content;
    }
}
