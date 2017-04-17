<?php

/**
 * Class ImageProfiles_SiteConfig
 * Clears out images if the profile's data has changed vs copy stored in SiteConfig
 */
class ImageProfiles_SiteConfig extends DataExtension implements Flushable
{

    private static $db = array(
    	'ImageProfiles' => 'Text' // serialized Profile data, for detecting new changes against
    );

	/**
	 * This function is triggered early in the request if the "flush" query is set
	 * Clear out obsolete profile files
	 *
	 * @see FlushRequestFilter
	 */
	public static function flush()
	{
		// on first-time dev/build, ImageProfiles field doesn't exist yet so don't try doing anything with it
		if (DB::get_schema()->hasField('SiteConfig', 'ImageProfiles')) {

			// get current profiles and compare to old stored version, deleting obsolete profile images
			$profiles = ImageProfiles::getProfiles();
			$config = SiteConfig::current_site_config();
			$oldConfig = unserialize($config->ImageProfiles);
			if( is_array($oldConfig) ){
				foreach ($oldConfig as $profileName => $oldProfile) {
					if (!array_key_exists($profileName, $profiles) || $oldProfile != $profiles[$profileName]) {
						$path = Director::baseFolder() . '/assets/_profiles/' . $profileName;
						if( file_exists($path) ){
							// wipe contents, keep folder if current profile still exists
							Filesystem::removeFolder($path, array_key_exists($profileName, $profiles));
						}
					}
				}
			}

			// store new profile config
			$config->ImageProfiles = serialize($profiles);
			$config->write();
		}

	}

}
