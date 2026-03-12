<?php
/**
 * Plugin Name: VETTRYX WP Fast Gallery
 * Plugin URI:  https://github.com/vettryx/vettryx-wp-fast-gallery
 * Description: Gerenciador simplificado de álbuns de serviços com fotos de "Antes e Depois" flexíveis.
 * Version:     1.3.3
 * Author:      VETTRYX Tech
 * Author URI:  https://vettryx.com.br
 * License:     GPLv3
 */

// Segurança: Impede o acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe principal do plugin
 */
class Vettryx_Fast_Gallery {

    public function __construct() {
        // 1. Registro do CPT e Taxonomia
        add_action('init', [$this, 'register_cpt']);
        add_action('init', [$this, 'register_taxonomies']);
        
        // 2. Meta Boxes e Salvamento
        add_action('add_meta_boxes', [$this, 'add_custom_meta_boxes']);
        add_action('save_post', [$this, 'save_gallery_data']);
        
        // 3. Enfileiramento de Scripts e JS do Uploader
        add_action('admin_enqueue_scripts', [$this, 'enqueue_media_uploader']);
        add_action('admin_footer', [$this, 'gallery_javascript']);

        // 4. Menu de Configurações
        add_action('admin_menu', [$this, 'add_settings_menu']);
        add_action('admin_init', [$this, 'register_settings']);

        // 5. Shortcodes Granulares (Para montagem manual no Elementor)
        add_shortcode('vtx_fg_descricao', [$this, 'sc_get_desc']);
        add_shortcode('vtx_fg_local', [$this, 'sc_get_location']);
        add_shortcode('vtx_fg_data', [$this, 'sc_get_date']);
        add_shortcode('vtx_fg_fotos_antes', [$this, 'sc_get_before_photos']);
        add_shortcode('vtx_fg_fotos_depois', [$this, 'sc_get_after_photos']);
        add_shortcode('vtx_fg_capa', [$this, 'sc_get_cover_image']);
        add_shortcode('vtx_fg_tags', [$this, 'sc_get_tags']);

        // 6. Filtro para forçar o slug dinâmico
        add_filter('wp_insert_post_data', [$this, 'force_dynamic_slug'], 10, 2);
    }

    // ==========================================
    // 1. CONFIGURAÇÕES E SLUGS DINÂMICOS
    // ==========================================
    
