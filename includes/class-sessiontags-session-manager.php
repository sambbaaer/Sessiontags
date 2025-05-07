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
        // Zu verfolgende Parameter holen
        $tracked_params = $this->get_tracked_parameters();
        $use_encoding = $this->is_url_encoding_enabled();

        // Parameter-Map für die Zuordnung von Kürzeln zu Original-Namen erstellen
        $param_map = [];
        foreach ($tracked_params as $param) {
            $param_name = $param['name'];
            $param_shortcode = !empty($param['shortcode']) ? $param['shortcode'] : '';
            
            // Original-Parameter auf sich selbst mappen
            $param_map[$param_name] = $param_name;

            // Kürzel auf Original-Parameter mappen, falls vorhanden
            if (!empty($param_shortcode)) {
                $param_map[$param_shortcode] = $param_name;
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
     * Dekodiert einen Parameter-Wert
     *
     * @param string $value Der kodierte Wert
     * @return string Der dekodierte Wert
     */
    public function decode_param_value($value)
    {
        // URL-sichere Dekodierung
        $value = strtr($value, '-_,', '+/=');

        // Base64-Entschlüsselung
        $decoded = base64_decode($value);

        // Secret-Key-Teil entfernen
        $secret_key = get_option('sessiontags_secret_key', '');
        $parts = explode('|', $decoded);

        if (count($parts) > 1 && end($parts) === $secret_key) {
            array_pop($parts);
            return implode('|', $parts);
        }

        return $decoded;
    }

    /**
     * Gibt einen gespeicherten Parameter zurück
     *
     * @param string $key Der Schlüssel des Parameters
     * @param string $default Der Standardwert, falls der Parameter nicht existiert
     * @return string Der Wert des Parameters oder der Standardwert
     */
    public function get_param($key, $default = '')
    {
        // Zuerst prüfen, ob ein individueller Fallback geliefert wurde
        if (empty($default)) {
            // Wenn nicht, den Standard-Fallback aus den Einstellungen verwenden
            $parameters = $this->get_tracked_parameters();
            foreach ($parameters as $param) {
                if ($param['name'] === $key && isset($param['fallback'])) {
                    $default = $param['fallback'];
                    break;
                }
            }
        }

        // Parameter aus Session zurückgeben oder Fallback
        return isset($_SESSION[$this->session_key][$key]) ? $_SESSION[$this->session_key][$key] : $default;
    }

    /**
     * Gibt die zu verfolgenden Parameter zurück
     *
     * @return array Die zu verfolgenden Parameter
     */
    public function get_tracked_parameters()
    {
        return get_option('sessiontags_parameters', [
            ['name' => 'quelle', 'shortcode' => 'q', 'fallback' => '']
        ]);
    }
}