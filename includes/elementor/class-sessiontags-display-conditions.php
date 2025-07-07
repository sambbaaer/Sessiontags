<?php

/**
 * SessionTags Elementor Pro Display Condition
 * * Fügt eine Display Condition zu Elementor Pro hinzu, um Elemente basierend auf SessionTags-Parametern anzuzeigen
 */

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

// FIX: Die Klassen nur definieren, wenn die Basisklasse von Elementor Pro existiert.
// Dies verhindert fatale Fehler, wenn Elementor Pro nicht aktiv ist.
if (class_exists('ElementorPro\Modules\DisplayConditions\Conditions\Base\Condition_Base')) {

    /**
     * SessionTags_Display_Condition_Base
     * * Eine abstrakte Basisklasse, um die Initialisierung des Session Managers zu zentralisieren.
     */
    abstract class SessionTags_Display_Condition_Base extends \ElementorPro\Modules\DisplayConditions\Conditions\Base\Condition_Base
    {

        /**
         * Gibt eine initialisierte Instanz des Session Managers zurück.
         *
         * @return SessionTagsSessionManager
         */
        protected function get_session_manager()
        {
            // Stellt sicher, dass die Session-Manager-Klasse geladen ist.
            if (!class_exists('SessionTagsSessionManager')) {
                require_once SESSIONTAGS_PATH . 'includes/class-sessiontags-session-manager.php';
            }
            $session_manager = new SessionTagsSessionManager();
            // FIX: Die init() Methode muss aufgerufen werden, um die Session mit den aktuellen URL-Parametern zu füllen.
            $session_manager->init();
            return $session_manager;
        }

        /**
         * Gibt die Gruppe der Condition zurück.
         * IMPROVEMENT: Eigene Gruppe für bessere Übersichtlichkeit im Elementor-Editor.
         * * @return string
         */
        public function get_group()
        {
            return 'sessiontags';
        }

        /**
         * Gibt die Optionen für die Bedingung zurück.
         * Muss von den Kindklassen implementiert werden.
         *
         * @return array
         */
        public function get_options()
        {
            // Diese Methode wird in den Kindklassen überschrieben.
            return [];
        }
    }


    /**
     * Condition: SessionTags Parameter
     * Prüft, ob ein bestimmter oder irgendein Parameter in der Session existiert.
     */
    class SessionTags_Display_Condition_Exists extends SessionTags_Display_Condition_Base
    {

        /**
         * Gibt den eindeutigen Namen der Condition zurück.
         * * @return string
         */
        public function get_name()
        {
            return 'sessiontags_param_exists';
        }

        /**
         * Gibt den Label der Condition zurück.
         * IMPROVEMENT: Klarere Benennung für die UI.
         * * @return string
         */
        public function get_label()
        {
            return __('SessionTags: Parameter existiert', 'sessiontags');
        }

        /**
         * Definiert die Steuerelemente für die Bedingung.
         */
        protected function register_controls()
        {
            $session_manager = $this->get_session_manager();
            $parameters = $session_manager->get_tracked_parameters();
            $options = [
                '_any' => __('Beliebiger Parameter', 'sessiontags'),
            ];

            if (!empty($parameters)) {
                foreach ($parameters as $param) {
                    $options[$param['name']] = sprintf(__('Parameter "%s"', 'sessiontags'), $param['name']);
                }
            }

            $this->add_control(
                'param_key',
                [
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'label' => __('Parameter', 'sessiontags'),
                    'options' => $options,
                    'default' => '_any',
                ]
            );
        }

        /**
         * Prüft die Condition.
         * * @param array $args
         * @return bool
         */
        public function check($args): bool
        {
            $session_manager = $this->get_session_manager();
            $param_key = $args['param_key'] ?? '_any';

            // Wenn "beliebiger Parameter" ausgewählt wurde
            if ($param_key === '_any') {
                $all_session_params = $_SESSION['sessiontags_params'] ?? [];
                return !empty($all_session_params);
            }

            // Spezifischen Parameter prüfen
            $value = $session_manager->get_param($param_key, null);

            // Prüfen ob der Parameter vorhanden ist (nicht null und nicht leer)
            return ($value !== null && $value !== '');
        }
    }

    /**
     * Condition: SessionTags Parameter Wert
     * Prüft, ob ein Parameter einen bestimmten Wert hat.
     */
    class SessionTags_Display_Condition_Value extends SessionTags_Display_Condition_Base
    {

        /**
         * Gibt den eindeutigen Namen der Condition zurück.
         * * @return string
         */
        public function get_name()
        {
            return 'sessiontags_param_value';
        }

        /**
         * Gibt den Label der Condition zurück.
         * IMPROVEMENT: Klarere Benennung für die UI.
         * * @return string
         */
        public function get_label()
        {
            return __('SessionTags: Parameter hat Wert', 'sessiontags');
        }

        /**
         * Definiert die Steuerelemente für die Bedingung.
         */
        protected function register_controls()
        {
            $session_manager = $this->get_session_manager();
            $parameters = $session_manager->get_tracked_parameters();
            $options = [];

            if (!empty($parameters)) {
                foreach ($parameters as $param) {
                    $options[$param['name']] = $param['name'];
                }
            }

            $this->add_control(
                'param_key',
                [
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'label' => __('Parameter', 'sessiontags'),
                    'options' => $options,
                    'default' => !empty($options) ? key($options) : '',
                ]
            );

            $this->add_control(
                'value',
                [
                    'type' => \Elementor\Controls_Manager::TEXT,
                    'label' => __('Wert', 'sessiontags'),
                    'placeholder' => __('Wert eingeben', 'sessiontags'),
                ]
            );
        }

        /**
         * Prüft die Condition.
         * * @param array $args
         * @return bool
         */
        public function check($args): bool
        {
            $session_manager = $this->get_session_manager();
            $param_key = $args['param_key'] ?? '';
            $expected_value = $args['value'] ?? '';

            if (empty($param_key)) {
                return false;
            }

            // Parameter-Wert aus der Session holen
            $actual_value = $session_manager->get_param($param_key, null);

            // Werte vergleichen (strikt, aber nach Konvertierung zu String, um Typ-Probleme zu vermeiden)
            return (string) $actual_value === (string) $expected_value;
        }
    }

    /**
     * Condition: SessionTags Parameter ist einer von
     * Prüft, ob der Parameter einen von mehreren Werten enthält.
     */
    class SessionTags_Display_Condition_Is_One_Of extends SessionTags_Display_Condition_Base
    {

        /**
         * Gibt den eindeutigen Namen der Condition zurück.
         * * @return string
         */
        public function get_name()
        {
            return 'sessiontags_param_is_one_of';
        }

        /**
         * Gibt den Label der Condition zurück.
         * IMPROVEMENT: Klarere Benennung für die UI.
         * * @return string
         */
        public function get_label()
        {
            return __('SessionTags: Parameter ist einer von', 'sessiontags');
        }

        /**
         * Definiert die Steuerelemente für die Bedingung.
         */
        protected function register_controls()
        {
            $session_manager = $this->get_session_manager();
            $parameters = $session_manager->get_tracked_parameters();
            $options = [];

            if (!empty($parameters)) {
                foreach ($parameters as $param) {
                    $options[$param['name']] = $param['name'];
                }
            }

            $this->add_control(
                'param_key',
                [
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'label' => __('Parameter', 'sessiontags'),
                    'options' => $options,
                    'default' => !empty($options) ? key($options) : '',
                ]
            );

            $this->add_control(
                'values',
                [
                    'type' => \Elementor\Controls_Manager::TEXTAREA,
                    'label' => __('Werte', 'sessiontags'),
                    'placeholder' => __('Ein Wert pro Zeile', 'sessiontags'),
                    'description' => __('Geben Sie jeden möglichen Wert in einer neuen Zeile ein.', 'sessiontags'),
                ]
            );
        }

        /**
         * Prüft die Condition.
         * * @param array $args
         * @return bool
         */
        public function check($args): bool
        {
            $session_manager = $this->get_session_manager();
            $param_key = $args['param_key'] ?? '';
            $values_string = $args['values'] ?? '';

            if (empty($param_key) || empty($values_string)) {
                return false;
            }

            // Parameter-Wert aus der Session holen
            $actual_value = $session_manager->get_param($param_key, null);

            // Werte-String in Array umwandeln
            $allowed_values = array_map('trim', explode("\n", $values_string));
            $allowed_values = array_filter($allowed_values, function ($value) {
                return $value !== '';
            });

            // Prüfen ob der aktuelle Wert in der Liste ist
            return in_array((string) $actual_value, $allowed_values, true);
        }
    }
}
