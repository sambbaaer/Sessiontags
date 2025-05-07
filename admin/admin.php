<?php
/**
 * SessionTagsAdmin-Klasse
 * 
 * Verwaltet die Benutzeroberfläche im WordPress-Admin-Bereich
 */
class SessionTagsAdmin {
    /**
     * Instanz der SessionManager-Klasse
     * 
     * @var SessionTagsSessionManager
     */
    private $session_manager;

    /**
     * Konstruktor der SessionTagsAdmin-Klasse
     * 
     * @param SessionTagsSessionManager $session_manager Die Instanz des SessionManagers
     */
    public function __construct($session_manager) {
        $this->session_manager = $session_manager;
        
        // Admin-Hooks registrieren
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
    }

    /**
     * Fügt das Admin-Menü hinzu
     */
    public function add_admin_menu() {
        add_options_page(
            'SessionTags Einstellungen',      // Seitentitel
            'SessionTags',                    // Menütitel
            'manage_options',                 // Berechtigungen
            'sessiontags',                    // Menü-Slug
            [$this, 'render_admin_page']      // Callback-Funktion
        );
    }

    /**
     * Registriert die Plugin-Einstellungen
     */
    public function register_settings() {
        // Parameter-Einstellungen
        register_setting(
            'sessiontags_settings',           // Option-Gruppe
            'sessiontags_parameters',         // Option-Name
            [
                'sanitize_callback' => [$this, 'sanitize_parameters'],
                'default' => ['quelle', 'kampagne', 'id']
            ]
        );
        
        // URL-Verschleierungseinstellungen
        register_setting(
            'sessiontags_settings',           // Option-Gruppe
            'sessiontags_url_encoding',       // Option-Name
            [
                'sanitize_callback' => [$this, 'sanitize_url_encoding'],
                'default' => false
            ]
        );
        
        // URL-Parameter-Kürzel
        register_setting(
            'sessiontags_settings',           // Option-Gruppe
            'sessiontags_url_shortcodes',     // Option-Name
            [
                'sanitize_callback' => [$this, 'sanitize_url_shortcodes'],
                'default' => []
            ]
        );
        
        // Geheimer Schlüssel für die Verschlüsselung
        if (!get_option('sessiontags_secret_key')) {
            update_option('sessiontags_secret_key', wp_generate_password(32, true, true));
        }
    }
    
    /**
     * Sanitisiert die URL-Encoding-Einstellung
     * 
     * @param mixed $input Die Eingabedaten
     * @return bool Die sanitisierten Daten
     */
    public function sanitize_url_encoding($input) {
        return (bool) $input;
    }
    
    /**
     * Sanitisiert die URL-Shortcode-Einstellungen
     * 
     * @param mixed $input Die Eingabedaten
     * @return array Die sanitisierten Daten
     */
    public function sanitize_url_shortcodes($input) {
        $sanitized_input = [];
        
        if (is_array($input) && !empty($input)) {
            foreach ($input as $param => $shortcode) {
                if (!empty($param) && !empty($shortcode)) {
                    $sanitized_input[sanitize_text_field($param)] = sanitize_text_field($shortcode);
                }
            }
        }
        
        return $sanitized_input;
    }

    /**
     * Lädt die Admin-Stylesheets
     * 
     * @param string $hook_suffix Der aktuelle Admin-Seiten-Hook
     */
    public function enqueue_admin_styles($hook_suffix) {
        if ('settings_page_sessiontags' === $hook_suffix) {
            wp_enqueue_style(
                'sessiontags-admin',
                SESSIONTAGS_URL . 'admin/css/sessiontags-admin.css',
                [],
                SESSIONTAGS_VERSION
            );
        }
    }

    /**
     * Sanitisiert die Parameter-Einstellungen
     * 
     * @param mixed $input Die Eingabedaten
     * @return array Die sanitisierten Daten
     */
    public function sanitize_parameters($input) {
        $sanitized_input = [];
        
        if (is_array($input) && !empty($input)) {
            foreach ($input as $key => $value) {
                if (!empty($value)) {
                    $sanitized_input[] = sanitize_text_field($value);
                }
            }
        }
        
        // Mindestens einen Parameter stellen sicher
        if (empty($sanitized_input)) {
            $sanitized_input = ['quelle'];
        }
        
        return $sanitized_input;
    }

