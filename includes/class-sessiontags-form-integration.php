<?php

/**
 * SessionTagsFormIntegration-Klasse
 * 
 * Integriert das Plugin mit Formular-Diensten wie Google Forms und Microsoft Forms
 */
class SessionTagsFormIntegration
{
    /**
     * Instanz der SessionManager-Klasse
     * 
     * @var SessionTagsSessionManager
     */
    private $session_manager;

    /**
     * Konstruktor der SessionTagsFormIntegration-Klasse
     * 
     * @param SessionTagsSessionManager $session_manager Die Instanz des SessionManagers
     */
    public function __construct($session_manager)
    {
        $this->session_manager = $session_manager;
    }

    /**
     * Registriert die Shortcodes
     */
    public function register_shortcodes()
    {
        add_shortcode('st_form', [$this, 'shortcode_embed_form']);
    }

    /**
     * Callback für den Shortcode [st_form]
     * 
     * @param array $atts Die Attribute des Shortcodes
     * @return string Der Ausgabewert des Shortcodes
     */
    public function shortcode_embed_form($atts)
    {
        $default_atts = [
            'type'          => 'google',       // Typ des Formulars (google oder microsoft)
            'url'           => '',             // URL des Formulars
            'width'         => '100%',         // Breite des iFrames
            'height'        => '800px',        // Höhe des iFrames
            'params'        => '',             // Komma-getrennte Liste von Session-Parametern, die übertragen werden sollen
            'form_params'   => '',             // Komma-getrennte Liste von Ziel-Formular-Parametern (optional, wenn abweichend von Session-Parametern)
            'title'         => '',             // Titel-Attribut für den iFrame
            'class'         => '',             // CSS-Klasse für den iFrame
        ];

        // Attribute mit Standardwerten zusammenführen
        $atts = shortcode_atts($default_atts, $atts, 'st_form');

        // Formular-URL prüfen
        if (empty($atts['url'])) {
            return '<p class="error">' . esc_html__('Fehler: Keine Formular-URL angegeben.', 'sessiontags') . '</p>';
        }

        // Parameter auslesen
        $session_params = [];
        $form_params = [];

        if (!empty($atts['params'])) {
            $session_params = explode(',', $atts['params']);
            $session_params = array_map('trim', $session_params);

            // Wenn form_params angegeben wurden, diese verwenden, sonst session_params
            if (!empty($atts['form_params'])) {
                $form_params = explode(',', $atts['form_params']);
                $form_params = array_map('trim', $form_params);

                // Sicherstellen, dass beide Arrays gleich lang sind
                if (count($form_params) !== count($session_params)) {
                    return '<p class="error">' . esc_html__('Fehler: Die Anzahl der Session-Parameter und Formular-Parameter muss übereinstimmen.', 'sessiontags') . '</p>';
                }
            } else {
                $form_params = $session_params;
            }
        }

        // Formular-URL mit Parametern generieren
        $form_url = $this->generate_form_url(
            $atts['url'],
            $atts['type'],
            $session_params,
            $form_params
        );

        // iFrame-Attribute
        $iframe_atts = [
            'src'             => esc_url($form_url),
            'width'           => esc_attr($atts['width']),
            'height'          => esc_attr($atts['height']),
            'frameborder'     => '0',
            'marginheight'    => '0',
            'marginwidth'     => '0',
        ];

        // Optionale Attribute
        if (!empty($atts['title'])) {
            $iframe_atts['title'] = esc_attr($atts['title']);
        }

        if (!empty($atts['class'])) {
            $iframe_atts['class'] = esc_attr($atts['class']);
        }

        // iFrame-Attribute in String umwandeln
        $iframe_atts_str = '';
        foreach ($iframe_atts as $key => $value) {
            $iframe_atts_str .= $key . '="' . $value . '" ';
        }

        // iFrame erstellen
        return '<iframe ' . $iframe_atts_str . '>' . esc_html__('Dein Browser unterstützt keine iFrames.', 'sessiontags') . '</iframe>';
    }

    /**
     * Generiert die URL für das Formular mit vorausgefüllten Parametern
     * 
     * @param string $url Die Basis-URL des Formulars
     * @param string $type Der Typ des Formulars (google oder microsoft)
     * @param array $session_params Die Session-Parameter
     * @param array $form_params Die Formular-Parameter
     * @return string Die generierte URL
     */
    private function generate_form_url($url, $type, $session_params, $form_params)
    {
        // Basis-URL bereinigen
        $url = trim($url);

        // Parameter-Werte aus der Session holen
        $param_values = [];
        foreach ($session_params as $index => $param) {
            $value = $this->session_manager->get_param($param);
            if (!empty($value)) {
                $param_values[$form_params[$index]] = $value;
            }
        }

        // Wenn keine Parameter vorhanden sind, URL unverändert zurückgeben
        if (empty($param_values)) {
            return $url;
        }

        // URL für den entsprechenden Formular-Typ generieren
        switch (strtolower($type)) {
            case 'google':
                return $this->generate_google_form_url($url, $param_values);
            case 'microsoft':
                return $this->generate_microsoft_form_url($url, $param_values);
            default:
                return $url;
        }
    }

    /**
     * Generiert die URL für ein Google Form mit vorausgefüllten Parametern
     * 
     * @param string $url Die Basis-URL des Google Forms
     * @param array $params Die Parameter und ihre Werte
     * @return string Die generierte URL
     */
    private function generate_google_form_url($url, $params)
    {
        // Prüfen, ob die URL bereits Parameter enthält
        $url_parts = parse_url($url);
        $has_query = isset($url_parts['query']) && !empty($url_parts['query']);
        $separator = $has_query ? '&' : '?';

        // Google Forms verwendet "entry.XXXXX" als Parameter-Format
        $param_strings = [];
        foreach ($params as $param => $value) {
            // Bei Google Forms sollte der Parameter ein "entry.XXXXX"-Format haben
            if (strpos($param, 'entry.') !== 0) {
                $param = 'entry.' . $param;
            }
            $param_strings[] = $param . '=' . urlencode($value);
        }

        // Parameter anhängen
        if (!empty($param_strings)) {
            // Sicherstellen, dass die URL auf /viewform endet (wichtig für Google Forms)
            if (strpos($url, '/viewform') === false) {
                if (substr($url, -1) !== '/') {
                    $url .= '/';
                }
                $url .= 'viewform';
            }

            $url .= $separator . implode('&', $param_strings);
        }

        return $url;
    }

    /**
     * Generiert die URL für ein Microsoft Form mit vorausgefüllten Parametern
     * 
     * @param string $url Die Basis-URL des Microsoft Forms
     * @param array $params Die Parameter und ihre Werte
     * @return string Die generierte URL
     */
    private function generate_microsoft_form_url($url, $params)
    {
        // Prüfen, ob die URL bereits Parameter enthält
        $url_parts = parse_url($url);
        $has_query = isset($url_parts['query']) && !empty($url_parts['query']);
        $separator = $has_query ? '&' : '?';

        // Microsoft Forms verwendet einfache Parameter-Namen
        $param_strings = [];
        foreach ($params as $param => $value) {
            $param_strings[] = $param . '=' . urlencode($value);
        }

        // Parameter anhängen
        if (!empty($param_strings)) {
            $url .= $separator . implode('&', $param_strings);
        }

        return $url;
    }
}
