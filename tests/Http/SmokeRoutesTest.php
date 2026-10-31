<?php

declare(strict_types=1);

namespace Tests\Http;

/** Smokes HTTP astral-starter — preuve de wiring routes / auth. */
final class SmokeRoutesTest extends HttpSmokeTestCase
{
    public function testHomeReturns200(): void
    {
        $response = $this->get('/');
        $this->assertSame(200, $response->getStatus());
        $this->assertStringContainsString('Astral Forge', $response->getContent());
    }

    public function testForgeFormReturns200(): void
    {
        $response = $this->get('/forge');
        $this->assertSame(200, $response->getStatus());
        $this->assertStringContainsString('forge-app', $response->getContent());
        $this->assertStringContainsString('Nouveau projet Astral', $response->getContent());
    }

    public function testLoginFormReturns200(): void
    {
        $response = $this->get('/login');
        $this->assertSame(200, $response->getStatus());
    }

    public function testRegisterFormReturns200(): void
    {
        $response = $this->get('/register');
        $this->assertSame(200, $response->getStatus());
    }

    public function testProfileWithoutAuthRedirectsToLogin(): void
    {
        $response = $this->get('/profile');
        $this->assertSame(302, $response->getStatus());
    }

    public function testAdminWithoutAuthRedirects(): void
    {
        $response = $this->get('/admin/users');
        $this->assertSame(302, $response->getStatus());
    }
}
