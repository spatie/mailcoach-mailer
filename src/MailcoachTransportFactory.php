<?php

namespace Spatie\MailcoachMailer;

use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

class MailcoachTransportFactory extends AbstractTransportFactory
{
    protected function getSupportedSchemes(): array
    {
        return ['mailcoach'];
    }

    public function create(Dsn $dsn): TransportInterface
    {
        $transport = new MailcoachApiTransport(
            $dsn->getOption('token'),
            $this->client,
            $this->dispatcher,
            $this->logger,
        );

        $transport
            ->setHost($dsn->getHost())
            ->setPort($dsn->getPort());

        return $transport;
    }
}
