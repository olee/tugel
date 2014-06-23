<?php

namespace ssko\UtilityBundle\Core;

class DebugTranslator extends \Symfony\Bundle\FrameworkBundle\Translation\Translator {
	
	public function trans($id, array $parameters = array(), $domain = null, $locale = null) {
		if (null === $locale) {
			$locale = $this->getLocale();
		}
		if (null === $domain) {
			$domain = 'messages';
		}
		if (!isset($this->catalogues[$locale])) {
			$this->loadCatalogue($locale);
		}
		
		if (!$this->catalogues[$locale]->has((string)$id, $domain)) {
			$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
			
			$file = $trace[1]['file'];
			$line = $trace[1]['line'];
			
			$cls = isset($trace[2]['class']) ? $trace[2]['class'] : null;
			if ($cls) {
				$rCls = new \ReflectionClass($cls);
				if ($rCls->isSubclassOf('\Twig_Template')) {
					$tpl = $rCls->newInstanceWithoutConstructor();
					$debug = $rCls->getMethod('getDebugInfo')->invoke($tpl);
	                foreach ($debug as $codeLine => $templateLine) {
	                    if ($codeLine <= $line) {
	                        $line = $templateLine;
	                        break;
	                    }
	                }
					$file = $rCls->getMethod('getTemplateName')->invoke($tpl);
					$realFile = $this->container->get('twig.loader')->getCacheKey($file);
				}
			}
			
			$msg = "Translation for \"$id\" missing in $file at line $line";
			if (!empty($realFile)) {
				$lines = file($realFile);
				$msg .= '<br>' . trim(htmlspecialchars($lines[$line-1]));
			}
			$this->container->get('session')->getFlashBag()->add('debug-error', $msg);
		}
		
		return strtr($this->catalogues[$locale]->get((string)$id, $domain), $parameters);
	}
	
}
