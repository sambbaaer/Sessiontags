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
                    <?php _e('SessionTags ist wie ein unsichtbarer Notizblock für Ihre Website. Es merkt sich, woher Ihre Besucher kommen (z.B. von Google, Facebook oder einem Newsletter) und kann diese Information überall auf Ihrer Website anzeigen. So können Sie personalisierte Inhalte erstellen und besseres Marketing betreiben.', 'sessiontags'); ?>
                </p>

                <div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #2271b1; margin: 15px 0;">
                    <strong><?php _e('Einfach erklärt:', 'sessiontags'); ?></strong><br>
                    <?php _e('Ein Besucher klickt auf Ihren Link "website.ch?quelle=facebook" → SessionTags merkt sich "facebook" → Sie können überall "Willkommen, Facebook-Besucher!" anzeigen.', 'sessiontags'); ?>
                </div>

                <h3><?php _e('In 3 einfachen Schritten starten', 'sessiontags'); ?></h3>
                <ol class="sessiontags-steps">
                    <li>
                        <strong><?php _e('Parameter definieren', 'sessiontags'); ?></strong><br>
                        <?php _e('Gehen Sie zu "Einstellungen" und legen Sie fest, was Sie verfolgen möchten (z.B. "quelle" für die Herkunft Ihrer Besucher).', 'sessiontags'); ?>
                    </li>
                    <li>
                        <strong><?php _e('Tracking-URL erstellen', 'sessiontags'); ?></strong><br>
                        <?php _e('Verwenden Sie den "URL-Builder" oder hängen Sie Parameter manuell an:', 'sessiontags'); ?><br>
                        <code><?php echo esc_url(home_url('/')); ?>?quelle=facebook</code>
                    </li>
                    <li>
                        <strong><?php _e('Werte anzeigen', 'sessiontags'); ?></strong><br>
                        <?php _e('Fügen Sie ', 'sessiontags'); ?><code>[st k="quelle"]</code><?php _e(' in Texte, Überschriften oder Elementor-Widgets ein.', 'sessiontags'); ?>
                    </li>
                </ol>

                <h3><?php _e('Vollständiges Beispiel', 'sessiontags'); ?></h3>
                <p><?php _e('Sie teilen diesen Link auf Facebook:', 'sessiontags'); ?></p>
                <div class="code-example">
                    <code><?php echo esc_url(home_url('/')); ?>?quelle=facebook&kampagne=weihnachten2024</code>
                </div>

                <p><?php _e('Jetzt können Sie überall auf Ihrer Website diese Werte verwenden:', 'sessiontags'); ?></p>
                <ul>
                    <li><code>[st k="quelle"]</code> zeigt: <strong>facebook</strong></li>
                    <li><code>[st k="kampagne"]</code> zeigt: <strong>weihnachten2024</strong></li>
                </ul>

                <p><?php _e('Praktische Anwendung in einem Text:', 'sessiontags'); ?></p>
                <div class="code-example">
                    <?php _e('Hallo [st k="quelle" d="lieber Besucher"]! Entdecken Sie unsere [st k="kampagne" d="aktuellen"] Angebote.', 'sessiontags'); ?>
                </div>
                <p><?php _e('Wird zu: "Hallo facebook! Entdecken Sie unsere weihnachten2024 Angebote."', 'sessiontags'); ?></p>

                <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0;">
                    <strong><?php _e('Tipp:', 'sessiontags'); ?></strong> <?php _e('Das "d=" ist ein Fallback-Wert. Wenn kein Parameter vorhanden ist, wird dieser Text angezeigt.', 'sessiontags'); ?>
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
        <div class="postbox">
            <h2 class="hndle"><span class="dashicons dashicons-shortcode"></span> <?php _e('Shortcode-Referenz', 'sessiontags'); ?></h2>
            <div class="inside">
                <h3><?php _e('Parameter anzeigen: [st]', 'sessiontags'); ?></h3>
                <p><?php _e('Mit diesem Shortcode zeigen Sie die gespeicherten Werte an. Kopieren Sie einfach den Code und passen Sie ihn an.', 'sessiontags'); ?></p>

                <h4><?php _e('So einfach geht\'s', 'sessiontags'); ?></h4>
                <div class="code-example">
                    <strong><?php _e('Basis-Version:', 'sessiontags'); ?></strong><br>
                    <code>[st k="quelle"]</code><br><br>
                    <strong><?php _e('Mit Fallback (empfohlen):', 'sessiontags'); ?></strong><br>
                    <code>[st k="quelle" d="unbekannt"]</code>
                </div>

                <h4><?php _e('Alle Optionen im Überblick', 'sessiontags'); ?></h4>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th style="width: 120px;"><?php _e('Option', 'sessiontags'); ?></th>
                            <th><?php _e('Bedeutung', 'sessiontags'); ?></th>
                            <th><?php _e('Beispiel', 'sessiontags'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>k="name"</code></td>
                            <td><?php _e('Welcher Parameter angezeigt werden soll', 'sessiontags'); ?></td>
                            <td><code>[st k="quelle"]</code></td>
                        </tr>
                        <tr>
                            <td><code>d="text"</code></td>
                            <td><?php _e('Was gezeigt wird, wenn der Parameter nicht vorhanden ist', 'sessiontags'); ?></td>
                            <td><code>[st k="quelle" d="direkt"]</code></td>
                        </tr>
                    </tbody>
                </table>

                <div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #2271b1; margin: 15px 0;">
                    <strong><?php _e('Praktisches Beispiel:', 'sessiontags'); ?></strong><br>
                    <code>[st k="quelle" d="lieber Besucher"]</code><br>
                    <?php _e('→ Zeigt "facebook" wenn der Besucher von Facebook kommt<br>→ Zeigt "lieber Besucher" wenn er direkt auf die Seite geht', 'sessiontags'); ?>
                </div>
            </div>
        </div>

        <div class="postbox">
            <h2 class="hndle"><span class="dashicons dashicons-admin-links"></span> <?php _e('Links erstellen: [st_url]', 'sessiontags'); ?></h2>
            <div class="inside">
                <p><?php _e('Erstellt automatisch Links, die die Parameter an andere Seiten weiterleiten. Ideal für interne Verlinkungen.', 'sessiontags'); ?></p>

                <h4><?php _e('Einfaches Beispiel', 'sessiontags'); ?></h4>
                <div class="code-example">
                    <code>[st_url url="/kontakt/" params="quelle=facebook,kampagne=winter2024"]Jetzt Kontakt aufnehmen[/st_url]</code>
                </div>
                <p><?php _e('Wird zu einem Link auf /kontakt/ mit allen Parametern automatisch übertragen.', 'sessiontags'); ?></p>

                <h4><?php _e('Alle Optionen', 'sessiontags'); ?></h4>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th style="width: 120px;"><?php _e('Option', 'sessiontags'); ?></th>
                            <th><?php _e('Bedeutung', 'sessiontags'); ?></th>
                            <th><?php _e('Beispiel', 'sessiontags'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>url=""</code></td>
                            <td><?php _e('Wohin der Link führt', 'sessiontags'); ?></td>
                            <td><code>url="/danke/"</code></td>
                        </tr>
                        <tr>
                            <td><code>params=""</code></td>
                            <td><?php _e('Welche Parameter mitgegeben werden (kommagetrennt)', 'sessiontags'); ?></td>
                            <td><code>params="quelle=google"</code></td>
                        </tr>
                        <tr>
                            <td><code>class=""</code></td>
                            <td><?php _e('CSS-Klasse für das Design', 'sessiontags'); ?></td>
                            <td><code>class="button-primary"</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="postbox">
            <h2 class="hndle"><span class="dashicons dashicons-forms"></span> <?php _e('Externe Formulare: [st_form]', 'sessiontags'); ?></h2>
            <div class="inside">
                <p><?php _e('Bindet Google Forms oder Microsoft Forms ein und füllt sie automatisch mit Ihren SessionTags-Werten aus. Perfekt für Lead-Generierung!', 'sessiontags'); ?></p>

                <h4><?php _e('Grundlegendes Beispiel', 'sessiontags'); ?></h4>
                <div class="code-example">
                    <code>[st_form type="google" url="https://docs.google.com/forms/d/e/1234567890/viewform" params="name,email"]</code>
                </div>

                <h4><?php _e('Alle verfügbaren Optionen', 'sessiontags'); ?></h4>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th style="width: 130px;"><?php _e('Option', 'sessiontags'); ?></th>
                            <th><?php _e('Bedeutung', 'sessiontags'); ?></th>
                            <th><?php _e('Beispiel', 'sessiontags'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>type=""</code></td>
                            <td><?php _e('Art des Formulars: "google" oder "microsoft"', 'sessiontags'); ?></td>
                            <td><code>type="google"</code></td>
                        </tr>
                        <tr>
                            <td><code>url=""</code></td>
                            <td><?php _e('Link zu Ihrem Formular', 'sessiontags'); ?></td>
                            <td><code>url="https://forms.gle/xyz"</code></td>
                        </tr>
                        <tr>
                            <td><code>params=""</code></td>
                            <td><?php _e('Welche SessionTags übertragen werden sollen', 'sessiontags'); ?></td>
                            <td><code>params="quelle,kampagne"</code></td>
                        </tr>
                        <tr>
                            <td><code>form_params=""</code></td>
                            <td><?php _e('Formularfeld-IDs (siehe Anleitung unten)', 'sessiontags'); ?></td>
                            <td><code>form_params="entry.123,entry.456"</code></td>
                        </tr>
                        <tr>
                            <td><code>width=""</code></td>
                            <td><?php _e('Breite des Formulars', 'sessiontags'); ?></td>
                            <td><code>width="100%"</code></td>
                        </tr>
                        <tr>
                            <td><code>height=""</code></td>
                            <td><?php _e('Höhe des Formulars', 'sessiontags'); ?></td>
                            <td><code>height="600px"</code></td>
                        </tr>
                    </tbody>
                </table>

                <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0;">
                    <strong><?php _e('Wichtig:', 'sessiontags'); ?></strong> <?php _e('Für die korrekte Funktion müssen Sie die Formular-Feld-IDs ermitteln. Eine detaillierte Anleitung finden Sie im Tab "Integrationen".', 'sessiontags'); ?>
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
        <div class="postbox">
            <h2 class="hndle"><span class="dashicons dashicons-admin-plugins"></span> <?php _e('Plugin-Integrationen', 'sessiontags'); ?></h2>
            <div class="inside">
                <h3><span class="dashicons dashicons-elementor" style="color: #9B3C8E;"></span> <?php _e('Elementor Integration', 'sessiontags'); ?></h3>
                <p><?php _e('SessionTags funktioniert perfekt mit Elementor Free und Pro. Sie können Werte dynamisch anzeigen und sogar ganze Bereiche nur für bestimmte Besucher zeigen.', 'sessiontags'); ?></p>

                <h4><?php _e('📝 Dynamic Tags (Elementor Free + Pro)', 'sessiontags'); ?></h4>
                <p><?php _e('Zeigen Sie SessionTags-Werte in jedem Textfeld dynamisch an:', 'sessiontags'); ?></p>
                <ol>
                    <li><?php _e('Bearbeiten Sie ein beliebiges Textfeld in Elementor', 'sessiontags'); ?></li>
                    <li><?php _e('Klicken Sie auf das Dynamic-Content-Symbol', 'sessiontags'); ?> <span class="dashicons dashicons-database" style="color: #9B3C8E;"></span></li>
                    <li><?php _e('Wählen Sie "SessionTags" aus der Kategorie-Liste', 'sessiontags'); ?></li>
                    <li><?php _e('Wählen Sie Ihren Parameter und optional einen Fallback-Wert', 'sessiontags'); ?></li>
                </ol>

                <h4><?php _e('🎯 Display Conditions (nur Elementor Pro)', 'sessiontags'); ?></h4>
                <p><?php _e('Verstecken oder zeigen Sie ganze Bereiche basierend auf SessionTags:', 'sessiontags'); ?></p>
                <ul>
                    <li><strong><?php _e('Parameter existiert:', 'sessiontags'); ?></strong> <?php _e('Nur anzeigen, wenn ein bestimmter Parameter vorhanden ist', 'sessiontags'); ?></li>
                    <li><strong><?php _e('Parameter hat Wert:', 'sessiontags'); ?></strong> <?php _e('Nur für spezifische Werte anzeigen (z.B. nur für "facebook"-Besucher)', 'sessiontags'); ?></li>
                    <li><strong><?php _e('Parameter ist einer von:', 'sessiontags'); ?></strong> <?php _e('Für mehrere Werte anzeigen (z.B. "facebook,instagram,twitter")', 'sessiontags'); ?></li>
                </ul>

                <h4><?php _e('📋 Elementor Pro Forms - Next Step Integration', 'sessiontags'); ?></h4>
                <p><?php _e('Speichern Sie Formular-Eingaben automatisch als SessionTags für die weitere Nutzung:', 'sessiontags'); ?></p>
                <ol>
                    <li><?php _e('Bearbeiten Sie ein Elementor Pro Formular', 'sessiontags'); ?></li>
                    <li><?php _e('Gehen Sie zu "Actions After Submit"', 'sessiontags'); ?></li>
                    <li><?php _e('Fügen Sie "In SessionTag speichern" hinzu', 'sessiontags'); ?></li>
                    <li><?php _e('Ordnen Sie Formularfelder den SessionTags-Parametern zu', 'sessiontags'); ?></li>
                    <li><?php _e('Nach dem Absenden stehen die Werte automatisch als SessionTags zur Verfügung', 'sessiontags'); ?></li>
                </ol>

                <div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #2271b1; margin: 15px 0;">
                    <strong><?php _e('Praktisches Beispiel:', 'sessiontags'); ?></strong><br>
                    <?php _e('Besucher füllt Name "Max Muster" im Kontaktformular aus → wird als SessionTag "name" gespeichert → auf der Danke-Seite können Sie "Danke Max Muster!" anzeigen.', 'sessiontags'); ?>
                </div>
            </div>
        </div>

        <div class="postbox">
            <h2 class="hndle"><span class="dashicons dashicons-forms" style="color: #4285F4;"></span> <?php _e('Google Forms Integration', 'sessiontags'); ?></h2>
            <div class="inside">
                <p><?php _e('Binden Sie Google Forms ein und füllen Sie sie automatisch mit SessionTags-Werten vor. Perfekt für Lead-Generierung mit Tracking-Information!', 'sessiontags'); ?></p>

                <h4><?php _e('📋 Schritt-für-Schritt Anleitung', 'sessiontags'); ?></h4>

                <h5><?php _e('1. Google Form vorbereiten', 'sessiontags'); ?></h5>
                <ol>
                    <li><?php _e('Erstellen Sie Ihr Google Form normal', 'sessiontags'); ?></li>
                    <li><?php _e('Fügen Sie Felder hinzu, die mit SessionTags befüllt werden sollen (z.B. "Quelle", "Kampagne")', 'sessiontags'); ?></li>
                    <li><?php _e('Klicken Sie auf "Senden" → "Link"-Tab → kopieren Sie die URL', 'sessiontags'); ?></li>
                </ol>

                <h5><?php _e('2. Formular-Feld-IDs finden', 'sessiontags'); ?></h5>
                <ol>
                    <li><?php _e('Öffnen Sie das Google Form in einem neuen Tab', 'sessiontags'); ?></li>
                    <li><?php _e('Rechtsklick auf ein Feld → "Element untersuchen"', 'sessiontags'); ?></li>
                    <li><?php _e('Suchen Sie nach "entry." gefolgt von Zahlen (z.B. "entry.1234567890")', 'sessiontags'); ?></li>
                    <li><?php _e('Notieren Sie sich diese IDs für jedes Feld', 'sessiontags'); ?></li>
                </ol>

                <h5><?php _e('3. Shortcode verwenden', 'sessiontags'); ?></h5>
                <div class="code-example">
                    <strong><?php _e('Vollständiges Beispiel:', 'sessiontags'); ?></strong><br>
                    <code>[st_form type="google" url="https://docs.google.com/forms/d/e/1FAIpQLSfABC123.../viewform" params="quelle,kampagne" form_params="entry.1234567890,entry.2345678901" height="700px"]</code>
                </div>

                <h4><?php _e('📝 Erklärung der Parameter', 'sessiontags'); ?></h4>
                <ul>
                    <li><strong>params="quelle,kampagne":</strong> <?php _e('Welche SessionTags übertragen werden', 'sessiontags'); ?></li>
                    <li><strong>form_params="entry.123,entry.456":</strong> <?php _e('Die entsprechenden Google Form Feld-IDs', 'sessiontags'); ?></li>
                </ul>

                <div style="background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 15px 0;">
                    <strong><?php _e('💡 Profi-Tipp:', 'sessiontags'); ?></strong> <?php _e('Verwenden Sie versteckte Felder in Google Forms für automatisches Tracking, ohne den Benutzer zu stören.', 'sessiontags'); ?>
                </div>
            </div>
        </div>

        <div class="postbox">
            <h2 class="hndle"><span class="dashicons dashicons-forms" style="color: #0078D4;"></span> <?php _e('Microsoft Forms Integration', 'sessiontags'); ?></h2>
            <div class="inside">
                <p><?php _e('Microsoft Forms funktioniert ähnlich wie Google Forms, mit einigen kleinen Unterschieden bei der Konfiguration.', 'sessiontags'); ?></p>

                <h4><?php _e('📋 Anleitung für Microsoft Forms', 'sessiontags'); ?></h4>

                <h5><?php _e('1. Microsoft Form vorbereiten', 'sessiontags'); ?></h5>
                <ol>
                    <li><?php _e('Erstellen Sie Ihr Microsoft Form', 'sessiontags'); ?></li>
                    <li><?php _e('Fügen Sie die gewünschten Felder hinzu', 'sessiontags'); ?></li>
                    <li><?php _e('Klicken Sie auf "Teilen" und kopieren Sie die URL', 'sessiontags'); ?></li>
                </ol>

                <h5><?php _e('2. Feld-Namen ermitteln', 'sessiontags'); ?></h5>
                <p><?php _e('Microsoft Forms verwendet einfachere Feld-Namen als Google Forms. Die Namen entsprechen meist dem Feldtitel oder können durch Inspektion des HTML-Codes ermittelt werden.', 'sessiontags'); ?></p>

                <h5><?php _e('3. Shortcode für Microsoft Forms', 'sessiontags'); ?></h5>
                <div class="code-example">
                    <strong><?php _e('Beispiel:', 'sessiontags'); ?></strong><br>
                    <code>[st_form type="microsoft" url="https://forms.office.com/pages/responsepage.aspx?id=ABC123..." params="quelle,kampagne" form_params="source,campaign"]</code>
                </div>

                <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0;">
                    <strong><?php _e('⚠️ Hinweis:', 'sessiontags'); ?></strong> <?php _e('Die Feld-Namen bei Microsoft Forms sind oft einfacher und entsprechen dem Feldtitel. Testen Sie verschiedene Varianten, falls es nicht sofort funktioniert.', 'sessiontags'); ?>
                </div>
            </div>
        </div>

        <div class="postbox">
            <h2 class="hndle"><span class="dashicons dashicons-admin-appearance" style="color: #E74C3C;"></span> <?php _e('Avada Fusion Builder Integration', 'sessiontags'); ?></h2>
            <div class="inside">
                <p><?php _e('Verwenden Sie SessionTags-Parameter direkt im Avada Fusion Builder für dynamische Inhalte.', 'sessiontags'); ?></p>
                <h4><?php _e('🎨 SessionTags-Element verwenden', 'sessiontags'); ?></h4>
                <ol>
                    <li><?php _e('Öffnen Sie den Fusion Builder', 'sessiontags'); ?></li>
                    <li><?php _e('Suchen Sie nach "SessionTags Parameter" in der Element-Liste', 'sessiontags'); ?></li>
                    <li><?php _e('Wählen Sie den gewünschten Parameter aus', 'sessiontags'); ?></li>
                    <li><?php _e('Konfigurieren Sie das HTML-Element und CSS-Klassen nach Bedarf', 'sessiontags'); ?></li>
                </ol>
            </div>
        </div>

        <div class="postbox">
            <h2 class="hndle"><span class="dashicons dashicons-php"></span> <?php _e('PHP-Integration für Entwickler', 'sessiontags'); ?></h2>
            <div class="inside">
                <p><?php _e('Verwenden Sie SessionTags-Parameter direkt in Ihrem PHP-Code für erweiterte Anpassungen.', 'sessiontags'); ?></p>

                <div class="code-example">
                    <code>
                        &lt;?php<br>
                        if (class_exists('SessionTagsSessionManager')) {<br>
                        &nbsp;&nbsp;$session_manager = new SessionTagsSessionManager();<br>
                        &nbsp;&nbsp;$session_manager->init();<br><br>
                        &nbsp;&nbsp;$quelle = $session_manager->get_param('quelle', 'unbekannt');<br>
                        &nbsp;&nbsp;echo 'Besucher kommt von: ' . esc_html($quelle);<br><br>
                        &nbsp;&nbsp;// Bedingte Inhalte<br>
                        &nbsp;&nbsp;if ($quelle === 'facebook') {<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;echo '&lt;div class="facebook-special"&gt;Spezielles Facebook-Angebot!&lt;/div&gt;';<br>
                        &nbsp;&nbsp;}<br>
                        }<br>
                        ?&gt;
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
                <h3><?php _e('🎯 Kampagnen-Tracking und personalisierte Begrüssung', 'sessiontags'); ?></h3>
                <p><?php _e('Begrüssen Sie Ihre Besucher persönlich und zeigen Sie, dass Sie wissen, woher sie kommen.', 'sessiontags'); ?></p>

                <h4><?php _e('Beispiel: Facebook-Kampagne', 'sessiontags'); ?></h4>
                <div class="code-example">
                    <strong><?php _e('Tracking-URL für Facebook:', 'sessiontags'); ?></strong><br>
                    <code><?php echo home_url('/'); ?>?quelle=facebook&kampagne=winter2024&angebot=20prozent</code>
                </div>

                <p><?php _e('Verwenden Sie dann auf Ihrer Website:', 'sessiontags'); ?></p>
                <div class="code-example">
                    <strong><?php _e('Personalisierte Überschrift:', 'sessiontags'); ?></strong><br>
                    Willkommen [st k="quelle" d="lieber Besucher"]! <br>
                    Entdecken Sie unser [st k="angebot" d="aktuelles"] Angebot der [st k="kampagne" d="Saison"].<br><br>

                    <strong><?php _e('Wird zu:', 'sessiontags'); ?></strong><br>
                    "Willkommen facebook! Entdecken Sie unser 20prozent Angebot der winter2024."
                </div>

                <h3><?php _e('🎨 Bedingte Inhalte mit Elementor Pro', 'sessiontags'); ?></h3>
                <p><?php _e('Zeigen Sie verschiedene Inhalte für verschiedene Besucherquellen.', 'sessiontags'); ?></p>

                <h4><?php _e('Szenario: Social Media vs. Google', 'sessiontags'); ?></h4>
                <ul>
                    <li><strong><?php _e('Für Facebook-Besucher:', 'sessiontags'); ?></strong> <?php _e('Zeigen Sie eine Sektion mit "Danke für das Folgen auf Facebook!"', 'sessiontags'); ?></li>
                    <li><strong><?php _e('Für Google-Besucher:', 'sessiontags'); ?></strong> <?php _e('Zeigen Sie SEO-optimierte Inhalte', 'sessiontags'); ?></li>
                    <li><strong><?php _e('Für alle anderen:', 'sessiontags'); ?></strong> <?php _e('Standard-Inhalt', 'sessiontags'); ?></li>
                </ul>

                <div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #2271b1; margin: 15px 0;">
                    <strong><?php _e('Elementor Display Condition Setup:', 'sessiontags'); ?></strong><br>
                    <code>SessionTags: Parameter hat Wert</code><br>
                    <code>Parameter: quelle</code><br>
                    <code>Wert: facebook</code>
                </div>

                <h3><?php _e('📋 Lead-Formulare mit automatischem Tracking', 'sessiontags'); ?></h3>
                <p><?php _e('Sammeln Sie wertvolle Marketing-Daten automatisch, ohne den Benutzer zu belästigen.', 'sessiontags'); ?></p>

                <h4><?php _e('Google Form mit versteckten Tracking-Feldern', 'sessiontags'); ?></h4>
                <ol>
                    <li><?php _e('Erstellen Sie ein Google Form mit sichtbaren Feldern (Name, E-Mail, Nachricht)', 'sessiontags'); ?></li>
                    <li><?php _e('Fügen Sie versteckte Felder hinzu: "Quelle", "Kampagne", "Angebot"', 'sessiontags'); ?></li>
                    <li><?php _e('Stellen Sie die versteckten Felder auf "immer verstecken"', 'sessiontags'); ?></li>
                    <li><?php _e('Verwenden Sie den SessionTags-Shortcode', 'sessiontags'); ?></li>
                </ol>

                <div class="code-example">
                    <code>[st_form type="google" url="https://docs.google.com/forms/d/e/IHRE_FORM_ID/viewform" params="quelle,kampagne,angebot" form_params="entry.123456,entry.789012,entry.345678"]</code>
                </div>

                <p><?php _e('Resultat: Sie wissen bei jeder Lead-Anfrage, woher der Kontakt kam!', 'sessiontags'); ?></p>

                <h3><?php _e('🔄 Parameter-Weitergabe zwischen Seiten', 'sessiontags'); ?></h3>
                <p><?php _e('Behalten Sie die Tracking-Information über mehrere Seiten hinweg bei.', 'sessiontags'); ?></p>

                <h4><?php _e('Beispiel: Von Landingpage zu Kontaktseite', 'sessiontags'); ?></h4>
                <p><?php _e('Auf Ihrer Landingpage verwenden Sie einen Button:', 'sessiontags'); ?></p>
                <div class="code-example">
                    <code>[st_url url="/kontakt/" params="quelle=[st k=quelle],kampagne=[st k=kampagne]" class="btn btn-primary"]Jetzt Kontakt aufnehmen[/st_url]</code>
                </div>
                <p><?php _e('Der Link führt automatisch alle Parameter mit zur Kontaktseite.', 'sessiontags'); ?></p>

                <h3><?php _e('🛒 E-Commerce Anwendungen', 'sessiontags'); ?></h3>
                <p><?php _e('Perfekt für Online-Shops und Lead-Generierung.', 'sessiontags'); ?></p>

                <h4><?php _e('Rabatt-Codes je nach Quelle', 'sessiontags'); ?></h4>
                <div class="code-example">
                    <strong><?php _e('Beispiel-Text auf der Website:', 'sessiontags'); ?></strong><br>
                    Ihr exklusiver [st k="quelle" d="Neukunden"]-Rabatt: <strong>[st k="rabatt" d="10%"]</strong><br><br>

                    <strong><?php _e('URLs für verschiedene Kanäle:', 'sessiontags'); ?></strong><br>
                    Facebook: <code>shop.ch?quelle=facebook&rabatt=15%</code><br>
                    Google: <code>shop.ch?quelle=google&rabatt=10%</code><br>
                    Newsletter: <code>shop.ch?quelle=newsletter&rabatt=20%</code>
                </div>

                <h3><?php _e('📊 A/B Testing und Optimierung', 'sessiontags'); ?></h3>
                <p><?php _e('Testen Sie verschiedene Inhalte und messen Sie die Performance.', 'sessiontags'); ?></p>

                <h4><?php _e('Beispiel: Verschiedene Überschriften testen', 'sessiontags'); ?></h4>
                <div class="code-example">
                    <strong><?php _e('Test-URLs:', 'sessiontags'); ?></strong><br>
                    Version A: <code>website.ch?version=a</code><br>
                    Version B: <code>website.ch?version=b</code><br><br>

                    <strong><?php _e('Elementor Display Conditions:', 'sessiontags'); ?></strong><br>
                    Überschrift A: Nur zeigen wenn "version = a"<br>
                    Überschrift B: Nur zeigen wenn "version = b"
                </div>
            </div>
        </div>

        <div class="postbox">
            <h2 class="hndle"><span class="dashicons dashicons-editor-help"></span> <?php _e('Häufige Fragen (FAQ)', 'sessiontags'); ?></h2>
            <div class="inside">
                <h4><?php _e('❓ Wie lange bleiben Parameter gespeichert?', 'sessiontags'); ?></h4>
                <p><?php _e('Parameter bleiben für die gesamte Browser-Session gespeichert, bis der Browser geschlossen wird. Das bedeutet: Ein Besucher kann durch Ihre gesamte Website navigieren und die Parameter bleiben erhalten.', 'sessiontags'); ?></p>

                <h4><?php _e('⚡ Funktioniert das mit WordPress Caching?', 'sessiontags'); ?></h4>
                <p><?php _e('Ja, perfekt! Da die Parameter serverseitig in der PHP-Session gespeichert und bei jedem Seitenaufruf neu verarbeitet werden, funktioniert es mit allen Caching-Plugins (WP Rocket, W3 Total Cache, etc.).', 'sessiontags'); ?></p>

                <h4><?php _e('🔒 Ist die URL-Verschleierung sicher?', 'sessiontags'); ?></h4>
                <p><?php _e('Die Verschleierung macht Parameter schwer lesbar und verhindert einfache Manipulation, ist aber kein Sicherheitsfeature für sensible Daten. Verwenden Sie es für Marketing-Parameter, nicht für Passwörter oder persönliche Informationen.', 'sessiontags'); ?></p>

                <h4><?php _e('📱 Funktioniert es auf mobilen Geräten?', 'sessiontags'); ?></h4>
                <p><?php _e('Ja, SessionTags funktioniert auf allen Geräten und Browsern, da es standard PHP-Sessions verwendet.', 'sessiontags'); ?></p>

                <h4><?php _e('🔄 Kann ich Parameter nachträglich ändern?', 'sessiontags'); ?></h4>
                <p><?php _e('Ja! Mit Elementor Pro Forms können Besucher zusätzliche Daten eingeben, die als neue SessionTags gespeichert werden. Ideal für mehrstufige Lead-Generierung.', 'sessiontags'); ?></p>

                <h4><?php _e('🎯 Wie viele Parameter kann ich verwenden?', 'sessiontags'); ?></h4>
                <p><?php _e('Theoretisch unbegrenzt. In der Praxis empfehlen wir 5-10 Parameter für eine saubere URL-Struktur.', 'sessiontags'); ?></p>

                <div style="background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 15px 0;">
                    <strong><?php _e('💡 Support-Tipp:', 'sessiontags'); ?></strong> <?php _e('Nutzen Sie den URL-Builder für erste Tests und aktivieren Sie die Verschleierung erst, wenn alles funktioniert.', 'sessiontags'); ?>
                </div>
            </div>
        </div>
<?php
    }
}
