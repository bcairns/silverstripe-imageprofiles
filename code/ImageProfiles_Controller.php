<?php

/**
 * Class ImageProfiles_Controller
 * Handles requests for non-existent images in assets/Profiles/ by intercepting 404 errors
 */
class ImageProfiles_Controller extends Extension
{

	public function onBeforeHTTPError404($request){

		$url = $request->getURL();
		if( substr($url,0,17) == 'assets/_profiles/'){

			$parts = explode('/',$url);
			$profile = $parts[2];

			if( !array_key_exists( $profile, ImageProfiles::getProfiles() ) ){
				// if requested profile doesn't exist, just return for normal 404
				return;
			}

			$relPath = substr( $url, 17 + strlen($profile) );

			if( $this->applyImageProfile( $profile, $relPath ) ){
				$newPath = '../assets/_profiles/'.$profile.$relPath;
				header('Content-Type: '.HTTP::get_mime_type( $newPath ) );
				header('Content-Length: '.filesize( $newPath ) );
				readfile( $newPath );
				exit;
			}else{
				return;
			}

		}

	}


	/**
	 * Apply profile commands to image and save resulting file in Profiles
	 * @param $profile: image profile to use
	 * @param $path: source image path, relative to ../assets/Uploads
	 */
	protected function applyImageProfile( $profile, $path ){

		//Debug::log(print_r(Config::inst()->get('Image', 'profiles'), 1));

		$image = Image::create();

		$source = 'assets' . $path;
		$dest = 'assets/_profiles/'.$profile . $path;

		$steps = ImageProfiles::getProfiles()[$profile];

		$quality = false;

		foreach( $steps as $step ){

			$format = array_keys( $step )[0];
			if( is_array( $step[$format] ) ){
				$args = array_merge( array($format), $step[$format] );
			}else{
				$args = array( $format, $step[$format] );
			}

			if( $format == 'Quality' ){
				$quality = $args[1];
				continue;
			}

			$backend = Injector::inst()->createWithArgs($image::config()->backend, array(
				Director::baseFolder()."/" . $source,
				$args
			));

			if( $quality ){
				$backend->setQuality( $quality );
			}

			if($backend->hasImageResource()) {

				$generateFunc = "generate$format";
				if($image->hasMethod($generateFunc)){

					array_shift($args);
					array_unshift($args, $backend);

					$backend = call_user_func_array(array($image, $generateFunc), $args);
					if($backend){
						$backend->writeTo(Director::baseFolder()."/" . $dest);

						// set source to dest for additional steps (keep overwriting self)
						$source = $dest;
					}

				} else {
					user_error("Image::generateFormattedImage - Image $format public function not found.",E_USER_WARNING);
				}
			}else{
				return false;
			}
		}

		return true;

	}


}
