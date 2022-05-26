<?php

namespace App;

use App\Bounds\Exception;
use JsonSchema\Validator;

class Bounds 
{
  protected $_bounds;

  public static function fromJsonString(string $input): self
  {
    $self = new self();
    $self->setBounds($self->json_decode($input));
    return $self;
 }

  public function getPgOptions(): array
  {
    if (!$this->_bounds) throw new Exception("bounds are not set", 400);
    return [
      ':sw_lat' => $this->_bounds['bounds']['_southWest']['lat'],
      ':sw_lng' => $this->_bounds['bounds']['_southWest']['lng'],
      ':ne_lat' => $this->_bounds['bounds']['_northEast']['lat'],
      ':ne_lng' => $this->_bounds['bounds']['_northEast']['lng'],
    ];
  }

  public function getCacheId($prefix = ""): string
  {
    return md5($prefix. serialize($this->getPgOptions()));
  }

  public function setBounds(array $bounds): self
  {
      $validator = new Validator();
      try {
        $validator->validate($bounds, (object)['$ref' => 'file://' . APP_ROOT . '/var/payload-schema.json']);
      } catch (\Exception $e) {
          throw new Exception("JSON schema validator error", 500);
      }

      if ($validator->isValid()) {
        $this->_bounds = $bounds;
        return $this;
      }

      $errors = [];
      foreach ($validator->getErrors() as $error) {
        $errors[] = $error['property']? "[{$error['property']}] " : "" . $error['message'];
      }
      throw new Exception("invalid payload:\n". implode("\n", $errors));
  }

  protected function json_decode($input): array {
      if (!$input) throw new Exception("empty payload");
      $data = @json_decode($input, true, 4,  JSON_INVALID_UTF8_IGNORE | JSON_BIGINT_AS_STRING );
      if (null == $data)  throw new Exception("invalid JSON payload");
      return $data;
  }

}
