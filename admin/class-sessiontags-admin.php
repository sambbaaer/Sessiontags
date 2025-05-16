<?php

/**
 * SessionTagsAdmin-Klasse
 * 
 * Verwaltet die Administrationsschnittstelle des Plugins
 */
class SessionTagsAdmin
{
    /**
     * Instanz der SessionManager-Klasse
     * 
     * @var SessionTagsSessionManager
     */
    private $session_manager;

    /**
     * Aktiver Tab im Admin-Bereich
     * 
     * @var string
     */
    private $active_tab;

    /**
     * Konstruktor der SessionTagsAdmin-Klasse
     * 
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
     * 
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

        // Admin-JavaScript laden
        wp_enqueue_script(
            'sessiontags-admin-script',
            SESSIONTAGS_URL . 'admin/js/sessiontags-admin.js',
            ['jquery'],
            SESSIONTAGS_VERSION,
            true
        );
    }

    /**
     * Rendert die Admin-Seite
     */
    public function render_admin_page()
    {
        // Sicherstellen, dass der Benutzer die erforderlichen Berechtigungen hat
        if (!current_user_can('manage_options')) {
            return;
        }

        // Aktiven Tab ermitteln
        $this->active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'settings';

        // Zu verfolgende Parameter und URL-Verschleierung holen
        $parameters = $this->session_manager->get_tracked_parameters();
        $use_encoding = $this->session_manager->is_url_encoding_enabled();

        // Beispiel-URL generieren
        $example_url = $this->get_example_url($parameters, $use_encoding);

        // Geheimen Schlüssel holen
        $secret_key = get_option('sessiontags_secret_key', '');

        // Admin-Seite rendern
        ?>
        <div class="wrap sessiontags-admin">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <!-- Tabs -->
            <h2 class="nav-tab-wrapper">
                <a href="?page=sessiontags&tab=settings" class="nav-tab <?php echo $this->active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html__('Einstellungen', 'sessiontags'); ?>
                </a>
                <a href="?page=sessiontags&tab=docs" class="nav-tab <?php echo $this->active_tab === 'docs' ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html__('Dokumentation', 'sessiontags'); ?>
                </a>
                <a href="?page=sessiontags&tab=docs-forms" class="nav-tab <?php echo $this->active_tab === 'docs-forms' ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html__('Formular-Integration', 'sessiontags'); ?>
                </a>
                <a href="?page=sessiontags&tab=docs-more" class="nav-tab <?php echo $this->active_tab === 'docs-more' ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html__('Weitere Informationen', 'sessiontags'); ?>
                </a>
            </h2>

            <?php if ($this->active_tab === 'settings') : ?>
                <!-- Einstellungen Tab -->
                <form method="post" action="options.php">
                    <?php settings_fields('sessiontags_settings'); ?>
                    
                    <div class="sessiontags-setting-section">
                        <h2><?php echo esc_html__('Parameter-Einstellungen', 'sessiontags'); ?></h2>
                        <p class="description">
                            <?php echo esc_html__('Definieren Sie die Parameter, die in der URL erkannt und in der Session gespeichert werden sollen.', 'sessiontags'); ?>
                        </p>
                        
                        <table class="sessiontags-parameter-table">
                            <thead>
                                <tr>
                                    <th><?php echo esc_html__('Parameter-Name', 'sessiontags'); ?></th>
                                    <th><?php echo esc_html__('URL-Kurzform', 'sessiontags'); ?></th>
                                    <th><?php echo esc_html__('Standard-Fallback', 'sessiontags'); ?></th>
                                    <th><?php echo esc_html__('Aktionen', 'sessiontags'); ?></th>
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
                                                    placeholder="<?php echo esc_attr__('z.B. quelle', 'sessiontags'); ?>"
                                                    required
                                                >
                                            </td>
                                            <td>
                                                <input type="text"
                                                    name="sessiontags_parameters[<?php echo (int) $index; ?>][shortcode]"
                                                    value="<?php echo esc_attr($param['shortcode'] ?? ''); ?>"
                                                    class="small-text sessiontags-parameter-shortcode"
                                                    placeholder="<?php echo esc_attr__('z.B. q', 'sessiontags'); ?>"
                                                >
                                            </td>
                                            <td>
                                                <input type="text"
                                                    name="sessiontags_parameters[<?php echo (int) $index; ?>][fallback]"
                                                    value="<?php echo esc_attr($param['fallback'] ?? ''); ?>"
                                                    class="regular-text sessiontags-parameter-fallback"
                                                    placeholder="<?php echo esc_attr__('Standard-Fallback', 'sessiontags'); ?>"
                                                >
                                            </td>
                                            <td>
                                                <button type="button" class="button remove-parameter" <?php echo (count($parameters) <= 1) ? 'style="display:none;"' : ''; ?>>
                                                    <?php echo esc_html__('Entfernen', 'sessiontags'); ?>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr class="parameter-row">
                                        <td>
                                            <input type="text"
                                                name="sessiontags_parameters[0][name]"
                                                value="quelle"
                                                class="regular-text sessiontags-parameter-name"
                                                placeholder="<?php echo esc_attr__('z.B. quelle', 'sessiontags'); ?>"
                                                required
                                            >
                                        </td>
                                        <td>
                                            <input type="text"
                                                name="sessiontags_parameters[0][shortcode]"
                                                value="q"
                                                class="small-text sessiontags-parameter-shortcode"
                                                placeholder="<?php echo esc_attr__('z.B. q', 'sessiontags'); ?>"
                                            >
                                        </td>
                                        <td>
                                            <input type="text"
                                                name="sessiontags_parameters[0][fallback]"
                                                value=""
                                                class="regular-text sessiontags-parameter-fallback"
                                                placeholder="<?php echo esc_attr__('Standard-Fallback', 'sessiontags'); ?>"
                                            >
                                        </td>
                                        <td>
                                            <button type="button" class="button remove-parameter" style="display:none;">
                                                <?php echo esc_html__('Entfernen', 'sessiontags'); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        
                        <div class="sessiontags-parameter-actions">
                            <button type="button" class="button add-parameter">
                                <?php echo esc_html__('Parameter hinzufügen', 'sessiontags'); ?>
                            </button>
                        </div>
                    </div>

                    <div class="sessiontags-setting-section">
                        <h2><?php echo esc_html__('URL-Verschleierung', 'sessiontags'); ?></h2>
                        <p class="description">
                            <?php echo esc_html__('URL-Parameter können verschlüsselt werden, um die Lesbarkeit zu erschweren und Manipulationen zu verhindern.', 'sessiontags'); ?>
                        </p>
                        
                        <div class="sessiontags-checkbox-setting">
                            <label>
                                <input type="checkbox"
                                    name="sessiontags_url_encoding"
                                    value="1"
                                    <?php checked(1, $use_encoding); ?>
                                >
                                <?php echo esc_html__('URL-Verschleierung aktivieren', 'sessiontags'); ?>
                            </label>
                            <p class="description">
                                <?php echo esc_html__('Wenn aktiviert, werden URL-Parameter beim Erstellen von Links mit [st_url] automatisch verschlüsselt.', 'sessiontags'); ?>
                            </p>
                        </div>
                        
                        <div class="sessiontags-secret-key-setting">
                            <label for="secret-key"><?php echo esc_html__('Geheimer Schlüssel', 'sessiontags'); ?></label>
                            <div class="secret-key-display">
                                <input type="password"
                                    id="secret-key"
                                    name="sessiontags_secret_key"
                                    value="<?php echo esc_attr($secret_key); ?>"
                                    class="regular-text"
                                    readonly
                                >
                                <button type="button" class="button regenerate-key">
                                    <?php echo esc_html__('Neu generieren', 'sessiontags'); ?>
                                </button>
                            </div>
                            <p class="description">
                                <?php echo esc_html__('Dieser Schlüssel wird für die Verschlüsselung der Parameter verwendet. Das Ändern des Schlüssels macht bestehende verschlüsselte URLs ungültig.', 'sessiontags'); ?>
                            </p>
                        </div>
                    </div>

                    <div class="sessiontags-setting-section">
                        <h2><?php echo esc_html__('Beispiel-URL', 'sessiontags'); ?></h2>
                        <p class="description">
                            <?php echo esc_html__('So würde eine URL mit Ihren konfigurierten Parametern aussehen:', 'sessiontags'); ?>
                        </p>
                        
                        <div class="sessiontags-example-url-display">
                            <code id="example-url"><?php echo esc_html($example_url); ?></code>
                            <button type="button" class="button copy-url">
                                <?php echo esc_html__('Kopieren', 'sessiontags'); ?>
                            </button>
                        </div>
                    </div>

                    <?php submit_button(); ?>
                </form>
            <?php elseif ($this->active_tab === 'docs') : ?>
                <!-- Dokumentation Tab -->
                <div class="sessiontags-documentation">
                    <h2><?php echo esc_html__('Dokumentation', 'sessiontags'); ?></h2>
                    
                    <div class="sessiontags-doc-section">
                        <h3><?php echo esc_html__('Shortcodes verwenden', 'sessiontags'); ?></h3>
                        <p><?php echo esc_html__('Mit SessionTags kannst du die gespeicherten Parameter an beliebiger Stelle auf deiner Website anzeigen lassen.', 'sessiontags'); ?></p>
                        
                        <div class="sessiontags-doc-subsection">
                            <h4><?php echo esc_html__('Einfacher Parameter-Shortcode', 'sessiontags'); ?></h4>
                            <p><?php echo esc_html__('Verwende den Shortcode [st] (oder [show_session_param]), um den Wert eines gespeicherten Parameters anzuzeigen:', 'sessiontags'); ?></p>
                            <div class="sessiontags-code-examples">
                                <code>[st k="quelle"]</code>
                                <p><?php echo esc_html__('oder mit vollständigen Attributnamen:', 'sessiontags'); ?></p>
                                <code>[show_session_param key="quelle"]</code>
                            </div>
                            <p><?php echo esc_html__('Du kannst auch einen individuellen Fallback-Wert angeben, der angezeigt wird, wenn der Parameter nicht gesetzt ist:', 'sessiontags'); ?></p>
                            <div class="sessiontags-code-examples">
                                <code>[st k="quelle" d="direkt"]</code>
                                <p><?php echo esc_html__('oder mit vollständigen Attributnamen:', 'sessiontags'); ?></p>
                                <code>[show_session_param key="quelle" default="direkt"]</code>
                            </div>
                        </div>
                        
                        <div class="sessiontags-doc-subsection">
                            <h4><?php echo esc_html__('URL-Generator-Shortcode', 'sessiontags'); ?></h4>
                            <p><?php echo esc_html__('Mit dem Shortcode [st_url] kannst du URLs erstellen, die automatisch deine gespeicherten Parameter weitergeben:', 'sessiontags'); ?></p>
                            <div class="sessiontags-code-examples">
                                <code>[st_url url="https://beispiel.de/seite/" params="quelle=[st k=quelle],kampagne=[st k=kampagne]"]Zur Beispielseite[/st_url]</code>
                            </div>
                            <p><?php echo esc_html__('Du kannst auch statische Werte für Parameter übergeben:', 'sessiontags'); ?></p>
                            <div class="sessiontags-code-examples">
                                <code>[st_url url="https://beispiel.de/seite/" params="quelle=newsletter,kampagne=herbst2025"]Zur Beispielseite[/st_url]</code>
                            </div>
                            <p><?php echo esc_html__('Weitere Attribute für den Link:', 'sessiontags'); ?></p>
                            <div class="sessiontags-code-examples">
                                <code>[st_url url="https://beispiel.de/" params="quelle=[st k=quelle]" class="meine-klasse" title="Tooltip-Text"]Link-Text[/st_url]</code>
                            </div>
                        </div>
                    </div>

                    <div class="sessiontags-doc-section">
                        <h3><?php echo esc_html__('Verwendungsbeispiele', 'sessiontags'); ?></h3>
                        
                        <div class="sessiontags-doc-subsection">
                            <h4><?php echo esc_html__('Beispiel 1: Kampagnen-Tracking', 'sessiontags'); ?></h4>
                            <p><?php echo esc_html__('Du kannst die Parameter "quelle" und "kampagne" verwenden, um die Herkunft deiner Besucher zu verfolgen.', 'sessiontags'); ?></p>
                            <div class="sessiontags-code-examples">
                                <p><?php echo esc_html__('URL:', 'sessiontags'); ?> <code>https://meine-website.de/?q=newsletter&k=herbst2025</code></p>
                                <p><?php echo esc_html__('Auf einer beliebigen Seite deiner Website:', 'sessiontags'); ?></p>
                                <code>Vielen Dank für deinen Besuch über unseren [st k="quelle" d="direkten"] Kanal!</code>
                            </div>
                        </div>
                        
                        <div class="sessiontags-doc-subsection">
                            <h4><?php echo esc_html__('Beispiel 2: Inhalte anpassen', 'sessiontags'); ?></h4>
                            <p><?php echo esc_html__('Du kannst die angezeigten Inhalte basierend auf den URL-Parametern anpassen.', 'sessiontags'); ?></p>
                            <div class="sessiontags-code-examples">
                                <p><?php echo esc_html__('URL:', 'sessiontags'); ?> <code>https://meine-website.de/?branche=gesundheit</code></p>
                                <p><?php echo esc_html__('Im Seiteninhalt oder in Elementor:', 'sessiontags'); ?></p>
                                <code>Willkommen! Hier findest du unsere Lösungen für die [st k="branche" d="Industrie"]-Branche.</code>
                            </div>
                        </div>
                        
                        <div class="sessiontags-doc-subsection">
                            <h4><?php echo esc_html__('Beispiel 3: Parameter weitergeben', 'sessiontags'); ?></h4>
                            <p><?php echo esc_html__('Du kannst die Parameter an andere Seiten oder Formulare weitergeben.', 'sessiontags'); ?></p>
                            <div class="sessiontags-code-examples">
                                <p><?php echo esc_html__('URL:', 'sessiontags'); ?> <code>https://meine-website.de/?partner=123</code></p>
                                <p><?php echo esc_html__('Links mit Parametern erstellen:', 'sessiontags'); ?></p>
                                <code>[st_url url="https://meine-website.de/kontakt/" params="partner=[st k=partner]"]Jetzt Kontakt aufnehmen[/st_url]</code>
                            </div>
                        </div>
                    </div>
                </div>
            <?php elseif ($this->active_tab === 'docs-forms') : ?>
                <!-- Formular-Integration Tab -->
                <div class="sessiontags-documentation">
                    <h2><?php echo esc_html__('Formular-Integration', 'sessiontags'); ?></h2>
                    
                    <div class="sessiontags-doc-section">
                        <h3><?php echo esc_html__('Mit dem st_form Shortcode Formulare einbetten', 'sessiontags'); ?></h3>
                        <p><?php echo esc_html__('SessionTags ermöglicht es dir, externe Formulare einzubetten und automatisch mit deinen gespeicherten Parametern vorzufüllen.', 'sessiontags'); ?></p>
                        
                        <div class="sessiontags-doc-options">
                            <h4><?php echo esc_html__('Verfügbare Attribute:', 'sessiontags'); ?></h4>
                            <table class="sessiontags-doc-table">
                                <thead>
                                    <tr>
                                        <th><?php echo esc_html__('Attribut', 'sessiontags'); ?></th>
                                        <th><?php echo esc_html__('Beschreibung', 'sessiontags'); ?></th>
                                        <th><?php echo esc_html__('Standard', 'sessiontags'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>type</code></td>
                                        <td><?php echo esc_html__('Art des Formulars (google oder microsoft)', 'sessiontags'); ?></td>
                                        <td><code>google</code></td>
                                    </tr>
                                    <tr>
                                        <td><code>url</code></td>
                                        <td><?php echo esc_html__('URL des Formulars', 'sessiontags'); ?></td>
                                        <td><?php echo esc_html__('(erforderlich)', 'sessiontags'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><code>params</code></td>
                                        <td><?php echo esc_html__('Kommagetrennte Liste der Session-Parameter', 'sessiontags'); ?></td>
                                        <td><?php echo esc_html__('(erforderlich)', 'sessiontags'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><code>form_params</code></td>
                                        <td><?php echo esc_html__('Kommagetrennte Liste der Formularfeld-IDs (falls abweichend von params)', 'sessiontags'); ?></td>
                                        <td><?php echo esc_html__('(optional)', 'sessiontags'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><code>width</code></td>
                                        <td><?php echo esc_html__('Breite des iFrames', 'sessiontags'); ?></td>
                                        <td><code>100%</code></td>
                                    </tr>
                                    <tr>
                                        <td><code>height</code></td>
                                        <td><?php echo esc_html__('Höhe des iFrames', 'sessiontags'); ?></td>
                                        <td><code>800px</code></td>
                                    </tr>
                                    <tr>
                                        <td><code>title</code></td>
                                        <td><?php echo esc_html__('Titel-Attribut für den iFrame (Barrierefreiheit)', 'sessiontags'); ?></td>
                                        <td><?php echo esc_html__('(optional)', 'sessiontags'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><code>class</code></td>
                                        <td><?php echo esc_html__('CSS-Klasse für den iFrame', 'sessiontags'); ?></td>
                                        <td><?php echo esc_html__('(optional)', 'sessiontags'); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="sessiontags-doc-section">
                        <h3><?php echo esc_html__('Google Forms Integration', 'sessiontags'); ?></h3>
                        <p><?php echo esc_html__('So kannst du Google Forms mit automatisch ausgefüllten Feldern einbetten:', 'sessiontags'); ?></p>
                        
                        <div class="sessiontags-doc-steps">
                            <h4><?php echo esc_html__('Verwendung mit Google Forms:', 'sessiontags'); ?></h4>
                            <ol>
                                <li><?php echo esc_html__('Erstelle ein Google-Formular', 'sessiontags'); ?></li>
                                <li><?php echo esc_html__('Finde die Formular-URL (Freigeben-Button → Link kopieren)', 'sessiontags'); ?></li>
                                <li><?php echo esc_html__('Identifiziere die Feldnamen im Formular (sie haben das Format "entry.XXXXXXX")', 'sessiontags'); ?></li>
                                <li><?php echo esc_html__('Verwende den Shortcode mit den entsprechenden Parametern', 'sessiontags'); ?></li>
                            </ol>
                        </div>

                        <div class="sessiontags-doc-note">
                            <p><?php echo esc_html__('So findest du die Feldnamen in Google Forms:', 'sessiontags'); ?></p>
                            <ol>
                                <li><?php echo esc_html__('Öffne das Formular zur Vorschau', 'sessiontags'); ?></li>
                                <li><?php echo esc_html__('Klicke mit der rechten Maustaste und wähle "Seitenquelltext anzeigen"', 'sessiontags'); ?></li>
                                <li><?php echo esc_html__('Suche nach "entry." gefolgt von einer Nummer', 'sessiontags'); ?></li>
                            </ol>
                            <p class="tip"><?php echo esc_html__('Die Feldnamen haben das Format "entry.XXXXXXX", z.B. "entry.1234567890"', 'sessiontags'); ?></p>
                        </div>

                        <div class="sessiontags-doc-example">
                                                        <h4><?php echo esc_html__('Beispiel:', 'sessiontags'); ?></h4>
                            <code>[st_form 
  type="microsoft" 
  url="https://forms.office.com/Pages/ResponsePage.aspx?id=YOUR_FORM_ID" 
  params="name,email" 
  form_params="name,email" 
  width="100%" 
  height="600px"
]</code>
                            <p><?php echo esc_html__('Dieses Beispiel bettet ein Microsoft-Formular ein und überträgt den Wert des Session-Parameters "name" in das Formularfeld "name" und den Wert von "email" in das Feld "email".', 'sessiontags'); ?></p>
                        </div>

                        <div class="sessiontags-doc-note">
                            <h4><?php echo esc_html__('Wichtig:', 'sessiontags'); ?></h4>
                            <p><?php echo esc_html__('Bei Microsoft Forms können die Feldnamen je nach Formular unterschiedlich sein. Die Namen entsprechen oft dem Feldtyp (z.B. "name", "email", "r1q1" oder "question1").', 'sessiontags'); ?></p>
                            <p><?php echo esc_html__('Wenn die form_params und params identisch sind, kannst du form_params weglassen:', 'sessiontags'); ?></p>
                            <code>[st_form type="microsoft" url="https://forms.office.com/Pages/ResponsePage.aspx?id=YOUR_FORM_ID" params="name,email"]</code>
>
                            <code>[st_form 
  type="google" 
  url="https://docs.google.com/forms/d/e/xxxxxxxxxxxxxxxxx/viewform" 
  params="name,email" 
  form_params="entry.1234567890,entry.2345678901" 
  width="100%" 
  height="600px"
]</code>
                            <p><?php echo esc_html__('Dieses Beispiel bettet ein Google-Formular ein und überträgt den Wert des Session-Parameters "name" in das Formularfeld "entry.1234567890" und den Wert von "email" in das Feld "entry.2345678901".', 'sessiontags'); ?></p>
                        </div>
                    </div>

                    <div class="sessiontags-doc-section">
                        <h3><?php echo esc_html__('Microsoft Forms Integration', 'sessiontags'); ?></h3>
                        <p><?php echo esc_html__('So kannst du Microsoft Forms mit automatisch ausgefüllten Feldern einbetten:', 'sessiontags'); ?></p>
                        
                        <div class="sessiontags-doc-note">
</p>
                        </div>

                        <div class="sessiontags-doc-steps">
                            <h4><?php echo esc_html__('Verwendung mit Microsoft Forms:', 'sessiontags'); ?></h4>
                            <ol>
                                <li><?php echo esc_html__('Erstelle ein Microsoft-Formular', 'sessiontags'); ?></li>
                                <li><?php echo esc_html__('Finde die Formular-URL (Teilen-Button → Link kopieren)', 'sessiontags'); ?></li>
                                <li><?php echo esc_html__('Identifiziere die Feldnamen im Formular', 'sessiontags'); ?></li>
                                <li><?php echo esc_html__('Verwende den Shortcode mit den entsprechenden Parametern', 'sessiontags'); ?></li>
                            </ol>
                        </div>

                        <div class="sessiontags-doc-note">
                            <p><?php echo esc_html__('So findest du die Feldnamen in Microsoft Forms:', 'sessiontags'); ?></p>
                            <ol>
                                <li><?php echo esc_html__('Öffne das Formular zur Vorschau', 'sessiontags'); ?></li>
                                <li><?php echo esc_html__('Füge "?name=test&email=test" an die URL an', 'sessiontags'); ?></li>
                                <li><?php echo esc_html__('Prüfe, ob die Felder vorausgefüllt sind (Microsoft Forms unterstützt einige Standardfeldnamen)', 'sessiontags'); ?></li>
                            </ol>
                            <p class="tip"><?php echo esc_html__('Hier sind "name" und "email" die Formularfeld-IDs.', 'sessiontags'); ?></p>
                        </div>

                        <div class="sessiontags-doc-example">
                            <h4><?php echo esc_html__('Beispiel:', 'sessiontags'); ?></h4