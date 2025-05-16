<p class="tip"><?php echo esc_html__('Hier sind "name" und "email" die Formularfeld-IDs.', 'sessiontags'); ?></p>
                        </div>

                        <div class="sessiontags-doc-example">
                            <h4><?php echo esc_html__('Beispiel:', 'sessiontags'); ?></h4>
                            <code>[st_form 
  type="microsoft" 
  url="https://forms.office.com/Pages/ResponsePage.aspx?id=YOUR_FORM_ID" 
  params="name,email" 
  form_params="name,email" 
  width="100%" 
  height="600px"
]</code>
                            <p><?php echo esc_html__('Dieses Beispiel bettet ein Microsoft-Formular ein und überträgt den Wert des Session-Parameters "name" in das Formularfeld "name" und den Wert von "email" in das Feld "email".', 'sessiontags'); ?></p>
                        </div>

                        <div class="sessiontags-doc-note">
                            <h4><?php echo esc_html__('Wichtig:', 'sessiontags'); ?></h4>
                            <p><?php echo esc_html__('Bei Microsoft Forms können die Feldnamen je nach Formular unterschiedlich sein. Die Namen entsprechen oft dem Feldtyp (z.B. "name", "email", "r1q1" oder "question1").', 'sessiontags'); ?></p>
                            <p><?php echo esc_html__('Wenn die form_params und params identisch sind, kannst du form_params weglassen:', 'sessiontags'); ?></p>
                            <code>[st_form type="microsoft" url="https://forms.office.com/Pages/ResponsePage.aspx?id=YOUR_FORM_ID" params="name,email"]</code>
                        </div>
                    </div>

                    <div class="sessiontags-doc-section">
                        <h3><?php echo esc_html__('Tipps und Hinweise', 'sessiontags'); ?></h3>
                        
                        <div class="sessiontags-doc-tips">
                            <div class="sessiontags-doc-tip">
                                <h4><?php echo esc_html__('Tipp 1: Testen vor dem Veröffentlichen', 'sessiontags'); ?></h4>
                                <p><?php echo esc_html__('Teste immer zuerst mit einigen Parameter-Werten in der URL, um zu prüfen, ob die Felder korrekt ausgefüllt werden.', 'sessiontags'); ?></p>
                            </div>
                            
                            <div class="sessiontags-doc-tip">
                                <h4><?php echo esc_html__('Tipp 2: Responsives Design', 'sessiontags'); ?></h4>
                                <p><?php echo esc_html__('Die Standardwerte width="100%" und height="800px" sorgen für eine gute Anpassung auf verschiedenen Geräten. Für eine bessere Kontrolle kannst du auch CSS-Klassen verwenden und die Darstellung über dein Theme anpassen.', 'sessiontags'); ?></p>
                            </div>
                            
                            <div class="sessiontags-doc-tip">
                                <h4><?php echo esc_html__('Tipp 3: Sicherheit', 'sessiontags'); ?></h4>
                                <p><?php echo esc_html__('Da Parameter über URLs übertragen werden, solltest du keine sensiblen Daten wie Passwörter oder personenbezogene Daten auf diesem Weg weitergeben.', 'sessiontags'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($this->active_tab === 'docs-more') : ?>
                <!-- Weitere Informationen Tab -->
                <div class="sessiontags-documentation">
                    <h2><?php echo esc_html__('Weitere Informationen und Funktionen', 'sessiontags'); ?></h2>
                    
                    <div class="sessiontags-doc-section">
                        <h3><?php echo esc_html__('Wie funktioniert SessionTags?', 'sessiontags'); ?></h3>
                        <div class="sessiontags-doc-cards">
                            <div class="sessiontags-doc-card">
                                <h4><?php echo esc_html__('1. Parameter erfassen', 'sessiontags'); ?></h4>
                                <p><?php echo esc_html__('Wenn ein Besucher deine Website mit bestimmten URL-Parametern aufruft (z.B. ?quelle=newsletter), erkennt SessionTags diese Parameter.', 'sessiontags'); ?></p>
                            </div>
                            
                            <div class="sessiontags-doc-card">
                                <h4><?php echo esc_html__('2. In Session speichern', 'sessiontags'); ?></h4>
                                <p><?php echo esc_html__('Die Werte werden in der PHP-Session des Besuchers gespeichert und bleiben während des gesamten Website-Besuchs erhalten, auch wenn der Besucher zwischen Seiten wechselt.', 'sessiontags'); ?></p>
                            </div>
                            
                            <div class="sessiontags-doc-card">
                                <h4><?php echo esc_html__('3. Abrufen und anzeigen', 'sessiontags'); ?></h4>
                                <p><?php echo esc_html__('Du kannst die gespeicherten Werte an beliebiger Stelle über Shortcodes, in Elementor, in Avada Fusion Builder oder in Formularen ausgeben.', 'sessiontags'); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="sessiontags-doc-section">
                        <h3><?php echo esc_html__('Elementor Integration', 'sessiontags'); ?></h3>
                        <p><?php echo esc_html__('SessionTags integriert sich nahtlos in Elementor über Dynamic Tags:', 'sessiontags'); ?></p>
                        
                        <div class="sessiontags-doc-steps">
                            <h4><?php echo esc_html__('So verwendest du SessionTags in Elementor:', 'sessiontags'); ?></h4>
                            <ol>
                                <li><?php echo esc_html__('Bearbeite ein Elementor-Widget (z.B. Überschrift oder Text)', 'sessiontags'); ?></li>
                                <li><?php echo esc_html__('Klicke auf das Dynamic Tags-Symbol (ein kleines Datensymbol)', 'sessiontags'); ?></li>
                                <li><?php echo esc_html__('Wähle "SessionTags" aus der Liste', 'sessiontags'); ?></li>
                                <li><?php echo esc_html__('Wähle den gewünschten Parameter aus dem Dropdown-Menü', 'sessiontags'); ?></li>
                                <li><?php echo esc_html__('Optional: Gib einen individuellen Fallback-Wert ein', 'sessiontags'); ?></li>
                                <li><?php echo esc_html__('Fertig! Der Wert wird nun dynamisch angezeigt', 'sessiontags'); ?></li>
                            </ol>
                        </div>
                        
                        <div class="sessiontags-doc-note">
                            <p><?php echo esc_html__('Mit Elementor kannst du Parameter überall verwenden - in Überschriften, Texten, Buttons, Bildbeschreibungen und vielem mehr!', 'sessiontags'); ?></p>
                        </div>
                    </div>

                    <div class="sessiontags-doc-section">
                        <h3><?php echo esc_html__('Avada Fusion Builder Integration', 'sessiontags'); ?></h3>
                        <p><?php echo esc_html__('Wenn du Avada verwendest, bietet SessionTags ein spezielles Element für den Fusion Builder:', 'sessiontags'); ?></p>
                        
                        <div class="sessiontags-doc-steps">
                            <h4><?php echo esc_html__('So verwendest du SessionTags in Avada:', 'sessiontags'); ?></h4>
                            <ol>
                                <li><?php echo esc_html__('Öffne den Fusion Builder', 'sessiontags'); ?></li>
                                <li><?php echo esc_html__('Suche nach dem Element "SessionTags Parameter"', 'sessiontags'); ?></li>
                                <li><?php echo esc_html__('Füge das Element hinzu und wähle den gewünschten Parameter aus', 'sessiontags'); ?></li>
                                <li><?php echo esc_html__('Optional: Passe die Darstellung und den Fallback-Wert an', 'sessiontags'); ?></li>
                            </ol>
                        </div>
                    </div>

                    <div class="sessiontags-doc-section">
                        <h3><?php echo esc_html__('Häufig gestellte Fragen', 'sessiontags'); ?></h3>
                        
                        <div class="sessiontags-doc-faq">
                            <div class="sessiontags-doc-faq-item">
                                <h4><?php echo esc_html__('Wie lange bleiben die Parameter gespeichert?', 'sessiontags'); ?></h4>
                                <p><?php echo esc_html__('Die Parameter bleiben für die Dauer der PHP-Session gespeichert. Das bedeutet in der Regel bis zum Schliessen des Browsers oder bis zu einer bestimmten Inaktivitätszeit (abhängig von der PHP-Konfiguration deines Servers).', 'sessiontags'); ?></p>
                            </div>
                            
                            <div class="sessiontags-doc-faq-item">
                                <h4><?php echo esc_html__('Kann ich die Parameter auch dauerhaft speichern?', 'sessiontags'); ?></h4>
                                <p><?php echo esc_html__('Das Plugin ist bewusst auf die temporäre Speicherung in der Session ausgelegt. Für eine dauerhafte Speicherung müsstest du das Plugin erweitern oder Cookies verwenden.', 'sessiontags'); ?></p>
                            </div>
                            
                            <div class="sessiontags-doc-faq-item">
                                <h4><?php echo esc_html__('Funktioniert die URL-Verschleierung auf allen Webseiten?', 'sessiontags'); ?></h4>
                                <p><?php echo esc_html__('Die URL-Verschleierung funktioniert für alle mit dem Plugin [st_url] generierten Links. Externe Systeme können die verschleierten Parameter jedoch nicht direkt interpretieren, sondern benötigen die entschlüsselten Werte.', 'sessiontags'); ?></p>
                            </div>
                            
                            <div class="sessiontags-doc-faq-item">
                                <h4><?php echo esc_html__('Ist das Plugin DSGVO-konform?', 'sessiontags'); ?></h4>
                                <p><?php echo esc_html__('Das Plugin selbst speichert Daten nur temporär in der PHP-Session des Besuchers, nicht dauerhaft in der Datenbank. Wenn du jedoch personenbezogene Daten in URL-Parametern überträgst, solltest du dies in deiner Datenschutzerklärung erwähnen.', 'sessiontags'); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="sessiontags-doc-section">
                        <h3><?php echo esc_html__('Anwendungsbeispiele', 'sessiontags'); ?></h3>
                        
                        <div class="sessiontags-doc-examples">
                            <div class="sessiontags-doc-example-card">
                                <h4><?php echo esc_html__('Marketing-Kampagnen nachverfolgen', 'sessiontags'); ?></h4>
                                <p><?php echo esc_html__('Verwende die Parameter "quelle" und "kampagne", um zu verfolgen, woher deine Besucher kommen. Beispiel-URL: ?q=newsletter&k=herbst2025', 'sessiontags'); ?></p>
                                <code>[st k="quelle" d="direkt"] / [st k="kampagne" d="standard"]</code>
                            </div>
                            
                            <div class="sessiontags-doc-example-card">
                                <h4><?php echo esc_html__('Personalisierte Ansprache', 'sessiontags'); ?></h4>
                                <p><?php echo esc_html__('Passe Inhalte basierend auf der Quelle des Besuchers an. Beispiel-URL: ?branche=gesundheit', 'sessiontags'); ?></p>
                                <code>Willkommen! Hier findest du spezialisierte Angebote für die [st k="branche" d="Industrie"]-Branche.</code>
                            </div>
                            
                            <div class="sessiontags-doc-example-card">
                                <h4><?php echo esc_html__('Kontaktformulare mit vorausgefüllten Feldern', 'sessiontags'); ?></h4>
                                <p><?php echo esc_html__('Leite Interessenten an dein Kontaktformular weiter, wobei die Herkunft bereits ausgefüllt ist. Beispiel-URL: ?interest=produkt-a', 'sessiontags'); ?></p>
                                <code>[st_form type="google" url="https://docs.google.com/forms/d/..." params="interest" form_params="entry.123456789"]</code>
                            </div>
                            
                            <div class="sessiontags-doc-example-card">
                                <h4><?php echo esc_html__('Partner- oder Affiliate-Programme', 'sessiontags'); ?></h4>
                                <p><?php echo esc_html__('Verfolge Partner-IDs über mehrere Seiten hinweg. Beispiel-URL: ?partner=123', 'sessiontags'); ?></p>
                                <code>[st_url url="https://example.com/anmeldung/" params="partner=[st k=partner]"]Jetzt anmelden[/st_url]</code>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

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
                        if (confirm('<?php echo esc_js(__('Bist du sicher, dass du den geheimen Schlüssel neu generieren möchtest? Dies kann bestehende verschlüsselte URLs ungültig machen.', 'sessiontags')); ?>')) {
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
        </div>
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