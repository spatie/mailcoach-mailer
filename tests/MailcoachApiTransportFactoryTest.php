<?php

use Spatie\MailcoachMailer\MailcoachApiTransport;
use Spatie\MailcoachMailer\MailcoachTransportFactory;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\HttpClient\ResponseInterface;

it('can create a transport', function () {
    $factory = (new MailcoachTransportFactory);

    $dsn = new Dsn('https', 'domain.mailcoach.app', options: ['token' => 'fake-token']);

    $transport = $factory->create($dsn);

    expect($transport)->toBeInstanceOf(MailcoachApiTransport::class);
});

it('uses the optional API base URL from a DSN', function (string $dsn, string $expectedUrl) {
    $client = new MockHttpClient(function (string $method, string $url, array $options) use ($expectedUrl): ResponseInterface {
        expect($method)->toBe('POST');
        expect($url)->toBe($expectedUrl);
        expect($options['headers'])->toContain('Authorization: Bearer fake-token');
        expect(json_decode($options['body'], true)['text'])->toBe('Hello');

        return new MockResponse('', ['http_code' => 204]);
    });

    $factory = new MailcoachTransportFactory(client: $client);
    $dsn = Dsn::fromString($dsn);

    expect($factory->supports($dsn))->toBeTrue();

    $transport = $factory->create($dsn);

    $transport->send((new Email)->from('from@example.com')->to('to@example.com')->text('Hello'));
})->with([
    'default HTTPS' => ['mailcoach://domain.mailcoach.app?token=fake-token', 'https://domain.mailcoach.app/api/transactional-mails/send'],
    'empty base URL' => ['mailcoach://domain.mailcoach.app?token=fake-token&api_base_url=', 'https://domain.mailcoach.app/api/transactional-mails/send'],
    'internal HTTP with port' => ['mailcoach://domain.mailcoach.app?token=fake-token&api_base_url=http%3A%2F%2Fmailcoach-service%3A8080', 'http://mailcoach-service:8080/api/transactional-mails/send'],
    'HTTPS with a path prefix' => ['mailcoach://domain.mailcoach.app?token=fake-token&api_base_url=https%3A%2F%2Finternal.example.com%3A8443%2Fmailcoach%2F', 'https://internal.example.com:8443/mailcoach/api/transactional-mails/send'],
]);

it('preserves custom HTTP client URL rewriting without a base URL override', function () {
    $client = new class extends MockHttpClient
    {
        public function request(string $method, string $url, array $options = []): ResponseInterface
        {
            expect($url)->toBe('https://domain.mailcoach.app/api/transactional-mails/send');

            $url = str_replace('https://domain.mailcoach.app', 'http://mailcoach-service:8080', $url);

            return parent::request($method, $url, $options);
        }
    };

    $client->setResponseFactory(function (string $method, string $url, array $options): ResponseInterface {
        expect($url)->toBe('http://mailcoach-service:8080/api/transactional-mails/send');
        expect($options['headers'])->toContain('Authorization: Bearer fake-token');

        return new MockResponse('', ['http_code' => 204]);
    });

    $factory = new MailcoachTransportFactory(client: $client);
    $transport = $factory->create(new Dsn('mailcoach', 'domain.mailcoach.app', options: ['token' => 'fake-token']));

    $transport->send((new Email)->from('from@example.com')->to('to@example.com')->text('Hello'));
});
