<?php
/**
 * SessionTags Elementor Dynamic Tag
 * 
 * Stellt einen Dynamic Tag für Elementor zur Verfügung
 */
class SessionTags_Dynamic_Tag extends \Elementor\Core\DynamicTags\Tag {
    /**
     * Gibt die Gruppe des Tags zurück
     * 
     * @return string Die Gruppe des Tags
     */
    public function get_group() {
        return 'sessiontags';
    }

    /**
     * Gibt die Kategorie des Tags zurück
     * 
     * @return string Die Kategorie des Tags
     */
    public function get_categories() {
        return ['text'];
    }

    /**
     * Gibt den Namen des Tags zurück
     * 
     * @return string Der Name des Tags
     */
    public function get_name() {
        return 'sessiontags-parameter';
    }

    /**
     * Gibt den Titel des Tags zurück
     * 
     * @return string Der Titel des Tags
     */
    public function get_title() {
        return esc_html__('SessionTags Parameter', 'sessiontags');
    }
    
    /**
     * Registriert die Steuerelemente des Tags
     */
    protected function register_controls() {
        // Die zu verfolgenden Parameter holen
        $sessionManager = new SessionTagsSessionManager();
        $parameters = $sessionManager->get_tracked_parameters();
        
        // Optionen für das Dropdown erstellen
        $options = [];
        foreach ($parameters as $parameter) {
            $options[$parameter] = $parameter;
        }
        
        // Wenn keine Parameter definiert sind, Hinweis anzeigen
        if (empty($options)) {
            $options['no_params'] = esc_html__('Keine Parameter definiert', 'sessiontags');
        }
        
        // Parameter-Steuerung hinzufügen
        $this->add_control(
            'parameter',
            [
                'label' => esc_html__('Parameter', 'sessiontags'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $options,
                'default' => array_key_first($options),
            ]
        );
        
        // Standardwert-Steuerung hinzufügen
        $this->add_control(
            'default_value',
            [
                'label' => esc_html__('Standardwert', 'sessiontags'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '',
                'description' => esc_html__('Wird angezeigt, wenn der Parameter nicht in der Session existiert.', 'sessiontags'),
            ]
        );
    }

    /**
     * Gibt den Wert des Tags zurück
     * 
     * @param array $options Die Optionen des Tags
     */
    public function render() {
        // Einstellungen abrufen
        $parameter = $this->get_settings('parameter');
        $default_value = $this->get_settings('default_value');
        
        // Wenn kein Parameter ausgewählt ist oder 'no_params' ausgewählt ist, nichts anzeigen
        if (empty($parameter) || $parameter === 'no_params') {
            return;
        }
        
        // Session-Manager erstellen
        $sessionManager = new SessionTagsSessionManager();
        
        // Parameter aus der Session holen und ausgeben
        echo esc_html($sessionManager->get_param($parameter, $default_value));
    }
}
