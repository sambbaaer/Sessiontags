<?php

/**
 * SessionTags Elementor Pro Display Condition
 * Fügt eine Display Condition zu Elementor Pro hinzu, um Elemente basierend auf SessionTags-Parametern anzuzeigen
 */

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

// Prüfen, ob die Basisklasse von Elementor Pro existiert, um fatale Fehler zu vermeiden.
if (class_exists('\ElementorPro\Modules\DisplayConditions\Conditions\Base\Condition_Base')) {

    /**
     * SessionTags_Display_Condition_Base
     * Eine abstrakte Basisklasse, um die Initialisierung des Session Managers zu zentralisieren
     * und eine eigene Gruppe für die Bedingungen zu definieren.
     */
    abstract class SessionTags_Display_Condition_Base extends \ElementorPro\Modules\DisplayConditions\Conditions\Base\Condition_Base
    {

        /**
         * Hält eine Singleton-Instanz des Session Managers.
         * @var SessionTagsSessionManager|null
         */
        private static $session_manager_instance = null;

        /**
         * Gibt eine initialisierte Instanz des Session Managers zurück.
         * Stellt sicher, dass die Session-Daten für die Prüfung verfügbar sind.
         *
         * @return SessionTagsSessionManager
         */
        protected function get_session_manager()
        {
            if (self::$session_manager_instance === null) {
                // Stellt sicher, dass die Session-Manager-Klasse geladen ist.
                if (!class_exists('SessionTagsSessionManager')) {
                    require_once SESSIONTAGS_PATH . 'includes/class-sessiontags-session-manager.php';
                }
                self::$session_manager_instance = new SessionTagsSessionManager();
                // WICHTIG: Die init() Methode muss aufgerufen werden, um sicherzustellen,
                // dass die Session gestartet und die Parameter verarbeitet wurden.
                self::$session_manager_instance->init();
            }
            return self::$session_manager_instance;
        }

        /**
         * Gibt die Gruppe der Condition zurück.
         * Erstellt eine eigene "SessionTags"-Gruppe für eine bessere Übersichtlichkeit.
         * @return string
         */
        public function get_group()
        {
            return 'sessiontags';
        }

        /**
         * Implementiert die abstrakte Methode aus der Elternklasse.
         * Diese Methode ist für die Kompatibilität mit Elementor Pro erforderlich.
         * In unserem Fall definieren wir die Steuerelemente manuell über register_controls(),
         * daher kann diese Methode ein leeres Array zurückgeben.
         *
         * @return array
         */
        public function get_options()
        {
            return [];
        }
    }


    /**
     * Condition: SessionTags Parameter existiert
     * Prüft, ob ein bestimmter oder irgendein Parameter in der Session existiert.
     */
    class SessionTags_Display_Condition_Exists extends SessionTags_Display_Condition_Base
    {

        /**
         * Gibt den eindeutigen Namen der Condition zurück.
         * @return string
         */
        public function get_name()
        {
            return 'sessiontags_param_exists';
        }

        /**
         * Gibt den Label der Condition zurück, der im Editor angezeigt wird.
         * @return string
         */
        public function get_label()
        {
            return __('SessionTags: Parameter existiert', 'sessiontags');
        }

        /**
         * Definiert die Steuerelemente für die Bedingung im Elementor-Editor.
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
                    if (!empty($param['name'])) {
                        $options[$param['name']] = sprintf(__('Parameter "%s"', 'sessiontags'), $param['name']);
                    }
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
         * @param array $args Die in den Controls gesetzten Werte.
         * @return bool
         */
        public function check($args): bool
        {
            $session_manager = $this->get_session_manager();
            $param_key = $args['param_key'] ?? '_any';

            // Wenn "beliebiger Parameter" ausgewählt wurde
            if ($param_key === '_any') {
                // Sicherstellen, dass das Session-Array existiert.
                $session_key = $this->get_session_key();
                if (!isset($_SESSION[$session_key]) || !is_array($_SESSION[$session_key])) {
                    return false;
                }
                return !empty($_SESSION[$session_key]);
            }

            // Spezifischen Parameter prüfen
            $value = $session_manager->get_param($param_key, null);

            // Prüfen ob der Parameter vorhanden ist (nicht null und nicht leer)
            return ($value !== null && $value !== '');
        }

        /**
         * Hilfsmethode, um den Session-Schlüssel zu bekommen (da er private ist).
         * Reflektiert die private Eigenschaft aus dem SessionManager.
         * @return string
         */
        private function get_session_key()
        {
            try {
                $reflection = new ReflectionProperty('SessionTagsSessionManager', 'session_key');
                $reflection->setAccessible(true);
                return $reflection->getValue($this->get_session_manager());
            } catch (ReflectionException $e) {
                // Fallback, falls die Eigenschaft nicht existiert oder umbenannt wird.
                return 'sessiontags_params';
            }
        }
    }

    /**
     * Condition: SessionTags Parameter hat Wert
     * Prüft, ob ein Parameter einen bestimmten Wert hat.
     */
    class SessionTags_Display_Condition_Value extends SessionTags_Display_Condition_Base
    {

        /**
         * Gibt den eindeutigen Namen der Condition zurück.
         * @return string
         */
        public function get_name()
        {
            return 'sessiontags_param_value';
        }

        /**
         * Gibt den Label der Condition zurück.
         * @return string
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
                    if (!empty($param['name'])) {
                        $options[$param['name']] = $param['name'];
                    }
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
         * @param array $args
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

            // Werte vergleichen (Groß-/Kleinschreibung wird nicht beachtet)
            return strtolower((string) $actual_value) === strtolower((string) $expected_value);
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
         * @return string
         */
        public function get_name()
        {
            return 'sessiontags_param_is_one_of';
        }

        /**
         * Gibt den Label der Condition zurück.
         * @return string
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
                    if (!empty($param['name'])) {
                        $options[$param['name']] = $param['name'];
                    }
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
         * @param array $args
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

            // Werte-String in Array umwandeln, trimmen und in Kleinbuchstaben konvertieren
            $allowed_values = array_map('trim', explode("\n", $values_string));
            $allowed_values = array_map('strtolower', $allowed_values);
            $allowed_values = array_filter($allowed_values, function ($value) {
                return $value !== '';
            });

            // Prüfen ob der aktuelle Wert (in Kleinbuchstaben) in der Liste ist
            return in_array(strtolower((string) $actual_value), $allowed_values, true);
        }
    }
}
