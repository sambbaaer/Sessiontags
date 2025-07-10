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
            ['jquery'],
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
        $parameters = $this->session_manager->get_tracked_parameters();
        $use_encoding = $this->session_manager->is_url_encoding_enabled();
        $secret_key = get_option('sessiontags_secret_key', '');
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
                <a href="?page=sessiontags&tab=docs_quickstart" class="nav-tab <?php echo $this->active_tab === 'docs_quickstart' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-lightbulb"></span>
                    <?php _e('Schnellstart', 'sessiontags'); ?>
                </a>
                <a href="?page=sessiontags&tab=docs_shortcodes" class="nav-tab <?php echo $this->active_tab === 'docs_shortcodes' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-shortcode"></span>
                    <?php _e('Shortcodes', 'sessiontags'); ?>
                </a>
                <a href="?page=sessiontags&tab=docs_integrations" class="nav-tab <?php echo $this->active_tab === 'docs_integrations' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-plugins"></span>
                    <?php _e('Integrationen', 'sessiontags'); ?>
                </a>
                <a href="?page=sessiontags&tab=docs_examples" class="nav-tab <?php echo $this->active_tab === 'docs_examples' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-format-aside"></span>
                    <?php _e('Beispiele', 'sessiontags'); ?>
                </a>
            </nav>

            <div class="tab-content">
                <?php if ($this->active_tab === 'settings') : ?>
                    <?php $this->render_settings_tab($parameters, $use_encoding, $secret_key); ?>

                <?php elseif ($this->active_tab === 'url_builder') : ?>
                    <?php $this->render_url_builder_tab(); ?>

                <?php elseif ($this->active_tab === 'docs_quickstart') : ?>
                    <?php $this->render_quickstart_tab(); ?>

                <?php elseif ($this->active_tab === 'docs_shortcodes') : ?>
                    <?php $this->render_shortcodes_tab(); ?>

                <?php elseif ($this->active_tab === 'docs_integrations') : ?>
                    <?php $this->render_integrations_tab(); ?>

                <?php elseif ($this->active_tab === 'docs_examples') : ?>
                    <?php $this->render_examples_tab(); ?>

                <?php endif; ?>
            </div>
        </div>
    <?php
    }

    /**
     * Rendert den Einstellungen-Tab
     */
    private function render_settings_tab($parameters, $use_encoding, $secret_key)
    {
    ?>
        <form method="post" action="options.php">
            <?php settings_fields('sessiontags_settings'); ?>

            <div class="sessiontags-card">
                <h2><span class="dashicons dashicons-admin-settings"></span> <?php _e('Parameter-Konfiguration', 'sessiontags'); ?></h2>
                <p class="description">
                    <?php _e('Definieren Sie die Parameter, die aus URLs erkannt und in der Session gespeichert werden sollen.', 'sessiontags'); ?>
                </p>

                <div class="sessiontags-table-wrapper">
                    <table class="widefat sessiontags-parameter-table">
                        <thead>
                            <tr>
                                <th style="width: 25%;"><?php _e('Parameter-Name', 'sessiontags'); ?> <span class="required">*</span></th>
                                <th style="width: 15%;"><?php _e('URL-Kürzel', 'sessiontags'); ?></th>
                                <th style="width: 25%;"><?php _e('Standard-Fallback', 'sessiontags'); ?></th>
                                <th style="width: 25%;"><?php _e('Weiterleitung', 'sessiontags'); ?></th>
                                <th style="width: 10%;"><?php _e('Aktionen', 'sessiontags'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="sessiontags-parameter-rows">
                            <?php if (!empty($parameters)) : ?>
                                <?php foreach ($parameters as $index => $param) : ?>
                                    <tr class="parameter-row">
                                        <td>
                                            <input type="text"
                                                name="sessiontags_parameters[<?php echo (int) $index; ?>][name]"
                                                value="<?php echo esc_attr($param['name']); ?>"
                                                class="regular-text sessiontags-parameter-name"
                                                placeholder="<?php _e('z.B. quelle', 'sessiontags'); ?>"
                                                required>
                                        </td>
                                        <td>
                                            <input type="text"
                                                name="sessiontags_parameters[<?php echo (int) $index; ?>][shortcode]"
                                                value="<?php echo esc_attr($param['shortcode'] ?? ''); ?>"
                                                class="small-text sessiontags-parameter-shortcode"
                                                placeholder="<?php _e('z.B. q', 'sessiontags'); ?>">
                                        </td>
                                        <td>
                                            <input type="text"
                                                name="sessiontags_parameters[<?php echo (int) $index; ?>][fallback]"
                                                value="<?php echo esc_attr($param['fallback'] ?? ''); ?>"
                                                class="regular-text sessiontags-parameter-fallback"
                                                placeholder="<?php _e('Standard-Fallback', 'sessiontags'); ?>">
                                        </td>
                                        <td>
                                            <input type="url"
                                                name="sessiontags_parameters[<?php echo (int) $index; ?>][redirect_url]"
                                                value="<?php echo esc_attr($param['redirect_url'] ?? ''); ?>"
                                                class="regular-text sessiontags-parameter-redirect-url"
                                                placeholder="<?php _e('https://beispiel.de/zielseite', 'sessiontags'); ?>">
                                        </td>
                                        <td>
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
                                    <td>
                                        <button type="button" class="button button-link-delete remove-parameter" title="<?php _e('Parameter entfernen', 'sessiontags'); ?>">
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <p class="submit">
                    <button type="button" class="button button-secondary add-parameter">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Parameter hinzufügen', 'sessiontags'); ?>
                    </button>
                </p>
            </div>

            <div class="sessiontags-card">
                <h2><span class="dashicons dashicons-lock"></span> <?php _e('URL-Verschleierung', 'sessiontags'); ?></h2>
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
                                <input type="password"
                                    id="secret-key"
                                    name="sessiontags_secret_key"
                                    value="<?php echo esc_attr($secret_key); ?>"
                                    class="regular-text"
                                    readonly>
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
        <div class="sessiontags-card">
            <h2><span class="dashicons dashicons-admin-links"></span> <?php _e('URL-Builder', 'sessiontags'); ?></h2>
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
                            <input type="url"
                                id="base-url"
                                value="<?php echo esc_url(home_url('/')); ?>"
                                class="large-text"
                                placeholder="https://ihre-website.de/seite/">
                            <p class="description">
                                <?php _e('Die URL, an die die Parameter angehängt werden sollen.', 'sessiontags'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <h3><?php _e('Parameter hinzufügen', 'sessiontags'); ?></h3>
                <div id="url-builder-params"></div>

                <p class="submit">
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

        <div class="sessiontags-card">
            <h3><span class="dashicons dashicons-info"></span> <?php _e('Verwendungshinweise', 'sessiontags'); ?></h3>
            <ul>
                <li><?php _e('Wählen Sie einen Parameter aus der Liste und geben Sie einen Wert ein', 'sessiontags'); ?></li>
                <li><?php _e('Fügen Sie weitere Parameter nach Bedarf hinzu', 'sessiontags'); ?></li>
                <li><?php _e('Die URL wird automatisch generiert und kann kopiert werden', 'sessiontags'); ?></li>
                <li><?php _e('Bei aktivierter Verschleierung werden die Werte automatisch verschlüsselt', 'sessiontags'); ?></li>
            </ul>
        </div>
    <?php
    }

    /**
     * Rendert den Schnellstart-Tab
     */
    private function render_quickstart_tab()
    {
    ?>
        <div class="sessiontags-card">
            <h2><span class="dashicons dashicons-lightbulb"></span> <?php _e('Schnellstart-Anleitung', 'sessiontags'); ?></h2>

            <div class="postbox">
                <div class="inside">
                    <h3><?php _e('Was ist SessionTags?', 'sessiontags'); ?></h3>
                    <p>
                        <?php _e('SessionTags erfasst Parameter aus URLs (wie ?quelle=google&kampagne=sommer2024) und speichert sie in der Benutzer-Session. Diese Werte können dann überall auf der Website angezeigt werden – perfekt für Tracking, Personalisierung und Marketing-Kampagnen.', 'sessiontags'); ?>
                    </p>
                </div>
            </div>

            <div class="postbox">
                <div class="inside">
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
                </div>
            </div>

            <div class="postbox">
                <div class="inside">
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

            <div class="postbox">
                <div class="inside">
                    <h3><?php _e('Nächste Schritte', 'sessiontags'); ?></h3>
                    <ul>
                        <li><a href="?page=sessiontags&tab=docs_shortcodes"><?php _e('Alle Shortcodes kennenlernen', 'sessiontags'); ?></a></li>
                        <li><a href="?page=sessiontags&tab=docs_integrations"><?php _e('Elementor & andere Integrationen entdecken', 'sessiontags'); ?></a></li>
                        <li><a href="?page=sessiontags&tab=docs_examples"><?php _e('Praktische Anwendungsbeispiele ansehen', 'sessiontags'); ?></a></li>
                    </ul>
                </div>
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
        <div class="sessiontags-card">
            <h2><span class="dashicons dashicons-shortcode"></span> <?php _e('Shortcode-Referenz', 'sessiontags'); ?></h2>

            <div class="postbox">
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

                    <h4><?php _e('Beispiele', 'sessiontags'); ?></h4>
                    <ul>
                        <li><code>[st k="quelle"]</code> - <?php _e('Zeigt den Parameter "quelle" an', 'sessiontags'); ?></li>
                        <li><code>[st k="kampagne" d="standard"]</code> - <?php _e('Mit Fallback-Wert "standard"', 'sessiontags'); ?></li>
                        <li><code>[st key="quelle" default="unbekannt"]</code> - <?php _e('Lange Attribut-Namen', 'sessiontags'); ?></li>
                    </ul>
                </div>
            </div>

            <div class="postbox">
                <div class="inside">
                    <h3><?php _e('URL generieren: [st_url]', 'sessiontags'); ?></h3>
                    <p><?php _e('Erstellt Links mit automatisch übertragenen Parametern.', 'sessiontags'); ?></p>

                    <h4><?php _e('Grundlegende Syntax', 'sessiontags'); ?></h4>
                    <div class="code-example">
                        <code>[st_url url="https://zielseite.de" params="quelle=wert,kampagne=wert2"]Linktext[/st_url]</code>
                    </div>

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

                    <h4><?php _e('Beispiele', 'sessiontags'); ?></h4>
                    <div class="code-example">
                        <code>[st_url url="/kontakt/" params="quelle=website"]Kontakt[/st_url]</code><br>
                        <small><?php _e('Erstellt: &lt;a href="/kontakt/?q=website"&gt;Kontakt&lt;/a&gt;', 'sessiontags'); ?></small>
                    </div>

                    <div class="code-example">
                        <code>[st_url params="quelle=newsletter,kampagne=december" class="button"]Jetzt bestellen[/st_url]</code><br>
                        <small><?php _e('Verwendet Homepage als Basis-URL', 'sessiontags'); ?></small>
                    </div>
                </div>
            </div>

            <div class="postbox">
                <div class="inside">
                    <h3><?php _e('Formulare: [st_form]', 'sessiontags'); ?></h3>
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

                    <h4><?php _e('Beispiel für Google Forms', 'sessiontags'); ?></h4>
                    <div class="code-example">
                        <code>[st_form type="google" url="https://docs.google.com/forms/d/e/FORM_ID/viewform" params="quelle,name" form_params="entry.123456789,entry.987654321"]</code>
                    </div>
                </div>
            </div>

            <div class="postbox">
                <div class="inside">
                    <h3><?php _e('Alternativer Shortcode: [show_session_param]', 'sessiontags'); ?></h3>
                    <p><?php _e('Identisch mit [st], aber mit ausgeschriebenen Attribut-Namen.', 'sessiontags'); ?></p>
                    <div class="code-example">
                        <code>[show_session_param key="quelle" default="direkt"]</code>
                    </div>
                </div>
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
        <div class="sessiontags-card">
            <h2><span class="dashicons dashicons-admin-plugins"></span> <?php _e('Plugin-Integrationen', 'sessiontags'); ?></h2>

            <div class="postbox">
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
                    <ul>
                        <li><strong><?php _e('Parameter existiert', 'sessiontags'); ?></strong> - <?php _e('Prüft, ob ein Parameter vorhanden ist', 'sessiontags'); ?></li>
                        <li><strong><?php _e('Parameter hat Wert', 'sessiontags'); ?></strong> - <?php _e('Prüft auf einen bestimmten Wert', 'sessiontags'); ?></li>
                        <li><strong><?php _e('Parameter ist einer von', 'sessiontags'); ?></strong> - <?php _e('Prüft gegen mehrere mögliche Werte', 'sessiontags'); ?></li>
                    </ul>

                    <h4><?php _e('Form Actions (nur Elementor Pro)', 'sessiontags'); ?></h4>
                    <p><?php _e('Speichern Sie Formular-Einträge automatisch in SessionTags.', 'sessiontags'); ?></p>
                    <ol>
                        <li><?php _e('Erstellen Sie ein Elementor Pro Form Widget', 'sessiontags'); ?></li>
                        <li><?php _e('Gehen Sie zu "Actions After Submit"', 'sessiontags'); ?></li>
                        <li><?php _e('Wählen Sie "In SessionTag speichern"', 'sessiontags'); ?></li>
                        <li><?php _e('Ordnen Sie Formularfelder zu SessionTags-Parametern zu', 'sessiontags'); ?></li>
                    </ol>
                </div>
            </div>

            <div class="postbox">
                <div class="inside">
                    <h3><span class="dashicons dashicons-admin-appearance" style="color: #E74C3C;"></span> <?php _e('Avada Fusion Builder Integration', 'sessiontags'); ?></h3>
                    <p><?php _e('Verwenden Sie SessionTags-Parameter direkt im Avada Fusion Builder.', 'sessiontags'); ?></p>

                    <h4><?php _e('SessionTags-Element verwenden', 'sessiontags'); ?></h4>
                    <ol>
                        <li><?php _e('Öffnen Sie den Fusion Builder', 'sessiontags'); ?></li>
                        <li><?php _e('Suchen Sie nach "SessionTags Parameter" in der Element-Liste', 'sessiontags'); ?></li>
                        <li><?php _e('Wählen Sie den gewünschten Parameter aus', 'sessiontags'); ?></li>
                        <li><?php _e('Konfigurieren Sie optional HTML-Element, CSS-Klassen und ID', 'sessiontags'); ?></li>
                    </ol>

                    <h4><?php _e('Verfügbare Optionen', 'sessiontags'); ?></h4>
                    <ul>
                        <li><strong><?php _e('Parameter', 'sessiontags'); ?></strong> - <?php _e('Wählen Sie aus Ihren konfigurierten Parametern', 'sessiontags'); ?></li>
                        <li><strong><?php _e('Individueller Fallback', 'sessiontags'); ?></strong> - <?php _e('Überschreibt den Standard-Fallback', 'sessiontags'); ?></li>
                        <li><strong><?php _e('HTML-Ausgabe', 'sessiontags'); ?></strong> - <?php _e('Wrapper-Element (span, div, h1-h6, oder keines)', 'sessiontags'); ?></li>
                        <li><strong><?php _e('CSS-Klasse & ID', 'sessiontags'); ?></strong> - <?php _e('Für individuelles Styling', 'sessiontags'); ?></li>
                    </ul>
                </div>
            </div>

            <div class="postbox">
                <div class="inside">
                    <h3><span class="dashicons dashicons-forms"></span> <?php _e('Externe Formulare', 'sessiontags'); ?></h3>

                    <h4><?php _e('Google Forms', 'sessiontags'); ?></h4>
                    <p><?php _e('Automatisches Ausfüllen von Google Forms mit Session-Parametern.', 'sessiontags'); ?></p>

                    <div class="notice notice-info inline">
                        <p><strong><?php _e('So finden Sie Google Forms Entry-IDs:', 'sessiontags'); ?></strong></p>
                        <ol>
                            <li><?php _e('Öffnen Sie Ihr Google Form im Bearbeitungsmodus', 'sessiontags'); ?></li>
                            <li><?php _e('Klicken Sie auf "Vorschau" (Augen-Symbol)', 'sessiontags'); ?></li>
                            <li><?php _e('Öffnen Sie die Entwicklertools (F12)', 'sessiontags'); ?></li>
                            <li><?php _e('Suchen Sie nach "entry." in der HTML-Quelle', 'sessiontags'); ?></li>
                            <li><?php _e('Die Zahlen nach "entry." sind Ihre Field-IDs', 'sessiontags'); ?></li>
                        </ol>
                    </div>

                    <h4><?php _e('Microsoft Forms', 'sessiontags'); ?></h4>
                    <p><?php _e('Unterstützung für Microsoft Forms mit einfacheren Parameter-Namen.', 'sessiontags'); ?></p>

                    <div class="code-example">
                        <code>[st_form type="microsoft" url="https://forms.office.com/..." params="quelle,kampagne"]</code>
                    </div>
                </div>
            </div>

            <div class="postbox">
                <div class="inside">
                    <h3><span class="dashicons dashicons-php"></span> <?php _e('PHP-Integration', 'sessiontags'); ?></h3>
                    <p><?php _e('Verwenden Sie SessionTags-Parameter in Ihrem PHP-Code.', 'sessiontags'); ?></p>

                    <div class="code-example">
                        <code>
                            // Session Manager erstellen<br>
                            $session_manager = new SessionTagsSessionManager();<br>
                            $session_manager->init();<br><br>

                            // Parameter abrufen<br>
                            $quelle = $session_manager->get_param('quelle', 'unbekannt');<br>
                            echo 'Besucher kommt von: ' . $quelle;
                        </code>
                    </div>
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
        <div class="sessiontags-card">
            <h2><span class="dashicons dashicons-format-aside"></span> <?php _e('Praktische Anwendungsbeispiele', 'sessiontags'); ?></h2>

            <div class="postbox">
                <div class="inside">
                    <h3><?php _e('Kampagnen-Tracking', 'sessiontags'); ?></h3>
                    <p><?php _e('Verfolgen Sie die Herkunft Ihrer Besucher und passen Sie Inhalte entsprechend an.', 'sessiontags'); ?></p>

                    <h4><?php _e('Szenario:', 'sessiontags'); ?></h4>
                    <p><?php _e('Sie führen verschiedene Marketing-Kampagnen durch und möchten wissen, welche am besten funktionieren.', 'sessiontags'); ?></p>

                    <h4><?php _e('Umsetzung:', 'sessiontags'); ?></h4>
                    <div class="code-example">
                        <?php _e('URL für Google Ads:', 'sessiontags'); ?><br>
                        <code><?php echo home_url('/'); ?>?quelle=google&kampagne=winter2024&medium=cpc</code><br><br>

                        <?php _e('URL für Facebook:', 'sessiontags'); ?><br>
                        <code><?php echo home_url('/'); ?>?quelle=facebook&kampagne=winter2024&medium=social</code><br><br>

                        <?php _e('URL für Newsletter:', 'sessiontags'); ?><br>
                        <code><?php echo home_url('/'); ?>?quelle=newsletter&kampagne=december&medium=email</code>
                    </div>

                    <h4><?php _e('Anzeige auf der Website:', 'sessiontags'); ?></h4>
                    <div class="code-example">
                        <strong><?php _e('Willkommen!', 'sessiontags'); ?></strong><br>
                        <?php _e('Sie kommen von: [st k="quelle" d="unserer Website"]', 'sessiontags'); ?><br>
                        <?php _e('Kampagne: [st k="kampagne" d="Standard"]', 'sessiontags'); ?>
                    </div>
                </div>
            </div>

            <div class="postbox">
                <div class="inside">
                    <h3><?php _e('Personalisierte Begrüssung', 'sessiontags'); ?></h3>
                    <p><?php _e('Erstellen Sie personalisierte Inhalte basierend auf der Herkunft der Besucher.', 'sessiontags'); ?></p>

                    <h4><?php _e('Mit Elementor Display Conditions:', 'sessiontags'); ?></h4>
                    <ul>
                        <li><?php _e('Zeigen Sie Google-Besuchern: "Danke für Ihre Google-Suche!"', 'sessiontags'); ?></li>
                        <li><?php _e('Zeigen Sie Facebook-Besuchern: "Willkommen von Facebook!"', 'sessiontags'); ?></li>
                        <li><?php _e('Zeigen Sie Newsletter-Abonnenten spezielle Angebote', 'sessiontags'); ?></li>
                    </ul>

                    <h4><?php _e('Mit Shortcodes im Text:', 'sessiontags'); ?></h4>
                    <div class="code-example">
                        <?php _e('Hallo! Schön, dass Sie [st k="quelle" d="zu uns gefunden haben"].', 'sessiontags'); ?><br>
                        <?php _e('Als [st k="quelle"]-Besucher erhalten Sie 10% Rabatt mit Code: [st k="kampagne"]10', 'sessiontags'); ?>
                    </div>
                </div>
            </div>

            <div class="postbox">
                <div class="inside">
                    <h3><?php _e('Lead-Formulare mit Tracking', 'sessiontags'); ?></h3>
                    <p><?php _e('Übertragen Sie Tracking-Informationen automatisch in Ihre Formulare.', 'sessiontags'); ?></p>

                    <h4><?php _e('Elementor Pro Formular:', 'sessiontags'); ?></h4>
                    <ol>
                        <li><?php _e('Erstellen Sie ein Kontaktformular', 'sessiontags'); ?></li>
                        <li><?php _e('Fügen Sie versteckte Felder für "quelle" und "kampagne" hinzu', 'sessiontags'); ?></li>
                        <li><?php _e('Konfigurieren Sie die SessionTags Form Action', 'sessiontags'); ?></li>
                        <li><?php _e('Alle Einträge enthalten automatisch Tracking-Daten', 'sessiontags'); ?></li>
                    </ol>

                    <h4><?php _e('Google Forms Integration:', 'sessiontags'); ?></h4>
                    <div class="code-example">
                        <code>[st_form type="google" url="https://docs.google.com/forms/d/e/IHRE_FORM_ID/viewform" params="quelle,kampagne,email" form_params="entry.123,entry.456,entry.789" height="600px"]</code>
                    </div>
                </div>
            </div>

            <div class="postbox">
                <div class="inside">
                    <h3><?php _e('E-Commerce Tracking', 'sessiontags'); ?></h3>
                    <p><?php _e('Verfolgen Sie die Customer Journey von der ersten Berührung bis zum Kauf.', 'sessiontags'); ?></p>

                    <h4><?php _e('Produktseiten personalisieren:', 'sessiontags'); ?></h4>
                    <div class="code-example">
                        <?php _e('Sie haben dieses Produkt über [st k="quelle" d="unsere Website"] gefunden.', 'sessiontags'); ?><br>
                        <?php _e('Spezialangebot für [st k="kampagne"]-Kunden: Kostenloser Versand!', 'sessiontags'); ?>
                    </div>

                    <h4><?php _e('Checkout-Formulare erweitern:', 'sessiontags'); ?></h4>
                    <p><?php _e('Fügen Sie versteckte Felder hinzu, um zu verfolgen, woher erfolgreiche Käufer kommen.', 'sessiontags'); ?></p>
                </div>
            </div>

            <div class="postbox">
                <div class="inside">
                    <h3><?php _e('Event-Tracking', 'sessiontags'); ?></h3>
                    <p><?php _e('Verfolgen Sie spezielle Events und Aktionen.', 'sessiontags'); ?></p>

                    <h4><?php _e('Webinar-Anmeldungen:', 'sessiontags'); ?></h4>
                    <div class="code-example">
                        <?php _e('URLs für verschiedene Kanäle:', 'sessiontags'); ?><br>
                        <code><?php echo home_url('/webinar/'); ?>?quelle=email&event=webinar_feb</code><br>
                        <code><?php echo home_url('/webinar/'); ?>?quelle=linkedin&event=webinar_feb</code><br>
                        <code><?php echo home_url('/webinar/'); ?>?quelle=website&event=webinar_feb</code>
                    </div>

                    <h4><?php _e('Anzeige auf der Anmeldeseite:', 'sessiontags'); ?></h4>
                    <div class="code-example">
                        <?php _e('Webinar-Anmeldung - [st k="event" d="Unser Event"]', 'sessiontags'); ?><br>
                        <?php _e('Teilnahme-Code: [st k="quelle"][st k="event"]', 'sessiontags'); ?>
                    </div>
                </div>
            </div>

            <div class="postbox">
                <div class="inside">
                    <h3><?php _e('Multi-Step Funnel', 'sessiontags'); ?></h3>
                    <p><?php _e('Führen Sie Besucher durch einen mehrstufigen Prozess.', 'sessiontags'); ?></p>

                    <h4><?php _e('Schritt 1 - Landing Page:', 'sessiontags'); ?></h4>
                    <div class="code-example">
                        <?php _e('Willkommen von [st k="quelle" d="unserer Website"]!', 'sessiontags'); ?><br>
                        [st_url url="/schritt2/" params="quelle=[st k=quelle],kampagne=[st k=kampagne],step=2"]<?php _e('Weiter zu Schritt 2', 'sessiontags'); ?>[/st_url]
                    </div>

                    <h4><?php _e('Schritt 2 - Weitere Informationen:', 'sessiontags'); ?></h4>
                    <div class="code-example">
                        <?php _e('Sie sind in Schritt [st k="step" d="1"] von 3.', 'sessiontags'); ?><br>
                        <?php _e('Ihre Kampagne: [st k="kampagne" d="Standard"]', 'sessiontags'); ?>
                    </div>
                </div>
            </div>

            <div class="postbox">
                <div class="inside">
                    <h3><?php _e('Häufige Fragen (FAQ)', 'sessiontags'); ?></h3>

                    <h4><?php _e('Wie lange bleiben Parameter gespeichert?', 'sessiontags'); ?></h4>
                    <p><?php _e('Parameter bleiben für die gesamte Browser-Session gespeichert, bis der Browser geschlossen wird oder die Session explizit gelöscht wird.', 'sessiontags'); ?></p>

                    <h4><?php _e('Was passiert bei URL-Kürzeln?', 'sessiontags'); ?></h4>
                    <p><?php _e('URL-Kürzel (z.B. "q" für "quelle") werden automatisch zum vollständigen Parameter-Namen erweitert und gespeichert.', 'sessiontags'); ?></p>

                    <h4><?php _e('Funktioniert das mit WordPress Caching?', 'sessiontags'); ?></h4>
                    <p><?php _e('Ja, da die Parameter in der PHP-Session gespeichert werden, funktioniert es auch mit Caching-Plugins. Die Shortcodes werden bei jedem Seitenaufruf verarbeitet.', 'sessiontags'); ?></p>

                    <h4><?php _e('Kann ich Parameter programmatisch setzen?', 'sessiontags'); ?></h4>
                    <p><?php _e('Ja, über das Elementor Pro Form Action "In SessionTag speichern" oder direkt in PHP mit der SessionManager-Klasse.', 'sessiontags'); ?></p>

                    <h4><?php _e('Ist die URL-Verschleierung sicher?', 'sessiontags'); ?></h4>
                    <p><?php _e('Die Verschleierung erschwert das Lesen und Manipulieren von Parametern, bietet aber keinen Schutz für hochsensible Daten. Verwenden Sie es für Marketing-Parameter, nicht für kritische Sicherheitsdaten.', 'sessiontags'); ?></p>
                </div>
            </div>
        </div>
<?php
    }
}
