<?php
/**
 * Plugin Name: Verificador de Números
 * Description: Plugin para verificação de números de WhatsApp cadastrados.
 * Version: 1.0
 * Author: Rapha Mendonça
 * Author URI: https://raphamendonca.com
 * Text Domain: verificador-numeros
 */

// Prevenir acesso direto
if (!defined('ABSPATH')) {
    exit;
}

class VerificadorNumeros {
    private static $instance = null;
    private $table_name;
    private $logs_table;
    private $max_attempts = 50;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'verificador_numeros';
        $this->logs_table = $wpdb->prefix . 'verificador_numeros_logs';
        
        // Iniciar sessão se não existir
        if (!session_id()) {
            session_start();
        }
        
        // Ações de inicialização
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Ações de importação/exportação
        add_action('admin_post_import_numeros', array($this, 'handle_import'));
        add_action('admin_post_export_numeros', array($this, 'handle_export'));
        add_action('admin_post_export_logs', array($this, 'handle_export_logs'));
        
        // Shortcode
        add_shortcode('verificador_numeros', array($this, 'shortcode_output'));
        
        // AJAX handlers
        add_action('wp_ajax_verificar_numero', array($this, 'verificar_numero'));
        add_action('wp_ajax_nopriv_verificar_numero', array($this, 'verificar_numero'));
        add_action('wp_ajax_excluir_log', array($this, 'ajax_excluir_log'));
        add_action('wp_ajax_excluir_todos_logs', array($this, 'ajax_excluir_todos_logs'));

