<?php

/**
 * Plugin Name: SessionTags
 * Plugin URI: https://github.com/sambbaaer/Sessiontags
 * Description: Erfasst und speichert URL-Parameter in der PHP-Session für personalisierte Website-Erlebnisse. Bietet vielseitige Shortcodes, Elementor Dynamic Tags, Avada Fusion Builder Integration sowie URL-Verschleierung für optimiertes Kampagnen-Tracking. Unterstützt kurze Parameter-Namen, individuelle Fallback-Werte, Google Forms & Microsoft Forms Integration, URL-Generator für Parameter-Weitergabe und verschlüsselte URLs für erhöhte Sicherheit. Einfache Konfiguration über das WordPress-Dashboard.
 * Version: 1.5.8
 * Author: Samuel Baer
 * Author URI: https://samuelbaer.ch/
 * Text Domain: sessiontags
 * Domain Path: /languages
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

// Plugin-Pfad und URL definieren
define('SESSIONTAGS_PATH', plugin_dir_path(__FILE__));
define('SESSIONTAGS_URL', plugin_dir_url(__FILE__));
define('SESSIONTAGS_VERSION', '1.5.8');

/**
 * Hauptklasse des SessionTags-Plugins
 */
class SessionTags
{
    /**
     * Instanz der Klasse (Singleton-Pattern)
     * @var SessionTags
     */
    private static $instance = null;

    /**
     * Instanz der SessionManager-Klasse
     * @var SessionTagsSessionManager
     */
    private $session_manager;

    /**
     * Instanz der ShortcodeHandler-Klasse
     * @var SessionTagsShortcodeHandler
     */
    private $shortcode_handler;

    /**
     * Instanz der Admin-Klasse
     * @var SessionTagsAdmin
     */
    private $admin;

    /**
     * Instanz der Elementor-Klasse
     * @var SessionTagsElementor
     */
    private $elementor;

    /**
     * Instanz der URL-Helper-Klasse
     * @var SessionTagsUrlHelper
     */
    private $url_helper;

    /**
     * Instanz der FormIntegration-Klasse
     * @var SessionTagsFormIntegration
     */
    private $form_integration;

    /**
     * Konstruktor der SessionTags-Klasse
     * Initialisiert die Plugin-Komponenten
     */
    private function __construct()
    {
        $this->init_components();
        $this->register_hooks();
    }

    /**
     * Gibt die einzige Instanz der Klasse zurück (Singleton-Pattern)
     * @return SessionTags
     */
    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialisiert die Komponenten des Plugins
     */
    private function init_components()
    {
        // Komponenten laden
        require_once SESSIONTAGS_PATH . 'includes/class-sessiontags-session-manager.php';
        require_once SESSIONTAGS_PATH . 'includes/class-sessiontags-shortcode-handler.php';
        require_once SESSIONTAGS_PATH . 'admin/class-sessiontags-admin.php';
        require_once SESSIONTAGS_PATH . 'includes/class-sessiontags-elementor.php';
        require_once SESSIONTAGS_PATH . 'includes/class-sessiontags-avada.php';
        require_once SESSIONTAGS_PATH . 'includes/class-sessiontags-url-helper.php';
        require_once SESSIONTAGS_PATH . 'includes/class-sessiontags-form-integration.php';

        // Instanzen erstellen
        $this->session_manager = new SessionTagsSessionManager();
        $this->shortcode_handler = new SessionTagsShortcodeHandler($this->session_manager);
        $this->admin = new SessionTagsAdmin($this->session_manager);
        $this->elementor = new SessionTagsElementor($this->session_manager);
        $this->avada = new SessionTagsAvada($this->session_manager);
        $this->url_helper = new SessionTagsUrlHelper($this->session_manager);
        $this->form_integration = new SessionTagsFormIntegration($this->session_manager);
    }

