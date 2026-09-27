<?php

namespace Tests\Feature;

use Tests\TestCase;

final class LegalPagesTest extends TestCase
{
    public function test_public_and_legal_pages_are_served_by_the_application(): void
    {
        $this->get('/')->assertOk()->assertSee('Create once. Publish intelligently.', escape: false);
        $this->get('/privacy-policy')->assertOk()->assertSee('Privacy Policy', escape: false);
        $this->get('/terms')->assertOk()->assertSee('Terms of Service', escape: false);
        $this->get('/data-deletion')->assertOk()->assertSee('Data Deletion Instructions', escape: false);
    }
}
