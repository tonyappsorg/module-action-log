<?php
namespace Tonyapps\ActionLog\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Tonyapps\ActionLog\Model\Logger;

class LoggerRedactTest extends TestCase
{
    private function redact(array $data): array
    {
        $logger = (new \ReflectionClass(Logger::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(Logger::class, 'redact');
        $method->setAccessible(true);

        return $method->invoke($logger, $data);
    }

    public function testRedactsKeysMatchingSensitivePatterns(): void
    {
        $result = $this->redact([
            'payment' => [
                'password' => ['value' => 'super-secret', 'inherit' => 0],
                'api_key' => 'abc123',
            ],
            'general' => ['store_name' => 'My Store'],
        ]);

        $this->assertSame('***redacted***', $result['payment']['password']);
        $this->assertSame('***redacted***', $result['payment']['api_key']);
        $this->assertSame('My Store', $result['general']['store_name']);
    }

    public function testLeavesUnrelatedKeysUntouched(): void
    {
        $result = $this->redact(['enabled' => '1', 'retention_days' => '90']);

        $this->assertSame(['enabled' => '1', 'retention_days' => '90'], $result);
    }

    public function testRedactsCaseInsensitively(): void
    {
        $result = $this->redact(['ACCESS_KEY' => 'shh', 'Secret' => 'shh']);

        $this->assertSame('***redacted***', $result['ACCESS_KEY']);
        $this->assertSame('***redacted***', $result['Secret']);
    }
}