    /**
     * Rendert die Admin-Seite
     */
    public function render_admin_page() {
        // Aktuelle Einstellungen abrufen
        $parameters = get_option('sessiontags_parameters', ['quelle', 'kampagne', 'id']);
        
        // Beispiel-URL zusammenstellen
        $example_url = $this->get_example_url($parameters);
        
        ?>
        <div class="wrap sessiontags-admin">
            <h1><?php echo esc_html__('SessionTags Einstellungen', 'sessiontags'); ?></h1>
            
            <div class="sessiontags-description">
                <p><?php echo esc_html__('Definieren Sie hier, welche URL-Parameter in der Session gespeichert und später über Shortcodes oder Elementor Dynamic Tags ausgegeben werden sollen.', 'sessiontags'); ?></p>
            </div>
            
            <form method="post" action="options.php">
                <?php settings_fields('sessiontags_settings'); ?>
                
                <div class="sessiontags-parameters">
                    <h2><?php echo esc_html__('URL-Parameter', 'sessiontags'); ?></h2>
                    <p class="description"><?php echo esc_html__('Geben Sie die Namen der URL-Parameter ein, die in der Session gespeichert werden sollen.', 'sessiontags'); ?></p>
                    
                    <table class="form-table" role="presentation">
                        <tbody id="sessiontags-parameter-rows">
                            <?php foreach ($parameters as $index => $parameter) : ?>
                                <tr>
                                    <th scope="row">
                                        <label for="sessiontags_parameter_<?php echo esc_attr($index); ?>">
                                            <?php echo esc_html__('Parameter', 'sessiontags'); ?> <?php echo esc_html($index + 1); ?>
                                        </label>
                                    </th>
                                    <td>
                                        <input type="text" 
                                               id="sessiontags_parameter_<?php echo esc_attr($index); ?>" 
                                               name="sessiontags_parameters[]" 
                                               value="<?php echo esc_attr($parameter); ?>" 
                                               class="regular-text sessiontags-parameter-input" 
                                               placeholder="<?php echo esc_attr__('z.B. quelle', 'sessiontags'); ?>"
                                        >
                                        <?php if (count($parameters) > 1) : ?>
                                        <button type="button" class="button remove-parameter" data-index="<?php echo esc_attr($index); ?>">
                                            <?php echo esc_html__('Entfernen', 'sessiontags'); ?>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <button type="button" class="button add-parameter">
                        <?php echo esc_html__('Parameter hinzufügen', 'sessiontags'); ?>
                    </button>
                </div>
                
                <div class="sessiontags-example-url">
                    <h2><?php echo esc_html__('Beispiel-URL', 'sessiontags'); ?></h2>
                    <p class="description"><?php echo esc_html__('So könnte ein Link mit den definierten Parametern aussehen:', 'sessiontags'); ?></p>
                    
                    <div class="sessiontags-example-url-display">
                        <code id="example-url"><?php echo esc_html($example_url); ?></code>
                        <button type="button" class="button copy-url" data-clipboard-target="#example-url">
                            <?php echo esc_html__('Kopieren', 'sessiontags'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="sessiontags-usage">
                    <h2><?php echo esc_html__('Verwendung', 'sessiontags'); ?></h2>
                    
                <div class="sessiontags-usage-section">
                        <h3><?php echo esc_html__('Shortcode', 'sessiontags'); ?></h3>
                        <p><?php echo esc_html__('Verwenden Sie einen der folgenden Shortcodes, um einen gespeicherten Parameter anzuzeigen:', 'sessiontags'); ?></p>
                        
                        <h4><?php echo esc_html__('Kurzer Shortcode (empfohlen):', 'sessiontags'); ?></h4>
                        <code>[st k="parameter_name" d="standardwert"]</code>
                        
                        <h4><?php echo esc_html__('Vollständiger Shortcode:', 'sessiontags'); ?></h4>
                        <code>[show_session_param key="parameter_name" default="standardwert"]</code>
                        
                        <h4><?php echo esc_html__('Beispiele:', 'sessiontags'); ?></h4>
                        <ul>
                            <?php foreach ($parameters as $parameter) : ?>
                                <li><code>[st k="<?php echo esc_html($parameter); ?>"]</code></li>
                                <li><code>[st k="<?php echo esc_html($parameter); ?>" d="Fallback-Wert"]</code></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="sessiontags-usage-section">
                        <h3><?php echo esc_html__('URL-Generierung mit Shortcode', 'sessiontags'); ?></h3>
                        <p><?php echo esc_html__('Mit dem folgenden Shortcode können Sie automatisch URLs erstellen, die Ihre Parameter mit Kürzeln und Verschleierung enthalten:', 'sessiontags'); ?></p>
                        
                        <code>[st_url url="https://example.com/seite/" params="quelle=newsletter,kampagne=winter2025"]Klicken Sie hier[/st_url]</code>
                        
                        <h4><?php echo esc_html__('Parameter:', 'sessiontags'); ?></h4>
                        <ul>
                            <li><code>url</code>: <?php echo esc_html__('Die Ziel-URL (Optional, Standard ist die aktuelle Website)', 'sessiontags'); ?></li>
                            <li><code>params</code>: <?php echo esc_html__('Komma-getrennte Liste von Parametern im Format "parameter=wert"', 'sessiontags'); ?></li>
                            <li><code>class</code>: <?php echo esc_html__('CSS-Klasse für den Link (Optional)', 'sessiontags'); ?></li>
                            <li><code>title</code>: <?php echo esc_html__('Tooltip-Text für den Link (Optional)', 'sessiontags'); ?></li>
                        </ul>
                        
                        <h4><?php echo esc_html__('Beispiel:', 'sessiontags'); ?></h4>
                        <code>[st_url params="quelle=newsletter,kampagne=winter2025" class="button" title="Winter-Angebote"]Zu den Angeboten[/st_url]</code>
                    </div>
                    
                    <div class="sessiontags-usage-section">
                        <h3><?php echo esc_html__('Elementor Dynamic Tag', 'sessiontags'); ?></h3>
                        <p><?php echo esc_html__('In Elementor können Sie den "SessionTags" Dynamic Tag verwenden:', 'sessiontags'); ?></p>
                        <ol>
                            <li><?php echo esc_html__('Bearbeiten Sie ein Text-Widget in Elementor', 'sessiontags'); ?></li>
                            <li><?php echo esc_html__('Klicken Sie auf das Dynamic Tags-Symbol', 'sessiontags'); ?></li>
                            <li><?php echo esc_html__('Wählen Sie "SessionTags" aus der Liste', 'sessiontags'); ?></li>
                            <li><?php echo esc_html__('Wählen Sie den gewünschten Parameter aus dem Dropdown-Menü', 'sessiontags'); ?></li>
                        </ol>
                    </div>
                </div>
                
                <?php submit_button(__('Einstellungen speichern', 'sessiontags')); ?>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Parameter hinzufügen
            $('.add-parameter').on('click', function() {
                var index = $('#sessiontags-parameter-rows tr').length;
                var newRow = '<tr>' +
                    '<th scope="row">' +
                    '<label for="sessiontags_parameter_' + index + '">' +
                    '<?php echo esc_js(__('Parameter', 'sessiontags')); ?> ' + (index + 1) +
                    '</label>' +
                    '</th>' +
                    '<td>' +
                    '<input type="text" ' +
                    'id="sessiontags_parameter_' + index + '" ' +
                    'name="sessiontags_parameters[]" ' +
                    'value="" ' +
                    'class="regular-text sessiontags-parameter-input" ' +
                    'placeholder="<?php echo esc_js(__('z.B. quelle', 'sessiontags')); ?>">' +
                    '<button type="button" class="button remove-parameter" data-index="' + index + '">' +
                    '<?php echo esc_js(__('Entfernen', 'sessiontags')); ?>' +
                    '</button>' +
                    '</td>' +
                    '</tr>';
                
                $('#sessiontags-parameter-rows').append(newRow);
                updateExampleUrl();
            });
            
            // Parameter entfernen
            $(document).on('click', '.remove-parameter', function() {
                $(this).closest('tr').remove();
                updateExampleUrl();
                
                // Parameter-Indices aktualisieren
                $('#sessiontags-parameter-rows tr').each(function(index) {
                    $(this).find('label').attr('for', 'sessiontags_parameter_' + index).text('<?php echo esc_js(__('Parameter', 'sessiontags')); ?> ' + (index + 1));
                    $(this).find('input').attr('id', 'sessiontags_parameter_' + index);
                    $(this).find('button.remove-parameter').attr('data-index', index);
                });
            });
            
            // Beispiel-URL aktualisieren, wenn ein Parameter geändert wird
            $(document).on('input', '.sessiontags-parameter-input', function() {
                updateExampleUrl();
            });
            
            // Beispiel-URL aktualisieren
            function updateExampleUrl() {
                var baseUrl = '<?php echo esc_js(home_url('/')); ?>';
                var url = baseUrl + '?';
                var params = [];
                
                $('.sessiontags-parameter-input').each(function() {
                    var paramName = $(this).val();
                    if (paramName) {
                        params.push(paramName + '=beispielwert');
                    }
                });
                
                url += params.join('&');
                $('#example-url').text(url);
            }
            
            // URL kopieren
            $('.copy-url').on('click', function() {
                var tempInput = $('<input>');
                $('body').append(tempInput);
                tempInput.val($('#example-url').text()).select();
                document.execCommand('copy');
                tempInput.remove();
                
                // Benachrichtigung anzeigen
                var button = $(this);
                var originalText = button.text();
                button.text('<?php echo esc_js(__('Kopiert!', 'sessiontags')); ?>');
                setTimeout(function() {
                    button.text(originalText);
                }, 2000);
            });
        });
        </script>
        <?php
    }

    /**
     * Erstellt eine Beispiel-URL mit den gegebenen Parametern
     * 
     * @param array $parameters Die Parameter
     * @param array $shortcodes Die Kürzel für die Parameter
     * @return string Die Beispiel-URL
     */
    private function get_example_url($parameters, $shortcodes = []) {
        $base_url = home_url('/');
        $params = [];
        $use_encoding = get_option('sessiontags_url_encoding', false);
        
        foreach ($parameters as $param) {
            if (!empty($param)) {
                // Kürzel verwenden, falls vorhanden
                $param_name = isset($shortcodes[$param]) && !empty($shortcodes[$param]) ? $shortcodes[$param] : $param;
                
                // Parameterwert verschleiern, falls aktiviert
                $param_value = 'beispielwert';
                if ($use_encoding) {
                    $param_value = base64_encode($param_value);
                }
                
                $params[] = $param_name . '=' . $param_value;
            }
        }
        
        if (!empty($params)) {
            return $base_url . '?' . implode('&', $params);
        }
        
        return $base_url;
    }
}
