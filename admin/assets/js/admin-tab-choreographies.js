jQuery(document).ready(function($) {
    'use strict';

    // Make sure the data from PHP is available
    if (typeof dsaChoreographyData === 'undefined') {
        return;
    }

    const modal = $('#dsa-add-choreography-modal');
    const form = $('#dsa-add-choreography-form');
    const messagesDiv = $('#dsa-modal-messages');

    // 1. Handle the "Add New" button click to open the modal
    $('#dsa-add-new-choreography-button').on('click', function() {
        modal.dialog('open');
    });

    // 2. Initialize the jQuery UI Dialog for the modal
    modal.dialog({
        autoOpen: false,
        modal: true,
        width: 700, // A bit wider to accommodate the form
        resizable: false,
        buttons: {
            "Create Choreography": function() {
                form.trigger('submit');
            },
            "Cancel": function() {
                $(this).dialog('close');
            }
        },
        close: function() {
            // Reset the form and clear any messages when the modal closes
            form[0].reset();
            messagesDiv.html('').hide();
        }
    });

    // 3. Handle the form submission via AJAX
    form.on('submit', function(e) {
        e.preventDefault();
        
        const submitButton = modal.parent().find('button:contains("Create Choreography")');
        submitButton.prop('disabled', true).text('Creating...');
        messagesDiv.html('').removeClass('notice-error notice-success').slideUp();

        let formData = form.serialize();
        formData += '&action=dsa_create_choreography_ajax';
        formData += '&nonce=' + dsaChoreographyData.nonce;

        $.post(dsaChoreographyData.ajax_url, formData)
        .done(function(response) {
            if (response.success) {
                messagesDiv.html('<p>' + response.data.message + '</p>').addClass('notice notice-success').slideDown();
                
                // If the "no items" row exists, remove it
                $('#the-list .no-items').remove();

                // Add the new row to the top of the table
                var newRow = $(response.data.html).hide();
                $('#the-list').prepend(newRow);
                newRow.fadeIn();

                // Close the modal after a short delay
                setTimeout(function() {
                    modal.dialog('close');
                }, 1200);

            } else {
                messagesDiv.html('<p>Error: ' + (response.data.message || 'An unknown error occurred.') + '</p>').addClass('notice notice-error').slideDown();
            }
        })
        .fail(function() {
            messagesDiv.html('<p>A critical server error occurred. Please try again.</p>').addClass('notice notice-error').slideDown();
        })
        .always(function() {
            submitButton.prop('disabled', false).text('Create Choreography');
        });
    });

    // 4. Media uploader for the song file in the modal
    modal.on('click', '.dsa-upload-song-button-modal', function(e) {
        e.preventDefault();
        var button = $(this);
        var mediaUploader = wp.media({
            title: 'Select Song File',
            button: { text: 'Use this song' },
            library: { type: 'audio' },
            multiple: false
        }).on('select', function(){
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            button.prev('input').val(attachment.url);
        }).open();
    });
});