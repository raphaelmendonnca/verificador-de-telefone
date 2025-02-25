<?php
if (!defined('ABSPATH')) exit;

// Verifica permissões
if (!current_user_can('manage_options')) {
    wp_die(__('Você não tem permissão para acessar esta página.', 'verificador-numeros'));
}

global $wpdb;
$table_name = $wpdb->prefix . 'verificador_numeros_logs';

// Paginação
$page = isset($_GET['paged']) ? abs((int)$_GET['paged']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Total de registros
$total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
$total_pages = ceil($total / $limit);

// Obtém os logs
$logs = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d",
        $limit,
        $offset
    )
);
?>

<div class="wrap">
    <h1>Logs de Verificação</h1>

    <?php if (!empty($logs)): ?>
        <div class="tablenav top">
            <div class="actions bulkactions">
                <button type="button" class="button action exportar-logs">Exportar CSV</button>
                <button type="button" class="button action excluir-todos-logs">Excluir Todos</button>
            </div>
        </div>

        <div class="card">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Número Verificado</th>
                        <th>IP</th>
                        <th>Resultado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo esc_html(date_i18n('d/m/Y H:i:s', strtotime($log->created_at))); ?></td>
                            <td><?php 
                                $numero_formatado = preg_replace('/([0-9]{2})([0-9]{5})([0-9]{4})/', '($1) $2-$3', $log->numero);
                                echo esc_html($numero_formatado);
                            ?></td>
                            <td><?php echo esc_html($log->ip); ?></td>
                            <td>
                                <?php if ($log->resultado): ?>
                                    <span class="status-success">Encontrado</span>
                                <?php else: ?>
                                    <span class="status-error">Não encontrado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="#" class="excluir-log" data-id="<?php echo esc_attr($log->id); ?>">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <span class="displaying-num">
                            <?php printf(_n('%s item', '%s itens', $total, 'verificador-numeros'), number_format_i18n($total)); ?>
                        </span>
                        <span class="pagination-links">
                            <?php
                            echo paginate_links(array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => __('&laquo;'),
                                'next_text' => __('&raquo;'),
                                'total' => $total_pages,
                                'current' => $page
                            ));
                            ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="card">
            <p>Nenhum log encontrado.</p>
        </div>
    <?php endif; ?>

    <!-- Form oculto para exportação -->
    <form id="exportar-logs-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:none;">
        <?php wp_nonce_field('export_logs_action', 'export_logs_nonce'); ?>
        <input type="hidden" name="action" value="export_logs">
    </form>
</div>

<style>
.card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-top: 20px;
    padding: 20px;
}

.status-success {
    color: #46b450;
    font-weight: 500;
}

.status-error {
    color: #dc3232;
    font-weight: 500;
}

.tablenav-pages {
    margin-top: 1em;
    text-align: right;
}

.pagination-links {
    margin-left: 10px;
}

.pagination-links a,
.pagination-links span {
    padding: 4px 8px;
    margin: 0 3px;
    border: 1px solid #ddd;
    border-radius: 3px;
    background: #f7f7f7;
    text-decoration: none;
}

.pagination-links span.current {
    background: #0073aa;
    border-color: #0073aa;
    color: #fff;
}

.excluir-log {
    color: #dc3232;
    text-decoration: none;
}

.excluir-log:hover {
    color: #dc3232;
    text-decoration: underline;
}

.tablenav.top {
    margin: 15px 0;
}

.bulkactions {
    display: flex;
    gap: 10px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Excluir log individual
    $('.excluir-log').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Tem certeza que deseja excluir este log?')) {
            return;
        }

        const $link = $(this);
        const id = $link.data('id');
        
        $.post(ajaxurl, {
            action: 'excluir_log',
            id: id,
            nonce: '<?php echo wp_create_nonce("excluir_log_nonce"); ?>'
        })
        .done(function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Erro ao excluir log: ' + response.data.message);
            }
        })
        .fail(function() {
            alert('Erro ao excluir log. Tente novamente.');
        });
    });

    // Excluir todos os logs
    $('.excluir-todos-logs').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Tem certeza que deseja excluir todos os logs? Esta ação não pode ser desfeita.')) {
            return;
        }

        $.post(ajaxurl, {
            action: 'excluir_todos_logs',
            nonce: '<?php echo wp_create_nonce("excluir_todos_logs_nonce"); ?>'
        })
        .done(function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Erro ao excluir logs: ' + response.data.message);
            }
        })
        .fail(function() {
            alert('Erro ao excluir logs. Tente novamente.');
        });
    });

    // Exportar logs
    $('.exportar-logs').on('click', function(e) {
        e.preventDefault();
        $('#exportar-logs-form').submit();
    });
});
</script>