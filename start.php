<?php
/**
 * Elgg community site web services
 */

register_elgg_event_handler('init', 'system', 'community_ws_init');

function community_ws_init() {
	expose_function(
			'plugins.update.check',
			'community_ws_plugin_check',
			array(
				'plugins' => array('type' => 'array', 'required' => true,),
				'version' => array('type' => 'string', 'required' => true,),
			),
			"Check if there are newer versions of an Elgg site's plugins available on the Elgg community site.",
			'GET',
			false,
			false
	);
	
}

/**
 * Check for updates on the requested plugins
 *
 * @param array  $plugins An array of plugin ids. The ids are a md5 hash of the
 *                        plugin directory, plugin version, and plugin author.
 * @param string $version The Elgg version string
 * @return array
 */
function community_ws_plugin_check($plugins, $version) {
	$updated_plugins = array();

	foreach ($plugins as $plugin_hash) {
		$release = elgg_get_entities_from_metadata(array(
			'type' => 'object',
			'subtype' => 'plugin_release',
			'metadata_name' => 'hash',
			'metadata_value' => $plugin_hash,
		));
		if ($release) {
			$release = $release[0];
		} else {
			continue;
		}

		$project = $release->getProject();
		$newer_releases = elgg_get_entities(array(
			'type' => 'object',
			'subtype' => 'plugin_release',
			'container_guid' => $project->getGUID(),
			'created_time_lower' => $release->getTimeCreated(),
			'order_by' => 'e.time_created desc',
		));

		if ($newer_releases) {
			$new_release = $newer_releases[0];
			$dl_link = get_config('wwwroot');
			$dl_link .= "pg/plugins/download/{$new_release->getGUID()}";
			
			$info = new stdClass();
			$info->plugin_id = $plugin_hash;
			$info->plugin_name = $project->title;
			$info->plugin_version = $new_release->version;
			$info->plugin_url = $new_release->getURL();
			$info->download_url = $dl_link;
			$updated_plugins[] = $info;
		}

	}

	return $updated_plugins;
}
