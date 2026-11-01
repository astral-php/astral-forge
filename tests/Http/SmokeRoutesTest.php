<?php

declare(strict_types=1);

namespace Tests\Http;

/** Smokes HTTP astral-forge — wiring routes / auth / pages 0.4. */
final class SmokeRoutesTest extends HttpSmokeTestCase
{
    public function testHomeReturns200(): void
    {
        $response = $this->get('/');
        $this->assertSame(200, $response->getStatus());
        $this->assertStringContainsString('Astral Forge', $response->getContent());
        $this->assertStringContainsString('Découvrir Forge', $response->getContent());
    }

    public function testForgeFormReturns200(): void
    {
        $response = $this->get('/forge');
        $this->assertSame(200, $response->getStatus());
        $this->assertStringContainsString('forge-app', $response->getContent());
        $this->assertStringContainsString('php bin/forge plan|generate', $response->getContent());
    }

    public function testTemplatesReturns200(): void
    {
        $response = $this->get('/templates');
        $this->assertSame(200, $response->getStatus());
        $this->assertStringContainsString('blog-lite', $response->getContent());
    }

    public function testDocsReturns200(): void
    {
        $response = $this->get('/docs');
        $this->assertSame(200, $response->getStatus());
        $this->assertStringContainsString('Documentation', $response->getContent());
    }

    public function testDiscoverReturns200(): void
    {
        $response = $this->get('/discover');
        $this->assertSame(200, $response->getStatus());
        $this->assertStringContainsString('Découvrir Forge', $response->getContent());
    }

    public function testExtensionsReturns200(): void
    {
        $response = $this->get('/extensions');
        $this->assertSame(200, $response->getStatus());
        $this->assertStringContainsString('astral-vite', $response->getContent());
    }

    public function testNewsReturns200(): void
    {
        $response = $this->get('/news');
        $this->assertSame(200, $response->getStatus());
        $this->assertStringContainsString('0.4.0', $response->getContent());
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
