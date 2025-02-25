<?php
if (!defined('ABSPATH')) exit;

$options = get_option('verificador_numeros_options');
?>
<div class="verificador-numeros-container">
    <form id="verificador-numeros-form">
        <input 
            type="text" 
            id="numero_whatsapp" 
            name="numero_whatsapp" 
            class="phone-mask" 
            placeholder="Insira o número de WhatsApp:"
            required
        >
        <button type="submit" class="verificador-button">Verificar</button>
    </form>
    <div id="verificador-resultado" style="display: none;"></div>
</div>

<style>
.verificador-numeros-container {
    width: 100%;
}

#verificador-numeros-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

#verificador-numeros-form input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.verificador-button {
    background: #00bf5f;
    color: white;
    border: none;
    padding: 10px;
    border-radius: 4px;
    cursor: pointer;
}

.verificador-button:hover {
    background:rgb(41, 235, 138)!important;
    color:#000000;
;

}

#verificador-resultado {
    margin-top: 15px;
    padding: 10px;
    border-radius: 4px;
    text-align: center;
}

#verificador-resultado.success {
    background: #ebf9f1;
    border: 1px solid #00bf5f;
    color: #00bf5f;
}

#verificador-resultado.error {
    background: #ffeaea;
    border: 1px solid #dc3545;
    color: #dc3545;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.phone-mask').mask('(00) 00000-0000');

    $('#verificador-numeros-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $resultado = $('#verificador-resultado');
        const $submitButton = $form.find('button[type="submit"]');
        const numero = $('#numero_whatsapp').val();

        // Desabilita o botão durante a verificação
        $submitButton.prop('disabled', true);
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'verificar_numero',
                numero: numero,
                nonce: '<?php echo wp_create_nonce('verificar_numero'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $resultado
                        .removeClass('error')
                        .addClass('success')
                        .html(response.data.message)
                        .show();
                } else {
                    $resultado
                        .removeClass('success')
                        .addClass('error')
                        .html(response.data.message)
                        .show();
                }
            },
            error: function() {
                $resultado
                    .removeClass('success')
                    .addClass('error')
                    .html('Erro ao verificar o número. Tente novamente.')
                    .show();
            },
            complete: function() {
                $submitButton.prop('disabled', false);
            }
        });
    });
});
</script>