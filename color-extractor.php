<?php

/**
 * This package can be used to get the most common colors in an image.
 *
 * @author     Pedro Sanção (pedro at sancao dot co)
 * @licence     GNU General Public Licence V2
 *
 * Based on the work of:
 * - Csongor Zalatnai (http://www.phpclasses.org/browse/package/3370.html)
 * - Kepler Gelotte (http://www.coolphptools.com/color_extract)
 *
 */
class ColorInfo {

    public $r;
    public $g;
    public $b;
    public $a;
    public $rgb;
    public $count;
    public $percentage;

    public function __construct($rgbHex = '') {
        if (preg_match('/^#?[A-F0-9]{6}$/i', $rgbHex)) {
            $rgb = str_split(ltrim($rgbHex, '#'), 2);
            $this->rgb = join($rgb);
            $this->r = hexdec($rgb[0]);
            $this->g = hexdec($rgb[1]);
            $this->b = hexdec($rgb[2]);
        } else {
            $this->rgb = '';
            $this->r = 0;
            $this->g = 0;
            $this->b = 0;
        }
        $this->a = 0;
        $this->count = 0;
        $this->percentage = 0.0;
    }

    public static function makeHex($r, $g, $b) {
        $color = str_pad(dechex($r), 2, '0', STR_PAD_LEFT);
        $color .= str_pad(dechex($g), 2, '0', STR_PAD_LEFT);
        $color .= str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
        return $color;
    }

    public function toHex() {
        return self::makeHex($this->r, $this->g, $this->b);
    }
    
    public function toHSL() {
        $r = $this->r / 255;
        $g = $this->g / 255;
        $b = $this->b / 255;
        $rgb = array($r, $g, $b);
        $max = max($rgb);
        $min = min($rgb);
        $chroma = $max - $min;

        if ($chroma == 0) {
            $hue = 0;
        }
        elseif ($max === $r) {
            $hue = fmod(( $g - $b ) / $chroma + 6, 6) * 60;
        }
        elseif ($max === $g) {
            $hue = fmod(( $b - $r ) / $chroma + 8, 6) * 60;
        }
        else {
            $hue = fmod(( $r - $g ) / $chroma + 10, 6) * 60;
        }
        $level = ($max + $min) / 2; // HSL mode
        //$value = $max; // HSV mode
        $saturation = 0;
        if ($chroma > 0) {
            //$saturation = $chroma / $value; // HSV mode
            $saturation = $chroma / ( 1 - abs(2 * $level - 1) ); // HSL mode
        }
        return array($hue, $saturation, $level);
    }

}

class ColorUtility {

    protected $colorStep;
    protected $debugMode = false;

    public function __construct($fidelity = 0) {
        // invert and prevent overflowed values
        $fidelity = 1 - min(max($fidelity, 0), 1);
        $this->colorStep = $fidelity * 50 + 1;
    }

    public function debug() {
        $this->debugMode = true;
    }

    protected function roundColorChannel($color) {
        return min(round($color / $this->colorStep) * $this->colorStep, 255);
    }

    public function roundColor(ColorInfo &$color) {
        $color->r = $this->roundColorChannel($color->r);
        $color->g = $this->roundColorChannel($color->g);
        $color->b = $this->roundColorChannel($color->b);
        $color->rgb = $color->toHex();
    }

    protected function getChannelVariations($value) {
        $step = $this->colorStep;
        return array(
                $value - $step,
                $value,
                $value + $step
        );
    }

    public function getLighterTints(ColorInfo &$color, $ignoreGrays = false) {
        $this->roundColor($color);
        $colors = array();
        $rgb = array($color->r, $color->g, $color->b);
        $max = max($rgb);
        $min = min($rgb);

        // treat black, white and gray
        if($min == 255 or $max == 0 or ($max == $min and $ignoreGrays)) {
            return array($color->toHex());
        }
        
        if ($max == 255) { // find second max, plus colorstep
           sort($rgb); // sort asc
           while($max = array_pop($rgb) and $max == 255);
           $max += $this->colorStep;
        }

        $limit = 255 - $max;
        for ($i = 0; $i <= $limit; $i += $this->colorStep) {
            $r = min(255, $color->r + $i);
            $g = min(255, $color->g + $i);
            $b = min(255, $color->b + $i);
            $rgb = ColorInfo::makeHex($r, $g, $b);
            array_push($colors, $rgb);
        }
        if (empty($colors)) {
            $colors = array($color->rgb);
        }
        return $colors;
    }

