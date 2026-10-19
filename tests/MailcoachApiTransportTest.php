<?php

use Spatie\MailcoachMailer\Exceptions\NoHostSet;
use Spatie\MailcoachMailer\Headers\FakeHeader;
use Spatie\MailcoachMailer\Headers\MailerHeader;
use Spatie\MailcoachMailer\Headers\ReplacementHeader;
use Spatie\MailcoachMailer\Headers\TransactionalMailHeader;
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

        expect($options['headers'][1])->toContain('fake-api-token');

        $body = json_decode($options['body'], true);

        expect($body['from'])->toBe('"From name" <from@example.com>');
        expect($body['to'])->toBe('"To name" <to@example.com>');
        expect($body['subject'])->toBe('My subject');
        expect($body['text'])->toBe('The text content');
        expect($body['html'])->toBe('The html content');

        return new MockResponse('', ['http_code' => 204]);
    });

    $transport = (new MailcoachApiTransport('fake-api-token', $client))->setHost('domain.mailcoach.app');

    $mail = (new Email)
        ->subject('My subject')
        ->to(new Address('to@example.com', 'To name'))
        ->from(new Address('from@example.com', 'From name'))
        ->text('The text content')
        ->html('The html content');

    $response = $transport->send($mail);

    expect($response)->toBeInstanceOf(SentMessage::class);
    expect($response->getMessageId())->toBeString();
});

it('can send a plaintext email', function () {
    $client = new MockHttpClient(function (string $method, string $url, array $options): ResponseInterface {
        expect($method)->toBe('POST');
        expect($url)->toBe('https://domain.mailcoach.app/api/transactional-mails/send');

        expect($options['headers'][1])->toContain('fake-api-token');

        $body = json_decode($options['body'], true);

        expect($body['from'])->toBe('"From name" <from@example.com>');
        expect($body['to'])->toBe('"To name" <to@example.com>');
        expect($body['subject'])->toBe('My subject');
        expect($body['text'])->toBe('The text content');
        expect($body['html'])->toBeNull();

        return new MockResponse('', ['http_code' => 204]);
    });

    $transport = (new MailcoachApiTransport('fake-api-token', $client))->setHost('domain.mailcoach.app');

    $mail = (new Email)
        ->subject('My subject')
        ->to(new Address('to@example.com', 'To name'))
        ->from(new Address('from@example.com', 'From name'))
        ->text('The text content');

    $response = $transport->send($mail);

    expect($response)->toBeInstanceOf(SentMessage::class);
    expect($response->getMessageId())->toBeString();
});

it('can process the transactional mail header', function () {
    $client = new MockHttpClient(function (string $method, string $url, array $options): ResponseInterface {
        $body = json_decode($options['body'], true);

        expect($body['mail_name'])->toBe('my_template');

        return new MockResponse('', ['http_code' => 204]);
    });

    $transport = (new MailcoachApiTransport('fake-api-token', $client))->setHost('domain.mailcoach.app');

    $mail = (new Email)
        ->subject('My subject')
        ->to(new Address('to@example.com', 'To name'))
        ->from(new Address('from@example.com', 'From name'))
        ->text('The text content')
        ->html('The html content');

    $mail->getHeaders()->add(new TransactionalMailHeader('my_template'));

    $transport->send($mail);
});

it('can process the mailer header', function () {
    $client = new MockHttpClient(function (string $method, string $url, array $options): ResponseInterface {
        $body = json_decode($options['body'], true);

        expect($body['mailer'])->toBe('transactional-mailer');

        return new MockResponse('', ['http_code' => 204]);
    });

    $transport = (new MailcoachApiTransport('fake-api-token', $client))->setHost('domain.mailcoach.app');

    $mail = (new Email)
        ->subject('My subject')
        ->to(new Address('to@example.com', 'To name'))
        ->from(new Address('from@example.com', 'From name'))
        ->text('The text content')
        ->html('The html content');

    $mail->getHeaders()->add(new MailerHeader('transactional-mailer'));

    $transport->send($mail);
});

