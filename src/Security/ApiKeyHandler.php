<?php 

namespace App\Security;

use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class ApiKeyHandler implements AccessTokenHandlerInterface
{
    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        if ($accessToken !== $this->apiKey) {
            throw new BadCredentialsException('Ungültiger API-Key.');
        }

        return new UserBadge('api_user');
    }
}
