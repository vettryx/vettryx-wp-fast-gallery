<?php
/**
 * Plugin Name: VETTRYX WP Fast Gallery
 * Plugin URI:  https://github.com/vettryx/vettryx-wp-fast-gallery
 * Description: Gerenciador simplificado de álbuns de serviços com fotos de "Antes e Depois" flexíveis.
 * Version:     1.1.0
 * Author:      VETTRYX Tech
 * Author URI:  https://vettryx.com.br
 * License:     GPLv3
 */

// Segurança: Impede o acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit;
}

// Classe principal do plugin
class Vettryx_Fast_Gallery {

    public function __construct() {
        // Inicialização do CPT e Taxonomia
        add_action('init', [$this, 'register_cpt']);
        add_action('init', [$this, 'register_taxonomies']);
        
        // Meta Boxes e Salvamento
        add_action('add_meta_boxes', [$this, 'add_custom_meta_boxes']);
        add_action('save_post', [$this, 'save_gallery_data']);
        
        // Scripts de Mídia
        add_action('admin_enqueue_scripts', [$this, 'enqueue_media_uploader']);
        add_action('admin_footer', [$this, 'gallery_javascript']);

        // Menu de Configurações (Slugs Dinâmicos)
        add_action('admin_menu', [$this, 'add_settings_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    // ==========================================
    // 1. CONFIGURAÇÕES E SLUGS DINÂMICOS
    // ==========================================
    
    public function add_settings_menu() {
        add_submenu_page(
            'edit.php?post_type=vtx_gallery', // Coloca abaixo do menu "Meus Trabalhos"
            'Configurações da Galeria',
            'Configurações',
            'manage_options', // Apenas administradores podem mudar o slug
            'vtx-gallery-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        // Registra as opções no banco de dados com sanitização para URLs (slugs)
        register_setting('vtx_gallery_settings_group', 'vtx_gallery_cpt_slug', 'sanitize_title');
        register_setting('vtx_gallery_settings_group', 'vtx_gallery_tax_slug', 'sanitize_title');
    }

    public function render_settings_page() {
        $cpt_slug = get_option('vtx_gallery_cpt_slug', 'servicos');
        $tax_slug = get_option('vtx_gallery_tax_slug', 'tipo-servico');
        ?>
        <div class="wrap">
            <h1>Configurações da Galeria (VETTRYX)</h1>
            <p>Personalize os links (slugs) de como os trabalhos aparecerão na URL do site.</p>
            
            <div class="notice notice-warning inline">
                <p><strong>Atenção:</strong> Sempre que você alterar e salvar novos slugs aqui, lembre-se de ir em <strong>Configurações > Links Permanentes</strong> e clicar em "Salvar Alterações" para o WordPress reconhecer as novas URLs e evitar o Erro 404.</p>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields('vtx_gallery_settings_group'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Slug Principal dos Trabalhos<br><small>(Ex: servicos, projetos, obras)</small></th>
                        <td>
                            <code><?php echo home_url('/'); ?></code>
                            <input type="text" name="vtx_gallery_cpt_slug" value="<?php echo esc_attr($cpt_slug); ?>" placeholder="servicos" />
                            <code>/nome-do-trabalho/</code>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Slug das Categorias<br><small>(Ex: tipo-servico, categoria-projeto)</small></th>
                        <td>
                            <code><?php echo home_url('/'); ?></code>
                            <input type="text" name="vtx_gallery_tax_slug" value="<?php echo esc_attr($tax_slug); ?>" placeholder="tipo-servico" />
                            <code>/nome-da-categoria/</code>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Salvar Slugs'); ?>
            </form>
        </div>
        <?php
    }

    // ==========================================
    // 2. REGISTRO DE CPT E TAXONOMIA
    // ==========================================

    public function register_cpt() {
        // Puxa o slug dinâmico do banco (ou usa 'servicos' como fallback)
        $cpt_slug = get_option('vtx_gallery_cpt_slug', 'servicos');
        if(empty($cpt_slug)) $cpt_slug = 'servicos';

        $labels = [
            'name'               => 'Meus Trabalhos',
            'singular_name'      => 'Trabalho/Álbum',
            'menu_name'          => 'Meus Trabalhos',
            'add_new'            => 'Adicionar Novo Álbum',
            'add_new_item'       => 'Adicionar Novo Álbum de Serviço',
            'edit_item'          => 'Editar Álbum',
            'all_items'          => 'Todos os Trabalhos',
        ];

        $args = [
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'menu_icon'          => 'dashicons-format-gallery',
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 5,
            'supports'           => ['title'], 
            'show_in_rest'       => false,
            'rewrite'            => ['slug' => $cpt_slug, 'with_front' => false],
        ];

        register_post_type('vtx_gallery', $args);
    }

    public function register_taxonomies() {
        // Puxa o slug dinâmico do banco (ou usa 'tipo-servico' como fallback)
        $tax_slug = get_option('vtx_gallery_tax_slug', 'tipo-servico');
        if(empty($tax_slug)) $tax_slug = 'tipo-servico';

        $labels = [
            'name'              => 'Tipos de Serviço',
            'singular_name'     => 'Tipo de Serviço',
            'search_items'      => 'Buscar Tipos',
            'all_items'         => 'Todos os Tipos',
            'parent_item'       => 'Tipo Pai',
            'parent_item_colon' => 'Tipo Pai:',
            'edit_item'         => 'Editar Tipo',
            'update_item'       => 'Atualizar Tipo',
            'add_new_item'      => 'Adicionar Novo Tipo',
            'new_item_name'     => 'Novo Nome',
            'menu_name'         => 'Tipos de Serviço',
        ];

        $args = [
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => $tax_slug],
            'show_in_rest'      => false,
        ];

        register_taxonomy('vtx_service_category', ['vtx_gallery'], $args);
    }

    // ==========================================
    // 3. META BOXES DE DADOS E FOTOS
    // ==========================================

    public function add_custom_meta_boxes() {
        add_meta_box('vtx_gallery_info', 'Informações do Serviço', [$this, 'render_info_box'], 'vtx_gallery', 'normal', 'high');
        add_meta_box('vtx_gallery_media', 'Galeria: Antes e Depois', [$this, 'render_media_box'], 'vtx_gallery', 'normal', 'high');
    }

    public function render_info_box($post) {
        wp_nonce_field('vtx_gallery_save', 'vtx_gallery_nonce');

        $location = get_post_meta($post->ID, 'vtx_service_location', true);
        $desc = get_post_meta($post->ID, 'vtx_service_desc', true);
        $day = get_post_meta($post->ID, 'vtx_service_day', true);
        $month = get_post_meta($post->ID, 'vtx_service_month', true);
        $year = get_post_meta($post->ID, 'vtx_service_year', true);

        $current_year = date('Y');
        ?>
        <div style="display: grid; gap: 15px;">
            <div>
                <label><strong>Descrição do Serviço:</strong></label><br>
                <textarea name="vtx_service_desc" rows="4" style="width: 100%;"><?php echo esc_textarea($desc); ?></textarea>
                <p class="description">Resumo do que foi feito (Ex: Restauração completa de sofá de 3 lugares).</p>
            </div>
            
            <div>
                <label><strong>Local (Opcional):</strong></label><br>
                <input type="text" name="vtx_service_location" value="<?php echo esc_attr($location); ?>" style="width: 100%;" placeholder="Ex: Condomínio XYZ, Centro, Empresa ABC...">
            </div>

            <div>
                <label><strong>Data da Execução:</strong></label><br>
                <select name="vtx_service_day">
                    <option value="">Dia (Opcional)</option>
                    <?php for($i=1; $i<=31; $i++) : $val = str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                        <option value="<?php echo $val; ?>" <?php selected($day, $val); ?>><?php echo $val; ?></option>
                    <?php endfor; ?>
                </select>
                
                <select name="vtx_service_month">
                    <option value="">Mês (Opcional)</option>
                    <?php 
                    $meses = [1=>'Jan',2=>'Fev',3=>'Mar',4=>'Abr',5=>'Mai',6=>'Jun',7=>'Jul',8=>'Ago',9=>'Set',10=>'Out',11=>'Nov',12=>'Dez'];
                    foreach($meses as $num => $nome) : $val = str_pad($num, 2, '0', STR_PAD_LEFT); ?>
                        <option value="<?php echo $val; ?>" <?php selected($month, $val); ?>><?php echo $nome; ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="vtx_service_year" required>
                    <option value="">Ano (Obrigatório)</option>
                    <?php for($i = $current_year; $i >= $current_year - 10; $i--) : ?>
                        <option value="<?php echo $i; ?>" <?php selected($year, $i); ?>><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
        <?php
    }

    public function render_media_box($post) {
        $before_ids = get_post_meta($post->ID, 'vtx_gallery_before', true);
        $after_ids = get_post_meta($post->ID, 'vtx_gallery_after', true);
        ?>
        <style>
            .vtx-gallery-container { display: flex; gap: 20px; }
            .vtx-gallery-column { flex: 1; border: 1px solid #ccd0d4; padding: 15px; background: #f9f9f9; border-radius: 4px; }
            .vtx-image-preview { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 15px; }
            .vtx-img-wrap { position: relative; width: 100px; height: 100px; border: 1px solid #ddd; background: #fff; }
            .vtx-img-wrap img { width: 100%; height: 100%; object-fit: cover; }
            .vtx-remove-img { position: absolute; top: -5px; right: -5px; background: red; color: white; border-radius: 50%; width: 20px; height: 20px; text-align: center; line-height: 18px; cursor: pointer; font-weight: bold; text-decoration: none;}
            .vtx-remove-img:hover { color: white; background: darkred; }
        </style>

        <div class="vtx-gallery-container">
            <div class="vtx-gallery-column">
                <h3 style="margin-top:0; color: #d63638;">📸 FOTOS DO ANTES</h3>
                <p class="description">Selecione uma ou mais fotos de como estava o local/produto.</p>
                <input type="hidden" id="vtx_before_input" name="vtx_gallery_before" value="<?php echo esc_attr($before_ids); ?>">
                <button type="button" class="button button-secondary vtx-upload-btn" data-target="before">Adicionar Fotos (Antes)</button>
                <div id="vtx_before_preview" class="vtx-image-preview">
                    <?php $this->render_preview_images($before_ids); ?>
                </div>
            </div>

            <div class="vtx-gallery-column">
                <h3 style="margin-top:0; color: #00a32a;">📸 FOTOS DO DEPOIS</h3>
                <p class="description">Selecione uma ou mais fotos do resultado final.</p>
                <input type="hidden" id="vtx_after_input" name="vtx_gallery_after" value="<?php echo esc_attr($after_ids); ?>">
                <button type="button" class="button button-primary vtx-upload-btn" data-target="after">Adicionar Fotos (Depois)</button>
                <div id="vtx_after_preview" class="vtx-image-preview">
                    <?php $this->render_preview_images($after_ids); ?>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_preview_images($ids_string) {
        if (empty($ids_string)) return;
        $ids = explode(',', $ids_string);
        foreach ($ids as $id) {
            $img = wp_get_attachment_image_src($id, 'thumbnail');
            if ($img) {
                echo '<div class="vtx-img-wrap" data-id="'.$id.'">';
                echo '<img src="'.$img[0].'">';
                echo '<a href="#" class="vtx-remove-img" title="Remover">&times;</a>';
                echo '</div>';
            }
        }
    }

    public function enqueue_media_uploader($hook) {
        global $post_type;
        if ($post_type == 'vtx_gallery') {
            wp_enqueue_media();
        }
    }

    public function gallery_javascript() {
        global $post_type;
        if ($post_type != 'vtx_gallery') return;
        ?>
        <script>
        jQuery(document).ready(function($){
            var mediaUploader;

            $('.vtx-upload-btn').click(function(e) {
                e.preventDefault();
                var target = $(this).data('target'); 
                var inputField = $('#vtx_' + target + '_input');
                var previewArea = $('#vtx_' + target + '_preview');

                mediaUploader = wp.media({
                    title: 'Selecione as fotos',
                    button: { text: 'Usar estas fotos' },
                    multiple: true 
                });

                mediaUploader.on('select', function() {
                    var attachments = mediaUploader.state().get('selection').map(function(a) {
                        a.toJSON();
                        return a;
                    });

                    var currentIds = inputField.val() ? inputField.val().split(',') : [];
                    
                    attachments.forEach(function(attachment) {
                        var id = attachment.id;
                        var url = attachment.attributes.sizes && attachment.attributes.sizes.thumbnail ? attachment.attributes.sizes.thumbnail.url : attachment.attributes.url;
                        
                        if($.inArray(id.toString(), currentIds) === -1) {
                            currentIds.push(id);
                            previewArea.append('<div class="vtx-img-wrap" data-id="'+id+'"><img src="'+url+'"><a href="#" class="vtx-remove-img" title="Remover">&times;</a></div>');
                        }
                    });

                    inputField.val(currentIds.join(','));
                });

                mediaUploader.open();
            });

            $(document).on('click', '.vtx-remove-img', function(e) {
                e.preventDefault();
                var wrap = $(this).closest('.vtx-img-wrap');
                var idToRemove = wrap.data('id').toString();
                var containerId = wrap.closest('.vtx-image-preview').attr('id');
                var target = containerId.replace('vtx_', '').replace('_preview', '');
                var inputField = $('#vtx_' + target + '_input');

                var currentIds = inputField.val().split(',');
                var newIds = $.grep(currentIds, function(value) {
                    return value != idToRemove;
                });

                inputField.val(newIds.join(','));
                wrap.remove();
            });
        });
        </script>
        <?php
    }


    public function save_gallery_data($post_id) {
        if (!isset($_POST['vtx_gallery_nonce']) || !wp_verify_nonce($_POST['vtx_gallery_nonce'], 'vtx_gallery_save')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $fields = ['vtx_service_location', 'vtx_service_desc', 'vtx_service_day', 'vtx_service_month', 'vtx_service_year', 'vtx_gallery_before', 'vtx_gallery_after'];
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }
}

// Inicializa o plugin
new Vettryx_Fast_Gallery();
