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

        // Initialisierung an den 'elementor/init' Hook hängen.
        add_action('elementor/init', [$this, 'init_elementor_features']);
    }

    /**
     * Initialisiert alle Elementor-spezifischen Funktionen.
     */
    public function init_elementor_features()
    {
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
        $is_elementor_pro = false;

        if (class_exists('\ElementorPro\Plugin')) {
            $is_elementor_pro = true;
        } elseif (defined('ELEMENTOR_PRO_VERSION')) {
            $is_elementor_pro = true;
        } elseif (function_exists('is_plugin_active') && is_plugin_active('elementor-pro/elementor-pro.php')) {
            $is_elementor_pro = true;
        }

        if ($is_elementor_pro) {
            // FIX: Die Pro-spezifischen Klassen nur laden, wenn Elementor Pro aktiv ist.
            require_once SESSIONTAGS_PATH . 'includes/elementor/class-sessiontags-display-conditions.php';
            require_once SESSIONTAGS_PATH . 'includes/elementor/class-sessiontags-form-action.php';

            // Display Conditions registrieren
            add_action('elementor_pro/display_conditions/register', [$this, 'register_display_conditions'], 100);

            // Form Actions registrieren
            add_action('elementor_pro/forms/actions/register', [$this, 'register_form_action'], 100);
        }
    }

    /**
     * Registriert den Dynamic Tag bei Elementor
     * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager Elementor Dynamic Tags Manager
     */
    public function register_dynamic_tags($dynamic_tags_manager)
    {
        require_once SESSIONTAGS_PATH . 'includes/elementor/class-sessiontags-dynamic-tag.php';

        $dynamic_tags_manager->register_group(
            'sessiontags',
            [
                'title' => 'SessionTags'
            ]
        );

        $dynamic_tags_manager->register(new SessionTags_Dynamic_Tag());
    }

    /**
     * Registriert die Display Conditions bei Elementor Pro
     * @param \ElementorPro\Modules\DisplayConditions\Module $conditions_manager
     */
    public function register_display_conditions($conditions_manager)
    {
        // Die Klassen sind bereits durch die Prüfung in init_elementor_pro_features geladen.
        // Die `class_exists` Prüfung hier ist eine zusätzliche Sicherheit.
        if (class_exists('SessionTags_Display_Condition_Exists')) {
            try {
                if (method_exists($conditions_manager, 'register_group')) {
                    $conditions_manager->register_group(
                        'sessiontags',
                        [
                            'label' => 'SessionTags',
                        ]
                    );
                }

                if (method_exists($conditions_manager, 'register_condition')) {
                    $conditions_manager->register_condition(new SessionTags_Display_Condition_Exists());
                    $conditions_manager->register_condition(new SessionTags_Display_Condition_Value());
                    $conditions_manager->register_condition(new SessionTags_Display_Condition_Is_One_Of());
                }
            } catch (Exception $e) {
                error_log('SessionTags Display Conditions Error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Registriert die Formular-Aktion bei Elementor Pro
     * @param object $forms_module Das Forms Module von Elementor
     */
    public function register_form_action($forms_module)
    {
        // Die Klasse ist bereits durch die Prüfung in init_elementor_pro_features geladen.
        if (class_exists('SessionTags_Form_Action')) {
            try {
                $action = new SessionTags_Form_Action();

                if (method_exists($forms_module, 'add_action')) {
                    $forms_module->add_action($action->get_name(), $action);
                } elseif (method_exists($forms_module, 'register')) {
                    $forms_module->register($action);
                }
            } catch (Exception $e) {
                error_log('SessionTags Form Action Error: ' . $e->getMessage());
            }
        }
    }
}
