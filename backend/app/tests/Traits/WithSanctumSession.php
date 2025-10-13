<?php

namespace Tests\Traits;

use Illuminate\Foundation\Testing\WithSession;

trait WithSanctumSession
{
    use WithSession;

    /**
     * Start a session for Sanctum tests
     */
    protected function startSession()
    {
        $this->startSession();
        return $this;
    }

    /**
     * Get CSRF token and start session
     */
    protected function withCsrfToken()
    {
        $response = $this->get('/sanctum/csrf-cookie');
        
        // Extract XSRF token from cookie if available
        $xsrfToken = null;
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === 'XSRF-TOKEN') {
                $xsrfToken = $cookie->getValue();
                break;
            }
        }

        if ($xsrfToken) {
            return $this->withHeaders([
                'X-XSRF-TOKEN' => $xsrfToken
            ]);
        }

        return $this;
    }

    /**
     * Make an authenticated session request
     */
    protected function actingAsSession($user, $guard = 'web')
    {
        $this->actingAs($user, $guard);
        $this->withSession(['_token' => 'test-csrf-token']);
        return $this;
    }
}