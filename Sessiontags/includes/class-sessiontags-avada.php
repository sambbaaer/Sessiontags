<?php

/**
 * SessionTagsAvada-Klasse
 * 
 * Integriert das Plugin in Avada Fusion Builder
 */
class SessionTagsAvada
{
    /**
     * Instanz der SessionManager-Klasse
     * 
     * @var SessionTagsSessionManager
     */
    private $session_manager;

    /**
     * Konstruktor der SessionTagsAvada-Klasse
     * 
     * @param SessionTagsSessionManager $session_manager Die Instanz des SessionManagers
     */
    public function __construct($session_manager)
    {
        $this->session_manager = $session_manager;

        // Nur weitermachen, wenn Avada/Fusion Builder aktiviert ist
        if (!defined('FUSION_BUILDER_VERSION')) {
            return;
        }

        // Avada/Fusion Builder-Hooks registrieren
        add_action('init', [$this, 'register_fusion_element'], 12);
        add_action('fusion_builder_before_init', [$this, 'register_fusion_element']);
    }

    /**
     * Registriert das benutzerdefinierte Element im Fusion Builder
     */
    public function register_fusion_element()
    {
        // Parameter-Optionen für das Dropdown erstellen
        $parameters = $this->session_manager->get_tracked_parameters();
        $param_options = [];

        foreach ($parameters as $parameter) {
            $param_options[$parameter['name']] = $parameter['name'];
        }

        // Element registrieren
        if (function_exists('fusion_builder_map')) {
            fusion_builder_map([
                'name'        => esc_attr__('SessionTags Parameter', 'sessiontags'),
                'shortcode'   => 'fusion_sessiontag',
                'icon'        => 'fusiona-tag',
                'category'    => 'Content',
                'description' => esc_attr__('Zeigt einen gespeicherten SessionTags-Parameter an.', 'sessiontags'),
                'params'      => [
                    [
                        'type'        => 'select',
                        'heading'     => esc_attr__('Parameter', 'sessiontags'),
                        'description' => esc_attr__('Wählen Sie den anzuzeigenden Parameter.', 'sessiontags'),
                        'param_name'  => 'parameter',
                        'value'       => $param_options,
                        'default'     => key($param_options),
                    ],
                    [
                        'type'        => 'textfield',
                        'heading'     => esc_attr__('Individueller Fallback', 'sessiontags'),
                        'description' => esc_attr__('Wird angezeigt, wenn der Parameter nicht in der Session existiert. Überschreibt den Standard-Fallback aus den Einstellungen.', 'sessiontags'),
                        'param_name'  => 'default',
                        'value'       => '',
                    ],
                    [
                        'type'        => 'select',
                        'heading'     => esc_attr__('HTML-Ausgabe', 'sessiontags'),
                        'description' => esc_attr__('Wählen Sie das HTML-Element für die Ausgabe.', 'sessiontags'),
                        'param_name'  => 'element',
                        'value'       => [
                            'none'   => esc_attr__('Kein Element (nur Text)', 'sessiontags'),
                            'span'   => esc_attr__('Span', 'sessiontags'),
                            'div'    => esc_attr__('Div', 'sessiontags'),
                            'p'      => esc_attr__('Paragraph', 'sessiontags'),
                            'h1'     => esc_attr__('H1', 'sessiontags'),
                            'h2'     => esc_attr__('H2', 'sessiontags'),
                            'h3'     => esc_attr__('H3', 'sessiontags'),
                            'h4'     => esc_attr__('H4', 'sessiontags'),
                            'h5'     => esc_attr__('H5', 'sessiontags'),
                            'h6'     => esc_attr__('H6', 'sessiontags'),
                        ],
                        'default'     => 'none',
                    ],
                    [
                        'type'        => 'textfield',
                        'heading'     => esc_attr__('CSS-Klasse', 'sessiontags'),
                        'description' => esc_attr__('Fügt eine benutzerdefinierte CSS-Klasse zum Wrapper-Element hinzu.', 'sessiontags'),
                        'param_name'  => 'class',
                        'value'       => '',
                        'group'       => esc_attr__('Design', 'sessiontags'),
                    ],
                    [
                        'type'        => 'textfield',
                        'heading'     => esc_attr__('CSS-ID', 'sessiontags'),
                        'description' => esc_attr__('Fügt eine benutzerdefinierte CSS-ID zum Wrapper-Element hinzu.', 'sessiontags'),
                        'param_name'  => 'id',
                        'value'       => '',
                        'group'       => esc_attr__('Design', 'sessiontags'),
                    ],
                ],
                'callback'    => [$this, 'render_fusion_element'],
            ]);
        }
    }

    /**
     * Rendert das Fusion Builder Element
     * 
     * @param array $args Die Shortcode-Attribute
     * @param string $content Der Inhalt des Shortcodes
     * @return string Die HTML-Ausgabe des Elements
     */
    public function render_fusion_element($args, $content = '')
    {
        $defaults = [
            'parameter' => '',
            'default'   => '',
            'element'   => 'none',
            'class'     => '',
            'id'        => '',
        ];

        $args = shortcode_atts($defaults, $args, 'fusion_sessiontag');

        // Wenn kein Parameter angegeben wurde, leeren String zurückgeben
        if (empty($args['parameter'])) {
            return '';
        }

        // Parameter aus Session holen
        $value = $this->session_manager->get_param($args['parameter'], $args['default']);
        $value = esc_html($value);

        // HTML-Element für die Ausgabe bestimmen
        if ($args['element'] === 'none') {
            return $value;
        } else {
            $class_attr = !empty($args['class']) ? ' class="' . esc_attr($args['class']) . '"' : '';
            $id_attr = !empty($args['id']) ? ' id="' . esc_attr($args['id']) . '"' : '';

            return '<' . $args['element'] . $class_attr . $id_attr . '>' . $value . '</' . $args['element'] . '>';
        }
    }
}
