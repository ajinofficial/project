<?php

namespace Tests\Unit;

use App\Rules\SafeEmail;
use PHPUnit\Framework\TestCase;

class SafeEmailTest extends TestCase
{
    public function test_it_accepts_a_normal_email_address(): void
    {
        $failures = [];

        (new SafeEmail)->validate('email', 'owner@example.com', function (string $message) use (&$failures) {
            $failures[] = $message;
        });

        $this->assertSame([], $failures);
    }

    public function test_it_rejects_mail_header_injection_characters(): void
    {
        $failures = [];

        (new SafeEmail)->validate(
            'email',
            "owner@example.com\r\nBcc: attacker@example.com",
            function (string $message) use (&$failures) {
                $failures[] = $message;
            }
        );

        $this->assertNotEmpty($failures);
    }
}
