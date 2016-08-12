<?php

namespace CloudinaryExtension\Image\Transformation;

class Dimensions
{
    private $width;

    private $height;

    private function __construct($width, $height)
    {
        $this->width = is_null($width) ? null : (int) round($width);
        $this->height = is_null($height) ? null : (int) round($height);
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public static function fromWidthAndHeight($width, $height)
    {
        return new Dimensions($width, $height);
    }

    public static function null()
    {
        return new Dimensions(null, null);
    }
}
