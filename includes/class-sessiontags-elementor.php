<?php

/**
 * SessionTagsElementor-Klasse
 * Integriert das Plugin in Elementor mit Dynamic Tags, Display Conditions und Form Actions.
 */
class SessionTagsElementor
{
    /**
     * Instanz der SessionManager-Klasse
     * @var SessionTagsSessionManager
     */
    private $session_manager;

    /**
     * Konstruktor der SessionTagsElementor-Klasse
     * @param SessionTagsSessionManager $session_manager Die Instanz des SessionManagers
     */
    public function __construct($session_manager)
    {
        $this->session_manager = $session_manager;

        // Initialisierung an den 'init' Hook hängen mit später Priorität
        add_action('init', [$this, 'init_elementor_features'], 999);
    }

    /**
     * Initialisiert alle Elementor-spezifischen Funktionen.
     */
    public function init_elementor_features()
    {
        // Prüfen ob Elementor geladen ist
        if (!did_action('elementor/loaded')) {
            return;
        }

        // Dynamic Tags registrieren (funktioniert mit Elementor Free und Pro)
        add_action('elementor/dynamic_tags/register', [$this, 'register_dynamic_tags']);

        // Elementor Pro Features registrieren
        $this->init_elementor_pro_features();
    }

    /**
     * Initialisiert Elementor Pro spezifische Features
     */
    private function init_elementor_pro_features()
    {
        // Mehrere Methoden zur Erkennung von Elementor Pro
        $is_elementor_pro = false;

        // Methode 1: Prüfen ob die Klasse existiert
        if (class_exists('\ElementorPro\Plugin')) {
            $is_elementor_pro = true;
        }

        // Methode 2: Prüfen ob die Konstante existiert
        if (!$is_elementor_pro && defined('ELEMENTOR_PRO_VERSION')) {
            $is_elementor_pro = true;
        }

        // Methode 3: Prüfen ob elementor-pro Plugin aktiv ist
        if (!$is_elementor_pro && is_plugin_active('elementor-pro/elementor-pro.php')) {
            $is_elementor_pro = true;
        }

        if ($is_elementor_pro) {
            // Display Conditions mit später Priorität registrieren
            add_action('elementor_pro/display_conditions/register', [$this, 'register_display_conditions'], 100);

            // Form Actions registrieren
            add_action('elementor_pro/forms/actions/register', [$this, 'register_form_action'], 100);

            // Alternative Hooks für ältere Versionen
            add_action('elementor/forms/actions/register', [$this, 'register_form_action'], 100);

            // Debug-Logging (kann später entfernt werden)
            add_action('admin_init', function () {
                if (current_user_can('manage_options') && isset($_GET['sessiontags_debug'])) {
                    error_log('SessionTags: Elementor Pro detected and hooks registered');
                }
            });
        }
    }

    /**
     * Registriert den Dynamic Tag bei Elementor
     * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager Elementor Dynamic Tags Manager
     */
    public function register_dynamic_tags($dynamic_tags_manager)
    {
        // Die Dynamic Tag-Klasse laden
        require_once SESSIONTAGS_PATH . 'includes/elementor/class-sessiontags-dynamic-tag.php';

        // Gruppe für den Dynamic Tag registrieren
        $dynamic_tags_manager->register_group(
            'sessiontags',
            [
                'title' => 'SessionTags'
            ]
        );

        // Dynamic Tag registrieren
        $dynamic_tags_manager->register(new SessionTags_Dynamic_Tag());
    }

    /**
     * Registriert die Display Conditions bei Elementor Pro
     *
     * @param \ElementorPro\Modules\DisplayConditions\Module $conditions_manager
     */
    public function register_display_conditions($conditions_manager)
    {
        // Sicherstellen, dass die Klassen verfügbar sind
        if (!class_exists('SessionTags_Display_Condition_Exists')) {
            require_once SESSIONTAGS_PATH . 'includes/elementor/class-sessiontags-display-conditions.php';
        }

        try {
            // Eigene Gruppe für die Conditions registrieren
            if (method_exists($conditions_manager, 'register_group')) {
                $conditions_manager->register_group(
                    'sessiontags',
                    [
                        'label' => 'SessionTags',
                    ]
                );
            }

            // Die einzelnen Conditions registrieren
            if (method_exists($conditions_manager, 'register_condition')) {
                $conditions_manager->register_condition(new SessionTags_Display_Condition_Exists());
                $conditions_manager->register_condition(new SessionTags_Display_Condition_Value());
                $conditions_manager->register_condition(new SessionTags_Display_Condition_Is_One_Of());

                // Debug-Logging
                if (current_user_can('manage_options') && isset($_GET['sessiontags_debug'])) {
                    error_log('SessionTags: Display Conditions registered successfully');
                }
            }
        } catch (Exception $e) {
            // Fehler loggen aber nicht die Ausführung stoppen
            error_log('SessionTags Display Conditions Error: ' . $e->getMessage());
        }
    }

    /**
     * Registriert die Formular-Aktion bei Elementor Pro
     *
     * @param object $forms_module Das Forms Module von Elementor
     */
    public function register_form_action($forms_module)
    {
        // Sicherstellen, dass die Klasse verfügbar ist
        if (!class_exists('SessionTags_Form_Action')) {
            require_once SESSIONTAGS_PATH . 'includes/elementor/class-sessiontags-form-action.php';
        }

        try {
            $action = new SessionTags_Form_Action();

            // Verschiedene Methoden zur Registrierung versuchen
            if (method_exists($forms_module, 'add_action')) {
                // Neuere Elementor Pro Versionen (>= 3.5)
                $forms_module->add_action($action->get_name(), $action);

                // Debug-Logging
                if (current_user_can('manage_options') && isset($_GET['sessiontags_debug'])) {
                    error_log('SessionTags: Form Action registered successfully via add_action');
                }
            } elseif (method_exists($forms_module, 'register')) {
                // Ältere Elementor Pro Versionen
                $forms_module->register($action);

                // Debug-Logging
                if (current_user_can('manage_options') && isset($_GET['sessiontags_debug'])) {
                    error_log('SessionTags: Form Action registered successfully via register');
                }
            } elseif (method_exists($forms_module, 'add_form_action')) {
                // Alternative Methode für einige Versionen
                $forms_module->add_form_action($action->get_name(), $action);

                // Debug-Logging
                if (current_user_can('manage_options') && isset($_GET['sessiontags_debug'])) {
                    error_log('SessionTags: Form Action registered successfully via add_form_action');
                }
            }
        } catch (Exception $e) {
            // Fehler loggen aber nicht die Ausführung stoppen
            error_log('SessionTags Form Action Error: ' . $e->getMessage());
        }
    }
}
