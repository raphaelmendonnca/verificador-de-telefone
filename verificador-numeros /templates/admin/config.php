<?php
if (!defined('ABSPATH')) exit;

// Verifica permissões
if (!current_user_can('manage_options')) {
    wp_die(__('Você não tem permissão para acessar esta página.', 'verificador-numeros'));
}

$options = get_option('verificador_numeros_options');
?>

<div class="wrap">
    <h1>Configurações do Verificador de Números</h1>

    <form method="post" action="options.php">
        <?php settings_fields('verificador_numeros_options'); ?>
        
        <div class="card">
            <h2>Mensagens</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="mensagem_sucesso">Mensagem quando encontrar o número</label>
                    </th>
                    <td>
                        <input type="text" id="mensagem_sucesso" name="verificador_numeros_options[mensagem_sucesso]" 
                               value="<?php echo esc_attr($options['mensagem_sucesso']); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="mensagem_erro">Mensagem quando não encontrar o número</label>
                    </th>
                    <td>
                        <input type="text" id="mensagem_erro" name="verificador_numeros_options[mensagem_erro]" 
                               value="<?php echo esc_attr($options['mensagem_erro']); ?>" class="regular-text">
                    </td>
                </tr>
            </table>
        </div>

        <div class="card">
            <h2>Logs de Verificação</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Período de Retenção</th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="radio" name="verificador_numeros_options[retencao_logs]" value="0" 
                                       <?php checked($options['retencao_logs'], '0'); ?>>
                                Não coletar logs
                            </label>
                            <br>
                            <label>
                                <input type="radio" name="verificador_numeros_options[retencao_logs]" value="1" 
                                       <?php checked($options['retencao_logs'], '1'); ?>>
                                Coletar e manter por 1 dia
                            </label>
                            <br>
                            <label>
                                <input type="radio" name="verificador_numeros_options[retencao_logs]" value="7" 
                                       <?php checked($options['retencao_logs'], '7'); ?>>
                                Coletar e manter por 1 semana
                            </label>
                            <br>
                            <label>
                                <input type="radio" name="verificador_numeros_options[retencao_logs]" value="30" 
                                       <?php checked($options['retencao_logs'], '30'); ?>>
                                Coletar e manter por 1 mês
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </table>
        </div>

        <?php submit_button(); ?>
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

.card h2 {
    margin-top: 0;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

fieldset label {
    margin: 5px 0;
    display: block;
}
</style>