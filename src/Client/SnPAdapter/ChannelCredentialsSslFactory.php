<?php
declare(strict_types=1);

namespace App\Client\SnPAdapter;

use Grpc\ChannelCredentials;

final class ChannelCredentialsSslFactory
{
    public static function create(string $rootCert): ChannelCredentials
    {
        return ChannelCredentials::createSsl(base64_decode($rootCert));
    }
}
