<?php
/**
 * Plugin Name: SessionTags
 * Plugin URI: https://example.com/sessiontags
 * Description: Speichert vordefinierte URL-Parameter in der PHP-Session und stellt einen Shortcode für deren Ausgabe bereit.
 * Version: 1.1.0
 * Author: Entwickelt für WordPress
 * Author URI: https://example.com
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
define('SESSIONTAGS_VERSION', '1.1.0');

/**
 * Hauptklasse des SessionTags-Plugins
 */
class SessionTags {
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
    private function __construct() {
        $this->init_components();
        $this->register_hooks();
    }

    /**
     * Gibt die einzige Instanz der Klasse zurück (Singleton-Pattern)
     * 
     * @return SessionTags
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialisiert die Komponenten des Plugins
     */
    private function init_components() {
        // Komponenten laden
        require_once SESSIONTAGS_PATH . 'includes/class-sessiontags-session-manager.php';
        require_once SESSIONTAGS_PATH . 'includes/class-sessiontags-shortcode-handler.php';
        require_once SESSIONTAGS_PATH . 'admin/class-sessiontags-admin.php';
        require_once SESSIONTAGS_PATH . 'includes/class-sessiontags-elementor.php';
        require_once SESSIONTAGS_PATH . 'includes/class-sessiontags-url-helper.php';

        // Instanzen erstellen
        $this->session_manager = new SessionTagsSessionManager();
        $this->shortcode_handler = new SessionTagsShortcodeHandler($this->session_manager);
        $this->admin = new SessionTagsAdmin($this->session_manager);
        $this->elementor = new SessionTagsElementor($this->session_manager);
        $this->url_helper = new SessionTagsUrlHelper($this->session_manager);
    }

    /**
     * Registriert die erforderlichen WordPress-Hooks
     */
    private function register_hooks() {
        // Session starten und URL-Parameter verarbeiten beim Plugin-Start
        add_action('init', [$this->session_manager, 'init'], 1);
        
        // Shortcode registrieren
        add_action('init', [$this->shortcode_handler, 'register_shortcodes'], 10);
        
        // Plugin aktivieren
        register_activation_hook(__FILE__, [$this, 'activate']);
    }
    
    /**
     * Beim Aktivieren des Plugins Standardwerte setzen
     */
    public function activate() {
        // Standard-Parameter setzen, falls noch nicht vorhanden
        if (!get_option('sessiontags_parameters')) {
            update_option('sessiontags_parameters', ['quelle', 'kampagne', 'id']);
        }
    }
}

/**
 * Plugin initialisieren
 */
function sessiontags_init() {
    SessionTags::get_instance();
}
add_action('plugins_loaded', 'sessiontags_init');