    public function getAllTints(ColorInfo &$color, $ignoreGrays = false) {
        return $this->getTints($color, $ignoreGrays, 0);
    }

    public function getTints(ColorInfo &$color, $ignoreGrays = false, $limit = 0) {
        $this->roundColor($color);
        $colors = array();
        $rgb = array($color->r, $color->g, $color->b);
        $max = max($rgb);
        $min = min($rgb);

        // treat black, white and gray
        if($min == 255 or $max == 0 or ($max == $min and $ignoreGrays)) {
            return array($color->toHex());
        }
        
        if ($max == 255) { // find second max, plus colorstep
           sort($rgb); // sort asc
           while($max = array_pop($rgb) and $max == 255);
           $max += $this->colorStep;
        }
        
        $start = max(-$min, $limit > 0 ? -$limit * $this->colorStep : -255);
        $topLimit = min(255 - $max, $limit > 0 ? $limit * $this->colorStep : 255);
        for ($i = $start; $i <= $topLimit; $i += $this->colorStep) { // change to "$i = $this->colorStep" to exclude edge (#00)
            $r = min(255, $color->r + $i);
            $g = min(255, $color->g + $i);
            $b = min(255, $color->b + $i);
            $colors[] = ColorInfo::makeHex($r, $g, $b);
        }
        if (empty($colors)) {
            $colors = array($color->rgb);
        }
        return $colors;
    }

    public function getGradients(ColorInfo &$color, $ignoreGrays = true) {
        return $this->getGradientVariations($color, $ignoreGrays);
    }
    
    public function getMoreGradients(ColorInfo &$color, $ignoreGrays = true) {
        return $this->getGradientVariations($color, $ignoreGrays, 1);
    }
    
    private function getGradientVariations(ColorInfo &$color, $ignoreGrays = true, $keepChannels = 2) {
        $this->roundColor($color);
        $colors = array();
        $rgb = array($color->r, $color->g, $color->b);
        $max = max($rgb);
        $min = min($rgb);

        // treat black, white and gray
        if($min == 255 or $max == 0 or ($max == $min and $ignoreGrays)) {
            return array($color->toHex());
        }

        $rs = $this->getChannelVariations($color->r);
        $gs = $this->getChannelVariations($color->g);
        $bs = $this->getChannelVariations($color->b);
        foreach ($rs as $ri => $r) {
            foreach ($gs as $gi => $g) {
                foreach ($bs as $bi => $b) {
                    $indexes = array(0, 1, 2, $ri, $gi, $bi);
                    $indexCount = array_count_values($indexes);
                    $curMax = max($r, $g, $b);
                    $curMin = min($r, $g, $b);
                    if ($indexCount[1] < $keepChannels+1 or $curMax > 255 or $curMin < 0 or $curMax === $curMin) {
                        continue;
                    }
                    array_push($colors, ColorInfo::makeHex($r, $g, $b));
                }
            }
        }
        return $colors;
    }

    public function getGradientTints(ColorInfo &$color, $ignoreGrays = true) {
        $this->roundColor($color);
        $tints = $this->getLighterTints($color, $ignoreGrays);
        $gredients = $this->getGradients($color, $ignoreGrays);
        $return = array_merge($tints, $gredients);
        sort($return);
        return array_unique($return);
    }

}

class ColorExtractor extends ColorUtility {

    protected $colors;
    protected $colorSumary;
    protected $imagePath;
    protected $pixelsCount;
    protected $backgroundColor;

    protected $previewWidth = 200;
    protected $previewHeight = 200;

    const REDUCE_NONE = 0;
    const REDUCE_TINTS = 1;
    const REDUCE_ALL_TINTS = 2;
    const REDUCE_GRADIENTS = 3;

    public function __construct($fidelity = 0, $backgroundColor = 'FFFFFF') {
        parent::__construct($fidelity);
        $this->colors = array();
        $this->colorSumary = array();
        $this->pixelsCount = 0;
        $this->backgroundColor = $backgroundColor;
    }

    public function getColors() {
        return $this->colors;
    }

    public function getColorSumary() {
        return $this->colorSumary;
    }

