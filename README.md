# SilverStripe Image Profiles

## Description

Normally, when using images in a template such as with: `$Image.SetWidth(200)`, this image is immediately resized (if needed) on the main page request.  This can potentially make the page take a long time to load, or even timeout entirely, if there are lots of images that need to be processed.

This module tackles this by using pre-defined image profiles, and merely outputting URLs on the main page request (for images that may not exist yet).  This means the main page request loads without delay, and then the images are generated on-demand, on subsequent file requests (eg from `<img>` tags).  Generated images are stored in `assets/_profiles`.

## Usage

After installing the module, you can define profiles in config.yml:

```
ImageProfiles:
  profiles:
    Small:
      - SetWidth: 100
    Medium:
      - SetWidth: 300
    Large:
      - SetWidth: 500
    PaddedRed:
      - SetWidth: 200
      - Pad: [200,200,'#f00']
```

You can then use these profiles on any Image field:

```
$Image.Profile(Small)    // output <img> tag
$Image.ProfileURL(Small) // just get the URL 
```

## Flushing

When making any changes to the defined profiles, you must `flush` for new settings to take effect.  This will also delete images in profiles that have changed.

## Known Issues

* If the source image is changed (while retaining identical filename) the profile versions won't be cleared and re-generated

## Planned Improvements

* Make `<img>` output better and more customizable
* Allow default `_profiles` folder to be changed
* Allow access via `$Image.Small` instead of `$Image.Profile(Small)` (unfortunately this has proven impossible so far, cannot add arbitrary methods at runtime)

## Acknowledgements

This module is inspired by Drupal's image handling approach (which stems from the [ImageCache](https://www.drupal.org/project/imagecache) module).
