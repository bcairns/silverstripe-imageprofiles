<?php

/**
 * Class ImageProfiles
 * Allows image profiles to be defined in Config, then generate URLs for "lazy", on-demand image creation
 * Inspired by Drupal's image handling approach
 */
class ImageProfiles extends DataExtension
{

	/**
	 * @var array lowercase => normal-case lookup table for Profile names
	 */
	protected static $caseLookup = array();

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
	 * todo: make this less shitty
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

	/**
	 * Defines all possible methods for this class. Used to support wildcard methods
	 *
	 * @return array
	 */
	public function allMethodNames() {
		$methods = array (
			'profile',
			'profileurl'
		);
		// add profile names
		foreach( array_keys( self::getProfiles() ) as $profile ){
			$lcase = strtolower( $profile );
			self::$caseLookup[ $lcase ] = $profile;
			$methods[] = $lcase;
			$methods[] = $lcase.'url';
		}
		return $methods;
	}

	/**
	 * A wildcard method for accepting any Profile name as a method.
	 * Ex: Thumbnail(), Small(), Large(), etc
	 *
	 * @param   string The method being called
	 * @param   array The arguments to the method
	 * @return  FieldList
	 */
	public function __call($method, $args) {
		Debug::log("__call method: $method");
		if( substr( $method, -3 ) == 'url' ){
			$profile = self::$caseLookup[ substr( $method, 0, -3 ) ];
			$method = 'ProfileURL';
		}else{
			$profile = self::$caseLookup[ $method ];
			$method = 'Profile';
		}
		if( $profile ){
			return $this->$method( $profile );
		}
	}

}
