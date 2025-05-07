
<?php
/**
 * SessionTagsShortcodeHandler-Klasse
 * 
 * Verwaltet die Shortcodes für die Ausgabe der Session-Parameter
 */
class SessionTagsShortcodeHandler {
    /**
     * Instanz der SessionManager-Klasse
     * 
     * @var SessionTagsSessionManager
     */
    private $session_manager;

    /**
     * Konstruktor der SessionTagsShortcodeHandler-Klasse
     * 
     * @param SessionTagsSessionManager $session_manager Die Instanz des SessionManagers
     */
    public function __construct($session_manager) {
        $this->session_manager = $session_manager;
    }

    /**
     * Registriert die Shortcodes
     */
    public function register_shortcodes() {
        // Shortcodes für Parameter-Anzeige
        add_shortcode('show_session_param', [$this, 'shortcode_session_param']);
        add_shortcode('st', [$this, 'shortcode_session_param']);
        
        // Shortcode für URL-Generierung
        add_shortcode('st_url', [$this, 'shortcode_generate_url']);
    }
    
    /**
     * Callback für den Shortcode [st_url]
     * 
     * @param array $atts Die Attribute des Shortcodes
     * @return string Der Ausgabewert des Shortcodes
     */
    public function shortcode_generate_url($atts, $content = null) {
        $default_atts = [
            'url' => '',       // Basis-URL
            'params' => '',    // Komma-getrennte Parameter im Format param=wert
            'class' => '',     // CSS-Klasse für den Link
            'title' => '',     // Titel-Attribut für den Link
        ];

        // Attribute mit Standardwerten zusammenführen
        $atts = shortcode_atts($default_atts, $atts, 'st_url');
        
        // URL prüfen
        $url = $atts['url'];
        if (empty($url)) {
            $url = home_url('/');
        }
        
        // Parameter verarbeiten
        $params = [];
        if (!empty($atts['params'])) {
            $param_strings = explode(',', $atts['params']);
            
            foreach ($param_strings as $param_string) {
                $parts = explode('=', $param_string, 2);
                
                if (count($parts) === 2) {
                    $param_name = trim($parts[0]);
                    $param_value = trim($parts[1]);
                    
                    if (!empty($param_name) && !empty($param_value)) {
                        $params[$param_name] = $param_value;
                    }
                }
            }
        }
        
        // URL generieren
        $url_helper = new SessionTagsUrlHelper($this->session_manager);
        $generated_url = $url_helper->generate_url($url, $params);
        
        // Link-Inhalt verarbeiten
        $link_content = do_shortcode($content);
        if (empty($link_content)) {
            $link_content = esc_html($generated_url);
        }
        
        // Link ausgeben
        $class_attr = !empty($atts['class']) ? ' class="' . esc_attr($atts['class']) . '"' : '';
        $title_attr = !empty($atts['title']) ? ' title="' . esc_attr($atts['title']) . '"' : '';
        
        return '<a href="' . esc_url($generated_url) . '"' . $class_attr . $title_attr . '>' . $link_content . '</a>';
    }

    /**
     * Callback für den Shortcode [show_session_param] und [st]
     * 
     * @param array $atts Die Attribute des Shortcodes
     * @return string Der Ausgabewert des Shortcodes
     */
    public function shortcode_session_param($atts) {
        $default_atts = [
            'key' => '',      // Parameter-Schlüssel
            'default' => '',  // Standardwert
            'k' => '',        // Kurzform für key
            'd' => '',        // Kurzform für default
        ];

        // Attribute mit Standardwerten zusammenführen
        $atts = shortcode_atts($default_atts, $atts, 'show_session_param');

        // Kurzform-Attribute prüfen und verwenden
        $key = !empty($atts['k']) ? $atts['k'] : $atts['key'];
        $default = !empty($atts['d']) ? $atts['d'] : $atts['default'];

        // Wenn kein Schlüssel angegeben wurde, leeren String zurückgeben
        if (empty($key)) {
            return '';
        }

        // Parameter aus Session holen und zurückgeben
        return esc_html($this->session_manager->get_param($key, $default));
    }
}

