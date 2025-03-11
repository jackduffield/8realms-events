( function( blocks, element, components ) {
    var el = element.createElement;
    var ServerSideRender = components.ServerSideRender;

    // Register the Upcoming Events block for Events Display.
    blocks.registerBlockType('events-display/upcoming-events', {
        title: 'Upcoming Events',
        icon: 'calendar-alt',
        category: 'widgets',
        edit: function( props ) {
            return el(ServerSideRender, {
                block: 'events/upcoming-events',
                attributes: props.attributes
            });
        },
        save: function() {
            return null;
        },
    });

} )(
    window.wp.blocks,
    window.wp.element,
    window.wp.components
);
