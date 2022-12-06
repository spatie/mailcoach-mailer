<?php

namespace Spatie\MailcoachMailer\Headers;

use Symfony\Component\Mime\Header\UnstructuredHeader;

class ReplacementHeader extends UnstructuredHeader
{
    private string $key;

    public function __construct(string $key, string $value)
    {
        $this->key = $key;

        parent::__construct('X-Mailcoach-Replacement-'.$key, $value);
    }

    public function getKey(): string
    {
        return $this->key;
    }
}