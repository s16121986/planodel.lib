<?php
namespace Validator\File;

use File\AbstractFile;
use Validator\AbstractValidator;

class MimeType extends AbstractValidator{



	public function isValid($value) {
		if ($value instanceof AbstractFile) {
			if (in_array($value->mime_type, $this->mimeType)) {
				return true;
			}
		}
		return false;
	}

}