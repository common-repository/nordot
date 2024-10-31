<?php

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\ElementInterface;

class StrikeThroughConverter implements ConverterInterface
{
    /**
     * @param ElementInterface $element
     *
     * @return string
     */
    public function convert(ElementInterface $element)
    {
        $text = trim($element->getValue(), "\t\n\r\0\x0B");

        return '~~' . $text . '~~';
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('del');
    }
}
