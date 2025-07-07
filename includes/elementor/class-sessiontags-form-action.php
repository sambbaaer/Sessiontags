<?php

/**
 * SessionTags Elementor Form Action
 * * Fügt eine "Action After Submit" zu Elementor Pro Formularen hinzu,
 * um Formularfeld-Werte in SessionTags zu speichern.
 */

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

// FIX: Die Klasse nur definieren, wenn die Basisklasse von Elementor Pro existiert.
// Dies verhindert fatale Fehler, wenn Elementor Pro nicht aktiv ist.
if (class_exists('ElementorPro\Modules\Forms\Classes\Action_Base')) {

    class SessionTags_Form_Action extends \ElementorPro\Modules\Forms\Classes\Action_Base
    {

        /**
         * Gibt den Namen der Aktion zurück.
         *
         * @return string
         */
        public function get_name()
        {
            return 'sessiontags_save';
        }

        /**
         * Gibt das Label der Aktion zurück.
         *
         * @return string
         */
        public function get_label()
        {
            return __('In SessionTag speichern', 'sessiontags');
        }

        /**
         * Registriert die Einstellungs-Sektion für die Aktion im Form-Widget.
         *
         * @param \Elementor\Widget_Base $widget
         */
        public function register_settings_section($widget)
        {
            $widget->start_controls_section(
                'section_sessiontags_save',
                [
                    'label' => __('SessionTags speichern', 'sessiontags'),
                    'condition' => [
                        'submit_actions' => $this->get_name(),
                    ],
                ]
            );

            // FIX: The correct class for the repeater is \Elementor\Repeater
            $repeater = new \Elementor\Repeater();

            // Holt die im Formular definierten Felder, um sie als Optionen anzubieten
            $form_fields = $widget->get_settings('form_fields');
            $field_options = [];
            if (!empty($form_fields)) {
                foreach ($form_fields as $field) {
                    if (!empty($field['custom_id'])) {
                        $field_options[$field['custom_id']] = $field['field_label'] ?: $field['custom_id'];
                    }
                }
            }
            if (empty($field_options)) {
                $field_options[''] = __('Bitte erst Formularfelder erstellen', 'sessiontags');
            }

            $repeater->add_control(
                'form_field_id',
                [
                    'label' => __('Formularfeld', 'sessiontags'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'options' => $field_options,
                    'default' => !empty($field_options) ? key($field_options) : '',
                    'label_block' => true,
                ]
            );

            // Holt die konfigurierten SessionTags-Parameter
            if (!class_exists('SessionTagsSessionManager')) {
                require_once SESSIONTAGS_PATH . 'includes/class-sessiontags-session-manager.php';
            }
            $session_manager = new SessionTagsSessionManager();
            $tracked_params = $session_manager->get_tracked_parameters();
            $param_options = [];
            if (!empty($tracked_params)) {
                foreach ($tracked_params as $param) {
                    $param_options[$param['name']] = $param['name'];
                }
            }

            $repeater->add_control(
                'session_tag_key',
                [
                    'label' => __('Speichern als SessionTag', 'sessiontags'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'options' => $param_options,
                    'default' => !empty($param_options) ? key($param_options) : '',
                    'description' => __('Wähle den Session-Parameter, in dem der Wert gespeichert werden soll.', 'sessiontags'),
                    'label_block' => true,
                ]
            );

            $widget->add_control(
                'sessiontags_mappings',
                [
                    'label' => __('Feldzuordnungen', 'sessiontags'),
                    'type' => \Elementor\Controls_Manager::REPEATER,
                    'fields' => $repeater->get_controls(),
                    'title_field' => '{{{ form_field_id }}} &rarr; {{{ session_tag_key }}}',
                    'description' => __('Ordne Formularfelder den SessionTags zu, die nach dem Absenden gespeichert werden sollen.', 'sessiontags'),
                ]
            );

            $widget->end_controls_section();
        }

        /**
         * Definiert, was die Aktion beim Absenden des Formulars tun soll.
         *
         * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record
         * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
         */
        public function run($record, $ajax_handler)
        {
            $settings = $record->get('form_settings');
            $mappings = $settings['sessiontags_mappings'] ?? [];

            if (empty($mappings)) {
                return;
            }

            // Session starten, falls noch nicht geschehen
            if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                session_start();
            }

            // Session-Array initialisieren
            if (!isset($_SESSION['sessiontags_params']) || !is_array($_SESSION['sessiontags_params'])) {
                $_SESSION['sessiontags_params'] = [];
            }

            $submitted_fields = $record->get('fields');

            foreach ($mappings as $mapping) {
                $form_field_id = $mapping['form_field_id'];
                $session_tag_key = $mapping['session_tag_key'];

                if (empty($form_field_id) || empty($session_tag_key)) {
                    continue;
                }

                if (isset($submitted_fields[$form_field_id])) {
                    $value = $submitted_fields[$form_field_id]['value'];

                    // Wert sanitisieren und in die Session schreiben
                    $_SESSION['sessiontags_params'][$session_tag_key] = sanitize_text_field($value);
                }
            }
        }

        /**
         * Definiert, was die Aktion bei einem Export tun soll (hier nicht benötigt).
         *
         * @param array $data
         */
        public function on_export($data)
        {
            // Nichts zu tun
        }
    }
}
