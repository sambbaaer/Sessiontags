<?php

/**
 * SessionTagsElementor-Klasse
 * * Integriert das Plugin in Elementor mit Dynamic Tags, Display Conditions und Form Actions.
 */
class SessionTagsElementor
{
    /**
     * Instanz der SessionManager-Klasse
     * * @var SessionTagsSessionManager
     */
    private $session_manager;

    /**
     * Konstruktor der SessionTagsElementor-Klasse
     * * @param SessionTagsSessionManager $session_manager Die Instanz des SessionManagers
     */
    public function __construct($session_manager)
    {
        $this->session_manager = $session_manager;

        // FIX: Hängt die Initialisierung an den 'elementor/init' Hook.
        // Dies stellt sicher, dass Elementor vollständig geladen ist, bevor wir versuchen,
        // unsere eigenen Funktionen zu registrieren.
        add_action('elementor/init', [$this, 'init_elementor_features']);
    }

    /**
     * Initialisiert alle Elementor-spezifischen Funktionen.
     * Wird über den 'elementor/init' Hook aufgerufen.
     */
    public function init_elementor_features()
    {
        // Registriert die Dynamic Tags.
        add_action('elementor/dynamic_tags/register', [$this, 'register_dynamic_tags']);

        // IMPROVEMENT: Prüft, ob die Hauptklasse von Elementor Pro existiert.
        // Das ist zuverlässiger als is_plugin_active().
        if (class_exists('\ElementorPro\Plugin')) {
            // Registriert die Display Conditions.
            add_action('elementor_pro/display_conditions/register', [$this, 'register_display_conditions']);

            // Registriert die Formular-Aktion.
            add_action('elementor/forms/actions/register', [$this, 'register_form_action']);
        }
    }

    /**
     * Registriert den Dynamic Tag bei Elementor
     * * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager Elementor Dynamic Tags Manager
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
        require_once SESSIONTAGS_PATH . 'includes/elementor/class-sessiontags-display-conditions.php';

        // Eigene Gruppe für die Conditions registrieren
        $conditions_manager->register_group(
            'sessiontags',
            [
                'label' => 'SessionTags',
            ]
        );

        // Die einzelnen Conditions registrieren
        $conditions_manager->register_condition(new SessionTags_Display_Condition_Exists());
        $conditions_manager->register_condition(new SessionTags_Display_Condition_Value());
        $conditions_manager->register_condition(new SessionTags_Display_Condition_Is_One_Of());
    }

    /**
     * Registriert die Formular-Aktion bei Elementor Pro
     *
     * @param \ElementorPro\Modules\Forms\Module $forms_module
     */
    public function register_form_action($forms_module)
    {
        require_once SESSIONTAGS_PATH . 'includes/elementor/class-sessiontags-form-action.php';
        $forms_module->add_action(new SessionTags_Form_Action());
    }
}
