#PHP Color extractor

PHP library for extracting  the colors of images.

Based on the work of [Csongor Zalatnai](http://www.phpclasses.org/browse/package/3370.html) and [Kepler Gelotte](http://www.coolphptools.com/color_extract).

I have improved the way the color are merged and created some utility functions


##Usage

1. include color-extractor.php
2. instantiate ColorExtractor `$extractor = new ColorExtractor($fidelity, $background);` optionaly informing the color fidelity (between 0 and 1) and color to use as background
3. call analyze `$extractor->analyze($imagePath, $reduceFlag);` where flag one of ColorExtractor::REDUCE_* constants
4. get result with getColors `$extractor->getColors()` to get colors objects or getColorSumary `$extractor->getColors()` to get only colors and colors and count

### TO DO list

- Add PHP Documentator comments
- Improve even more the color merge process, changing how color distance is calculated, check [Colors-Of-Image](https://github.com/humanmade/Colors-Of-Image)
