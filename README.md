# SilverStripe Image Profiles

## Description

Normally, when using images in a template such as with: `$Image.SetWidth(200)`, this image is immediately resized (if needed) on the main page request.  This can potentially make the page take a long time to load, or even timeout entirely, if there are lots of images that need to be processed.

This module tackles this by using pre-defined image profiles, and merely outputting URLs on the main page request (for images that may not exist yet).  This means the main page request loads without delay, and then the images are generated on-demand via 404 handling of separate HTTP file requests (eg from `<img>` tags).  Generated images are cached in `assets/_profiles` for subsequent requests.

## Usage

After installing the module, you can define profiles in config.yml:

```
ImageProfiles:
  profiles:
    Small:
      - SetWidth: 100
    Medium:
      - Quality: 95
      - SetWidth: 300
    Large:
      - SetWidth: 500
    PaddedRed:
      - SetWidth: 200
      - Pad: [200,200,'#f00']
```

Commands may be any normal Image manipulation methods, or the `Quality` keyword which will set the image quality for steps remaining in the current profile.

You can then use these profiles on any Image field:

```
$Image.Small    // output <img> tag
$Image.SmallURL // just get the URL 
```

You can also use Profile and ProfileURL methods, with the profile name as the parameter:

```
$Image.Profile(Small)    // output <img> tag
$Image.ProfileURL(Small) // just get the URL 
```

And finally, if you wish to output the original image, you can use Original and OriginalURL. This can be useful in conjunction with [AssetProxy](https://github.com/bcairns/silverstripe-assetproxy) to allow missing images to be fetched remotely.

```
$Image.Original    // output <img> tag
$Image.OriginalURL // just get the URL 
```

## Flushing

When making any changes to the defined profiles, you must `flush` for new settings to take effect.  This will also delete images in profiles that have changed.

## Known Issues

* If the source image is changed (while retaining an identical filename) the profile versions won't be cleared and re-generated

## Planned Improvements

* Allow default `_profiles` folder to be changed

## Acknowledgements

* This module is inspired by Drupal's image handling approach (which stems from the [ImageCache](https://www.drupal.org/project/imagecache) module).
* Big thanks to [unclecheese](https://github.com/unclecheese) for help getting magic methods working via an extension (see also [Zen Fields](https://github.com/unclecheese/silverstripe-zen-fields)).
