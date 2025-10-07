jQuery(document).ready(function($) {
    'use strict';

    var mediaUploader;

    // Handle the logo upload button click
    $(document).on('click', '.dsa-upload-logo-button', function(e) {
        e.preventDefault();
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: dsaSettingsData.l10n.selectLogo || 'Choose Studio Logo',
            button: { text: dsaSettingsData.l10n.useLogo || 'Use this logo' },
            multiple: false
        });
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#dsa_studio_logo_id').val(attachment.id);
            var previewUrl = attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
            $('#dsa-logo-preview').html('<img src="' + esc_url(previewUrl) + '" style="max-width:200px; max-height:150px; border:1px solid #ddd;"/>');
            $('.dsa-remove-logo-button').show();
        });
        mediaUploader.open();
    });

    // Handle the logo remove button click
    $(document).on('click', '.dsa-remove-logo-button', function(e) {
        e.preventDefault();
        $('#dsa_studio_logo_id').val('0');
        $('#dsa-logo-preview').html('');
        $(this).hide();
    });
    
    // Basic esc_url for client-side security
    function esc_url(url){
        if(!url||typeof url!=='string')return '';return url.replace(/[^-A-Za-z0-9+&@#/%?=~_|!:,.;\(\)]/g,function(m){return'%'+m.charCodeAt(0).toString(16);});
    }
});