        // Ativação e desativação
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Limpar logs antigos diariamente
        add_action('verificador_numeros_limpar_logs', array($this, 'limpar_logs_antigos'));
    }

    public function register_settings() {
        register_setting(
            'verificador_numeros_options',
            'verificador_numeros_options',
            array(
                'type' => 'array',
                'default' => array(
                    'mensagem_sucesso' => 'Número encontrado em nossa base!',
                    'mensagem_erro' => 'Número não encontrado em nossa base.',
                    'retencao_logs' => '7'
                )
            )
        );
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Tabela principal para números
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            numero VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY numero (numero)
        ) $charset_collate;";

        // Tabela de logs
        $sql_logs = "CREATE TABLE IF NOT EXISTS {$this->logs_table} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            numero VARCHAR(20) NOT NULL,
            ip VARCHAR(45) NOT NULL,
            resultado TINYINT(1) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($sql_logs);

        // Opções padrão
        add_option('verificador_numeros_options', array(
            'mensagem_sucesso' => 'Número encontrado em nossa base!',
            'mensagem_erro' => 'Número não encontrado em nossa base.',
            'retencao_logs' => '7'
        ));

        // Agenda limpeza de logs
        if (!wp_next_scheduled('verificador_numeros_limpar_logs')) {
            wp_schedule_event(time(), 'daily', 'verificador_numeros_limpar_logs');
        }
    }

    public function deactivate() {
        wp_clear_scheduled_hook('verificador_numeros_limpar_logs');
    }

    public function add_admin_menu() {
        add_menu_page(
            'Verificador de Números',
            'Verificador de Números',
            'manage_options',
            'verificador-numeros',
            array($this, 'admin_page'),
            'dashicons-smartphone'
        );

        add_submenu_page(
            'verificador-numeros',
            'Configurações',
            'Configurações',
            'manage_options',
            'verificador-numeros-config',
            array($this, 'config_page')
        );

        add_submenu_page(
            'verificador-numeros',
            'Logs',
            'Logs',
            'manage_options',
            'verificador-numeros-logs',
            array($this, 'logs_page')
        );
    }

    public function admin_page() {
        include plugin_dir_path(__FILE__) . 'templates/admin/main.php';
    }

    public function config_page() {
        include plugin_dir_path(__FILE__) . 'templates/admin/config.php';
    }

    public function logs_page() {
        include plugin_dir_path(__FILE__) . 'templates/admin/logs.php';
    }

    public function shortcode_output($atts) {
        ob_start();
        include plugin_dir_path(__FILE__) . 'templates/frontend/form.php';
        return ob_get_clean();
    }

    private function get_attempts() {
        if (!isset($_SESSION['verificador_attempts'])) {
            $_SESSION['verificador_attempts'] = 0;
        }
        return intval($_SESSION['verificador_attempts']);
    }

    private function increment_attempts() {
        if (!isset($_SESSION['verificador_attempts'])) {
            $_SESSION['verificador_attempts'] = 0;
        }
        $_SESSION['verificador_attempts']++;
    }

    public function verificar_numero() {
        check_ajax_referer('verificar_numero', 'nonce');

        // Verifica limite de tentativas
        if ($this->get_attempts() >= $this->max_attempts) {
            wp_send_json_error(array(
                'message' => 'Limite de tentativas excedido. Recarregue a página para continuar.'
            ));
        }

        $numero = sanitize_text_field($_POST['numero']);
        $numero = preg_replace('/[^0-9]/', '', $numero);

        // Incrementa tentativas
        $this->increment_attempts();

        global $wpdb;
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE numero = %s",
            $numero
        ));

        // Registra o log
        $wpdb->insert(
            $this->logs_table,
            array(
                'numero' => $numero,
                'ip' => $_SERVER['REMOTE_ADDR'],
                'resultado' => $exists ? 1 : 0
            ),
            array('%s', '%s', '%d')
        );

        $options = get_option('verificador_numeros_options');
        $mensagem_sucesso = !empty($options['mensagem_sucesso']) ? $options['mensagem_sucesso'] : 'Número encontrado em nossa base!';
        $mensagem_erro = !empty($options['mensagem_erro']) ? $options['mensagem_erro'] : 'Número não encontrado em nossa base.';

        if ($exists) {
            wp_send_json_success(array('message' => $mensagem_sucesso));
        } else {
            wp_send_json_error(array('message' => $mensagem_erro));
        }
    }

    public function handle_import() {
        if (!current_user_can('manage_options')) {
            wp_die('Permissão negada.');
        }

        check_admin_referer('import_numeros_action', 'import_numeros_nonce');

        if (!isset($_FILES['import_file'])) {
            wp_die('Nenhum arquivo enviado.');
        }

        $file = $_FILES['import_file'];
        if ($file['type'] !== 'text/csv') {
            wp_die('Por favor, envie um arquivo CSV.');
        }

        $handle = fopen($file['tmp_name'], 'r');
        $count = 0;

        if ($handle !== false) {
            while (($data = fgetcsv($handle)) !== false) {
                if (!empty($data[0])) {
                    $numero = preg_replace('/[^0-9]/', '', $data[0]);
                    if (strlen($numero) === 11) {
                        $this->adicionar_numero($numero);
                        $count++;
                    }
                }
            }
            fclose($handle);
        }

        wp_redirect(add_query_arg(
            array(
                'page' => 'verificador-numeros',
                'imported' => $count
            ),
            admin_url('admin.php')
        ));
        exit;
    }

    public function handle_export() {
        if (!current_user_can('manage_options')) {
            wp_die('Permissão negada.');
        }

        check_admin_referer('export_numeros_action', 'export_numeros_nonce');

        global $wpdb;
        $numeros = $wpdb->get_results("SELECT numero FROM {$this->table_name} ORDER BY created_at DESC");

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=numeros.csv');

        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        foreach ($numeros as $row) {
            $numero_formatado = preg_replace('/([0-9]{2})([0-9]{5})([0-9]{4})/', '($1) $2-$3', $row->numero);
            fputcsv($output, array($numero_formatado));
        }

        fclose($output);
        exit;
    }

    public function handle_export_logs() {
        if (!current_user_can('manage_options')) {
            wp_die('Permissão negada.');
        }

        check_admin_referer('export_logs_action', 'export_logs_nonce');

        global $wpdb;
        $logs = $wpdb->get_results("SELECT * FROM {$this->logs_table} ORDER BY created_at DESC");

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=logs-verificacao.csv');

        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Cabeçalhos
        fputcsv($output, array('Data/Hora', 'Número', 'IP', 'Resultado'));

        foreach ($logs as $log) {
            $numero_formatado = preg_replace('/([0-9]{2})([0-9]{5})([0-9]{4})/', '($1) $2-$3', $log->numero);
            $resultado = $log->resultado ? 'Encontrado' : 'Não encontrado';
            
            fputcsv($output, array(
                date_i18n('d/m/Y H:i:s', strtotime($log->created_at)),
                $numero_formatado,
                $log->ip,
                $resultado
            ));
        }

        fclose($output);
        exit;
    }

    public function limpar_logs_antigos() {
        $options = get_option('verificador_numeros_options');
        if (empty($options['retencao_logs'])) return;

        $dias = intval($options['retencao_logs']);
        if ($dias <= 0) return;

        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->logs_table} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $dias
        ));
    }

    public function adicionar_numero($numero) {
        $numero = preg_replace('/[^0-9]/', '', $numero);
        
        global $wpdb;
        return $wpdb->insert(
            $this->table_name,
            array('numero' => $numero),
            array('%s')
        );
    }

    public function excluir_numero($id) {
        global $wpdb;
        return $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
    }

    public function excluir_log($id) {
        global $wpdb;
        return $wpdb->delete(
            $this->logs_table,
            array('id' => $id),
            array('%d')
        );
    }

    public function excluir_todos_logs() {
        global $wpdb;
        return $wpdb->query("TRUNCATE TABLE {$this->logs_table}");
    }

    public function ajax_excluir_log() {
        check_ajax_referer('excluir_log_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permissão negada.'));
        }

        $id = intval($_POST['id']);
        if ($this->excluir_log($id)) {
            wp_send_json_success();
        } else {
            wp_send_json_error(array('message' => 'Erro ao excluir log.'));
        }
    }

    public function ajax_excluir_todos_logs() {
        check_ajax_referer('excluir_todos_logs_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permissão negada.'));
        }

        if ($this->excluir_todos_logs()) {
            wp_send_json_success();
        } else {
            wp_send_json_error(array('message' => 'Erro ao excluir logs.'));
        }
    }
}

// Função AJAX do admin modificada
add_action('wp_ajax_add_numero', 'admin_add_numero');
function admin_add_numero() {
    check_ajax_referer('add_numero_action', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permissão negada.'));
    }

    $numero = sanitize_text_field($_POST['numero']);
    $numero_limpo = preg_replace('/[^0-9]/', '', $numero);
    
    // Verificar se o número já existe antes de tentar inserir
    global $wpdb;
    $table_name = $wpdb->prefix . 'verificador_numeros';
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_name} WHERE numero = %s",
        $numero_limpo
    ));
    
    if ($exists) {
        wp_send_json_error(array('message' => 'Este número já está cadastrado na base de dados.'));
        return;
    }
    
    $instance = VerificadorNumeros::get_instance();
    
    if ($instance->adicionar_numero($numero_limpo)) {
        wp_send_json_success();
    } else {
        wp_send_json_error(array('message' => 'Erro ao adicionar número.'));
    }
}

// Inicialização do plugin
function verificador_numeros() {
    return VerificadorNumeros::get_instance();
}

verificador_numeros();
