<?php

use Spatie\MailcoachMailer\Exceptions\NoHostSet;
use Spatie\MailcoachMailer\MailcoachApiTransport;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\HttpClient\ResponseInterface;

it('can be converted to string', function () {
    $transport = (new MailcoachApiTransport('dummy-token'))->setHost('domain.mailcoach.app');

    expect((string) $transport)->toBe('mailcoach+api://domain.mailcoach.app');
});

it('can send an email', function () {
    $client = new MockHttpClient(function (string $method, string $url, array $options): ResponseInterface {
        expect($method)->toBe('POST');
        expect($url)->toBe('https://domain.mailcoach.app/api/transactional-mails/send');

        expect(strtolower($options['headers'][1]))->toContain(strtolower('fake-api-token'));

        $body = json_decode($options['body'], true);

        expect($body['from'])->toBe('"From name" <from@example.com>');
        expect($body['to'])->toBe('"To name" <to@example.com>');
        expect($body['subject'])->toBe('My subject');
        expect($body['text'])->toBe('The text content');
        expect($body['html'])->toBe('The html content');

        return new MockResponse('', ['http_code' => 204]);
    });

    $transport = (new MailcoachApiTransport('fake-api-token', $client))->setHost('domain.mailcoach.app');

    $mail = (new Email())
        ->subject('My subject')
        ->to(new Address('to@example.com', 'To name'))
        ->from(new Address('from@example.com', 'From name'))
        ->text('The text content')
        ->html('The html content');

    $response = $transport->send($mail);

    expect($response)->toBeInstanceOf(SentMessage::class);
    expect($response->getMessageId())->toBeString();
});

it('will throw an exception if the host is not set', function () {
    $transport = (new MailcoachApiTransport('fake-api-token'));

    $mail = (new Email())
        ->to(new Address('to@example.com', 'To name'))
        ->from(new Address('from@example.com', 'From name'))
        ->text('The text content');

    $transport->send($mail);
})->throws(NoHostSet::class);
