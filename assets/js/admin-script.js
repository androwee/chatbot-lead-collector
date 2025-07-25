jQuery(document).ready(function($) {
    $('body').on('click', '.lcc-upload-button', function(e) {
        e.preventDefault();
        const button = $(this);
        const targetId = button.data('target-id');
        let mediaFrame = wp.media({
            title: 'Pilih Gambar', button: { text: 'Gunakan Gambar Ini' }, multiple: false
        });
        mediaFrame.on('select', function() {
            const attachment = mediaFrame.state().get('selection').first().toJSON();
            $('#' + targetId + '_url').val(attachment.url);
            $('#' + targetId + '_preview').attr('src', attachment.url).show();
        });
        mediaFrame.open();
    });

    $('body').on('click', '.lcc-remove-button', function(e) {
        e.preventDefault();
        const button = $(this);
        const targetId = button.data('target-id');
        $('#' + targetId + '_url').val('');
        $('#' + targetId + '_preview').hide();
    });
});