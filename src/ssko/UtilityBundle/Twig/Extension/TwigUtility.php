<?php

namespace ssko\UtilityBundle\Twig\Extension;

use Twig_Extension;
use Twig_Filter_Method;

class TwigUtility extends Twig_Extension {

	public function getFilters() {
		return array(
			'get_class' => new Twig_Filter_Method($this, 'get_class'),
			'nl2p' => new Twig_Filter_Method($this, 'nl2p', array(
				'pre_escape' => 'html',
				'is_safe' => array('html'),
			)),
			'readmore' => new Twig_Filter_Method($this, 'readmore', array(
				'pre_escape' => 'html',
				'is_safe' => array('html'),
			)),
			'json_decode' => new Twig_Filter_Method($this, 'json_decode'),
		);
	}

	public function get_class($arg1) {
		//return basename(get_class($arg1));
		$s = get_class($arg1);
		return substr($s, strrpos($s, '\\') + 1, strlen($s));
	}

	public function nl2p($arg1) {
		return '<p>' . nl2br(preg_replace('/\n\n/', '</p><p>', $arg1)) . '</p>';
	}

	public function readmore($arg1, $length = 140) {
		$showLabel = 'weiterlesen';
		$hideLabel = '[ausblenden]';
		$showTag = '&hellip;<a class="link" href="javascript:void();" onclick="var p=$(this).parent().parent(); p.css({display:\'none\'}); p.next().css({display:\'\'});">' . $showLabel . '</a>';
		$hideTag = '<a class="link" href="javascript:void();" onclick="var p=$(this).parent().parent(); p.css({display:\'none\'}); p.prev().css({display:\'\'});">' . $hideLabel . '</a>';
		return '<div><p>' . (substr($arg1, 0, $length) . $showTag) . '</p></div>' . '<div style="display:none;">' . $this->nl2p($arg1 . $hideTag) . '</div>';
	}

	public function json_decode($arg1) {
		return json_decode($arg1);
	}
	
	public function getName() {
		return 'twig_extension';
	}

}