it('can process the fake header', function () {
    $client = new MockHttpClient(function (string $method, string $url, array $options): ResponseInterface {
        $body = json_decode($options['body'], true);

        expect($body['fake'])->toBe('1');

        return new MockResponse('', ['http_code' => 204]);
    });

    $transport = (new MailcoachApiTransport('fake-api-token', $client))->setHost('domain.mailcoach.app');

    $mail = (new Email)
        ->subject('My subject')
        ->to(new Address('to@example.com', 'To name'))
        ->from(new Address('from@example.com', 'From name'))
        ->text('The text content')
        ->html('The html content');

    $mail->getHeaders()->add(new FakeHeader);

    $transport->send($mail);
});

it('throws when trying to define it twice', function () {
    $client = new MockHttpClient(function (): ResponseInterface {
        return new MockResponse('', ['http_code' => 204]);
    });

    $transport = (new MailcoachApiTransport('fake-api-token', $client))->setHost('domain.mailcoach.app');

    $mail = (new Email)
        ->subject('My subject')
        ->to(new Address('to@example.com', 'To name'))
        ->from(new Address('from@example.com', 'From name'))
        ->text('The text content')
        ->html('The html content');

    $mail->getHeaders()->add(new TransactionalMailHeader('my_template'));
    $mail->getHeaders()->add(new TransactionalMailHeader('another_template'));

    $this->expectExceptionMessage('Mailcoach only allows a single transactional mail to be defined.');

    $transport->send($mail);
});

it('can pass through replacements', function () {
    $client = new MockHttpClient(function (string $method, string $url, array $options): ResponseInterface {
        $body = json_decode($options['body'], true);

        expect($body['replacements']['first_name'])->toBe('John');
        expect($body['replacements']['last_name'])->toBe('Doe');
        expect($body['replacements']['array'])->toBe(['foo', 'bar']);

        return new MockResponse('', ['http_code' => 204]);
    });

    $transport = (new MailcoachApiTransport('fake-api-token', $client))->setHost('domain.mailcoach.app');

    $mail = (new Email)
        ->subject('My subject')
        ->to(new Address('to@example.com', 'To name'))
        ->from(new Address('from@example.com', 'From name'))
        ->text('The text content')
        ->html('The html content');

    $mail->getHeaders()->add(new ReplacementHeader('first_name', 'John'));
    $mail->getHeaders()->add(new ReplacementHeader('last_name', 'Doe'));
    $mail->getHeaders()->add(new ReplacementHeader('array', ['foo', 'bar']));

    $transport->send($mail);
});

it('will throw an exception if the host is not set', function () {
    $transport = (new MailcoachApiTransport('fake-api-token'));

    $mail = (new Email)
        ->to(new Address('to@example.com', 'To name'))
        ->from(new Address('from@example.com', 'From name'))
        ->text('The text content');

    $transport->send($mail);
})->throws(NoHostSet::class);

