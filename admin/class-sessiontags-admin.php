<?php

/**
 * SessionTagsAdmin-Klasse
 * * Verwaltet die Administrationsschnittstelle des Plugins
 */
class SessionTagsAdmin
{
    /**
     * Instanz der SessionManager-Klasse
     * * @var SessionTagsSessionManager
     */
    private $session_manager;

    /**
     * Aktiver Tab im Admin-Bereich
     * * @var string
     */
    private $active_tab;

    /**
     * Konstruktor der SessionTagsAdmin-Klasse
     * * @param SessionTagsSessionManager $session_manager Die Instanz des SessionManagers
     */
    public function __construct($session_manager)
    {
        $this->session_manager = $session_manager;
        $this->active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'settings';

        // Admin-Hooks registrieren
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Fügt das Admin-Menü hinzu
     */
    public function add_admin_menu()
    {
        add_options_page(
            __('SessionTags', 'sessiontags'),
            __('SessionTags', 'sessiontags'),
            'manage_options',
            'sessiontags',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Registriert die Plugin-Einstellungen
     */
    public function register_settings()
    {
        register_setting('sessiontags_settings', 'sessiontags_parameters');
        register_setting('sessiontags_settings', 'sessiontags_url_encoding');
        register_setting('sessiontags_settings', 'sessiontags_secret_key');
    }

    /**
     * Lädt die Admin-Assets (CSS und JavaScript)
     * * @param string $hook Der Hook der aktuellen Admin-Seite
     */
    public function enqueue_admin_assets($hook)
    {
        // Nur auf der Plugin-Einstellungsseite laden
        if ($hook !== 'settings_page_sessiontags') {
            return;
        }

        // Admin-Stylesheet laden
        wp_enqueue_style(
            'sessiontags-admin-style',
            SESSIONTAGS_URL . 'admin/css/sessiontags-admin.css',
            [],
            SESSIONTAGS_VERSION
        );

        // Dashicons für den Papierkorb
        wp_enqueue_style('dashicons');

        // Admin-JavaScript laden
        wp_enqueue_script(
            'sessiontags-admin-script',
            SESSIONTAGS_URL . 'admin/admin-js.js',
            ['jquery'],
            SESSIONTAGS_VERSION,
            true
        );

        // Daten für JavaScript bereitstellen (URL Builder)
        $parameters = $this->session_manager->get_tracked_parameters();
        $param_data = [];
        foreach ($parameters as $param) {
            $param_data[] = [
                'name' => $param['name'],
                'shortcode' => !empty($param['shortcode']) ? $param['shortcode'] : $param['name']
            ];
        }

        wp_localize_script(
            'sessiontags-admin-script',
            'sessiontags_data',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('regenerate_secret_key'),
                'home_url' => home_url('/'),
                'parameters' => $param_data,
                'use_encoding' => $this->session_manager->is_url_encoding_enabled(),
                'strings' => [
                    'generating' => __('Wird generiert...', 'sessiontags'),
                    'success' => __('Neuer geheimer Schlüssel wurde generiert!', 'sessiontags'),
                    'error' => __('Fehler beim Generieren des Schlüssels.', 'sessiontags'),
                    'server_error' => __('Fehler bei der Kommunikation mit dem Server.', 'sessiontags'),
                    'regenerate' => __('Neu generieren', 'sessiontags'),
                    'copied' => __('Kopiert!', 'sessiontags'),
                    'copy' => __('Kopieren', 'sessiontags'),
                ]
            ]
        );
    }

    /**
     * Rendert die Admin-Seite
     */
    public function render_admin_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $this->active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'settings';
        $parameters = $this->session_manager->get_tracked_parameters();
        $use_encoding = $this->session_manager->is_url_encoding_enabled();
        $secret_key = get_option('sessiontags_secret_key', '');
?>
        <div class="wrap sessiontags-admin">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <h2 class="nav-tab-wrapper">
                <a href="?page=sessiontags&tab=settings" class="nav-tab <?php echo $this->active_tab === 'settings' ? 'nav-tab-active' : ''; ?>"><?php _e('Einstellungen', 'sessiontags'); ?></a>
                <a href="?page=sessiontags&tab=url_builder" class="nav-tab <?php echo $this->active_tab === 'url_builder' ? 'nav-tab-active' : ''; ?>"><?php _e('URL-Builder', 'sessiontags'); ?></a>
                <a href="?page=sessiontags&tab=docs_basic" class="nav-tab <?php echo $this->active_tab === 'docs_basic' ? 'nav-tab-active' : ''; ?>"><?php _e('Doku: Grundlagen', 'sessiontags'); ?></a>
                <a href="?page=sessiontags&tab=docs_integrations" class="nav-tab <?php echo $this->active_tab === 'docs_integrations' ? 'nav-tab-active' : ''; ?>"><?php _e('Doku: Integrationen', 'sessiontags'); ?></a>
                <a href="?page=sessiontags&tab=docs_examples" class="nav-tab <?php echo $this->active_tab === 'docs_examples' ? 'nav-tab-active' : ''; ?>"><?php _e('Doku: Anwendungsbeispiele', 'sessiontags'); ?></a>
            </h2>

            <?php if ($this->active_tab === 'settings') : ?>
                <form method="post" action="options.php">
                    <?php settings_fields('sessiontags_settings'); ?>
                    <div class="sessiontags-setting-section">
                        <h2><?php _e('Parameter-Einstellungen', 'sessiontags'); ?></h2>
                        <p class="description"><?php _e('Definieren Sie die Parameter, die in der URL erkannt und in der Session gespeichert werden sollen.', 'sessiontags'); ?></p>
                        <table class="sessiontags-parameter-table">
                            <thead>
                                <tr>
                                    <th><?php _e('Parameter-Name', 'sessiontags'); ?></th>
                                    <th><?php _e('URL-Kurzform', 'sessiontags'); ?></th>
                                    <th><?php _e('Standard-Fallback', 'sessiontags'); ?></th>
                                    <th><?php _e('Weiterleitung', 'sessiontags'); ?></th>
                                    <th><?php _e('Aktionen', 'sessiontags'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="sessiontags-parameter-rows">
                                <?php if (!empty($parameters)) : ?>
                                    <?php foreach ($parameters as $index => $param) : ?>
                                        <tr class="parameter-row">
                                            <td><input type="text" name="sessiontags_parameters[<?php echo (int) $index; ?>][name]" value="<?php echo esc_attr($param['name']); ?>" class="regular-text sessiontags-parameter-name" placeholder="<?php _e('z.B. quelle', 'sessiontags'); ?>" required></td>
                                            <td><input type="text" name="sessiontags_parameters[<?php echo (int) $index; ?>][shortcode]" value="<?php echo esc_attr($param['shortcode'] ?? ''); ?>" class="small-text sessiontags-parameter-shortcode" placeholder="<?php _e('z.B. q', 'sessiontags'); ?>"></td>
                                            <td><input type="text" name="sessiontags_parameters[<?php echo (int) $index; ?>][fallback]" value="<?php echo esc_attr($param['fallback'] ?? ''); ?>" class="regular-text sessiontags-parameter-fallback" placeholder="<?php _e('Standard-Fallback', 'sessiontags'); ?>"></td>
                                            <td><input type="text" name="sessiontags_parameters[<?php echo (int) $index; ?>][redirect_url]" value="<?php echo esc_attr($param['redirect_url'] ?? ''); ?>" class="regular-text sessiontags-parameter-redirect-url" placeholder="<?php _e('https://beispiel.de/zielseite', 'sessiontags'); ?>"></td>
                                            <td><span class="dashicons dashicons-trash trash-icon remove-parameter"></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr class="parameter-row">
                                        <td><input type="text" name="sessiontags_parameters[0][name]" value="quelle" class="regular-text sessiontags-parameter-name" placeholder="<?php _e('z.B. quelle', 'sessiontags'); ?>" required></td>
                                        <td><input type="text" name="sessiontags_parameters[0][shortcode]" value="q" class="small-text sessiontags-parameter-shortcode" placeholder="<?php _e('z.B. q', 'sessiontags'); ?>"></td>
                                        <td><input type="text" name="sessiontags_parameters[0][fallback]" value="" class="regular-text sessiontags-parameter-fallback" placeholder="<?php _e('Standard-Fallback', 'sessiontags'); ?>"></td>
                                        <td><input type="text" name="sessiontags_parameters[0][redirect_url]" value="" class="regular-text sessiontags-parameter-redirect-url" placeholder="<?php _e('https://beispiel.de/zielseite', 'sessiontags'); ?>"></td>
                                        <td><span class="dashicons dashicons-trash trash-icon remove-parameter"></span></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <button type="button" class="button add-parameter"><?php _e('Parameter hinzufügen', 'sessiontags'); ?></button>
                    </div>

                    <div class="sessiontags-setting-section">
                        <h2><?php _e('URL-Verschleierung', 'sessiontags'); ?></h2>
                        <p class="description"><?php _e('URL-Parameter können verschlüsselt werden, um die Lesbarkeit zu erschweren und Manipulationen zu verhindern.', 'sessiontags'); ?></p>
                        <div class="sessiontags-checkbox-setting">
                            <label><input type="checkbox" name="sessiontags_url_encoding" value="1" <?php checked(1, $use_encoding); ?>> <?php _e('URL-Verschleierung aktivieren', 'sessiontags'); ?></label>
                        </div>
                        <div class="sessiontags-secret-key-setting">
                            <label for="secret-key"><?php _e('Geheimer Schlüssel', 'sessiontags'); ?></label>
                            <div class="secret-key-display">
                                <input type="password" id="secret-key" name="sessiontags_secret_key" value="<?php echo esc_attr($secret_key); ?>" class="regular-text" readonly>
                                <button type="button" class="button regenerate-key"><?php _e('Neu generieren', 'sessiontags'); ?></button>
                            </div>
                            <p class="description"><?php _e('Dieser Schlüssel wird für die Verschlüsselung verwendet. Eine Änderung macht bestehende verschlüsselte URLs ungültig.', 'sessiontags'); ?></p>
                        </div>
                    </div>
                    <?php submit_button(); ?>
                </form>

            <?php elseif ($this->active_tab === 'url_builder') : ?>
                <div class="sessiontags-url-builder-section">
                    <h2><?php _e('Visueller URL-Builder', 'sessiontags'); ?></h2>
                    <p class="description"><?php _e('Erstellen Sie hier ganz einfach Ihre Tracking-URLs. Wählen Sie die Parameter, geben Sie Werte ein und kopieren Sie die fertige URL.', 'sessiontags'); ?></p>

                    <div class="url-builder-form">
                        <div class="url-builder-row">
                            <label for="base-url"><?php _e('Basis-URL', 'sessiontags'); ?></label>
                            <input type="text" id="base-url" value="<?php echo esc_url(home_url('/')); ?>" class="regular-text">
                        </div>
                        <div id="url-builder-params">
                            <!-- Parameter-Zeilen werden hier per JS eingefügt -->
                        </div>
                        <div class="url-builder-actions">
                            <button type="button" class="button add-builder-param"><?php _e('Weiteren Parameter hinzufügen', 'sessiontags'); ?></button>
                        </div>
                    </div>

                    <div class="sessiontags-generated-url-display">
                        <h3><?php _e('Generierte URL', 'sessiontags'); ?></h3>
                        <div class="url-display-wrapper">
                            <code id="generated-url"></code>
                            <button type="button" class="button copy-url" data-target="generated-url"><?php _e('Kopieren', 'sessiontags'); ?></button>
                        </div>
                    </div>
                </div>

            <?php elseif ($this->active_tab === 'docs_basic') : ?>
                <div class="sessiontags-documentation">
                    <h2><?php _e('Grundlagen und Shortcodes', 'sessiontags'); ?></h2>
                    <!-- Komplette Doku für Grundlagen hier einfügen -->
                </div>

            <?php elseif ($this->active_tab === 'docs_integrations') : ?>
                <div class="sessiontags-documentation">
                    <h2><?php _e('Integrationen (Elementor, Avada, Formulare)', 'sessiontags'); ?></h2>
                    <!-- Komplette Doku für Integrationen hier einfügen -->
                </div>

            <?php elseif ($this->active_tab === 'docs_examples') : ?>
                <div class="sessiontags-documentation">
                    <h2><?php _e('Anwendungsbeispiele & FAQ', 'sessiontags'); ?></h2>
                    <!-- Komplette Doku für Beispiele hier einfügen -->
                </div>
            <?php endif; ?>
        </div>
<?php
    }
}
