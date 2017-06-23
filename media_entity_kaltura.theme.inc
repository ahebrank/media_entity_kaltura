<?php

/**
 * @file
 * Theme functions for the media_entity_kaltura module.
 */

/**
 * Prepares variables for media_entity_kaltura templates.
 *
 * Default template: media-kaltura-embed.html.twig.
 */
function template_preprocess_media_kaltura_embed(&$variables) {
    // make up an element ID
    $variables['playerId'] = 'kaltura-' . uniqid();
}
