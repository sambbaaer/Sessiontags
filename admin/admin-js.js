jQuery(document).ready(function ($) {
    'use strict';

    // --- Parameter-Management (Einstellungen) ---

    // Parameter hinzufügen
    $('.add-parameter').on('click', function () {
        var index = $('#sessiontags-parameter-rows tr').length;
        var newRow = `
            <tr class="parameter-row">
                <td>
                    <input type="text" 
                           name="sessiontags_parameters[${index}][name]" 
                           value="" 
                           class="regular-text sessiontags-parameter-name" 
                           placeholder="z.B. quelle" 
                           required>
                </td>
                <td>
                    <input type="text" 
                           name="sessiontags_parameters[${index}][shortcode]" 
                           value="" 
                           class="small-text sessiontags-parameter-shortcode" 
                           placeholder="z.B. q">
                </td>
                <td>
                    <input type="text" 
                           name="sessiontags_parameters[${index}][fallback]" 
                           value="" 
                           class="regular-text sessiontags-parameter-fallback" 
                           placeholder="Standard-Fallback">
                </td>
                <td>
                    <input type="url" 
                           name="sessiontags_parameters[${index}][redirect_url]" 
                           value="" 
                           class="regular-text sessiontags-parameter-redirect-url" 
                           placeholder="https://beispiel.de/zielseite">
                </td>
                <td>
                    <button type="button" 
                            class="button button-link-delete remove-parameter" 
                            title="Parameter entfernen">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </td>
            </tr>`;

        $('#sessiontags-parameter-rows').append(newRow);

        // Focus auf das neue Eingabefeld setzen
        $('#sessiontags-parameter-rows tr:last-child input:first').focus();
    });

    // Parameter entfernen
    $(document).on('click', '.remove-parameter', function () {
        var $row = $(this).closest('tr');

        // Bestätigung nur wenn mehr als eine Zeile vorhanden
        if ($('#sessiontags-parameter-rows tr').length > 1) {
            if (!confirm('Möchten Sie diesen Parameter wirklich entfernen?')) {
                return;
            }
        }

        $row.fadeOut(300, function () {
            $(this).remove();
            reindexParameterRows();
        });
    });

    // Parameter-Indizes neu berechnen
    function reindexParameterRows() {
        $('#sessiontags-parameter-rows tr').each(function (index) {
            $(this).find('input').each(function () {
                var name = $(this).attr('name');
                if (name) {
                    name = name.replace(/\[\d+\]/, '[' + index + ']');
                    $(this).attr('name', name);
                }
            });
        });
    }

    // Secret Key regenerieren
    $('.regenerate-key').on('click', function (e) {
        e.preventDefault();

        var $button = $(this);
        var originalText = $button.html();

        // Bestätigung
        if (!confirm('Möchten Sie wirklich einen neuen geheimen Schlüssel generieren? Bestehende verschlüsselte URLs werden ungültig.')) {
            return;
        }

        // Loading-Zustand
        $button.prop('disabled', true)
            .html('<span class="dashicons dashicons-update spin"></span> ' + sessiontags_data.strings.generating);

        // AJAX-Request
        $.ajax({
            url: sessiontags_data.ajax_url,
            type: 'POST',
            data: {
                action: 'regenerate_secret_key',
                nonce: sessiontags_data.nonce
            },
            success: function (response) {
                if (response.success) {
                    $('#secret-key').val(response.data);
                    showNotice(sessiontags_data.strings.success, 'success');
                } else {
                    showNotice(response.data.message || sessiontags_data.strings.error, 'error');
                }
            },
            error: function () {
                showNotice(sessiontags_data.strings.server_error, 'error');
            },
            complete: function () {
                $button.prop('disabled', false).html(originalText);
            }
        });
    });

    // --- URL-Builder ---

    var urlBuilderContainer = $('#url-builder-params');
    var generatedUrlField = $('#generated-url');

    // Parameter-Optionen für Dropdown erstellen
    function getParameterOptions() {
        var options = '<option value="">Parameter wählen...</option>';

        if (sessiontags_data.parameters && sessiontags_data.parameters.length > 0) {
            sessiontags_data.parameters.forEach(function (param) {
                var shortcode = param.shortcode || param.name;
                options += `<option value="${shortcode}">${param.name} (${shortcode})</option>`;
            });
        } else {
            options = '<option value="">Keine Parameter definiert</option>';
        }

        return options;
    }

    // URL-Builder-Zeile hinzufügen
    function addBuilderRow() {
        var index = urlBuilderContainer.find('.url-builder-param-row').length;
        var newRow = `
            <div class="url-builder-param-row" data-index="${index}">
                <label for="builder-param-${index}">Parameter:</label>
                <div>
                    <select class="builder-param-select" id="builder-param-${index}">
                        ${getParameterOptions()}
                    </select>
                    <input type="text" 
                           class="regular-text builder-param-value" 
                           placeholder="Wert eingeben..."
                           style="margin-top: 8px;">
                </div>
                <button type="button" 
                        class="button button-link-delete remove-builder-param" 
                        title="Parameter entfernen">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>`;

        urlBuilderContainer.append(newRow);
        updateGeneratedUrl();

        // Focus auf das neue Select-Element
        urlBuilderContainer.find('.url-builder-param-row:last-child select').focus();
    }

    // URL-Builder-Zeile entfernen
    $(document).on('click', '.remove-builder-param', function () {
        $(this).closest('.url-builder-param-row').fadeOut(300, function () {
            $(this).remove();
            updateGeneratedUrl();
        });
    });

    // URL aktualisieren
    function updateGeneratedUrl() {
        var baseUrl = $('#base-url').val().trim();

        if (!baseUrl) {
            baseUrl = sessiontags_data.home_url;
        }

        // URL validieren
        if (!isValidUrl(baseUrl)) {
            generatedUrlField.val('Ungültige Basis-URL');
            return;
        }

        var params = [];
        var hasValidParams = false;

        $('.url-builder-param-row').each(function () {
            var key = $(this).find('.builder-param-select').val();
            var value = $(this).find('.builder-param-value').val().trim();

            if (key && value) {
                // Einfache Verschlüsselungs-Simulation für Vorschau
                if (sessiontags_data.use_encoding) {
                    // Nur visuelle Andeutung - echte Verschlüsselung passiert serverseitig
                    value = btoa(value + '|' + 'preview').replace(/[=]/g, '').substring(0, 12) + '...';
                }

                params.push(`${key}=${encodeURIComponent(value)}`);
                hasValidParams = true;
            }
        });

        var finalUrl = baseUrl;
        if (hasValidParams) {
            var separator = baseUrl.includes('?') ? '&' : '?';
            finalUrl += separator + params.join('&');
        }

        generatedUrlField.val(finalUrl);
    }

    // URL-Validierung
    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            // Relative URLs erlauben
            if (string.startsWith('/')) {
                return true;
            }
            return false;
        }
    }

    // Event-Listener für URL-Builder
    if (urlBuilderContainer.length) {
        // Initial eine Zeile hinzufügen
        addBuilderRow();
    }

    // URL-Builder Parameter hinzufügen
    $('.add-builder-param').on('click', addBuilderRow);

    // Änderungen überwachen und URL aktualisieren
    $(document).on('input change', '#base-url, .builder-param-select, .builder-param-value', function () {
        updateGeneratedUrl();
    });

    // URL kopieren
    $('.copy-url').on('click', function (e) {
        e.preventDefault();

        var $button = $(this);
        var targetId = $button.data('target');
        var $target = $('#' + targetId);
        var urlText = $target.val() || $target.text();

        if (!urlText || urlText.trim() === '') {
            showNotice('Keine URL zum Kopieren vorhanden.', 'error');
            return;
        }

        // Clipboard API verwenden (moderne Browser)
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(urlText).then(function () {
                showCopySuccess($button);
            }).catch(function (err) {
                console.error('Clipboard API fehlgeschlagen: ', err);
                fallbackCopy(urlText, $button);
            });
        } else {
            // Fallback für ältere Browser
            fallbackCopy(urlText, $button);
        }
    });

    // Fallback-Kopierfunktion
    function fallbackCopy(text, $button) {
        var $tempInput = $('<textarea>');
        $('body').append($tempInput);
        $tempInput.val(text).select();

        try {
            document.execCommand('copy');
            showCopySuccess($button);
        } catch (err) {
            console.error('Fallback-Kopieren fehlgeschlagen: ', err);
            showNotice('Kopieren fehlgeschlagen. Bitte manuell auswählen und kopieren.', 'error');
        }

        $tempInput.remove();
    }

    // Kopier-Erfolg anzeigen
    function showCopySuccess($button) {
        var originalHtml = $button.html();
        $button.html('<span class="dashicons dashicons-yes"></span> ' + sessiontags_data.strings.copied)
            .addClass('sessiontags-success');

        setTimeout(function () {
            $button.html(originalHtml).removeClass('sessiontags-success');
        }, 2000);
    }

    // --- Hilfsfunktionen ---

    // Benachrichtigung anzeigen
    function showNotice(message, type) {
        type = type || 'info';

        var noticeClass = 'notice notice-' + type;
        if (type === 'error') {
            noticeClass += ' notice-error';
        } else if (type === 'success') {
            noticeClass += ' notice-success';
        }

        var $notice = $('<div>', {
            'class': noticeClass + ' is-dismissible',
            'html': '<p>' + message + '</p>'
        });

        // Notice einfügen
        $('.sessiontags-admin .wrap > h1').after($notice);

        // Automatisch entfernen nach 5 Sekunden
        setTimeout(function () {
            $notice.fadeOut(function () {
                $(this).remove();
            });
        }, 5000);

        // Dismiss-Button funktional machen
        $notice.on('click', '.notice-dismiss', function () {
            $notice.fadeOut(function () {
                $(this).remove();
            });
        });
    }

    // Spin-Animation für Loading-Buttons
    var style = $('<style>');
    style.text(`
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .spin {
            animation: spin 1s linear infinite;
        }
    `);
    $('head').append(style);

    // Form-Validierung verbessern
    $('form').on('submit', function () {
        var hasError = false;

        // Erforderliche Felder prüfen
        $(this).find('input[required]').each(function () {
            if (!$(this).val().trim()) {
                $(this).addClass('error');
                hasError = true;
            } else {
                $(this).removeClass('error');
            }
        });

        if (hasError) {
            showNotice('Bitte füllen Sie alle erforderlichen Felder aus.', 'error');
            return false;
        }
    });

    // Error-Klasse bei Input entfernen
    $(document).on('input', 'input.error', function () {
        $(this).removeClass('error');
    });

    // Keyboard-Shortcuts
    $(document).on('keydown', function (e) {
        // Ctrl/Cmd + Enter: URL kopieren (im URL-Builder)
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 13) {
            var $copyButton = $('.copy-url:visible');
            if ($copyButton.length) {
                e.preventDefault();
                $copyButton.click();
            }
        }
    });

    // Tooltips für bessere UX
    $('[title]').each(function () {
        var $this = $(this);
        var title = $this.attr('title');

        $this.removeAttr('title').on('mouseenter', function () {
            var $tooltip = $('<div>', {
                'class': 'sessiontags-tooltip',
                'text': title,
                'css': {
                    'position': 'absolute',
                    'background': '#1d2327',
                    'color': '#fff',
                    'padding': '6px 10px',
                    'border-radius': '4px',
                    'font-size': '12px',
                    'z-index': 1000,
                    'white-space': 'nowrap',
                    'pointer-events': 'none'
                }
            });

            $('body').append($tooltip);

            var offset = $this.offset();
            $tooltip.css({
                'left': offset.left + ($this.outerWidth() / 2) - ($tooltip.outerWidth() / 2),
                'top': offset.top - $tooltip.outerHeight() - 8
            });

        }).on('mouseleave', function () {
            $('.sessiontags-tooltip').remove();
        });
    });

    // Auto-Save für URL-Builder (localStorage)
    function saveBuilderState() {
        if (!window.localStorage) return;

        var state = {
            baseUrl: $('#base-url').val(),
            params: []
        };

        $('.url-builder-param-row').each(function () {
            var key = $(this).find('.builder-param-select').val();
            var value = $(this).find('.builder-param-value').val();

            if (key || value) {
                state.params.push({ key: key, value: value });
            }
        });

        localStorage.setItem('sessiontags_builder_state', JSON.stringify(state));
    }

    function loadBuilderState() {
        if (!window.localStorage) return;

        try {
            var state = JSON.parse(localStorage.getItem('sessiontags_builder_state'));
            if (!state) return;

            if (state.baseUrl) {
                $('#base-url').val(state.baseUrl);
            }

            if (state.params && state.params.length > 0) {
                // Erst alle bestehenden Zeilen entfernen
                $('.url-builder-param-row').remove();

                // Dann gespeicherte Zeilen hinzufügen
                state.params.forEach(function (param) {
                    addBuilderRow();
                    var $lastRow = $('.url-builder-param-row:last');
                    $lastRow.find('.builder-param-select').val(param.key);
                    $lastRow.find('.builder-param-value').val(param.value);
                });

                updateGeneratedUrl();
            }
        } catch (e) {
            // State ist korrupt, ignorieren
            localStorage.removeItem('sessiontags_builder_state');
        }
    }

    // Auto-Save aktivieren (nur im URL-Builder)
    if (urlBuilderContainer.length) {
        loadBuilderState();

        $(document).on('input change', '#base-url, .builder-param-select, .builder-param-value', function () {
            setTimeout(saveBuilderState, 500); // Debounce
        });
    }
});