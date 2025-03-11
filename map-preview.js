jQuery(document).ready(function($) {
    $('#show-map-btn').on('click', function(e) {
        e.preventDefault();
        var address = $('#location-input').val();
        if (!address) {
            alert('Please enter a location.');
            return;
        }
        
        // Build the Google Maps embed URL.
        // The URL format: https://www.google.com/maps/embed/v1/place?key=YOUR_API_KEY&q=ADDRESS
        var embedUrl = 'https://www.google.com/maps/embed/v1/place?key=' + EM_MapVars.apiKey + '&q=' + encodeURIComponent(address);
        
        // Build the iframe element.
        var iframe = '<iframe width="100%" height="300" frameborder="0" style="border:0" src="' + embedUrl + '" allowfullscreen></iframe>';
        
        // Insert the iframe into the location-map div and show it.
        $('#location-map').html(iframe).show();
    });
});