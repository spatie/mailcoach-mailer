<?php

use Spatie\MailcoachMailer\MailcoachApiTransport;
use Spatie\MailcoachMailer\MailcoachTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

it('can create a transport', function () {
    $factory = (new MailcoachTransportFactory);

    $dsn = new Dsn('https', 'domain.mailcoach.app', options: ['token' => 'fake-token']);

    $transport = $factory->create($dsn);

    expect($transport)->toBeInstanceOf(MailcoachApiTransport::class);
});
