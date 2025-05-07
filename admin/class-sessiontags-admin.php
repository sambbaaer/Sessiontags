<?php

/**
 * SessionTagsAdmin-Klasse
 * 
 * Verwaltet die Benutzeroberfläche im WordPress-Admin-Bereich
 */
class SessionTagsAdmin
{
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
    public function __construct($session_manager)
    {
        $this->session_manager = $session_manager;

        // Admin-Hooks registrieren
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    /**
     * Fügt das Admin-Menü hinzu
     */
    public function add_admin_menu()
    {
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
    public function register_settings()
    {
        // Parameter-Einstellungen (jetzt erweitert mit Kurzform und Fallback)
        register_setting(
            'sessiontags_settings',           // Option-Gruppe
            'sessiontags_parameters',         // Option-Name
            [
                'sanitize_callback' => [$this, 'sanitize_parameters'],
                'default' => [
                    ['name' => 'quelle', 'shortcode' => 'q', 'fallback' => '']
                ]
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

        // Geheimer Schlüssel für die Verschlüsselung
        if (!get_option('sessiontags_secret_key')) {
            update_option('sessiontags_secret_key', wp_generate_password(32, true, true));
        }
    }

    /**
     * Lädt die Admin-Stylesheets
     * 
     * @param string $hook_suffix Der aktuelle Admin-Seiten-Hook
     */
    public function enqueue_admin_styles($hook_suffix)
    {
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
     * Lädt die Admin-Scripts
     * 
     * @param string $hook_suffix Der aktuelle Admin-Seiten-Hook
     */
    public function enqueue_admin_scripts($hook_suffix)
    {
        if ('settings_page_sessiontags' === $hook_suffix) {
            wp_enqueue_script(
                'sessiontags-admin',
                SESSIONTAGS_URL . 'admin/js/sessiontags-admin.js',
                ['jquery'],
                SESSIONTAGS_VERSION,
                true
            );
        }
    }

    /**
     * Sanitisiert die URL-Encoding-Einstellung
     * 
     * @param mixed $input Die Eingabedaten
     * @return bool Die sanitisierten Daten
     */
    public function sanitize_url_encoding($input)
    {
        return (bool) $input;
    }

    /**
     * Sanitisiert die Parameter-Einstellungen
     * 
     * @param mixed $input Die Eingabedaten
     * @return array Die sanitisierten Daten
     */
    public function sanitize_parameters($input)
    {
        $sanitized_input = [];

        if (is_array($input) && !empty($input)) {
            foreach ($input as $param) {
                if (!empty($param['name'])) {
                    $sanitized_param = [
                        'name' => sanitize_text_field($param['name']),
                        'shortcode' => sanitize_text_field($param['shortcode'] ?? ''),
                        'fallback' => sanitize_text_field($param['fallback'] ?? '')
                    ];
                    $sanitized_input[] = $sanitized_param;
                }
            }
        }

        // Mindestens einen Parameter sicherstellen
        if (empty($sanitized_input)) {
            $sanitized_input = [
                [
                    'name' => 'quelle',
                    'shortcode' => 'q',
                    'fallback' => ''
                ]
            ];
        }

        return $sanitized_input;
    }

    /**
     * Regeneriert den geheimen Schlüssel
     */
    public function regenerate_secret_key()
    {
        $new_key = wp_generate_password(32, true, true);
        update_option('sessiontags_secret_key', $new_key);
        return $new_key;
    }

    /**
     * Rendert die Admin-Seite
     */
    public function render_admin_page()
    {
        // Aktuelle Einstellungen abrufen
        $parameters = get_option('sessiontags_parameters', [
            ['name' => 'quelle', 'shortcode' => 'q', 'fallback' => '']
        ]);
        $use_encoding = get_option('sessiontags_url_encoding', false);
        $secret_key = get_option('sessiontags_secret_key', '');

        // Beispiel-URL zusammenstellen
        $example_url = $this->get_example_url($parameters, $use_encoding);

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
                    <p class="description"><?php echo esc_html__('Konfigurieren Sie die URL-Parameter, die in der Session gespeichert werden sollen.', 'sessiontags'); ?></p>

                    <table class="form-table sessiontags-parameter-table" role="presentation">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Parameter-Name', 'sessiontags'); ?></th>
                                <th><?php echo esc_html__('URL-Kurzform', 'sessiontags'); ?></th>
                                <th><?php echo esc_html__('Standard-Fallback', 'sessiontags'); ?></th>
                                <th><?php echo esc_html__('Aktionen', 'sessiontags'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="sessiontags-parameter-rows">
                            <?php foreach ($parameters as $index => $parameter) : ?>
                                <tr class="parameter-row">
                                    <td>
                                        <input type="text"
                                            name="sessiontags_parameters[<?php echo esc_attr($index); ?>][name]"
                                            value="<?php echo esc_attr($parameter['name']); ?>"
                                            class="regular-text sessiontags-parameter-name"
                                            placeholder="<?php echo esc_attr__('z.B. quelle', 'sessiontags'); ?>"
                                            required>
                                    </td>
                                    <td>
                                        <input type="text"
                                            name="sessiontags_parameters[<?php echo esc_attr($index); ?>][shortcode]"
                                            value="<?php echo esc_attr($parameter['shortcode'] ?? ''); ?>"
                                            class="small-text sessiontags-parameter-shortcode"
                                            placeholder="<?php echo esc_attr__('z.B. q', 'sessiontags'); ?>">
                                    </td>
                                    <td>
                                        <input type="text"
                                            name="sessiontags_parameters[<?php echo esc_attr($index); ?>][fallback]"
                                            value="<?php echo esc_attr($parameter['fallback'] ?? ''); ?>"
                                            class="regular-text sessiontags-parameter-fallback"
                                            placeholder="<?php echo esc_attr__('Standard-Fallback', 'sessiontags'); ?>">
                                    </td>
                                    <td>
                                        <?php if (count($parameters) > 1) : ?>
                                            <button type="button" class="button remove-parameter">
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

                <div class="sessiontags-encoding-settings">
                    <h2><?php echo esc_html__('URL-Verschleierung', 'sessiontags'); ?></h2>
                    <p class="description"><?php echo esc_html__('Aktivieren Sie diese Option, um Parameter-Werte in der URL zu verschleiern.', 'sessiontags'); ?></p>

                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php echo esc_html__('URL-Verschleierung aktivieren', 'sessiontags'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox"
                                        name="sessiontags_url_encoding"
                                        value="1"
                                        <?php checked($use_encoding, true); ?>>
                                    <?php echo esc_html__('Parameter-Werte in der URL verschleiern', 'sessiontags'); ?>
                                </label>
                                <p class="description"><?php echo esc_html__('Hinweis: Die Verschleierung verwendet eine Base64-Kodierung mit einem geheimen Schlüssel.', 'sessiontags'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__('Geheimer Schlüssel', 'sessiontags'); ?></th>
                            <td>
                                <div class="secret-key-display">
                                    <input type="text"
                                        id="secret-key"
                                        value="<?php echo esc_attr($secret_key); ?>"
                                        class="regular-text"
                                        readonly>
                                    <button type="button" class="button regenerate-key">
                                        <?php echo esc_html__('Neu generieren', 'sessiontags'); ?>
                                    </button>
                                </div>
                                <p class="description"><?php echo esc_html__('Dieser Schlüssel wird für die Verschlüsselung verwendet. Ändern Sie ihn nur, wenn nötig.', 'sessiontags'); ?></p>
                            </td>
                        </tr>
                    </table>
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
                                <li><code>[st k="<?php echo esc_html($parameter['name']); ?>"]</code> - Verwendet den Standard-Fallback: <code><?php echo esc_html($parameter['fallback'] ?: '(leer)'); ?></code></li>
                                <li><code>[st k="<?php echo esc_html($parameter['name']); ?>" d="Individueller Fallback"]</code> - Überschreibt den Standard-Fallback</li>
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

                        <h4><?php echo esc_html__('Beispiel mit Kurzform-Parametern:', 'sessiontags'); ?></h4>
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
                            <li><?php echo esc_html__('Optional können Sie einen individuellen Fallback definieren', 'sessiontags'); ?></li>
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
                    var newRow = '<tr class="parameter-row">' +
                        '<td>' +
                        '<input type="text" ' +
                        'name="sessiontags_parameters[' + index + '][name]" ' +
                        'value="" ' +
                        'class="regular-text sessiontags-parameter-name" ' +
                        'placeholder="<?php echo esc_js(__('z.B. quelle', 'sessiontags')); ?>" ' +
                        'required' +
                        '>' +
                        '</td>' +
                        '<td>' +
                        '<input type="text" ' +
                        'name="sessiontags_parameters[' + index + '][shortcode]" ' +
                        'value="" ' +
                        'class="small-text sessiontags-parameter-shortcode" ' +
                        'placeholder="<?php echo esc_js(__('z.B. q', 'sessiontags')); ?>"' +
                        '>' +
                        '</td>' +
                        '<td>' +
                        '<input type="text" ' +
                        'name="sessiontags_parameters[' + index + '][fallback]" ' +
                        'value="" ' +
                        'class="regular-text sessiontags-parameter-fallback" ' +
                        'placeholder="<?php echo esc_js(__('Standard-Fallback', 'sessiontags')); ?>"' +
                        '>' +
                        '</td>' +
                        '<td>' +
                        '<button type="button" class="button remove-parameter">' +
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
                        $(this).find('input').each(function() {
                            var name = $(this).attr('name');
                            name = name.replace(/\[\d+\]/, '[' + index + ']');
                            $(this).attr('name', name);
                        });
                    });

                    // Prüfen, ob nur noch ein Parameter übrig ist
                    if ($('#sessiontags-parameter-rows tr').length === 1) {
                        $('#sessiontags-parameter-rows tr:first-child').find('.remove-parameter').hide();
                    }
                });

                // Beispiel-URL aktualisieren, wenn ein Parameter geändert wird
                $(document).on('input', '.sessiontags-parameter-name, .sessiontags-parameter-shortcode', function() {
                    updateExampleUrl();
                });

                // URL-Verschleierung-Checkbox
                $('input[name="sessiontags_url_encoding"]').on('change', function() {
                    updateExampleUrl();
                });

                // Geheimen Schlüssel neu generieren
                $('.regenerate-key').on('click', function() {
                    if (confirm('<?php echo esc_js(__('Sind Sie sicher, dass Sie den geheimen Schlüssel neu generieren möchten? Dies kann bestehende verschlüsselte URLs ungültig machen.', 'sessiontags')); ?>')) {
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'regenerate_secret_key',
                                nonce: '<?php echo wp_create_nonce('regenerate_secret_key'); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    $('#secret-key').val(response.data);
                                    alert('<?php echo esc_js(__('Der geheime Schlüssel wurde erfolgreich neu generiert.', 'sessiontags')); ?>');
                                } else {
                                    alert('<?php echo esc_js(__('Fehler beim Generieren des geheimen Schlüssels.', 'sessiontags')); ?>');
                                }
                            }
                        });
                    }
                });

                // Beispiel-URL aktualisieren
                function updateExampleUrl() {
                    var baseUrl = '<?php echo esc_js(home_url('/')); ?>';
                    var url = baseUrl + '?';
                    var params = [];
                    var useEncoding = $('input[name="sessiontags_url_encoding"]').is(':checked');

                    $('.parameter-row').each(function() {
                        var paramName = $(this).find('.sessiontags-parameter-name').val();
                        var paramShortcode = $(this).find('.sessiontags-parameter-shortcode').val();

                        if (paramName) {
                            var displayParam = paramShortcode && paramShortcode.trim() !== '' ? paramShortcode : paramName;
                            var paramValue = 'beispielwert';

                            if (useEncoding) {
                                paramValue = '<verschlüsselt>';
                            }

                            params.push(displayParam + '=' + paramValue);
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
     * @param bool $use_encoding Ob die URL-Verschleierung aktiviert ist
     * @return string Die Beispiel-URL
     */
    private function get_example_url($parameters, $use_encoding = false)
    {
        $base_url = home_url('/');
        $params = [];

        foreach ($parameters as $param) {
            if (!empty($param['name'])) {
                // Kürzel verwenden, falls vorhanden
                $param_name = !empty($param['shortcode']) ? $param['shortcode'] : $param['name'];

                // Parameterwert verschleiern, falls aktiviert
                $param_value = 'beispielwert';
                if ($use_encoding) {
                    $param_value = '<verschlüsselt>';
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