    /**
     * Registriert die erforderlichen WordPress-Hooks
     */
    private function register_hooks()
    {
        // Session starten und URL-Parameter verarbeiten beim Plugin-Start
        add_action('init', [$this->session_manager, 'init'], 1);

        // Shortcode registrieren
        add_action('init', [$this->shortcode_handler, 'register_shortcodes'], 10);

        // Formular-Integration Shortcodes registrieren
        add_action('init', [$this->form_integration, 'register_shortcodes'], 10);

        // AJAX-Handler für die Regenerierung des geheimen Schlüssels
        add_action('wp_ajax_regenerate_secret_key', [$this, 'ajax_regenerate_secret_key']);

        // Elementor-Integration initialisieren
        add_action('plugins_loaded', [$this, 'init_elementor_integration']);

        // Plugin aktivieren
        register_activation_hook(__FILE__, [$this, 'activate']);

        // Plugin deaktivieren
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    /**
     * Initialisiert die Elementor-Integration
     */
    public function init_elementor_integration()
    {
        // Prüfen ob Elementor vollständig geladen ist
        if (!did_action('elementor/loaded')) {
            return; // Elementor nicht geladen, frühzeitig beenden.
        }

        // Registriere die benutzerdefinierten Steuerelemente für Elementor-Container
        add_action('elementor/element/container/section_layout/after_section_start', [$this, 'add_sessiontags_visibility_controls'], 10, 2);

        // Registriere den benutzerdefinierten Sichtbarkeitsfilter für Container
        add_filter('elementor/frontend/container/should_render', [$this, 'apply_sessiontags_container_visibility'], 10, 2);
    }

    /**
     * Fügt benutzerdefinierte Steuerelemente für die Sichtbarkeitslogik zu Elementor-Containern hinzu.
     *
     * @param \Elementor\Controls_Stack $element Die Elementor-Elementinstanz.
     * @param \Elementor\Core\Base\Base_Object $args Argumente.
     */
    public function add_sessiontags_visibility_controls($element, $args)
    {
        // Verfügbare Parameter für Dropdown holen
        $parameters = $this->session_manager->get_tracked_parameters();
        $param_options = [];

        foreach ($parameters as $param) {
            if (!empty($param['name'])) {
                $param_options[$param['name']] = $param['name'];
            }
        }

        // Fallback, falls keine Parameter konfiguriert sind
        if (empty($param_options)) {
            $param_options[''] = esc_html__('Keine Parameter konfiguriert', 'sessiontags');
        }

        $element->start_controls_section(
            'sessiontags_visibility_section',
            [
                'label' => esc_html__('SessionTags Sichtbarkeit', 'sessiontags'),
                'tab' => \Elementor\Controls_Manager::TAB_ADVANCED,
            ]
        );

        $element->add_control(
            'sessiontags_enable_visibility',
            [
                'label' => esc_html__('SessionTags Sichtbarkeit aktivieren', 'sessiontags'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Ja', 'sessiontags'),
                'label_off' => esc_html__('Nein', 'sessiontags'),
                'return_value' => 'yes',
                'default' => '',
                'description' => esc_html__('Aktiviert die bedingte Sichtbarkeit basierend auf SessionTags-Parametern.', 'sessiontags'),
            ]
        );

        $element->add_control(
            'sessiontags_param_key',
            [
                'label' => esc_html__('SessionTags Parameter', 'sessiontags'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $param_options,
                'default' => !empty($param_options) ? key($param_options) : '',
                'description' => esc_html__('Wählen Sie den SessionTags-Parameter für die Sichtbarkeitsprüfung.', 'sessiontags'),
                'condition' => [
                    'sessiontags_enable_visibility' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'sessiontags_param_value',
            [
                'label' => esc_html__('Erwarteter Parameterwert', 'sessiontags'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => esc_html__('z.B. premium', 'sessiontags'),
                'description' => esc_html__('Der Wert, mit dem der Parameter verglichen werden soll.', 'sessiontags'),
                'condition' => [
                    'sessiontags_enable_visibility' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'sessiontags_visibility_logic',
            [
                'label' => esc_html__('Sichtbarkeitslogik', 'sessiontags'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'show' => esc_html__('Anzeigen, wenn Bedingung erfüllt', 'sessiontags'),
                    'hide' => esc_html__('Ausblenden, wenn Bedingung erfüllt', 'sessiontags'),
                ],
                'default' => 'show',
                'description' => esc_html__('Bestimmt, ob das Element angezeigt oder ausgeblendet wird, wenn die Bedingung erfüllt ist.', 'sessiontags'),
                'condition' => [
                    'sessiontags_enable_visibility' => 'yes',
                ],
            ]
        );

        // Informations-Control für bessere UX
        $element->add_control(
            'sessiontags_visibility_info',
            [
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => sprintf(
                    '<div style="background: #f8f9fa; padding: 12px; border-radius: 4px; border-left: 4px solid #007cba; margin-top: 10px;">
                        <strong>%s</strong><br>
                        %s<br><br>
                        <strong>%s:</strong><br>
                        <code>%s</code>
                    </div>',
                    esc_html__('Hinweis', 'sessiontags'),
                    esc_html__('Diese Sichtbarkeitsregeln funktionieren serverseitig. Das Element wird nur gerendert, wenn die Bedingung erfüllt ist. Parameter müssen zuerst in den SessionTags-Einstellungen konfiguriert werden.', 'sessiontags'),
                    esc_html__('Beispiel-URL', 'sessiontags'),
                    esc_url(home_url('/?tag=premium'))
                ),
                'condition' => [
                    'sessiontags_enable_visibility' => 'yes',
                ],
            ]
        );

        $element->end_controls_section();
    }

    /**
     * Wendet benutzerdefinierte bedingte Rendering-Logik für Elementor-Container basierend auf SessionTags-Parametern an.
     *
     * @param bool $should_render Gibt an, ob das Element derzeit zum Rendern eingestellt ist (standardmäßig true).
     * @param \Elementor\Controls_Stack $element Die Elementor-Elementinstanz (Container).
     * @return bool True, um das Element zu rendern, false, um es auszublenden.
     */
    public function apply_sessiontags_container_visibility($should_render, $element)
    {
        // Nur für Container anwenden
        if ('container' !== $element->get_type()) {
            return $should_render;
        }

        $settings = $element->get_settings();

        // Prüfen, ob die Sichtbarkeit für diesen Container aktiviert ist
        if (empty($settings['sessiontags_enable_visibility']) || 'yes' !== $settings['sessiontags_enable_visibility']) {
            return $should_render; // Nicht aktiviert, ursprünglichen Status zurückgeben.
        }

        $param_key   = isset($settings['sessiontags_param_key']) ? $settings['sessiontags_param_key'] : '';
        $param_value = isset($settings['sessiontags_param_value']) ? $settings['sessiontags_param_value'] : '';
        $logic       = isset($settings['sessiontags_visibility_logic']) ? $settings['sessiontags_visibility_logic'] : 'show';

        // Wenn kein Parameter-Schlüssel oder Wert definiert ist, ursprünglichen Status zurückgeben
        if (empty($param_key) || empty($param_value)) {
            return $should_render;
        }

        // Sicherstellen, dass SessionManager initialisiert ist
        if (!$this->session_manager) {
            return $should_render;
        }

        // Parameter aus Session abrufen (KORRIGIERT: verwende SessionManager statt direkten $_GET-Zugriff)
        $actual_param_value = $this->session_manager->get_param($param_key, null);

        // Prüfen ob der Parameter existiert und nicht leer ist
        if ($actual_param_value === null || $actual_param_value === '') {
            // Parameter existiert nicht in der Session
            if ('show' === $logic) {
                return false; // Nicht anzeigen, da Parameter nicht vorhanden
            } else {
                return true; // Anzeigen, da Parameter nicht vorhanden (hide-Logik)
            }
        }

        // Parameter-Werte vergleichen (case-insensitive)
        $condition_met = (strtolower(trim($actual_param_value)) === strtolower(trim($param_value)));

        if ('show' === $logic) {
            return $condition_met; // Anzeigen, wenn Bedingung erfüllt
        } else { // 'hide' logic
            return !$condition_met; // Ausblenden, wenn Bedingung erfüllt
        }
    }

    /**
     * Beim Aktivieren des Plugins Standardwerte setzen
     */
    public function activate()
    {
        // Standard-Parameter setzen, falls noch nicht vorhanden
        if (!get_option('sessiontags_parameters')) {
            update_option('sessiontags_parameters', [
                [
                    'name' => 'quelle',
                    'shortcode' => 'q',
                    'fallback' => ''
                ],
                [
                    'name' => 'kampagne',
                    'shortcode' => 'k',
                    'fallback' => ''
                ],
                [
                    'name' => 'tag',
                    'shortcode' => 't',
                    'fallback' => ''
                ]
            ]);
        }

        // Geheimen Schlüssel setzen, falls noch nicht vorhanden
        if (!get_option('sessiontags_secret_key')) {
            update_option('sessiontags_secret_key', wp_generate_password(32, true, true));
        }
    }

    /**
     * Beim Deaktivieren des Plugins
     */
    public function deactivate()
    {
        // Hier könnten Aufräumarbeiten stattfinden
    }

    /**
     * AJAX-Handler für die Regenerierung des geheimen Schlüssels
     */
    public function ajax_regenerate_secret_key()
    {
        // Nonce prüfen
        check_ajax_referer('regenerate_secret_key', 'nonce');

        // Berechtigungen prüfen
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Keine Berechtigung!', 'sessiontags')]);
            return;
        }

        // Neuen Schlüssel generieren
        $new_key = wp_generate_password(32, true, true);

        // Schlüssel speichern
        update_option('sessiontags_secret_key', $new_key);

        // Erfolg zurückgeben
        wp_send_json_success($new_key);
    }
}

/**
 * Plugin initialisieren
 */
function sessiontags_init()
{
    SessionTags::get_instance();
}
add_action('plugins_loaded', 'sessiontags_init');
