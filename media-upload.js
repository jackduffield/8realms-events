jQuery(document).ready(function($) {
    var mediaUploader;
    $('#upload-thumbnail-button').click(function(e) {
        e.preventDefault();
        // Reuse mediaUploader if it already exists.
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        // Create a new media frame
        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Choose Thumbnail Image',
            button: {
                text: 'Choose Image'
            },
            multiple: false
        });
        // When an image is selected, grab its URL and set it as the input value.
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#thumbnail-url').val(attachment.url);
        });
        // Open the uploader dialog
        mediaUploader.open();
    });
});
