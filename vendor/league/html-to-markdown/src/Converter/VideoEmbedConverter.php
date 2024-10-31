<?php

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\ElementInterface;


class VideoEmbedConverter implements ConverterInterface
{
    /**
     * @param ElementInterface $element
     *
     * @return string
     */
    public function convert(ElementInterface $element)
    {    		    		
    		return $element->getValue();
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('figure');
    }
}
