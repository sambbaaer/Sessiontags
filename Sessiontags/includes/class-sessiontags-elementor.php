<?php

/**
 * SessionTagsElementor-Klasse
 * 
 * Integriert das Plugin in Elementor mit Dynamic Tags
 */
class SessionTagsElementor
{
    /**
     * Instanz der SessionManager-Klasse
     * 
     * @var SessionTagsSessionManager
     */
    private $session_manager;

    /**
     * Konstruktor der SessionTagsElementor-Klasse
     * 
     * @param SessionTagsSessionManager $session_manager Die Instanz des SessionManagers
     */
    public function __construct($session_manager)
    {
        $this->session_manager = $session_manager;

        // Nur weitermachen, wenn Elementor aktiviert ist
        if (!did_action('elementor/loaded')) {
            return;
        }

        // Elementor-Hooks registrieren
        add_action('elementor/dynamic_tags/register_tags', [$this, 'register_dynamic_tags']);
    }

    /**
     * Registriert den Dynamic Tag bei Elementor
     * 
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
        $dynamic_tags_manager->register_tag('SessionTags_Dynamic_Tag');
    }
}
