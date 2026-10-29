<?php

namespace App\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureInsecureSmtpWhenCertIsUntrusted();
    }

    /**
     * MAIL_VERIFY_PEER=false relaxes TLS peer verification on the SMTP transport.
     * Needed while hosting.bintankab.go.id's SSL cert is expired; Laravel's
     * built-in SMTP transport factory has no config option for this, so the
     * transport is rebuilt here with stream options applied directly.
     */
    private function configureInsecureSmtpWhenCertIsUntrusted(): void
    {
        if (config('mail.default') !== 'smtp' || env('MAIL_VERIFY_PEER', true)) {
            return;
        }

        Mail::extend('smtp', function (array $config) {
            $scheme = $config['scheme'] ?? (($config['port'] ?? null) == 465 ? 'smtps' : 'smtp');

            $transport = (new EsmtpTransportFactory)->create(new Dsn(
                $scheme,
                $config['host'],
                $config['username'] ?? null,
                $config['password'] ?? null,
                $config['port'] ?? null,
                $config,
            ));

            $stream = $transport->getStream();

            if ($stream instanceof SocketStream) {
                $stream->setStreamOptions(array_replace_recursive($stream->getStreamOptions(), [
                    'ssl' => [
                        'allow_self_signed' => true,
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                ]));
            }

            return $transport;
        });
    }
}
