<?php

namespace Spatie\MailcoachMailer\Headers;

use Symfony\Component\Mime\Header\UnstructuredHeader;

class TransactionalMailHeader extends UnstructuredHeader
{
    public function __construct(string $value)
    {
        parent::__construct('X-Mailcoach-Transactional-Mail', $value);
    }
}
