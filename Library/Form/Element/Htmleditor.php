<?php
namespace Form\Element;

require_once 'Library/Form/Element/Textarea.php';

class Htmleditor extends Textarea{

	private static $tinymce = array(
		'src' => '/js/tinymce/tinymce.min.js',
		'language' => 'ru',
		'force_br_newlines' => true,
		'force_p_newlines' => false,
		'relative_urls' => false,
		//remove_script_host: false,
		'extended_valid_elements' => 'i[class],span[class]',
		'forced_root_block' => '',
		'fontsize_formats' => '8pt 10pt 12pt 14pt 18pt 24pt 36pt',
		'plugins' => array(
			'advlist autolink lists link image charmap print preview anchor',
			'searchreplace visualblocks code fullscreen',
			'insertdatetime media table contextmenu paste moxiemanager textcolor'
		),
		'toolbar1' => 'fontselect fontsizeselect',
		'toolbar2' => 'insertfile undo redo | styleselect | bold italic | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image'
	);

	protected function prepareValue($value) {
		return trim((string)$value);
	}

	public function getHtml() {
        $html = parent::getHtml();
		if (null === ($config = $this->tinymce)) {
			 if (class_exists('Cfg', false) && isset(\Cfg::$tinymce)) {
				$config = \Cfg::$tinymce;
			} else {
				$config = self::$tinymce;
			}
		}
		if (isset($config['src']) && $config['src']) {
			if (!defined('FORM_ELEMENT_HTMLEDITOR_READY')) {
				define('FORM_ELEMENT_HTMLEDITOR_READY', 1);
				$html .= '<script type="text/javascript" src="' . $config['src'] . '"></script>';
			}
			unset($config['src']);
		}
		$config['selector'] = '#' . $this->id;
		$html .= '<script type="text/javascript">tinymce.init(' . json_encode($config) . ');</script>';
		return $html;
	}

}
