<?php
if (!defined('ABSPATH')) exit;

// Verifica permissões
if (!current_user_can('manage_options')) {
    wp_die('Você não tem permissão para acessar esta página.');
}

global $wpdb;
$table_name = $wpdb->prefix . 'verificador_numeros';
$numeros = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

// Mensagem de importação
if (isset($_GET['imported'])) {
    $count = intval($_GET['imported']);
    ?>
    <div class="notice notice-success">
        <p><?php printf(_n('%s número importado com sucesso.', '%s números importados com sucesso.', $count, 'verificador-numeros'), number_format_i18n($count)); ?></p>
    </div>
    <?php
}
?>

<div class="wrap">
    <h1>Verificador de Números</h1>
    
    <div class="notice notice-info">
        <p>Para usar o verificador em suas páginas, adicione o shortcode: <code>[verificador_numeros]</code></p>
    </div>

    <!-- Formulário de Adição -->
    <div class="card">
        <h2>Adicionar Novo Número</h2>
        <form id="add-numero-form" method="post">
            <input type="text" name="numero" id="numero" class="phone-mask" placeholder="(XX) XXXXX-XXXX" required>
            <?php wp_nonce_field('add_numero_action', 'add_numero_nonce'); ?>
            <input type="submit" class="button button-primary" value="Adicionar Número">
        </form>
    </div>

    <!-- Importar/Exportar -->
    <div class="card">
        <h2>Importar/Exportar</h2>
        <div class="import-export-container">
            <div class="import-section">
                <h3>Importar Números</h3>
                <form method="post" enctype="multipart/form-data" action="<?php echo admin_url('admin-post.php'); ?>">
                    <p class="description">Arquivo CSV com um número por linha</p>
                    <input type="file" name="import_file" accept=".csv" required>
                    <?php wp_nonce_field('import_numeros_action', 'import_numeros_nonce'); ?>
                    <input type="hidden" name="action" value="import_numeros">
                    <p class="submit">
                        <input type="submit" class="button" value="Importar">
                    </p>
                </form>
            </div>
            <div class="export-section">
                <h3>Exportar Números</h3>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <?php wp_nonce_field('export_numeros_action', 'export_numeros_nonce'); ?>
                    <input type="hidden" name="action" value="export_numeros">
                    <p class="submit">
                        <input type="submit" class="button" value="Exportar CSV">
                    </p>
                </form>
            </div>
        </div>
    </div>

    <!-- Lista de Números -->
    <div class="card">
        <h2>Números Cadastrados</h2>
        <?php if (!empty($numeros)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Data de Cadastro</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($numeros as $numero): ?>
                        <tr>
                            <td><?php 
                                $numero_formatado = preg_replace('/([0-9]{2})([0-9]{5})([0-9]{4})/', '($1) $2-$3', $numero->numero);
                                echo esc_html($numero_formatado); 
                            ?></td>
                            <td><?php echo esc_html(date_i18n('d/m/Y H:i:s', strtotime($numero->created_at))); ?></td>
                            <td>
                                <a href="#" class="delete-numero" data-id="<?php echo esc_attr($numero->id); ?>">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Nenhum número cadastrado.</p>
        <?php endif; ?>
    </div>
</div>

<style>
.card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-top: 20px;
    padding: 20px;
}

#add-numero-form {
    display: flex;
    gap: 10px;
    align-items: center;
}

#add-numero-form input[type="text"] {
    width: 200px;
}

.delete-numero {
    color: #dc3232;
    text-decoration: none;
}

.delete-numero:hover {
    color: #dc3232;
    text-decoration: underline;
}

.import-export-container {
    display: flex;
    gap: 40px;
    margin-top: 15px;
}

.import-section,
.export-section {
    flex: 1;
}

.description {
    color: #666;
    font-style: italic;
    margin: 5px 0;
}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
jQuery(document).ready(function($) {
    // Máscara para campos de telefone
    $('.phone-mask').mask('(00) 00000-0000');

    // Adicionar número
    $('#add-numero-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitButton = $form.find('input[type="submit"]');
        const numero = $('#numero').val();

        $submitButton.prop('disabled', true);

        $.post(ajaxurl, {
            action: 'add_numero',
            numero: numero,
            nonce: $('#add_numero_nonce').val()
        })
        .done(function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Erro ao adicionar número: ' + response.data.message);
            }
        })
        .fail(function() {
            alert('Erro ao adicionar número. Tente novamente.');
        })
        .always(function() {
            $submitButton.prop('disabled', false);
        });
    });

    // Exclusão de número
    $('.delete-numero').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Tem certeza que deseja excluir este número?')) {
            return;
        }

        const $link = $(this);
        const id = $link.data('id');
        
        $.post(ajaxurl, {
            action: 'delete_numero',
            id: id,
            nonce: '<?php echo wp_create_nonce("delete_numero_nonce"); ?>'
        })
        .done(function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Erro ao excluir número: ' + response.data.message);
            }
        })
        .fail(function() {
            alert('Erro ao excluir número. Tente novamente.');
        });
    });
});
</script>