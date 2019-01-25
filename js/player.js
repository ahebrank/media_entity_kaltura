(function ($, Drupal) {
  Drupal.behaviors.MediaEntityKalturaPlayer = {
    attach: function (context, settings) {
      $('.media-entity-kaltura-player', context).once('media-entity-kaltura-player').each(function () {
        var wrapper = $(this);
        kWidget.embed({
          'targetId': wrapper.attr('id'),
          'wid': '_' + wrapper.data('kaltura-partner-id'),
          'uiconf_id' : wrapper.data('kaltura-ui-conf-id'),
          'entry_id' : wrapper.data('kaltura-entry-id'),
          'flashvars': {
            'autoPlay': false
          },
          'params': {
            'wmode': 'transparent'
          },
          readyCallback: function(playerId) {
            wrapper.trigger('media_entity_kaltura.player_initialized');
          }
        });
      });
    }
  };
})(jQuery, Drupal);
