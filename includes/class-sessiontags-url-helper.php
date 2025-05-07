<?php
/**
 * SessionTagsUrlHelper-Klasse
 * 
 * Stellt Hilfsfunktionen für die Generierung von SessionTags-URLs bereit
 */
class SessionTagsUrlHelper {
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
    public function __construct($session_manager) {
        $this->session_manager = $session_manager;
    }
    
    /**
     * Generiert eine URL mit den angegebenen Parametern
     * 
     * @param string $url Die Basis-URL
     * @param array $params Die Parameter und ihre Werte
     * @return string Die generierte URL
     */
    public function generate_url($url, $params = []) {
        // Prüfen, ob die URL bereits Parameter enthält
        $url_parts = parse_url($url);
        $has_query = isset($url_parts['query']) && !empty($url_parts['query']);
        
        // Zu verfolgende Parameter und Kürzel aus den Einstellungen holen
        $tracked_params = $this->session_manager->get_tracked_parameters();
        $url_shortcodes = $this->session_manager->get_url_shortcodes();
        $use_encoding = $this->session_manager->is_url_encoding_enabled();
        
        // Parameter-String erstellen
        $param_strings = [];
        
        foreach ($params as $param => $value) {
            // Nur verfolgte Parameter verwenden
            if (in_array($param, $tracked_params)) {
                // Kürzel verwenden, falls vorhanden
                $param_name = isset($url_shortcodes[$param]) && !empty($url_shortcodes[$param]) ? $url_shortcodes[$param] : $param;
                
                // Parameterwert verschleiern, falls aktiviert
                if ($use_encoding) {
                    $value = $this->session_manager->encode_param_value($value);
                }
                
                $param_strings[] = $param_name . '=' . urlencode($value);
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
