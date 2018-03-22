<?php
namespace Api\Router\Response;

class Json extends AbstractFormat{
	
	public function getContent() {
		$response = $this->response;
		$data = array(
			'date' => self::getFormattedDate(),
			'status' => array(
				'code' => self::getStatusCode($response),
				'message' => self::getStatusMessage($response)
			),
			'result' => null
		);
		if (!$response->isValid()) {
			$data['status']['error'] = $response->getErrorCode();
		}
		$result = $response->get();
		$results = $response->getResults();
		if ($results) {
			$data['result'] = array();
			if (null !== $result) $data['result'][] = $result;
			foreach ($results as $result) {
				$data['result'][] = $result->get();
			}
		} else {
			$data['result'] = $result;
		}
		return json_encode($data);
	}
	
}