<?php

namespace Spatie\MailcoachMailer\Exceptions;

use Exception;

class NoHostSet extends Exception
{
    public static function make(): self
    {
        return new self("You must set a Mailcoach domain before you can send a mail.");
    }
}
