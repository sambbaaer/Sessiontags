<?php

/**
 * Plugin Name: SessionTags
 * Plugin URI: https://github.com/sambbaaer/Sessiontags
 * Description: Erfasst und speichert URL-Parameter in der PHP-Session für personalisierte Website-Erlebnisse. Bietet Shortcodes, Elementor-Integration, Avada-Unterstützung sowie URL-Verschleierung für optimiertes Kampagnen-Tracking.
 * Version: 1.2.0
 * Author: Samuel Baer mit Claude (KI)
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
define('SESSIONTAGS_VERSION', '1.2.0');

/**
 * Hauptklasse des SessionTags-Plugins
 */
class SessionTags
{
    /**
     * Instanz der Klasse (Singleton-Pattern)
     * 
     * @var SessionTags
     */
    private static $instance = null;

    /**
     * Instanz der SessionManager-Klasse
     * 
     * @var SessionTagsSessionManager
     */
    private $session_manager;

    /**
     * Instanz der ShortcodeHandler-Klasse
     * 
     * @var SessionTagsShortcodeHandler
     */
    private $shortcode_handler;

    /**
     * Instanz der Admin-Klasse
     * 
     * @var SessionTagsAdmin
     */
    private $admin;

    /**
     * Instanz der Elementor-Klasse
     * 
     * @var SessionTagsElementor
     */
    private $elementor;

    /**
     * Instanz der URL-Helper-Klasse
     * 
     * @var SessionTagsUrlHelper
     */
    private $url_helper;

    /**
     * Konstruktor der SessionTags-Klasse
     * 
     * Initialisiert die Plugin-Komponenten
     */
    private function __construct()
    {
        $this->init_components();
        $this->register_hooks();
    }

    /**
     * Gibt die einzige Instanz der Klasse zurück (Singleton-Pattern)
     * 
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

        // Instanzen erstellen
        $this->session_manager = new SessionTagsSessionManager();
        $this->shortcode_handler = new SessionTagsShortcodeHandler($this->session_manager);
        $this->admin = new SessionTagsAdmin($this->session_manager);
        $this->elementor = new SessionTagsElementor($this->session_manager);
        $this->avada = new SessionTagsAvada($this->session_manager);
        $this->url_helper = new SessionTagsUrlHelper($this->session_manager);
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

        // AJAX-Handler für die Regenerierung des geheimen Schlüssels
        add_action('wp_ajax_regenerate_secret_key', [$this, 'ajax_regenerate_secret_key']);

        // Plugin aktivieren
        register_activation_hook(__FILE__, [$this, 'activate']);

        // Plugin deaktivieren
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
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
                    'name' => 'id',
                    'shortcode' => 'i',
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
