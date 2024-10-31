<?php

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\ElementInterface;


require_once(WP_PLUGIN_DIR . '/nordot/nordot-common.php');

class ImageConverter implements ConverterInterface
{
    /**
     * @param ElementInterface $element
     *
     * @return string
     */
    public function convert(ElementInterface $element)
    {
        $src = $element->getAttribute('src');

        $imageId = nordot_upload_image($src);
        if ($imageId >= 0) {
        		return '[[image]](' . $imageId . ')';
        }
        
        return '';
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('img');
    }
}
