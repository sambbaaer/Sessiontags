<?php
/**
 * SessionTagsSessionManager-Klasse
 * 
 * Verwaltet die PHP-Session und die URL-Parameter
 */
class SessionTagsSessionManager {
    /**
     * Session-Schlüssel für die gespeicherten Parameter
     * 
     * @var string
     */
    private $session_key = 'sessiontags_params';

    /**
     * Konstruktor der SessionTagsSessionManager-Klasse
     */
    public function __construct() {
        // Konstruktor ohne Parameter
    }

    /**
     * Initialisiert die Session und verarbeitet die URL-Parameter
     */
    public function init() {
        // Session nur starten, wenn noch keine existiert
        if (!session_id() && !headers_sent()) {
            session_start();
        }

        // Session-Array initialisieren, falls es noch nicht existiert
        if (!isset($_SESSION[$this->session_key]) || !is_array($_SESSION[$this->session_key])) {
            $_SESSION[$this->session_key] = [];
        }

        // URL-Parameter prüfen und in Session speichern
        $this->process_url_parameters();
    }

    /**
     * Verarbeitet die URL-Parameter und speichert sie in der Session
     */
    private function process_url_parameters() {
        // Zu verfolgende Parameter und Kürzel aus den Einstellungen holen
        $tracked_params = $this->get_tracked_parameters();
        $url_shortcodes = $this->get_url_shortcodes();
        $use_encoding = $this->is_url_encoding_enabled();
        
        // Parameter-Map für die Zuordnung von Kürzeln zu Original-Namen erstellen
        $param_map = [];
        foreach ($tracked_params as $param) {
            $param_map[$param] = $param; // Original-Parameter auf sich selbst mappen
            
            // Kürzel auf Original-Parameter mappen, falls vorhanden
            if (isset($url_shortcodes[$param]) && !empty($url_shortcodes[$param])) {
                $param_map[$url_shortcodes[$param]] = $param;
            }
        }
        
        // Jeden Parameter und sein mögliches Kürzel prüfen
        foreach ($param_map as $url_param => $session_param) {
            if (isset($_GET[$url_param]) && !empty($_GET[$url_param])) {
                // Parameter-Wert holen
                $value = $_GET[$url_param];
                
                // Wert dekodieren, falls Verschleierung aktiviert ist
                if ($use_encoding) {
                    $value = $this->decode_param_value($value);
                }
                
                // Parameter-Wert sanitisieren und in Session speichern
                $_SESSION[$this->session_key][$session_param] = sanitize_text_field($value);
            }
        }
    }
    
    /**
     * Prüft, ob die URL-Verschleierung aktiviert ist
     * 
     * @return bool True, wenn die URL-Verschleierung aktiviert ist, sonst false
     */
    public function is_url_encoding_enabled() {
        return (bool) get_option('sessiontags_url_encoding', false);
    }
    
    /**
     * Holt die Kürzel für die URL-Parameter aus den Einstellungen
     * 
     * @return array Die Kürzel für die URL-Parameter
     */
    public function get_url_shortcodes() {
        return get_option('sessiontags_url_shortcodes', []);
    }
    
    /**
     * Kodiert einen Parameter-Wert
     * 
     * @param string $value Der Wert, der kodiert werden soll
     * @return string Der kodierte Wert
     */
    public function encode_param_value($value) {
        // Base64-Verschlüsselung mit zusätzlicher Sicherheit
        $secret_key = get_option('sessiontags_secret_key', '');
        $encoded = base64_encode($value . '|' . $secret_key);
        
        // URL-sichere Kodierung
        return strtr($encoded, '+/=', '-_,');
    }
    
    /**
     * Dekodiert einen Parameter-Wert
     * 
     * @param string $value Der Wert, der dekodiert werden soll
     * @return string Der dekodierte Wert oder der ursprüngliche Wert, falls die Dekodierung fehlschlägt
     */
    public function decode_param_value($value) {
        try {
            // URL-sichere Dekodierung
            $decoded = strtr($value, '-_,', '+/=');
            
            // Base64-Entschlüsselung
            $decoded = base64_decode($decoded);
            
            if ($decoded === false) {
                return $value; // Dekodierung fehlgeschlagen, Original zurückgeben
            }
            
            // Secret Key abtrennen
            $secret_key = get_option('sessiontags_secret_key', '');
            $parts = explode('|', $decoded);
            
            if (count($parts) === 2 && $parts[1] === $secret_key) {
                return $parts[0];
            }
            
            // Wenn der Secret Key nicht übereinstimmt, Original zurückgeben
            return $value;
        } catch (Exception $e) {
            // Bei Fehlern Original zurückgeben
            return $value;
        }
    }

    /**
     * Gibt den Wert eines bestimmten Parameters aus der Session zurück
     * 
     * @param string $key Der Schlüssel des Parameters
     * @param string $default Der Standardwert, falls der Parameter nicht existiert
     * @return string Der Wert des Parameters oder der Standardwert
     */
    public function get_param($key, $default = '') {
        if (isset($_SESSION[$this->session_key][$key])) {
            return $_SESSION[$this->session_key][$key];
        }
        return $default;
    }

    /**
     * Gibt alle gespeicherten Parameter zurück
     * 
     * @return array Die gespeicherten Parameter
     */
    public function get_all_params() {
        return isset($_SESSION[$this->session_key]) ? $_SESSION[$this->session_key] : [];
