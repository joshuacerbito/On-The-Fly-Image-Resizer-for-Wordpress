# On-The-Fly Image Resizer for Wordpress
### Version 1.1

* Author: Joshua Cerbito
* Website: [Joshua Cerbito's Blog](http://medium.com/@joshcerbito)

An alternative (and a heavily-inspired) script to Aqua Resize (https://github.com/syamilmj/Aqua-Resizer).


## How to use

Just copy `otf_image_resize.php` into your theme and reference it from your functions.php file e.g. `require_once('otf_image_resize.php');`, and that's it.

You can then use it in your theme as such (simplest way to use it):
```
otf_image_resize($img_obj, $width);
```


## License

[WTFPL](http://sam.zoy.org/wtfpl/)

Do whatever you want with it. A linkback would be nice :)


## Contacts

Twitter: http://twitter.com/joshuacerbito


## Changelog

**v1.2**
- Updated $wpdb prepare query to accomodate guid or id checking
- Fixed "returning array even on $return_object = false" bug

**v1.1**
- Added readme.md

**v1.0**
- Initial commit
