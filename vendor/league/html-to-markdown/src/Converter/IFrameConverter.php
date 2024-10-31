<?php

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\Configuration;
use League\HTMLToMarkdown\ConfigurationAwareInterface;
use League\HTMLToMarkdown\ElementInterface;

class IFrameConverter implements ConverterInterface, ConfigurationAwareInterface
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
    		$link = $element->getAttribute('src');

    		if (strpos($link, 'youtube') !== false) {
    			$arr = explode("/",$link);
    			return '[[embed-youtube]](https://www.youtube.com/watch?v=' . $arr[count($arr) - 1] . ')';
    		}
    		
    		if (strpos($link, 'open.spotify.com') !== false) {
    			$urlArray = explode("/", $link);
    			$spotifyId = $urlArray[count($urlArray) - 1];
    			
    			
    			if (strpos($link, 'embed/track') !== false) {
    				return '[[embed-spotify]](spotify:track:'. $spotifyId . ')';
    			}
    			
    			if (strpos($link, 'embed/album') !== false) {    				
    				return '[[embed-spotify]](spotify:album:'. $spotifyId . ')';
    			}
    			
    			if (strpos($link, 'embed/artist') !== false) {
    				return '[[embed-spotify]](spotify:artist:'. $spotifyId . ')';
    			}
    			
    			if (strpos($link, 'embed/playlist') !== false) {
    				return '[[embed-spotify]](spotify:playlist:'. $spotifyId . ')';
    			}
    		}
    			
    		return $element->getValue();
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('iframe');
    }
}
