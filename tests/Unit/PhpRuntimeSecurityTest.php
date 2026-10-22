<?php

declare(strict_types=1);

test('PHP includes the October 2026 critical security fixes', function () {
    expect(PHP_VERSION_ID)->toBeGreaterThanOrEqual(80512);
});
