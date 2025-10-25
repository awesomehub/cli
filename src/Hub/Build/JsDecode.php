<?php

declare(strict_types=1);

namespace Hub\Build;

use Symfony\Component\Serializer\Encoder\JsonDecode;

class JsDecode extends JsonDecode
{
    public function decode(string $data, string $format, array $context = []): mixed
    {
        // Strip off the JS export wrapper before decoding
        $data = preg_replace('/^\s*export\s+default\s+/i', '', $data);

        return parent::decode($data, 'json', $context);
    }
}
