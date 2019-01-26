# Media Entity Kaltura
Kaltura integration for the Media core module.

## Installation

1. Recommended way to install this module is via 
`composer require drupal/media_entity_kaltura`. 
Otherwise make sure to install all of its dependencies from _composer.json_ 
manually.
2. Enable the media_entity_kaltura module.
3. Go to `/admin/config/media/media-kaltura` and supply all necessary data 
for integration with your Kaltura service.
4. Go to `/admin/structure/media` and click 'Add media type' to create a new 
media type.
5. Under **Media source** select Kaltura.
6. Save the form.

## Configuration

1. Go to the **Manage display** section for the media type.
2. For the source field, select **Kaltura Embed** under **Format**.
3. Click on the settings icon to configure the embedded player.
4. Save.

## License

http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
