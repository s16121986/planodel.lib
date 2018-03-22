<?php
namespace Navigation;

use Navigation;

class Breadcrumbs extends Navigation{
	
	protected $separator = ' | ';
	protected $minDepth = 1;
	protected $linkLast = false;
	
	public function setSeparator($separator) {
		$this->separator = $separator;
		return $this;
	}
	
	public function setMinDepth($depth) {
		$this->minDepth = $depth;
		return $this;
	}
	
	public function setLinkLast($flag) {
		$this->isLinkLast = (bool)$flag;
		return $this;
	}
	
	public function render() {
		$html = '';
		if (($count = $this->count()) > $this->minDepth) {
			$html = '<nav class="breadcrumbs">';
			$menu = array();
			foreach ($this->toArray() as $i => $item) {
				if ((false === $this->linkLast && ($i + 1) == $count) || !isset($item['href'])) {
					$menu[] = $item['label'];
				} else {
					$menu[] = '<a href="' . $item['href'] . '">' . $item['label'] . '</a>';
				}
			}
			$html .= implode($this->separator, $menu);
			$html .= '</nav>';
		}
		return $html;
	}
	
}