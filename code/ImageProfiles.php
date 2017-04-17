<?php

/**
 * Class ImageProfiles
 * Allows image profiles to be defined in Config, then generate URLs for "lazy", on-demand image creation
 * Inspired by Drupal's image handling approach
 */
class ImageProfiles extends DataExtension
{

	/**
	 * Returns image profiles defined in Config, or empty array() if not found
	 * @return array
	 */
	public static function getProfiles(){
		$profiles = Config::inst()->get(__CLASS__,'profiles');
		return is_array($profiles) ? $profiles : array();
	}

	/**
	 * Ensure directory for this file exists in _profiles
	 * We have to do this prior to the actual HTTP request, otherwise we get 403 instead of 404 for non-existent dirs
	 * @param $relPath path of file relative to assets/Uploads, includes starting slash
	 */
	protected function verifyDirectoryExists($profile, $relPath){
		// don't create folders for non-existent Profiles
		if( array_key_exists($profile, self::getProfiles())){
			$fullPath = Director::baseFolder() . '/assets/_profiles/' . $profile . $relPath;
			$dirPath = dirname( $fullPath );
			if( !file_exists($dirPath) ){
				Filesystem::makeFolder($dirPath);
			}
		}
	}

	/**
	 * Returns an <img> tag for the requested profile
	 * @param $profileName
	 * @return string
	 */
	public function Profile($profileName){
		return '<img src="'.$this->ProfileURL($profileName).'">';
	}

	/**
	 * Returns a URL for the requested profile
	 * @param $profileName
	 * @return string
	 */
	public function ProfileURL($profileName){
		$relPath = substr($this->owner->getRelativePath(), 6); // lop off "assets"
		$this->verifyDirectoryExists($profileName, $relPath);
		return '/assets/_profiles/'.$profileName.$relPath;
	}


}