    public function add_settings_menu() {
        add_submenu_page(
            'edit.php?post_type=vtx_gallery',
            'Configurações da Galeria',
            'Configurações',
            'manage_options',
            'vtx-gallery-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Registra as configurações do plugin
     */
    public function register_settings() {
        register_setting('vtx_gallery_settings_group', 'vtx_gallery_cpt_slug', 'sanitize_title');
        register_setting('vtx_gallery_settings_group', 'vtx_gallery_tax_slug', 'sanitize_title');
        register_setting('vtx_gallery_settings_group', 'vtx_gallery_tag_slug', 'sanitize_title');
    }

    /**
     * Renderiza a página de configurações
     */
    public function render_settings_page() {
        $cpt_slug = get_option('vtx_gallery_cpt_slug', 'servicos');
        $tax_slug = get_option('vtx_gallery_tax_slug', 'tipo-servico');
        $tag_slug = get_option('vtx_gallery_tag_slug', 'detalhe-servico');
        ?>
        <div class="wrap">
            <h1>Configurações da Galeria (VETTRYX)</h1>
            <p>Personalize os links (slugs) de como os trabalhos aparecerão na URL do site.</p>
            
            <div class="notice notice-warning inline">
                <p><strong>Atenção:</strong> Sempre que alterar e salvar novos slugs, lembre-se de ir em <strong>Configurações > Links Permanentes</strong> e clicar em "Salvar Alterações".</p>
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
                    <tr valign="top">
                        <th scope="row">Slug das Tags (Micro-serviços)<br><small>(Ex: detalhe-servico, tags)</small></th>
                        <td>
                            <code><?php echo home_url('/'); ?></code>
                            <input type="text" name="vtx_gallery_tag_slug" value="<?php echo esc_attr(get_option('vtx_gallery_tag_slug', 'detalhe-servico')); ?>" placeholder="detalhe-servico" />
                        </td>
                    </tr>
                </table>
                <?php submit_button('Salvar Slugs'); ?>
            </form>

            <hr style="margin-top: 30px; border: 0; border-top: 1px solid #ccd0d4;">

            <div style="margin-top: 20px; background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-left: 4px solid var(--brand-primary, #023047); box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <h2 style="margin-top: 0;">Shortcodes para Montagem Manual (Elementor)</h2>
                <p>Utilize os shortcodes abaixo para construir livremente o layout do seu <strong>Single Post Template</strong> ou do <strong>Loop Builder</strong>. Clique para copiar.</p>

                <table class="wp-list-table widefat fixed striped" style="margin-top: 15px; width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 25%;">Dado a Exibir</th>
                            <th style="width: 35%;">Shortcode</th>
                            <th>O que ele faz?</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Descrição do Serviço</strong></td>
                            <td><input type="text" readonly value="[vtx_fg_descricao]" style="width: 100%; font-family: monospace; background: transparent; border: none; cursor: pointer; color: #d63638; font-weight: bold;" onfocus="this.select();"></td>
                            <td>Imprime o texto digitado na caixa de descrição.</td>
                        </tr>
                        <tr>
                            <td><strong>Localização</strong></td>
                            <td><input type="text" readonly value="[vtx_fg_local]" style="width: 100%; font-family: monospace; background: transparent; border: none; cursor: pointer; color: #d63638; font-weight: bold;" onfocus="this.select();"></td>
                            <td>Imprime o texto do local (Ex: Condomínio XYZ).</td>
                        </tr>
                        <tr>
                            <td><strong>Data Formatada</strong></td>
                            <td><input type="text" readonly value="[vtx_fg_data]" style="width: 100%; font-family: monospace; background: transparent; border: none; cursor: pointer; color: #d63638; font-weight: bold;" onfocus="this.select();"></td>
                            <td>Imprime a data (Ex: 18 de Fev, 2026).</td>
                        </tr>
                        <tr>
                            <td><strong>Grade de Fotos: ANTES</strong></td>
                            <td><input type="text" readonly value="[vtx_fg_fotos_antes]" style="width: 100%; font-family: monospace; background: transparent; border: none; cursor: pointer; color: #d63638; font-weight: bold;" onfocus="this.select();"></td>
                            <td>Gera apenas o bloco de imagens da coluna "Antes".</td>
                        </tr>
                        <tr>
                            <td><strong>Grade de Fotos: DEPOIS</strong></td>
                            <td><input type="text" readonly value="[vtx_fg_fotos_depois]" style="width: 100%; font-family: monospace; background: transparent; border: none; cursor: pointer; color: #d63638; font-weight: bold;" onfocus="this.select();"></td>
                            <td>Gera apenas o bloco de imagens da coluna "Depois".</td>
                        </tr>
                        <tr>
                            <td><strong>URL da Foto de Capa</strong></td>
                            <td><input type="text" readonly value="[vtx_fg_capa]" style="width: 100%; font-family: monospace; background: transparent; border: none; cursor: pointer; color: #d63638; font-weight: bold;" onfocus="this.select();"></td>
                            <td>Retorna o LINK puro da primeira foto do "Depois". Ideal para usar no Elementor como URL Dinâmica de fundo do <strong>Loop Builder</strong>.</td>
                        </tr>
                        <tr>
                            <td><strong>Tags (Micro-serviços)</strong></td>
                            <td><input type="text" readonly value="[vtx_fg_tags]" style="width: 100%; font-family: monospace; background: transparent; border: none; cursor: pointer; color: #d63638; font-weight: bold;" onfocus="this.select();"></td>
                            <td>Imprime a lista de micro-serviços em formato de etiquetas visuais (badges).</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
        <?php
    }

    /**
     * Força a criação do slug no formato YYYYMMDD-titulo
     */
    public function force_dynamic_slug($data, $postarr) {
        // Garante que só afete o nosso Custom Post Type e ignora salvamentos automáticos
        if ($data['post_type'] !== 'vtx_gallery' || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)) {
            return $data;
        }

        // Ignora rascunhos automáticos
        if (in_array($data['post_status'], ['auto-draft', 'trash'])) {
            return $data;
        }

        // Captura os dados de data enviados no formulário ($_POST)
        // Se não houver POST (ex: edição rápida), tenta pegar do banco de dados
        $year = isset($_POST['vtx_service_year']) ? sanitize_text_field($_POST['vtx_service_year']) : get_post_meta($postarr['ID'], 'vtx_service_year', true);
        $month = isset($_POST['vtx_service_month']) ? sanitize_text_field($_POST['vtx_service_month']) : get_post_meta($postarr['ID'], 'vtx_service_month', true);
        $day = isset($_POST['vtx_service_day']) ? sanitize_text_field($_POST['vtx_service_day']) : get_post_meta($postarr['ID'], 'vtx_service_day', true);

        // Se não tiver ano preenchido, não fazemos nada
        if (empty($year)) {
            return $data;
        }

        // Monta o prefixo concatenando o que existir (YYYY, YYYYMM ou YYYYMMDD)
        $date_prefix = $year . $month . $day;

        // Pega o título e limpa para formato URL
        $title_slug = sanitize_title($data['post_title']);

        // Monta o slug desejado final
        $desired_slug = $date_prefix . '-' . $title_slug;

        // Compara com o slug atual. Se for diferente, mandamos o WP atualizar.
        // O wp_unique_post_slug é a inteligência nativa do WP: se já existir um "2026-pintura", ele gera "2026-pintura-2"
        if ($data['post_name'] !== $desired_slug) {
            $data['post_name'] = wp_unique_post_slug($desired_slug, $postarr['ID'], $data['post_status'], $data['post_type'], $data['post_parent']);
        }

        return $data;
    }

    // ==========================================
    // 2. REGISTRO DE CPT E TAXONOMIA
    // ==========================================

    public function register_cpt() {
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

    /**
     * Registra a taxonomia
     */
    public function register_taxonomies() {
        // Taxonomia de Categorias
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

        // Taxonomia de Tags
        $tag_slug = get_option('vtx_gallery_tag_slug', 'detalhe-servico');
        if(empty($tag_slug)) $tag_slug = 'detalhe-servico';

        $labels_tag = [
            'name'              => 'Serviços Detalhados (Tags)',
            'singular_name'     => 'Serviço Detalhado',
            'search_items'      => 'Buscar Serviços',
            'all_items'         => 'Todos os Serviços Detalhados',
            'edit_item'         => 'Editar Serviço',
            'update_item'       => 'Atualizar Serviço',
            'add_new_item'      => 'Adicionar Novo Serviço (Tag)',
            'new_item_name'     => 'Novo Nome de Serviço',
            'menu_name'         => 'Tags (Micro-serviços)',
        ];

        $args_tag = [
            'hierarchical'      => false, // ATENÇÃO: É isto que transforma em "Tag"
            'labels'            => $labels_tag,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => $tag_slug],
            'show_in_rest'      => false, 
        ];

        register_taxonomy('vtx_service_tag', ['vtx_gallery'], $args_tag);
    }

    // ==========================================
    // 3. META BOXES DE DADOS E FOTOS
    // ==========================================

    /**
     * Adiciona os meta boxes
     */
    public function add_custom_meta_boxes() {
        add_meta_box('vtx_gallery_info', 'Informações do Serviço', [$this, 'render_info_box'], 'vtx_gallery', 'normal', 'high');
        add_meta_box('vtx_gallery_media', 'Galeria: Antes e Depois', [$this, 'render_media_box'], 'vtx_gallery', 'normal', 'high');
    }

    /**
     * Renderiza o meta box de informações
     */
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

    /**
     * Renderiza o meta box de mídia
     */
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

    /**
     * Renderiza as imagens de preview
     */
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

    /**
     * Enfileira os scripts e JS do uploader
     */
    public function enqueue_media_uploader($hook) {
        global $post_type;
        if ($post_type == 'vtx_gallery') {
            wp_enqueue_media();
        }
    }

    /**
     * JavaScript do uploader
     */
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

    /**
     * Salva os dados do meta box
     */
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

    // ==========================================
    // 4. FUNÇÕES DOS SHORTCODES GRANULARES
    // ==========================================

    /**
     * Retorna a descrição do serviço
     */
    public function sc_get_desc() {
        $desc = get_post_meta(get_the_ID(), 'vtx_service_desc', true);
        return $desc ? nl2br(esc_html($desc)) : '';
    }

    /**
     * Retorna a data do serviço
     */
    public function sc_get_date() {
        $day = get_post_meta(get_the_ID(), 'vtx_service_day', true);
        $month = get_post_meta(get_the_ID(), 'vtx_service_month', true);
        $year = get_post_meta(get_the_ID(), 'vtx_service_year', true);
        
        if (!$year) return '';

        // Array com os nomes completos dos meses
        $meses = [
            '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março',
            '04' => 'Abril',   '05' => 'Maio',      '06' => 'Junho',
            '07' => 'Julho',   '08' => 'Agosto',    '09' => 'Setembro',
            '10' => 'Outubro', '11' => 'Novembro',  '12' => 'Dezembro'
        ];
        
        $month_text = ($month && isset($meses[$month])) ? $meses[$month] : '';

        // Monta a string dependendo do que foi preenchido
        if ($day && $month_text) {
            return "$day de $month_text de $year";
        } elseif ($month_text) {
            return "$month_text de $year";
        } else {
            return $year;
        }
    }

    /**
     * Retorna a localização do serviço
     */
    public function sc_get_location() {
        return esc_html(get_post_meta(get_the_ID(), 'vtx_service_location', true));
    }

    /**
     * Retorna as fotos do antes
     */
    public function sc_get_before_photos() {
        $ids = get_post_meta(get_the_ID(), 'vtx_gallery_before', true);
        return $this->render_photo_grid($ids);
    }

    /**
     * Retorna as fotos do depois
     */
    public function sc_get_after_photos() {
        $ids = get_post_meta(get_the_ID(), 'vtx_gallery_after', true);
        return $this->render_photo_grid($ids);
    }

    /**
     * Retorna a imagem de capa
     */
    public function sc_get_cover_image() {
        $after_ids = get_post_meta(get_the_ID(), 'vtx_gallery_after', true);
        if (!empty($after_ids)) {
            $first_id = explode(',', $after_ids)[0];
            return esc_url(wp_get_attachment_image_url($first_id, 'large'));
        }
        return '';
    }

    /**
     * Renderiza a grade de fotos
     */
    private function render_photo_grid($ids_string) {
        if (empty($ids_string)) return '';
        $html = '<div class="vtx-sc-photo-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 15px;">';
        foreach (explode(',', $ids_string) as $id) {
            $img_url = wp_get_attachment_image_url($id, 'large');
            if ($img_url) {
                $html .= '<a href="'.esc_url($img_url).'" target="_blank"><img src="'.esc_url($img_url).'" style="width:100%; height:120px; object-fit:cover; border-radius:5px;"></a>';
            }
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * Retorna as tags (micro-serviços) do post
     */
    public function sc_get_tags() {
        $terms = get_the_terms(get_the_ID(), 'vtx_service_tag');
        
        if ($terms && !is_wp_error($terms)) {
            $tags_html = [];
            foreach ($terms as $term) {
                $tags_html[] = '<span class="vtx-tag" style="display:inline-block; background:#e2e8f0; color:#475569; padding:4px 8px; border-radius:4px; font-size:12px; margin-right:5px; margin-bottom:5px;">' . esc_html($term->name) . '</span>';
            }
            return '<div class="vtx-tags-wrapper">' . implode('', $tags_html) . '</div>';
        }
        
        return '';
    }
}

// Inicializa o plugin
new Vettryx_Fast_Gallery();