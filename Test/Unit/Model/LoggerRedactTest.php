<?php
namespace Tonyapps\ActionLog\Test\Unit\Model;

use Magento\Config\Model\Config\Structure;
use Magento\Config\Model\Config\Structure\Element\Field;
use Magento\Config\Model\Config\TypePool;
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

    public function testRedactsRealPaymentSecretFieldNames(): void
    {
        $result = $this->redact([
            'braintree' => ['private_key' => 'k', 'sandbox_private_key' => 'k'],
            'paypal' => ['api_signature' => 's'],
            'recaptcha' => ['private_key' => 'k'],
        ]);

        $this->assertSame('***redacted***', $result['braintree']['private_key']);
        $this->assertSame('***redacted***', $result['braintree']['sandbox_private_key']);
        $this->assertSame('***redacted***', $result['paypal']['api_signature']);
        $this->assertSame('***redacted***', $result['recaptcha']['private_key']);
    }

    public function testRedactsConfigFieldsTheStructureMarksSecret(): void
    {
        $fields = [
            'payment/acme/creds/merchant_code' => $this->field('obscure', ''),
            'payment/acme/creds/nested/hash' => $this->field('text', \Magento\Config\Model\Config\Backend\Encrypted::class),
            'payment/acme/creds/sensitive' => $this->field('text', '', 'payment/acme/sensitive_path'),
            'payment/acme/creds/title' => $this->field('text', ''),
        ];
        $structure = $this->createMock(Structure::class);
        $structure->method('getElement')->willReturnCallback(static fn ($path) => $fields[$path] ?? null);
        $typePool = $this->createMock(TypePool::class);
        $typePool->method('isPresent')->willReturnCallback(
            static fn ($path, $type) => $path === 'payment/acme/sensitive_path' && $type === TypePool::TYPE_SENSITIVE
        );

        $logger = (new \ReflectionClass(Logger::class))->newInstanceWithoutConstructor();
        foreach (['configStructure' => $structure, 'typePool' => $typePool] as $name => $value) {
            (new \ReflectionProperty(Logger::class, $name))->setValue($logger, $value);
        }
        $result = (new \ReflectionMethod(Logger::class, 'redactSensitiveFields'))->invoke($logger, 'payment', [
            'acme' => ['groups' => ['creds' => [
                'fields' => [
                    'merchant_code' => ['value' => 'm'],
                    'sensitive' => ['value' => 's'],
                    'title' => ['value' => 'Acme Pay'],
                ],
                'groups' => ['nested' => ['fields' => ['hash' => ['value' => 'h']]]],
            ]]],
        ]);

        $creds = $result['acme']['groups']['creds'];
        $this->assertSame('***redacted***', $creds['fields']['merchant_code']['value']);
        $this->assertSame('***redacted***', $creds['fields']['sensitive']['value']);
        $this->assertSame('***redacted***', $creds['groups']['nested']['fields']['hash']['value']);
        $this->assertSame('Acme Pay', $creds['fields']['title']['value']);
    }

    public function testSnapshotKeepsScalarsAndRedactsCustomerSecrets(): void
    {
        $logger = (new \ReflectionClass(Logger::class))->newInstanceWithoutConstructor();
        $result = (new \ReflectionMethod(Logger::class, 'snapshot'))->invoke($logger, [
            'email' => 'a@example.com',
            'password_hash' => 'hash',
            'rp_token' => 'tok',
            'extension_attributes' => new \stdClass(),
            'media_gallery' => ['images' => []],
            'gender' => null,
        ]);

        $this->assertSame(
            ['email' => 'a@example.com', 'password_hash' => '***redacted***', 'rp_token' => '***redacted***', 'gender' => null],
            $result
        );
    }

    private function field(string $type, string $backend, string $configPath = ''): Field
    {
        $field = $this->createMock(Field::class);
        $field->method('getType')->willReturn($type);
        $field->method('getAttribute')->willReturnCallback(static fn ($key) => $key === 'backend_model' ? $backend : null);
        $field->method('getConfigPath')->willReturn($configPath);
        return $field;
    }
}
