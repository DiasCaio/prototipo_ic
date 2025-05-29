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
        global $DB;
        mtrace('[local_imagedesc] Tarefa agendada iniciada.');

        $since = time() - 10800;
        mtrace('[local_imagedesc] Selecionando posts criados apos ' . date('c', $since));

        $sql = "SELECT * FROM {forum_posts}
                WHERE created > :since
                  AND message LIKE '%<img%'
                  AND message LIKE '%@@PLUGINFILE@@%'";
        $posts = $DB->get_records_sql($sql, ['since' => $since]);
        mtrace('[local_imagedesc] ' . count($posts) . ' posts encontrados para processamento.');

        $processed = [];
        foreach ($posts as $post) {
            $pid = $post->id;
            if (in_array($pid, $processed)) {
                mtrace("[local_imagedesc] Aviso: post ID $pid ja processado neste ciclo. Pulando.");
                continue;
            }
            $processed[] = $pid;

            mtrace("[local_imagedesc] ----- Início post ID $pid -----");
            $event = (object)['objectid' => $pid];
            try {
                \local_imagedesc\observers::update_post_description($event);
            } catch (\Throwable $e) {
                mtrace("[local_imagedesc] ERRO ao processar post ID $pid: " . $e->getMessage());
            }
            mtrace("[local_imagedesc] ----- Fim post ID $pid -----");
        }

        mtrace('[local_imagedesc] Tarefa agendada finalizada.');
    }
}
