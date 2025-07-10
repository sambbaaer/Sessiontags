<?php

/**
 * SessionTagsAdmin-Klasse
 * Verwaltet die Administrationsschnittstelle des Plugins
 */
class SessionTagsAdmin
{
    /**
     * Instanz der SessionManager-Klasse
     * @var SessionTagsSessionManager
     */
    private $session_manager;

    /**
     * Aktiver Tab im Admin-Bereich
     * @var string
     */
    private $active_tab;

    /**
     * Konstruktor der SessionTagsAdmin-Klasse
     * @param SessionTagsSessionManager $session_manager Die Instanz des SessionManagers
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
     * @param string $hook Der Hook der aktuellen Admin-Seite
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

        // Dashicons für Icons
        wp_enqueue_style('dashicons');

        // Admin-JavaScript laden
        wp_enqueue_script(
            'sessiontags-admin-script',
            SESSIONTAGS_URL . 'admin/admin-js.js',
            ['jquery', 'wp-util'], // wp-util für Templates hinzufügen
            SESSIONTAGS_VERSION,
            true
        );

        // Daten für JavaScript bereitstellen
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
?>
        <div class="wrap sessiontags-admin">
            <h1>
                <span class="dashicons dashicons-tag" style="margin-right: 8px;"></span>
                <?php echo esc_html(get_admin_page_title()); ?>
            </h1>

            <p class="description" style="margin-bottom: 20px;">
                <?php _e('SessionTags erfasst URL-Parameter und speichert sie in der Session für personalisierte Website-Erlebnisse.', 'sessiontags'); ?>
            </p>

            <nav class="nav-tab-wrapper wp-clearfix">
                <a href="?page=sessiontags&tab=settings" class="nav-tab <?php echo $this->active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <?php _e('Einstellungen', 'sessiontags'); ?>
                </a>
                <a href="?page=sessiontags&tab=url_builder" class="nav-tab <?php echo $this->active_tab === 'url_builder' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-links"></span>
                    <?php _e('URL-Builder', 'sessiontags'); ?>
                </a>
                <a href="?page=sessiontags&tab=docs" class="nav-tab <?php echo strpos($this->active_tab, 'docs') === 0 ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-book-alt"></span>
                    <?php _e('Dokumentation & Hilfe', 'sessiontags'); ?>
                </a>
            </nav>

            <div class="tab-content">
                <?php
                switch ($this->active_tab) {
                    case 'settings':
                        $this->render_settings_tab();
                        break;
                    case 'url_builder':
                        $this->render_url_builder_tab();
                        break;
                    case 'docs':
                    default:
                        // Wenn der Tab 'docs' ist oder mit 'docs_' beginnt
                        if (strpos($this->active_tab, 'docs') === 0 || $this->active_tab === 'docs') {
                            $this->render_docs_tabs();
                        } else {
                            // Fallback auf die Einstellungsseite
                            $this->render_settings_tab();
                        }
                        break;
                }
                ?>
            </div>
        </div>
    <?php
    }

    /**
     * Rendert den Einstellungen-Tab
     */
    private function render_settings_tab()
    {
        $parameters = $this->session_manager->get_tracked_parameters();
        $use_encoding = $this->session_manager->is_url_encoding_enabled();
        $secret_key = get_option('sessiontags_secret_key', '');
    ?>
        <form method="post" action="options.php">
            <?php settings_fields('sessiontags_settings'); ?>

            <div id="poststuff">
                <div class="postbox">
                    <h2 class="hndle"><span class="dashicons dashicons-admin-settings"></span> <?php _e('Parameter-Konfiguration', 'sessiontags'); ?></h2>
                    <div class="inside">
                        <p class="description">
                            <?php _e('Definieren Sie die Parameter, die aus URLs erkannt und in der Session gespeichert werden sollen.', 'sessiontags'); ?>
                        </p>

                        <div class="sessiontags-table-wrapper">
                            <table class="widefat striped sessiontags-parameter-table">
                                <thead>
                                    <tr>
                                        <th style="width: 25%;"><?php _e('Parameter-Name', 'sessiontags'); ?> <span class="required">*</span></th>
                                        <th style="width: 15%;"><?php _e('URL-Kürzel', 'sessiontags'); ?></th>
                                        <th style="width: 25%;"><?php _e('Standard-Fallback', 'sessiontags'); ?></th>
                                        <th style="width: 25%;"><?php _e('Weiterleitung', 'sessiontags'); ?></th>
                                        <th style="width: 10%; text-align: right;"><?php _e('Aktionen', 'sessiontags'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="sessiontags-parameter-rows">
                                    <?php if (!empty($parameters)) : ?>
                                        <?php foreach ($parameters as $index => $param) : ?>
                                            <tr class="parameter-row">
                                                <td>
                                                    <input type="text" name="sessiontags_parameters[<?php echo (int) $index; ?>][name]" value="<?php echo esc_attr($param['name']); ?>" class="regular-text sessiontags-parameter-name" placeholder="<?php _e('z.B. quelle', 'sessiontags'); ?>" required>
                                                </td>
                                                <td>
                                                    <input type="text" name="sessiontags_parameters[<?php echo (int) $index; ?>][shortcode]" value="<?php echo esc_attr($param['shortcode'] ?? ''); ?>" class="small-text sessiontags-parameter-shortcode" placeholder="<?php _e('z.B. q', 'sessiontags'); ?>">
                                                </td>
                                                <td>
                                                    <input type="text" name="sessiontags_parameters[<?php echo (int) $index; ?>][fallback]" value="<?php echo esc_attr($param['fallback'] ?? ''); ?>" class="regular-text sessiontags-parameter-fallback" placeholder="<?php _e('Standard-Fallback', 'sessiontags'); ?>">
                                                </td>
                                                <td>
                                                    <input type="url" name="sessiontags_parameters[<?php echo (int) $index; ?>][redirect_url]" value="<?php echo esc_attr($param['redirect_url'] ?? ''); ?>" class="regular-text sessiontags-parameter-redirect-url" placeholder="<?php _e('https://beispiel.de/zielseite', 'sessiontags'); ?>">
                                                </td>
                                                <td class="actions">
                                                    <button type="button" class="button button-link-delete remove-parameter" title="<?php _e('Parameter entfernen', 'sessiontags'); ?>">
                                                        <span class="dashicons dashicons-trash"></span>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <tr class="parameter-row">
                                            <td>
                                                <input type="text" name="sessiontags_parameters[0][name]" value="quelle" class="regular-text sessiontags-parameter-name" placeholder="<?php _e('z.B. quelle', 'sessiontags'); ?>" required>
                                            </td>
                                            <td>
                                                <input type="text" name="sessiontags_parameters[0][shortcode]" value="q" class="small-text sessiontags-parameter-shortcode" placeholder="<?php _e('z.B. q', 'sessiontags'); ?>">
                                            </td>
                                            <td>
                                                <input type="text" name="sessiontags_parameters[0][fallback]" value="" class="regular-text sessiontags-parameter-fallback" placeholder="<?php _e('Standard-Fallback', 'sessiontags'); ?>">
                                            </td>
                                            <td>
                                                <input type="url" name="sessiontags_parameters[0][redirect_url]" value="" class="regular-text sessiontags-parameter-redirect-url" placeholder="<?php _e('https://beispiel.de/zielseite', 'sessiontags'); ?>">
                                            </td>
                                            <td class="actions">
                                                <button type="button" class="button button-link-delete remove-parameter" title="<?php _e('Parameter entfernen', 'sessiontags'); ?>">
                                                    <span class="dashicons dashicons-trash"></span>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="button button-secondary add-parameter">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php _e('Parameter hinzufügen', 'sessiontags'); ?>
                        </button>
                    </div>
                </div>

                <div class="postbox">
                    <h2 class="hndle"><span class="dashicons dashicons-lock"></span> <?php _e('URL-Verschleierung', 'sessiontags'); ?></h2>
                    <div class="inside">
                        <p class="description">
                            <?php _e('Verschleiern Sie URL-Parameter, um die Lesbarkeit zu erschweren und Manipulationen zu verhindern.', 'sessiontags'); ?>
                        </p>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Verschleierung aktivieren', 'sessiontags'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="sessiontags_url_encoding" value="1" <?php checked(1, $use_encoding); ?>>
                                        <?php _e('URL-Parameter verschleiern', 'sessiontags'); ?>
                                    </label>
                                    <p class="description">
                                        <?php _e('Wenn aktiviert, werden Parameter-Werte vor der Übertragung verschlüsselt.', 'sessiontags'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Geheimer Schlüssel', 'sessiontags'); ?></th>
                                <td>
                                    <div class="secret-key-wrapper">
                                        <input type="password" id="secret-key" name="sessiontags_secret_key" value="<?php echo esc_attr($secret_key); ?>" class="regular-text" readonly>
                                        <button type="button" class="button regenerate-key">
                                            <span class="dashicons dashicons-update"></span>
                                            <?php _e('Neu generieren', 'sessiontags'); ?>
                                        </button>
                                    </div>
                                    <p class="description">
                                        <?php _e('Dieser Schlüssel wird für die Verschlüsselung verwendet. Eine Änderung macht bestehende verschlüsselte URLs ungültig.', 'sessiontags'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <?php submit_button(__('Einstellungen speichern', 'sessiontags'), 'primary', 'submit', true, ['id' => 'submit-settings']); ?>
        </form>
    <?php
    }

    /**
     * Rendert den URL-Builder-Tab
     */
    private function render_url_builder_tab()
    {
    ?>
        <div id="poststuff">
            <div class="postbox">
                <h2 class="hndle"><span class="dashicons dashicons-admin-links"></span> <?php _e('URL-Builder', 'sessiontags'); ?></h2>
                <div class="inside">
                    <p class="description">
                        <?php _e('Erstellen Sie ganz einfach Tracking-URLs mit Ihren konfigurierten Parametern.', 'sessiontags'); ?>
                    </p>

                    <div class="url-builder-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="base-url"><?php _e('Basis-URL', 'sessiontags'); ?></label>
                                </th>
                                <td>
                                    <input type="url" id="base-url" value="<?php echo esc_url(home_url('/')); ?>" class="large-text" placeholder="https://ihre-website.de/seite/">
                                    <p class="description">
                                        <?php _e('Die URL, an die die Parameter angehängt werden sollen.', 'sessiontags'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <h3><?php _e('Parameter hinzufügen', 'sessiontags'); ?></h3>
                        <div id="url-builder-params"></div>

                        <p>
                            <button type="button" class="button button-secondary add-builder-param">
                                <span class="dashicons dashicons-plus-alt"></span>
                                <?php _e('Parameter hinzufügen', 'sessiontags'); ?>
                            </button>
                        </p>
                    </div>

                    <div class="sessiontags-generated-url">
                        <h3><?php _e('Generierte URL', 'sessiontags'); ?></h3>
                        <div class="url-output">
                            <textarea id="generated-url" readonly class="large-text code" rows="3" placeholder="<?php _e('Die URL wird hier angezeigt...', 'sessiontags'); ?>"></textarea>
                            <p>
                                <button type="button" class="button copy-url" data-target="generated-url">
                                    <span class="dashicons dashicons-clipboard"></span>
                                    <?php _e('URL kopieren', 'sessiontags'); ?>
                                </button>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="postbox">
                <h2 class="hndle"><span class="dashicons dashicons-info"></span> <?php _e('Verwendungshinweise', 'sessiontags'); ?></h2>
                <div class="inside">
                    <ul>
                        <li><?php _e('Wählen Sie einen Parameter aus der Liste und geben Sie einen Wert ein.', 'sessiontags'); ?></li>
                        <li><?php _e('Fügen Sie weitere Parameter nach Bedarf hinzu.', 'sessiontags'); ?></li>
                        <li><?php _e('Die URL wird automatisch generiert und kann kopiert werden.', 'sessiontags'); ?></li>
                        <li><?php _e('Bei aktivierter Verschleierung werden die Werte in der Vorschau nur angedeutet. Die echte Verschlüsselung erfolgt serverseitig.', 'sessiontags'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    <?php
    }

    /**
     * Rendert die Dokumentations-Tabs
     */
    private function render_docs_tabs()
    {
        $current_doc_tab = isset($_GET['doc_tab']) ? sanitize_key($_GET['doc_tab']) : 'quickstart';
    ?>
        <div class="docs-wrapper">
            <div class="docs-nav">
                <a href="?page=sessiontags&tab=docs&doc_tab=quickstart" class="<?php echo $current_doc_tab === 'quickstart' ? 'current' : ''; ?>">
                    <span class="dashicons dashicons-lightbulb"></span> <?php _e('Schnellstart', 'sessiontags'); ?>
                </a>
                <a href="?page=sessiontags&tab=docs&doc_tab=shortcodes" class="<?php echo $current_doc_tab === 'shortcodes' ? 'current' : ''; ?>">
                    <span class="dashicons dashicons-shortcode"></span> <?php _e('Shortcodes', 'sessiontags'); ?>
                </a>
                <a href="?page=sessiontags&tab=docs&doc_tab=integrations" class="<?php echo $current_doc_tab === 'integrations' ? 'current' : ''; ?>">
                    <span class="dashicons dashicons-admin-plugins"></span> <?php _e('Integrationen', 'sessiontags'); ?>
                </a>
                <a href="?page=sessiontags&tab=docs&doc_tab=examples" class="<?php echo $current_doc_tab === 'examples' ? 'current' : ''; ?>">
                    <span class="dashicons dashicons-format-aside"></span> <?php _e('Beispiele', 'sessiontags'); ?>
                </a>
            </div>
            <div class="docs-content">
                <div id="poststuff">
                    <?php
                    switch ($current_doc_tab) {
                        case 'quickstart':
                            $this->render_quickstart_tab();
                            break;
                        case 'shortcodes':
                            $this->render_shortcodes_tab();
                            break;
                        case 'integrations':
                            $this->render_integrations_tab();
                            break;
                        case 'examples':
                            $this->render_examples_tab();
                            break;
                    }
                    ?>
                </div>
            </div>
        </div>
    <?php
    }


    /**
     * Rendert den Schnellstart-Tab
     */
    private function render_quickstart_tab()
    {
    ?>
        <div class="postbox">
            <h2 class="hndle"><span class="dashicons dashicons-lightbulb"></span> <?php _e('Schnellstart-Anleitung', 'sessiontags'); ?></h2>
            <div class="inside">
                <h3><?php _e('Was ist SessionTags?', 'sessiontags'); ?></h3>
                <p>
                    <?php _e('SessionTags erfasst Parameter aus URLs (wie ?quelle=google&kampagne=sommer2024) und speichert sie in der Benutzer-Session. Diese Werte können dann überall auf der Website angezeigt werden – perfekt für Tracking, Personalisierung und Marketing-Kampagnen.', 'sessiontags'); ?>
                </p>

                <h3><?php _e('In 3 Schritten zur ersten Tracking-URL', 'sessiontags'); ?></h3>
                <ol class="sessiontags-steps">
                    <li>
                        <strong><?php _e('Parameter konfigurieren', 'sessiontags'); ?></strong><br>
                        <?php _e('Gehen Sie zum Tab "Einstellungen" und definieren Sie Ihre Parameter (z.B. "quelle" mit Kürzel "q").', 'sessiontags'); ?>
                    </li>
                    <li>
                        <strong><?php _e('URL erstellen', 'sessiontags'); ?></strong><br>
                        <?php _e('Nutzen Sie den "URL-Builder" oder erstellen Sie manuell: ', 'sessiontags'); ?>
                        <code><?php echo esc_url(home_url('/')); ?>?q=google</code>
                    </li>
                    <li>
                        <strong><?php _e('Wert anzeigen', 'sessiontags'); ?></strong><br>
                        <?php _e('Verwenden Sie den Shortcode ', 'sessiontags'); ?><code>[st k="quelle"]</code><?php _e(' in Ihren Inhalten.', 'sessiontags'); ?>
                    </li>
                </ol>

                <h3><?php _e('Praktisches Beispiel', 'sessiontags'); ?></h3>
                <p><?php _e('Stellen Sie sich vor, ein Besucher kommt über diese URL auf Ihre Website:', 'sessiontags'); ?></p>
                <div class="code-example">
                    <code><?php echo esc_url(home_url('/')); ?>?quelle=google&kampagne=sommer2024</code>
                </div>

                <p><?php _e('Jetzt können Sie überall auf der Website anzeigen:', 'sessiontags'); ?></p>
                <ul>
                    <li><code>[st k="quelle"]</code> → <strong>google</strong></li>
                    <li><code>[st k="kampagne"]</code> → <strong>sommer2024</strong></li>
                </ul>

                <p><?php _e('In einem Text würde das so aussehen:', 'sessiontags'); ?></p>
                <div class="code-example">
                    <?php _e('Willkommen! Sie kommen von: <strong>[st k="quelle"]</strong>', 'sessiontags'); ?>
                </div>
                <p><?php _e('Ergebnis: "Willkommen! Sie kommen von: <strong>google</strong>"', 'sessiontags'); ?></p>
            </div>
        </div>
    <?php
    }

    /**
     * Rendert den Shortcodes-Tab
     */
    private function render_shortcodes_tab()
    {
    ?>
        <div class="postbox">
            <h2 class="hndle"><span class="dashicons dashicons-shortcode"></span> <?php _e('Shortcode-Referenz', 'sessiontags'); ?></h2>
            <div class="inside">
                <h3><?php _e('Parameter anzeigen: [st]', 'sessiontags'); ?></h3>
                <p><?php _e('Der Haupt-Shortcode zur Anzeige von Session-Parametern.', 'sessiontags'); ?></p>

                <h4><?php _e('Grundlegende Syntax', 'sessiontags'); ?></h4>
                <div class="code-example">
                    <code>[st k="parametername"]</code>
                </div>

                <h4><?php _e('Attribute', 'sessiontags'); ?></h4>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Attribut', 'sessiontags'); ?></th>
                            <th><?php _e('Beschreibung', 'sessiontags'); ?></th>
                            <th><?php _e('Beispiel', 'sessiontags'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>k</code> oder <code>key</code></td>
                            <td><?php _e('Der Name des Parameters (erforderlich)', 'sessiontags'); ?></td>
                            <td><code>[st k="quelle"]</code></td>
                        </tr>
                        <tr>
                            <td><code>d</code> oder <code>default</code></td>
                            <td><?php _e('Fallback-Wert, wenn Parameter nicht existiert', 'sessiontags'); ?></td>
                            <td><code>[st k="quelle" d="direkt"]</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="postbox">
            <h2 class="hndle"><span class="dashicons dashicons-admin-links"></span> <?php _e('URL generieren: [st_url]', 'sessiontags'); ?></h2>
            <div class="inside">
                <p><?php _e('Erstellt Links mit automatisch übertragenen Parametern.', 'sessiontags'); ?></p>
                <h4><?php _e('Attribute', 'sessiontags'); ?></h4>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Attribut', 'sessiontags'); ?></th>
                            <th><?php _e('Beschreibung', 'sessiontags'); ?></th>
                            <th><?php _e('Standard', 'sessiontags'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>url</code></td>
                            <td><?php _e('Ziel-URL', 'sessiontags'); ?></td>
                            <td><?php _e('Aktuelle Homepage', 'sessiontags'); ?></td>
                        </tr>
                        <tr>
                            <td><code>params</code></td>
                            <td><?php _e('Parameter im Format "name=wert" (kommagetrennt)', 'sessiontags'); ?></td>
                            <td><?php _e('Keine', 'sessiontags'); ?></td>
                        </tr>
                        <tr>
                            <td><code>class</code></td>
                            <td><?php _e('CSS-Klasse für den Link', 'sessiontags'); ?></td>
                            <td><?php _e('Keine', 'sessiontags'); ?></td>
                        </tr>
                        <tr>
                            <td><code>title</code></td>
                            <td><?php _e('Title-Attribut für den Link', 'sessiontags'); ?></td>
                            <td><?php _e('Keiner', 'sessiontags'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="postbox">
            <h2 class="hndle"><span class="dashicons dashicons-forms"></span> <?php _e('Formulare: [st_form]', 'sessiontags'); ?></h2>
            <div class="inside">
                <p><?php _e('Bindet externe Formulare (Google Forms, Microsoft Forms) mit automatisch ausgefüllten Parametern ein.', 'sessiontags'); ?></p>
                <h4><?php _e('Attribute', 'sessiontags'); ?></h4>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Attribut', 'sessiontags'); ?></th>
                            <th><?php _e('Beschreibung', 'sessiontags'); ?></th>
                            <th><?php _e('Standard', 'sessiontags'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>type</code></td>
                            <td><?php _e('Formular-Typ: "google" oder "microsoft"', 'sessiontags'); ?></td>
                            <td><code>google</code></td>
                        </tr>
                        <tr>
                            <td><code>url</code></td>
                            <td><?php _e('URL des Formulars (erforderlich)', 'sessiontags'); ?></td>
                            <td><?php _e('Keine', 'sessiontags'); ?></td>
                        </tr>
                        <tr>
                            <td><code>params</code></td>
                            <td><?php _e('Session-Parameter (kommagetrennt)', 'sessiontags'); ?></td>
                            <td><?php _e('Keine', 'sessiontags'); ?></td>
                        </tr>
                        <tr>
                            <td><code>form_params</code></td>
                            <td><?php _e('Formular-Feld-IDs (kommagetrennt)', 'sessiontags'); ?></td>
                            <td><?php _e('Wie params', 'sessiontags'); ?></td>
                        </tr>
                        <tr>
                            <td><code>width</code></td>
                            <td><?php _e('Breite des iFrames', 'sessiontags'); ?></td>
                            <td><code>100%</code></td>
                        </tr>
                        <tr>
                            <td><code>height</code></td>
                            <td><?php _e('Höhe des iFrames', 'sessiontags'); ?></td>
                            <td><code>800px</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    <?php
    }

    /**
     * Rendert den Integrationen-Tab
     */
    private function render_integrations_tab()
    {
    ?>
        <div class="postbox">
            <h2 class="hndle"><span class="dashicons dashicons-admin-plugins"></span> <?php _e('Plugin-Integrationen', 'sessiontags'); ?></h2>
            <div class="inside">
                <h3><span class="dashicons dashicons-elementor" style="color: #9B3C8E;"></span> <?php _e('Elementor Integration', 'sessiontags'); ?></h3>
                <p><?php _e('SessionTags integriert sich nahtlos in Elementor (Free und Pro) mit Dynamic Tags, Display Conditions und Form Actions.', 'sessiontags'); ?></p>

                <h4><?php _e('Dynamic Tags (Elementor Free + Pro)', 'sessiontags'); ?></h4>
                <ol>
                    <li><?php _e('Bearbeiten Sie ein beliebiges Textfeld in Elementor', 'sessiontags'); ?></li>
                    <li><?php _e('Klicken Sie auf das Dynamic-Content-Symbol', 'sessiontags'); ?> <span class="dashicons dashicons-database"></span></li>
                    <li><?php _e('Wählen Sie "SessionTags" aus der Kategorie-Liste', 'sessiontags'); ?></li>
                    <li><?php _e('Wählen Sie Ihren Parameter und optional einen Fallback-Wert', 'sessiontags'); ?></li>
                </ol>

                <h4><?php _e('Display Conditions (nur Elementor Pro)', 'sessiontags'); ?></h4>
                <p><?php _e('Zeigen oder verstecken Sie Elemente basierend auf SessionTags-Parametern.', 'sessiontags'); ?></p>

                <h4><?php _e('Form Actions (nur Elementor Pro)', 'sessiontags'); ?></h4>
                <p><?php _e('Speichern Sie Formular-Einträge automatisch in SessionTags.', 'sessiontags'); ?></p>
            </div>
        </div>
        <div class="postbox">
            <h2 class="hndle"><span class="dashicons dashicons-admin-appearance" style="color: #E74C3C;"></span> <?php _e('Avada Fusion Builder Integration', 'sessiontags'); ?></h2>
            <div class="inside">
                <p><?php _e('Verwenden Sie SessionTags-Parameter direkt im Avada Fusion Builder.', 'sessiontags'); ?></p>
                <h4><?php _e('SessionTags-Element verwenden', 'sessiontags'); ?></h4>
                <ol>
                    <li><?php _e('Öffnen Sie den Fusion Builder', 'sessiontags'); ?></li>
                    <li><?php _e('Suchen Sie nach "SessionTags Parameter" in der Element-Liste', 'sessiontags'); ?></li>
                    <li><?php _e('Wählen Sie den gewünschten Parameter aus', 'sessiontags'); ?></li>
                </ol>
            </div>
        </div>
        <div class="postbox">
            <h2 class="hndle"><span class="dashicons dashicons-php"></span> <?php _e('PHP-Integration', 'sessiontags'); ?></h2>
            <div class="inside">
                <p><?php _e('Verwenden Sie SessionTags-Parameter in Ihrem PHP-Code.', 'sessiontags'); ?></p>

                <div class="code-example">
                    <code>
                        if (class_exists('SessionTagsSessionManager')) {<br>
                        &nbsp;&nbsp;$session_manager = new SessionTagsSessionManager();<br>
                        &nbsp;&nbsp;$session_manager->init();<br><br>
                        &nbsp;&nbsp;$quelle = $session_manager->get_param('quelle', 'unbekannt');<br>
                        &nbsp;&nbsp;echo 'Besucher kommt von: ' . esc_html($quelle);<br>
                        }
                    </code>
                </div>
            </div>
        </div>
    <?php
    }

    /**
     * Rendert den Beispiele-Tab
     */
    private function render_examples_tab()
    {
    ?>
        <div class="postbox">
            <h2 class="hndle"><span class="dashicons dashicons-format-aside"></span> <?php _e('Praktische Anwendungsbeispiele', 'sessiontags'); ?></h2>
            <div class="inside">
                <h3><?php _e('Kampagnen-Tracking', 'sessiontags'); ?></h3>
                <p><?php _e('Verfolgen Sie die Herkunft Ihrer Besucher und passen Sie Inhalte entsprechend an.', 'sessiontags'); ?></p>
                <div class="code-example">
                    <?php _e('URL für Google Ads:', 'sessiontags'); ?><br>
                    <code><?php echo home_url('/'); ?>?quelle=google&kampagne=winter2024</code><br><br>
                    <?php _e('Anzeige auf der Website:', 'sessiontags'); ?><br>
                    <code><?php _e('Willkommen! Sie kommen von: [st k="quelle" d="unserer Website"]', 'sessiontags'); ?></code>
                </div>

                <h3><?php _e('Personalisierte Begrüssung', 'sessiontags'); ?></h3>
                <p><?php _e('Erstellen Sie personalisierte Inhalte basierend auf der Herkunft der Besucher.', 'sessiontags'); ?></p>
                <div class="code-example">
                    <?php _e('Mit Elementor Display Conditions können Sie ganzen Sektionen nur für Besucher von "facebook" anzeigen.', 'sessiontags'); ?>
                </div>

                <h3><?php _e('Lead-Formulare mit Tracking', 'sessiontags'); ?></h3>
                <p><?php _e('Übertragen Sie Tracking-Informationen automatisch in Ihre Formulare (z.B. mit der Elementor Pro Form Action).', 'sessiontags'); ?></p>
            </div>
        </div>
        <div class="postbox">
            <h2 class="hndle"><span class="dashicons dashicons-editor-help"></span> <?php _e('Häufige Fragen (FAQ)', 'sessiontags'); ?></h2>
            <div class="inside">
                <h4><?php _e('Wie lange bleiben Parameter gespeichert?', 'sessiontags'); ?></h4>
                <p><?php _e('Parameter bleiben für die gesamte Browser-Session gespeichert, bis der Browser geschlossen wird oder die Session explizit gelöscht wird.', 'sessiontags'); ?></p>

                <h4><?php _e('Funktioniert das mit WordPress Caching?', 'sessiontags'); ?></h4>
                <p><?php _e('Ja, da die Parameter serverseitig in der PHP-Session gespeichert und die Shortcodes bei jedem Seitenaufruf verarbeitet werden, ist es mit Caching-Plugins kompatibel.', 'sessiontags'); ?></p>

                <h4><?php _e('Ist die URL-Verschleierung sicher?', 'sessiontags'); ?></h4>
                <p><?php _e('Die Verschleierung erschwert das Lesen und Manipulieren von Parametern, bietet aber keinen Schutz für hochsensible Daten. Verwenden Sie es für Marketing-Parameter, nicht für kritische Sicherheitsdaten.', 'sessiontags'); ?></p>
            </div>
        </div>
<?php
    }
}