    protected function reset() {
        $this->imagePath = '';
        $this->colors = array();
        $this->colorSumary = array();
        $this->pixelsCount = 0;
    }

    protected static function colorSort($a, $b) {
        $a = $a->count;
        $b = $b->count;
        if ($a == $b) {
            return 0;
        }
        return ($a < $b) ? 1 : -1;
    }

    protected function calculatePercentage() {
        foreach ($this->colors as $rgb => &$color) {
            $color->percentage = round($color->count / $this->pixelsCount, 2);
            $this->colorSumary[$rgb] = $color->percentage;
        }
    }

    protected function filterColors(ColorInfo $color, $colorSet) {
        while (list($index, $rgb) = each($colorSet)) {
            if (!array_key_exists($rgb, $this->colors) or $rgb == $color->rgb) {
                unset($colorSet[$index]);
            }
        }
        return $colorSet;
    }

    protected function findColors(ColorInfo &$color, $callback) {
        $colors = call_user_func_array(array($this, $callback), array($color, true));
        return $this->filterColors($color, $colors);
    }

    protected function reduce($callback) {
        $color = new ColorInfo('333333');
        $skip = max(round(count($this->colors) / 5), count($this->getAllTints($color)) + 1);
        $loopCount = 0;
        while (list($rgb, $color) = each($this->colors)) {
            if ($loopCount++ < $skip) {
                continue;
            }
            $colorVariations = $this->findColors($color, $callback);
            foreach ($colorVariations as $colorVariation) {
                if($this->colors[$rgb]->count > $this->colors[$colorVariation]->count) {
                    $this->colors[$rgb]->count += $this->colors[$colorVariation]->count;
                    $this->colorSumary[$rgb] = $this->colors[$rgb]->count;
                    unset($this->colors[$colorVariation], $this->colorSumary[$colorVariation]);
                }
            }
        }
    }

    protected function reduceProcess($callback) {
        $color = new ColorInfo('333333');
        $skip = max(round(count($this->colors) / 5), count($this->getAllTints($color)) + 1);
        $loopCount = 0;
        while (list($rgb, $color) = each($this->colors)) {
            if ($loopCount++ < $skip) {
                continue;
            }
            $colorVariations = $this->findColors($color, $callback);
            printf('<table><tr><td%s style="background-color:#%s">&nbsp;</td>', count($colorVariations) ? ' rowspan="' . count($colorVariations) . '"' : '', $rgb);
            $c = 0;
            foreach ($colorVariations as $colorVariation) {
                if($this->colors[$rgb]->count > $this->colors[$colorVariation]->count) {
                    if ($c++ == 0)
                        printf('<td style="background-color:#%s">&nbsp;</td></tr>', $colorVariation);
                    else
                        printf('<tr><td style="background-color:#%s">&nbsp;</td></tr>', $colorVariation);

                    $this->colors[$rgb]->count += $conta = $this->colors[$colorVariation]->count;
                    $this->colorSumary[$rgb] = $this->colors[$rgb]->count;
                    unset($this->colors[$colorVariation], $this->colorSumary[$colorVariation]);
                }
            }
            echo count($colorVariations) ? '' : '</tr>', '</table>';
        }
        printf('<img src="%s" height="400"/>', $this->imagePath);
    }

    protected function reduceCall($callback) {
        if (!$this->debugMode) {
            $this->reduce($callback);
        } else {
            $this->reduceProcess($callback);
        }
    }