it('uses the API base URL without changing the request', function (?string $baseUrl, string $expectedUrl) {
    $client = new MockHttpClient(function (string $method, string $url, array $options) use ($expectedUrl): ResponseInterface {
        expect($method)->toBe('POST');
        expect($url)->toBe($expectedUrl);
        expect($options['headers'])->toContain('Accept: application/json', 'Authorization: Bearer fake-api-token');
        expect(json_decode($options['body'], true))->toBe([
            'from' => '"From name" <from@example.com>',
            'to' => '"To name" <to@example.com>',
            'cc' => 'cc@example.com',
            'bcc' => 'bcc@example.com',
            'reply_to' => 'reply@example.com',
            'subject' => 'My subject',
            'text' => 'The text content',
            'html' => '<p>The html content</p>',
            'attachments' => [
                [
                    'name' => 'example.txt',
                    'content' => base64_encode('Attachment content'),
                    'content_type' => 'text/plain',
                ],
            ],
            'mail_name' => 'my_template',
            'replacements' => ['first_name' => 'John'],
            'mailer' => 'transactional-mailer',
            'fake' => '1',
        ]);

        return new MockResponse('', ['http_code' => 204]);
    });

    $transport = (new MailcoachApiTransport('fake-api-token', $client))
        ->setHost('domain.mailcoach.app')
        ->setApiBaseUrl($baseUrl);

    $mail = (new Email)
        ->from(new Address('from@example.com', 'From name'))
        ->to(new Address('to@example.com', 'To name'))
        ->cc('cc@example.com')
        ->bcc('bcc@example.com')
        ->replyTo('reply@example.com')
        ->subject('My subject')
        ->text('The text content')
        ->html('<p>The html content</p>')
        ->attach('Attachment content', 'example.txt', 'text/plain');

    $mail->getHeaders()->add(new TransactionalMailHeader('my_template'));
    $mail->getHeaders()->add(new ReplacementHeader('first_name', 'John'));
    $mail->getHeaders()->add(new MailerHeader('transactional-mailer'));
    $mail->getHeaders()->add(new FakeHeader);

    expect($transport->send($mail))->toBeInstanceOf(SentMessage::class);
})->with([
    'default HTTPS' => [null, 'https://domain.mailcoach.app/api/transactional-mails/send'],
    'empty base URL' => ['', 'https://domain.mailcoach.app/api/transactional-mails/send'],
    'internal HTTP' => ['http://mailcoach-service.default.svc.cluster.local:8080', 'http://mailcoach-service.default.svc.cluster.local:8080/api/transactional-mails/send'],
    'trailing slash' => ['http://mailcoach-service:8080/', 'http://mailcoach-service:8080/api/transactional-mails/send'],
    'HTTPS with a path prefix' => ['https://internal.example.com:8443/mailcoach/', 'https://internal.example.com:8443/mailcoach/api/transactional-mails/send'],
]);

it('can use an API base URL without a host', function () {
    $client = new MockHttpClient(function (string $method, string $url): ResponseInterface {
        expect($url)->toBe('http://mailcoach-service/api/transactional-mails/send');

        return new MockResponse('', ['http_code' => 204]);
    });

    $transport = (new MailcoachApiTransport('fake-api-token', $client))
        ->setApiBaseUrl('http://mailcoach-service');

    $mail = (new Email)->from('from@example.com')->to('to@example.com')->text('Hello');

    expect($transport->send($mail))->toBeInstanceOf(SentMessage::class);
});

it('can reset the API base URL to the default HTTPS host', function (?string $baseUrl) {
    $client = new MockHttpClient(function (string $method, string $url): ResponseInterface {
        expect($url)->toBe('https://domain.mailcoach.app/api/transactional-mails/send');

        return new MockResponse('', ['http_code' => 204]);
    });

    $transport = (new MailcoachApiTransport('fake-api-token', $client))
        ->setHost('domain.mailcoach.app')
        ->setApiBaseUrl('http://mailcoach-service')
        ->setApiBaseUrl($baseUrl);

    $mail = (new Email)->from('from@example.com')->to('to@example.com')->text('Hello');

    expect($transport->send($mail))->toBeInstanceOf(SentMessage::class);
    expect((string) $transport)->toBe('mailcoach+api://domain.mailcoach.app');
})->with([null, '']);

it('still requires a host after clearing the API base URL', function (?string $baseUrl) {
    $transport = (new MailcoachApiTransport('fake-api-token'))
        ->setApiBaseUrl('http://mailcoach-service')
        ->setApiBaseUrl($baseUrl);

    $transport->send((new Email)->from('from@example.com')->to('to@example.com')->text('Hello'));
})->with([null, ''])->throws(NoHostSet::class);
