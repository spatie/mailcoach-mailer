<?php

namespace Spatie\MailcoachMailer;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Spatie\MailcoachMailer\Exceptions\NoHostSet;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractApiTransport;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class MailcoachApiTransport extends AbstractApiTransport
{
    public function __construct(
        protected string $apiToken,
        HttpClientInterface $client = null,
        EventDispatcherInterface $dispatcher = null,
        LoggerInterface $logger = null
    ) {
        parent::__construct($client, $dispatcher, $logger);
    }

    protected function doSendApi(
        SentMessage $sentMessage,
        Email $email,
        Envelope $envelope
    ): ResponseInterface {
        $payload = $this->getPayload($email, $envelope);

        if (! $this->host) {
            throw NoHostSet::make();
        }

        $response = $this->client->request('POST', "https://{$this->host}/api/transactional-mails/send", [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => "Bearer {$this->apiToken}",
            ],
            'json' => $payload,
        ]);

        try {
            $statusCode = $response->getStatusCode();
        } catch (DecodingExceptionInterface) {
            throw new HttpTransportException("Unable to send an email to {$payload['to']}.", $response);
        } catch (TransportExceptionInterface $exception) {
            throw new HttpTransportException('Could not reach the remote Mailcoach server.', $response, 0, $exception);
        }

        if (! in_array($statusCode, [200, 204])) {
            throw new HttpTransportException("Unable to send an email to {$payload['to']} (code {$statusCode}).", $response);
        }

        return $response;
    }

    protected function getPayload(Email $email, Envelope $envelope): array
    {
        return [
            'from' => $envelope->getSender()->toString(),
            'to' => implode(',', $this->stringifyAddresses($this->getRecipients($email, $envelope))),
            'cc' => implode(',', $this->stringifyAddresses($email->getCc())),
            'bcc' => implode(',', $this->stringifyAddresses($email->getBcc())),
            'replyTo' => implode(',', $this->stringifyAddresses($email->getReplyTo())),
            'subject' => $email->getSubject(),
            'text' => $email->getTextBody(),
            'html' => $email->getHtmlBody(),
            'attachments' => $this->getAttachments($email),
        ];
    }

    protected function getAttachments(Email $email): array
    {
        $attachments = [];
        foreach ($email->getAttachments() as $attachment) {
            $headers = $attachment->getPreparedHeaders();
            $filename = $headers->getHeaderParameter('Content-Disposition', 'filename');
            $disposition = $headers->getHeaderBody('Content-Disposition');

            $att = [
                'Name' => $filename,
                'Content' => $attachment->bodyToString(),
                'ContentType' => $headers->get('Content-Type')->getBody(),
            ];

            if ('inline' === $disposition) {
                $att['ContentID'] = 'cid:'.$filename;
            }

            $attachments[] = $att;
        }

        return $attachments;
    }

    public function __toString(): string
    {
        return "mailcoach+api://{$this->host}";
    }
}
