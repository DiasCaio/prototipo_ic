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
            <form id="descricao-imagens-form" action="' . $form_url . '" method="post" enctype="multipart/form-data">
                <label for="imagem">Envie uma imagem:</label>
                <input type="file" name="imagem" id="imagem" accept="image/*" required>
                <button type="submit">Enviar</button>
            </form>
            <div id="descricao-output" style="margin-top:10px;"></div>

            <script>
                document.getElementById("descricao-imagens-form").addEventListener("submit", function(event) {
                    event.preventDefault();
                    var formData = new FormData(this);
                    var outputDiv = document.getElementById("descricao-output");
                    outputDiv.innerHTML = "<p>Processando a imagem...</p>";

                    fetch("' . $form_url . '", {
                        method: "POST",
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        outputDiv.innerHTML = data.descricao ? "<h3>Descrição:</h3><p>" + data.descricao + "</p>" : "<p style=\'color:red;\'>" + data.erro + "</p>";
                    })
                    .catch(error => {
                        outputDiv.innerHTML = "<p style=\'color:red;\'>Erro ao conectar com a API.</p>";
                    });
                });
            </script>
        ';

        return $this->content;
    }
}
