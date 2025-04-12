<?php
namespace local_imagedesc\task;

defined('MOODLE_INTERNAL') || die();

class process_pending_posts extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('task_process_pending_posts', 'local_imagedesc');
    }

    public function get_run_if_component_enabled() {
        return true;
    }

    public function execute() {
        error_log('[local_imagedesc] Tarefa agendada foi iniciada.');
        global $DB;

        // Verifica se a tabela local_imagedesc_posts existe.
        $dbman = $DB->get_manager();
        $table = new \xmldb_table('local_imagedesc_posts');
        $hastrackingtable = $dbman->table_exists($table);

        if (!$hastrackingtable) {
            error_log('[local_imagedesc] Tabela local_imagedesc_posts não existe. A tarefa continuará sem o controle de posts já processados.');
        }

        error_log('[local_imagedesc] Cron rodando: verificando posts com imagem ainda não processados.');
        $since = time() - 600; // Ajuste para 3600 se quiser processar posts de até 1 hora atrás.

        // Monta o subselect se a tabela de tracking existir.
        $subquery = $hastrackingtable ? "AND id NOT IN (SELECT postid FROM {local_imagedesc_posts} WHERE status = 'ok')" : "";

        $sql = "SELECT * FROM {forum_posts}
                WHERE created > :since
                AND message LIKE '%<img%'
                AND message LIKE '%@@PLUGINFILE@@%'
                $subquery";

        $posts = $DB->get_records_sql($sql, ['since' => $since]);

        foreach ($posts as $post) {
            error_log("[local_imagedesc] Processando post ID {$post->id}...");
            $event = (object)['objectid' => $post->id];
            try {
                \local_imagedesc\observers::update_post_description($event);

                if ($hastrackingtable) {
                    $record = (object)[
                        'postid' => $post->id,
                        'status' => 'ok',
                        'timemodified' => time()
                    ];
                    $DB->insert_record('local_imagedesc_posts', $record, false);
                }
            } catch (\Throwable $e) {
                error_log("[local_imagedesc] ERRO ao processar post ID {$post->id}: " . $e->getMessage());
                if ($hastrackingtable) {
                    $record = (object)[
                        'postid' => $post->id,
                        'status' => 'erro',
                        'timemodified' => time()
                    ];
                    $DB->insert_record('local_imagedesc_posts', $record, false);
                }
            }
        }

        error_log('[local_imagedesc] Cron finalizado.');
    }
}
