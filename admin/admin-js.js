jQuery(document).ready(function ($) {
    // --- Bestehender Code für Einstellungsseite ---

    // Parameter hinzufügen (Einstellungen)
    $('.add-parameter').on('click', function () {
        var index = $('#sessiontags-parameter-rows tr').length;
        var newRow = `
            <tr class="parameter-row">
                <td><input type="text" name="sessiontags_parameters[${index}][name]" value="" class="regular-text sessiontags-parameter-name" placeholder="z.B. quelle" required></td>
                <td><input type="text" name="sessiontags_parameters[${index}][shortcode]" value="" class="small-text sessiontags-parameter-shortcode" placeholder="z.B. q"></td>
                <td><input type="text" name="sessiontags_parameters[${index}][fallback]" value="" class="regular-text sessiontags-parameter-fallback" placeholder="Standard-Fallback"></td>
                <td><input type="text" name="sessiontags_parameters[${index}][redirect_url]" value="" class="regular-text sessiontags-parameter-redirect-url" placeholder="https://beispiel.de/zielseite"></td>
                <td><span class="dashicons dashicons-trash trash-icon remove-parameter"></span></td>
            </tr>`;
        $('#sessiontags-parameter-rows').append(newRow);
    });

    // Parameter entfernen (Einstellungen)
    $(document).on('click', '.remove-parameter', function () {
        $(this).closest('tr').remove();
        // Indizes neu berechnen
        $('#sessiontags-parameter-rows tr').each(function (index) {
            $(this).find('input').each(function () {
                var name = $(this).attr('name');
                if (name) {
                    name = name.replace(/\[\d+\]/, '[' + index + ']');
                    $(this).attr('name', name);
                }
            });
        });
    });

    // Secret Key regenerieren
    $('.regenerate-key').on('click', function (e) {
        e.preventDefault();
        // ... (AJAX-Code bleibt gleich)
    });

    // --- NEU: Code für den visuellen URL-Builder ---

    function getParameterOptions() {
        let options = '';
        if (sessiontags_data.parameters.length > 0) {
            sessiontags_data.parameters.forEach(param => {
                options += `<option value="${param.shortcode}">${param.name} (${param.shortcode})</option>`;
            });
        } else {
            options = '<option value="">Keine Parameter definiert</option>';
        }
        return options;
    }

    function addBuilderRow() {
        const index = $('#url-builder-params .url-builder-row').length;
        const newRow = `
            <div class="url-builder-row" data-index="${index}">
                <label>Parameter</label>
                <select class="builder-param-select">${getParameterOptions()}</select>
                <input type="text" class="regular-text builder-param-value" placeholder="Wert eingeben">
                <span class="dashicons dashicons-trash trash-icon remove-builder-param"></span>
            </div>`;
        $('#url-builder-params').append(newRow);
        updateGeneratedUrl();
    }

    function updateGeneratedUrl() {
        let baseUrl = $('#base-url').val().trim();
        if (!baseUrl) {
            baseUrl = sessiontags_data.home_url;
        }

        let params = [];
        $('.url-builder-row').each(function () {
            const key = $(this).find('.builder-param-select').val();
            let value = $(this).find('.builder-param-value').val().trim();

            if (key && value) {
                // Verschlüsselung simulieren, wenn aktiviert
                if (sessiontags_data.use_encoding) {
                    // Nur eine visuelle Andeutung, die echte Verschlüsselung passiert serverseitig
                    value = btoa(value).replace(/=/g, '');
                }
                params.push(`${key}=${encodeURIComponent(value)}`);
            }
        });

        let finalUrl = baseUrl;
        if (params.length > 0) {
            finalUrl += (baseUrl.includes('?') ? '&' : '?') + params.join('&');
        }

        $('#generated-url').text(finalUrl);
    }

    // Event Listeners für den URL-Builder
    if ($('.sessiontags-url-builder-section').length) {
        addBuilderRow(); // Initial eine Zeile hinzufügen
    }

    $('.add-builder-param').on('click', addBuilderRow);

    $(document).on('click', '.remove-builder-param', function () {
        $(this).closest('.url-builder-row').remove();
        updateGeneratedUrl();
    });

    $(document).on('input', '#base-url, .builder-param-select, .builder-param-value', function () {
        updateGeneratedUrl();
    });

    // URL kopieren (jetzt allgemeiner)
    $('.copy-url').on('click', function () {
        const targetId = $(this).data('target');
        const urlText = $(`#${targetId}`).text();

        navigator.clipboard.writeText(urlText).then(() => {
            const button = $(this);
            const originalText = button.text();
            button.text(sessiontags_data.strings.copied);
            setTimeout(() => button.text(originalText), 2000);
        }).catch(err => {
            console.error('Kopieren fehlgeschlagen: ', err);
            // Fallback
            const tempInput = $('<input>');
            $('body').append(tempInput);
            tempInput.val(urlText).select();
            document.execCommand('copy');
            tempInput.remove();
        });
    });
});
