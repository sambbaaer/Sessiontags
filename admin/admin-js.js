/**
 * SessionTags Admin JavaScript
 */
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
            'placeholder="z.B. quelle" ' +
            'required' +
            '>' +
            '</td>' +
            '<td>' +
            '<input type="text" ' +
            'name="sessiontags_parameters[' + index + '][shortcode]" ' +
            'value="" ' +
            'class="small-text sessiontags-parameter-shortcode" ' +
            'placeholder="z.B. q"' +
            '>' +
            '</td>' +
            '<td>' +
            '<input type="text" ' +
            'name="sessiontags_parameters[' + index + '][fallback]" ' +
            'value="" ' +
            'class="regular-text sessiontags-parameter-fallback" ' +
            'placeholder="Standard-Fallback"' +
            '>' +
            '</td>' +
            '<td>' +
            '<button type="button" class="button remove-parameter">' +
            'Entfernen' +
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
    
    // Beispiel-URL aktualisieren
    function updateExampleUrl() {
        var baseUrl = window.location.origin + '/';
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
        button.text('Kopiert!');
        setTimeout(function() {
            button.text(originalText);
        }, 2000);
    });
});
