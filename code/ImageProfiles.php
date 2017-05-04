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
	 * We have to do this prior to the actual HTTP request, otherwise we get 403 instead of 404 for non-existent dirs
	 * @param $rootPath string root-relative path of file, eg "/assets/Uploads/foo.jpg"
	 */
	protected static function ensureDirectoryExists($rootPath){
		$dirPath = dirname( Director::baseFolder() . $rootPath );
		if( !file_exists($dirPath) ){
			Filesystem::makeFolder($dirPath);
		}
	}

	/**
	 * Returns an <img> tag for the given URL
	 * @see Image::getTag()
	 * @param $url
	 * @return string
	 */
	protected function tagMarkup($url){
		$title = ($this->owner->Title) ? $this->owner->Title : $this->owner->Filename;
		if($this->owner->Title) {
			$title = Convert::raw2att($this->owner->Title);
		} else {
			if(preg_match("/([^\/]*)\.[a-zA-Z0-9]{1,6}$/", $title, $matches)) {
				$title = Convert::raw2att($matches[1]);
			}
		}
		return "<img src=\"$url\" alt=\"$title\" />";
	}

	/**
	 * Returns an <img> tag for the requested profile
	 * @param $profileName
	 * @return string
	 */
	public function Profile($profileName){
		return $this->tagMarkup( $this->ProfileURL($profileName) );
	}

	/**
	 * Returns a URL for the requested profile
	 * @param $profileName string profile name, or "Original"
	 * @return string
	 */
	public function ProfileURL($profileName){
		if( $profileName == 'Original' ){
			return $this->OriginalURL();
		}else{
			$relPath = substr($this->owner->getRelativePath(), 6); // lop off "assets"
			$rootPath = '/assets/_profiles/'.$profileName.$relPath;
			if( array_key_exists($profileName, self::getProfiles())){
				self::ensureDirectoryExists($rootPath);
			}
			return $rootPath;
		}
	}

	/**
	 * Returns an <img> tag for the original image
	 * @return string
	 */
	public function Original(){
		return $this->tagMarkup( $this->OriginalURL() );
	}

	/**
	 * Returns URL of original image
	 * @return string
	 */
	public function OriginalURL(){
		$rootPath = '/' . $this->owner->getRelativePath();
		self::ensureDirectoryExists($rootPath);
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
	 * @param $method string The method being called
	 * @param $args array The arguments to the method
	 * @return string
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
