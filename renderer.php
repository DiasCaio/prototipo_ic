<?php
class block_descricao_imagens_renderer extends plugin_renderer_base {
    public function render_block($content) {
        return html_writer::div($content, 'descricao-imagens-block');
    }
}
