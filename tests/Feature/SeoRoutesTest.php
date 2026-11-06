<?php

namespace Tests\Feature;

use Tests\TestCase;

class SeoRoutesTest extends TestCase
{
    public function test_robots_txt_allows_public_pages_and_points_to_sitemap(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $this->assertStringStartsWith('text/plain', $response->headers->get('Content-Type'));
        $response->assertSee('Allow: /comment-jouer', false);
        $response->assertSee('Disallow: /', false);
        $response->assertSee('Sitemap: ' . route('sitemap'), false);
    }

    public function test_sitemap_lists_public_pages_only(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $this->assertStringStartsWith('application/xml', $response->headers->get('Content-Type'));
        $response->assertSee('<loc>' . route('welcome') . '</loc>', false);
        $response->assertSee('<loc>' . route('guide') . '</loc>', false);
        $response->assertSee('<loc>' . route('privacy') . '</loc>', false);
        $response->assertDontSee('game-saves', false);
    }
}
