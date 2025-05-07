<?php

/**
 * SessionTagsUrlHelper-Klasse
 * 
 * Stellt Hilfsfunktionen für die Generierung von SessionTags-URLs bereit
 */
class SessionTagsUrlHelper
{
    /**
     * Instanz der SessionManager-Klasse
     * 
     * @var SessionTagsSessionManager
     */
    private $session_manager;

    /**
     * Konstruktor der SessionTagsUrlHelper-Klasse
     * 
     * @param SessionTagsSessionManager $session_manager Die Instanz des SessionManagers
     */
    public function __construct($session_manager)
    {
        $this->session_manager = $session_manager;
    }

    /**
     * Generiert eine URL mit den angegebenen Parametern
     * 
     * @param string $url Die Basis-URL
     * @param array $params Die Parameter und ihre Werte
     * @return string Die generierte URL
     */
    public function generate_url($url, $params = [])
    {
        // Prüfen, ob die URL bereits Parameter enthält
        $url_parts = parse_url($url);
        $has_query = isset($url_parts['query']) && !empty($url_parts['query']);

        // Zu verfolgende Parameter holen
        $tracked_params = $this->session_manager->get_tracked_parameters();
        $use_encoding = $this->session_manager->is_url_encoding_enabled();

        // Parameter-String erstellen
        $param_strings = [];

        foreach ($params as $param => $value) {
            // Prüfen, ob der Parameter verfolgt wird
            $is_tracked = false;
            $param_name = '';
            $param_shortcode = '';

            foreach ($tracked_params as $tracked_param) {
                if ($tracked_param['name'] === $param) {
                    $is_tracked = true;
                    $param_name = $tracked_param['name'];
                    $param_shortcode = !empty($tracked_param['shortcode']) ? $tracked_param['shortcode'] : '';
                    break;
                }
            }

            if ($is_tracked) {
                // Kürzel verwenden, falls vorhanden
                $url_param = !empty($param_shortcode) ? $param_shortcode : $param_name;

                // Parameterwert verschleiern, falls aktiviert
                if ($use_encoding) {
                    $value = $this->session_manager->encode_param_value($value);
                }

                $param_strings[] = $url_param . '=' . urlencode($value);
            }
        }

        // Parameter an URL anfügen
        if (!empty($param_strings)) {
            $separator = $has_query ? '&' : '?';
            $url .= $separator . implode('&', $param_strings);
        }

        return $url;
    }
}
