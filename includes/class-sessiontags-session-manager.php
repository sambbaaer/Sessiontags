<?php

/**
 * SessionTagsSessionManager-Klasse
 *
 * Verwaltet die PHP-Session und die URL-Parameter
 */
class SessionTagsSessionManager
{
    /**
     * Session-Schlüssel für die gespeicherten Parameter
     * @var string
     */
    private $session_key = 'sessiontags_params';

    /**
     * Cache für die zu verfolgenden Parameter
     * @var array|null
     */
    private static $tracked_parameters = null;

    /**
     * Cache für den geheimen Schlüssel
     * @var string|null
     */
    private static $secret_key = null;

    /**
     * Cache für die URL-Verschlüsselungs-Einstellung
     * @var bool|null
     */
    private static $url_encoding_enabled = null;

    /**
     * Konstruktor der SessionTagsSessionManager-Klasse
     */
    public function __construct()
    {
        // Konstruktor ohne Parameter
    }

    /**
     * Initialisiert die Session und verarbeitet die URL-Parameter
     */
    public function init()
    {
        // Session nur starten, wenn noch keine existiert
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        // Session-Array initialisieren, falls es noch nicht existiert
        if (!isset($_SESSION[$this->session_key]) || !is_array($_SESSION[$this->session_key])) {
            $_SESSION[$this->session_key] = [];
        }

        // URL-Parameter prüfen und in Session speichern
        $this->process_url_parameters();

        // Auf notwendige Weiterleitungen prüfen
        $this->check_and_perform_redirects();
    }

    /**
     * Verarbeitet die URL-Parameter und speichert sie in der Session
     */
    private function process_url_parameters()
    {
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
     * Gibt die zu verfolgenden Parameter zurück (mit Caching)
     *
     * @return array Die zu verfolgenden Parameter
     */
    public function get_tracked_parameters()
    {
        if (self::$tracked_parameters === null) {
            self::$tracked_parameters = get_option('sessiontags_parameters', [
                ['name' => 'quelle', 'shortcode' => 'q', 'fallback' => '', 'redirect_url' => '']
            ]);
        }
        return self::$tracked_parameters;
    }

    /**
     * Prüft, ob die URL-Verschleierung aktiviert ist (mit Caching)
     *
     * @return bool True, wenn die URL-Verschleierung aktiviert ist, sonst false
     */
    public function is_url_encoding_enabled()
    {
        if (self::$url_encoding_enabled === null) {
            self::$url_encoding_enabled = (bool) get_option('sessiontags_url_encoding', false);
        }
        return self::$url_encoding_enabled;
    }

    /**
     * Holt den geheimen Schlüssel (mit Caching)
     * * @return string Der geheime Schlüssel
     */
    private function get_secret_key()
    {
        if (self::$secret_key === null) {
            self::$secret_key = get_option('sessiontags_secret_key', '');
        }
        return self::$secret_key;
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
        if ($decoded === false) {
            return ''; // Fehler bei der Dekodierung
        }

        // Secret-Key-Teil entfernen
        $secret_key = $this->get_secret_key();
        $parts = explode('|', $decoded);

        if (count($parts) > 1 && end($parts) === $secret_key) {
            array_pop($parts);
            return implode('|', $parts);
        }

        return $decoded;
    }

    /**
     * Kodiert einen Parameter-Wert
     *
     * @param string $value Der zu kodierende Wert
     * @return string Der kodierte Wert
     */
    public function encode_param_value($value)
    {
        // Secret Key holen
        $secret_key = $this->get_secret_key();

        // Wert mit Secret Key kombinieren
        $encoded = $value . '|' . $secret_key;

        // Base64-Verschlüsselung
        $encoded = base64_encode($encoded);

        // URL-sichere Kodierung
        $encoded = strtr($encoded, '+/=', '-_,');

        return $encoded;
    }

    /**
     * Gibt einen gespeicherten Parameter zurück
     *
     * @param string $key Der Schlüssel des Parameters
     * @param string $default Der individuelle Standardwert, falls der Parameter nicht existiert
     * @return string Der Wert des Parameters oder der Standardwert
     */
    public function get_param($key, $default = '')
    {
        // Parameter aus Session holen
        $value = isset($_SESSION[$this->session_key][$key]) ? $_SESSION[$this->session_key][$key] : null;

        // Wenn der Wert in der Session existiert, diesen zurückgeben
        if ($value !== null && $value !== '') {
            return $value;
        }

        // Wenn ein individueller Fallback übergeben wurde, diesen verwenden
        if (!empty($default)) {
            return $default;
        }

        // Andernfalls den Standard-Fallback aus den Einstellungen suchen
        $parameters = $this->get_tracked_parameters();
        foreach ($parameters as $param) {
            if ($param['name'] === $key && isset($param['fallback']) && $param['fallback'] !== '') {
                return $param['fallback'];
            }
        }

        // Wenn nichts gefunden wurde, einen leeren String zurückgeben
        return '';
    }

    /**
     * Überprüft, ob eine Weiterleitung für einen Parameter notwendig ist
     * und führt diese gegebenenfalls durch
     */
    public function check_and_perform_redirects()
    {
        // Zu verfolgende Parameter holen
        $tracked_params = $this->get_tracked_parameters();

        foreach ($tracked_params as $param) {
            $param_name = $param['name'];
            $param_shortcode = !empty($param['shortcode']) ? $param['shortcode'] : '';
            $redirect_url = !empty($param['redirect_url']) ? $param['redirect_url'] : '';

            // Wenn eine Weiterleitungs-URL definiert ist und der Parameter in der URL vorkommt
            if (
                !empty($redirect_url) &&
                (isset($_GET[$param_name]) || (!empty($param_shortcode) && isset($_GET[$param_shortcode])))
            ) {

                // Überprüfen, ob die aktuelle URL bereits die Weiterleitungs-URL ist, um Loops zu vermeiden
                $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                if (strpos($current_url, $redirect_url) === 0) {
                    continue; // Wir sind bereits auf der Ziel-URL, also nicht weiterleiten
                }

                // Parameter zum Ziel übertragen
                $url_helper = new SessionTagsUrlHelper($this);
                $final_redirect_url = $redirect_url;

                // Alle aktuellen GET-Parameter an die Weiterleitungs-URL anhängen
                $params_to_pass = [];
                $param_value = isset($_GET[$param_name]) ? $_GET[$param_name] : (isset($_GET[$param_shortcode]) ? $_GET[$param_shortcode] : '');
                $params_to_pass[$param_name] = $param_value;

                $final_redirect_url = $url_helper->generate_url($redirect_url, $params_to_pass);

                // Weiterleitung durchführen und Skriptausführung beenden
                wp_redirect($final_redirect_url);
                exit;
            }
        }
    }
}
