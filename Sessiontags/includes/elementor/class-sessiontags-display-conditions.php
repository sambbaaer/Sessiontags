<?php

/**
 * SessionTags Elementor Pro Display Condition
 * 
 * Fügt eine Display Condition zu Elementor Pro hinzu, um Elemente basierend auf SessionTags-Parametern anzuzeigen
 */
class SessionTags_Display_Condition extends \ElementorPro\Modules\DisplayConditions\Conditions\Base\Condition_Base
{

    /**
     * Gibt den eindeutigen Namen der Condition zurück
     * 
     * @return string
     */
    public function get_name()
    {
        return 'sessiontags_parameter';
    }

    /**
     * Gibt den Label der Condition zurück
     * 
     * @return string
     */
    public function get_label()
    {
        return __('SessionTags Parameter', 'sessiontags');
    }

    /**
     * Gibt die Gruppe der Condition zurück
     * 
     * @return string
     */
    public function get_group()
    {
        return 'other';
    }

    /**
     * Registriert die Sub-Conditions
     * 
     * @return array
     */
    public function register_sub_conditions()
    {
        // Session Manager erstellen
        $session_manager = new SessionTagsSessionManager();
        $parameters = $session_manager->get_tracked_parameters();

        $sub_conditions = [];

        // Für jeden definierten Parameter eine Sub-Condition erstellen
        foreach ($parameters as $param) {
            $sub_conditions[] = [
                'key' => $param['name'],
                'label' => sprintf(__('Parameter "%s"', 'sessiontags'), $param['name']),
            ];
        }

        // Zusätzlich eine "Any Parameter" Option
        $sub_conditions[] = [
            'key' => '_any_parameter',
            'label' => __('Beliebiger Parameter', 'sessiontags'),
        ];

        return $sub_conditions;
    }

    /**
     * Prüft die Condition
     * 
     * @param array $args
     * @return bool
     */
    public function check($args)
    {
        $session_manager = new SessionTagsSessionManager();
        $sub_condition_key = $args['key'] ?? '';

        // Wenn "beliebiger Parameter" ausgewählt wurde
        if ($sub_condition_key === '_any_parameter') {
            $parameters = $session_manager->get_tracked_parameters();

            // Prüfen, ob mindestens ein Parameter in der Session vorhanden ist
            foreach ($parameters as $param) {
                $value = $session_manager->get_param($param['name'], null);
                if ($value !== null && $value !== '') {
                    return true;
                }
            }
            return false;
        }

        // Spezifischen Parameter prüfen
        $value = $session_manager->get_param($sub_condition_key, null);

        // Prüfen ob der Parameter vorhanden ist (nicht null und nicht leer)
        return ($value !== null && $value !== '');
    }
}

/**
 * SessionTags Elementor Pro Display Condition mit Wert-Vergleich
 * 
 * Erweiterte Condition für Wert-Vergleiche
 */
class SessionTags_Display_Condition_Value extends \ElementorPro\Modules\DisplayConditions\Conditions\Base\Condition_Base
{

    /**
     * Gibt den eindeutigen Namen der Condition zurück
     * 
     * @return string
     */
    public function get_name()
    {
        return 'sessiontags_parameter_value';
    }

    /**
     * Gibt den Label der Condition zurück
     * 
     * @return string
     */
    public function get_label()
    {
        return __('SessionTags Parameter Wert', 'sessiontags');
    }

    /**
     * Gibt die Gruppe der Condition zurück
     * 
     * @return string
     */
    public function get_group()
    {
        return 'other';
    }

    /**
     * Registriert die Sub-Conditions
     * 
     * @return array
     */
    public function register_sub_conditions()
    {
        // Session Manager erstellen
        $session_manager = new SessionTagsSessionManager();
        $parameters = $session_manager->get_tracked_parameters();

        $sub_conditions = [];

        // Für jeden definierten Parameter eine Sub-Condition erstellen
        foreach ($parameters as $param) {
            $sub_conditions[] = [
                'key' => $param['name'],
                'label' => sprintf(__('Parameter "%s" ist gleich', 'sessiontags'), $param['name']),
                'controls' => [
                    [
                        'name' => 'value',
                        'type' => \Elementor\Controls_Manager::TEXT,
                        'label' => __('Wert', 'sessiontags'),
                        'placeholder' => __('Wert eingeben', 'sessiontags'),
                    ],
                ],
            ];
        }

        return $sub_conditions;
    }

    /**
     * Prüft die Condition
     * 
     * @param array $args
     * @return bool
     */
    public function check($args)
    {
        $session_manager = new SessionTagsSessionManager();
        $sub_condition_key = $args['key'] ?? '';
        $expected_value = $args['value'] ?? '';

        // Parameter-Wert aus der Session holen
        $actual_value = $session_manager->get_param($sub_condition_key, '');

        // Werte vergleichen
        return ($actual_value === $expected_value);
    }
}

/**
 * SessionTags Elementor Pro Display Condition für mehrere Werte
 * 
 * Prüft ob der Parameter einen von mehreren Werten enthält
 */
class SessionTags_Display_Condition_Multiple extends \ElementorPro\Modules\DisplayConditions\Conditions\Base\Condition_Base
{

    /**
     * Gibt den eindeutigen Namen der Condition zurück
     * 
     * @return string
     */
    public function get_name()
    {
        return 'sessiontags_parameter_multiple';
    }

    /**
     * Gibt den Label der Condition zurück
     * 
     * @return string
     */
    public function get_label()
    {
        return __('SessionTags Parameter ist einer von', 'sessiontags');
    }

    /**
     * Gibt die Gruppe der Condition zurück
     * 
     * @return string
     */
    public function get_group()
    {
        return 'other';
    }

    /**
     * Registriert die Sub-Conditions
     * 
     * @return array
     */
    public function register_sub_conditions()
    {
        // Session Manager erstellen
        $session_manager = new SessionTagsSessionManager();
        $parameters = $session_manager->get_tracked_parameters();

        $sub_conditions = [];

        // Für jeden definierten Parameter eine Sub-Condition erstellen
        foreach ($parameters as $param) {
            $sub_conditions[] = [
                'key' => $param['name'],
                'label' => sprintf(__('Parameter "%s" ist einer von', 'sessiontags'), $param['name']),
                'controls' => [
                    [
                        'name' => 'values',
                        'type' => \Elementor\Controls_Manager::TEXTAREA,
                        'label' => __('Werte', 'sessiontags'),
                        'placeholder' => __('Ein Wert pro Zeile', 'sessiontags'),
                        'description' => __('Geben Sie jeden möglichen Wert in einer neuen Zeile ein.', 'sessiontags'),
                    ],
                ],
            ];
        }

        return $sub_conditions;
    }

    /**
     * Prüft die Condition
     * 
     * @param array $args
     * @return bool
     */
    public function check($args)
    {
        $session_manager = new SessionTagsSessionManager();
        $sub_condition_key = $args['key'] ?? '';
        $values_string = $args['values'] ?? '';

        // Parameter-Wert aus der Session holen
        $actual_value = $session_manager->get_param($sub_condition_key, '');

        // Werte-String in Array umwandeln
        $allowed_values = array_map('trim', explode("\n", $values_string));
        $allowed_values = array_filter($allowed_values); // Leere Zeilen entfernen

        // Prüfen ob der aktuelle Wert in der Liste ist
        return in_array($actual_value, $allowed_values, true);
    }
}
