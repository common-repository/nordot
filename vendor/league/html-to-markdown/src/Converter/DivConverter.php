<?php

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\Configuration;
use League\HTMLToMarkdown\ConfigurationAwareInterface;
use League\HTMLToMarkdown\ElementInterface;

class DivConverter implements ConverterInterface, ConfigurationAwareInterface
{
    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @param Configuration $config
     */
    public function setConfig(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * @param ElementInterface $element
     *
     * @return string
     */
    public function convert(ElementInterface $element)
    {
    		if (strpos($element->getAttribute('class'), 'wp-block-embed__wrapper') !== false) {
    			$link = $element->getValue();
    			
                // Make sure the $link is an actual link
                if (strlen($link) > 0 && substr( $link, 0, 4 ) !== "http") return $element->getValue() ;
    			
                $parent = $element->getParent();
    			if ($parent != null) {
    				$class = $parent->getAttribute('class');
    				$classArray = explode(' ', $class);
    				
    				if (in_array('wp-block-embed-twitter', $classArray)) {
    					return '[[embed-twitter]](' . $link . ')';
    				}
    				
    				if (in_array('wp-block-embed-facebook', $classArray)) {
    					return '[[embed-facebook]](' . $link . ')';
    				}
    				
    				if (in_array('wp-block-embed-instagram', $classArray)) {
    					return '[[embed-instagram]](' . $link . ')';
    				}
    				
    				/* This is handled by IFrame converter
    				 if (in_array('is-provider-youtube', $classArray)) {
    					return '[[embed-youtube]](' . $link . ')';
    				}*/
    				
    				if (in_array('is-provider-vimeo', $classArray)) {
    					return '[[embed-vimeo]](' . $link . ')';
    				}
    				
    				if (in_array('is-provider-brightcove', $classArray)) {
    					return '[[embed-brightcove]](' . $link . ')';
    				}
    			}
    		}

    		if (!$this->config->getOption('strip_tags')) {
    		//	return "~~~\n" . $element->getValue() . "~~~\n";
            }
        return $element->getValue() ;
        //return html_entity_decode($element->getChildrenAsString());
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('div');
    }
}