    public function analyze($imagePath, $reduceFlag = self::REDUCE_TINTS) {
        $this->reset();
        if (!is_readable($imagePath)) {
            return false;
        }
        $this->imagePath = $imagePath;
        $imageInfo = getimagesize($imagePath);
        if($imageInfo === false) {
            return false;
        }
        $scale = min($this->previewWidth / $imageInfo[0], $this->previewHeight / $imageInfo[1]);
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        // resizing, only need the most signifcant colors
        if ($scale < 1) {
            $width = floor($scale * $imageInfo[0]);
            $height = floor($scale * $imageInfo[1]);
        }

        $imageNew = imagecreatetruecolor($width, $height);
        if ($imageInfo[2] == 1) {
            $imageOrig = imagecreatefromgif($imagePath);
        } elseif ($imageInfo[2] == 2) {
            $imageOrig = imagecreatefromjpeg($imagePath);
        } elseif ($imageInfo[2] == 3) {
            $imageOrig = imagecreatefrompng($imagePath);
        }
        imagecopyresampled($imageNew, $imageOrig, 0, 0, 0, 0, $width, $height, $imageInfo[0], $imageInfo[1]);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $color = new ColorInfo();
                $colorIndex = imagecolorat($imageNew, $x, $y);
                $color->r = ($colorIndex & 0x00FF0000) >> 16;
                $color->g = ($colorIndex & 0x0000FF00) >> 8;
                $color->b = ($colorIndex & 0x000000FF) >> 0;
                $color->a = ($colorIndex & 0x7F000000) >> 24;

                $this->roundColor($color);

                // ignore this pixel
                // verify alpha channel and background
                if ($color->a > 64 or strcasecmp($color->rgb, $this->backgroundColor) == 0) {
                    continue;
                } else {
                    $this->pixelsCount++;
                }

                if (isset($this->colors[$color->rgb])) {
                    $this->colors[$color->rgb]->count++;
                    $this->colorSumary[$color->rgb]++;
                } else {
                    $color->count = 1;
                    $this->colors[$color->rgb] = $color;
                    $this->colorSumary[$color->rgb] = 1;
                }
                unset($colorIndex, $color);
            }
        }
        uasort($this->colors, array('self', 'colorSort'));
        arsort($this->colorSumary);

        switch ($reduceFlag) {
            case self::REDUCE_TINTS:
                $this->reduceCall('getLighterTints');
                break;
            case self::REDUCE_ALL_TINTS:
                $this->reduceCall('getAllTints');
                break;
            case self::REDUCE_GRADIENTS:
                $this->reduceCall('getGradients');
                break;
            case self::REDUCE_NONE:
            default:
                break;
        }
        uasort($this->colors, array('self', 'colorSort'));
        arsort($this->colorSumary);

        $this->calculatePercentage();

        return true;
    }

}

class ColorExtractorDebugger extends ColorExtractor {

    public function showColors($imagePath, ColorInfo $color, $reduceFlag = self::REDUCE_TINTS) {
        if (!is_readable($imagePath)) {
            return false;
        }
        $imageInfo = getimagesize($imagePath);
        if($imageInfo === false) {
            return false;
        }
        $scale = min($this->previewWidth / $imageInfo[0], $this->previewHeight / $imageInfo[1]);
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        // resizing, only need the most signifcant colors
        if ($scale < 1) {
            $width = floor($scale * $imageInfo[0]);
            $height = floor($scale * $imageInfo[1]);
        }

        switch ($reduceFlag) {
            default:
            case self::REDUCE_TINTS:
                $match = $this->getLighterTints($color, true);
                break;
            case self::REDUCE_ALL_TINTS:
                $match = $this->getAllTints($color, true);
                break;
            case self::REDUCE_GRADIENTS:
                $match = $this->getGradients($color, true);
                break;
        }

        $imageNew = imagecreatetruecolor($width, $height);
        if ($imageInfo[2] == 1) {
            $imageOrig = imagecreatefromgif($imagePath);
        } elseif ($imageInfo[2] == 2) {
            $imageOrig = imagecreatefromjpeg($imagePath);
        } elseif ($imageInfo[2] == 3) {
            $imageOrig = imagecreatefrompng($imagePath);
        }
        imagecopyresampled($imageNew, $imageOrig, 0, 0, 0, 0, $width, $height, $imageInfo[0], $imageInfo[1]);

        $remove = imagecolorallocate($imageNew, 255, 0, 255);
        
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $color = new ColorInfo();
                $colorIndex = imagecolorat($imageNew, $x, $y);
                $color->r = ($colorIndex & 0x00FF0000) >> 16;
                $color->g = ($colorIndex & 0x0000FF00) >> 8;
                $color->b = ($colorIndex & 0x000000FF) >> 0;

                $this->roundColor($color);

                if (!in_array($color->rgb, $match)) {
                    imagesetpixel($imageNew, $x, $y, $remove);
                }
                imagecolortransparent($imageNew, $remove);
                unset($colorIndex, $color);
            }
        }
        ob_start();
        imagepng($imageNew);
        $return = ob_get_clean();
        ob_end_clean();
        return $return;
    }

}
