<?php

namespace Spatie\MailcoachMailer;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Bridge\Postmark\Transport\MessageStreamHeader;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Header\MetadataHeader;
use Symfony\Component\Mailer\Header\TagHeader;
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
        protected string $key,
        HttpClientInterface $client = null,
        EventDispatcherInterface $dispatcher = null,
        LoggerInterface $logger = null
    ) {
        parent::__construct($client, $dispatcher, $logger);
    }

    protected function doSendApi(SentMessage $sentMessage, Email $email, Envelope $envelope): ResponseInterface
    {
        $response = $this->client->request('POST', "https://{$this->host}/api/transactional-mails/send-raw", [
            'headers' => [
                'Accept' => 'application/json',
                'X-Postmark-Server-Token' => $this->key,
            ],
            'json' => $this->getPayload($email, $envelope),
        ]);

        try {
            $statusCode = $response->getStatusCode();
            $result = $response->toArray(false);
        } catch (DecodingExceptionInterface) {
            throw new HttpTransportException("Unable to send an email: {$response->getContent(false)} (code {$statusCode}).", $response);
        } catch (TransportExceptionInterface $exception) {
            throw new HttpTransportException('Could not reach the remote Postmark server.', $response, 0, $exception);
        }

        if (200 !== $statusCode) {
            throw new HttpTransportException("Unable to send an email: {$result['Message']} (code {$result['ErrorCode']}).", $response);
        }

        $sentMessage->setMessageId($result['MessageID']);

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
            'textBody' => $email->getTextBody(),
            'htmlBody' => $email->getHtmlBody(),
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
        return "mailcoach+api://https://mailcoach.app";
    }
}
