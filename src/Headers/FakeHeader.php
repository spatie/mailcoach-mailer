<?php

namespace Spatie\MailcoachMailer\Headers;

use Symfony\Component\Mime\Header\UnstructuredHeader;

class FakeHeader extends UnstructuredHeader
{
    public function __construct(bool $value = true)
    {
        parent::__construct('X-Mailcoach-Fake', $value);
    }
}
