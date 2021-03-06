<?php


require_once("classes/resources/Field.php");
$field = new Field();

switch (strtolower($this->request->getMethod()))
{
	case 'get':
		$field->loadFromUri($this->request->getURI());
        $field->buildJSON();
		$this->response->setPayload($field->getJSON());	
		$this->setStatus(true);
		break;
	case 'put':
	case 'post':
		if ($field->loadFromPayload($this->request->getPayload()))
		{
			if ($field->save())
			{
				$this->setStatus(true);
				break;
			}
		}
		$this->setStatus(false);
		break;
	case 'delete':
		$field->loadFromUri($this->request->getURI());
		if ($field->delete())
		{
			$this->setStatus(true);
			break;
		}
		$this->setStatus(false);
		break;
}
?>
