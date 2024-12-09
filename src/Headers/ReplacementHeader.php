<?php

namespace Spatie\MailcoachMailer\Headers;

use Symfony\Component\Mime\Header\UnstructuredHeader;

class ReplacementHeader extends UnstructuredHeader
{
    protected string $key;

    public function __construct(string $key, string|array|null $value)
    {
        $this->key = $key;

        parent::__construct("X-Mailcoach-Replacement-{$key}", json_encode($value));
    }

    public function getKey(): string
    {
        return $this->key;
    }
}
