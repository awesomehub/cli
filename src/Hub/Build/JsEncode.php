<?php

declare(strict_types=1);

namespace Hub\Build;

use Symfony\Component\Serializer\Encoder\JsonEncode;

class JsEncode extends JsonEncode
{
    public function encode(mixed $data, string $format, array $context = []): string
    {
        // Use parent JsonEncoder to generate proper JSON
        $json = parent::encode($data, 'json', $context);

        // Wrap it as a JavaScript ES module
        return 'export default '.$json;
    }
}
