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
	 * Ensure directory exists
	 * We have to do this prior to the actual HTTP request, otherwise we get 403 instead of 404 for non-existent dirs
	 * @param $rootPath root-relative path of file, eg "/assets/Uploads/foo.jpg"
	 */
	protected function verifyDirectoryExists($rootPath){
		$dirPath = dirname( Director::baseFolder() . $rootPath );
		if( !file_exists($dirPath) ){
			Filesystem::makeFolder($dirPath);
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
		$rootPath = '/assets/_profiles/'.$profileName.$relPath;
		if( array_key_exists($profileName, self::getProfiles())){
			$this->verifyDirectoryExists($rootPath);
		}
		return $rootPath;
	}

	public function Original(){
		return '<img src="'.$this->OriginalURL().'">';
	}

	public function OriginalURL(){
		$rootPath = '/' . $this->owner->getRelativePath();
		$this->verifyDirectoryExists($rootPath);
		return $rootPath;
	}

	/**
	 * Defines all possible methods for this class. Used to support wildcard methods
	 *
	 * @return array
	 */
	public function allMethodNames() {
		$methods = array (
			'profile',
			'profileurl',
			'original',
			'originalurl'
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
