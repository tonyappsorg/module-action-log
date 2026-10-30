<?php
namespace Tonyapps\ActionLog\Model;

use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Config\Model\Config\Backend\Encrypted;
use Magento\Config\Model\Config\Structure;
use Magento\Config\Model\Config\Structure\Element\Field;
use Magento\Config\Model\Config\TypePool;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Serialize\Serializer\Json;
use Tonyapps\ActionLog\Model\ResourceModel\ActionLog as ActionLogResource;
use Tonyapps\ActionLog\Model\ResourceModel\AdminLoginLog as LoginLogResource;
use Tonyapps\ActionLog\Model\ResourceModel\AdminSession as SessionResource;

class Logger
{
    public const REDACTED = '***redacted***';

    public function __construct(
        private readonly ActionLogFactory $actionLogFactory,
        private readonly ActionLogResource $actionLogResource,
        private readonly LoginLogResource $loginLogResource,
        private readonly SessionResource $sessionResource,
        private readonly RemoteAddress $remoteAddress,
        private readonly RequestInterface $request,
        private readonly AuthSession $authSession,
        private readonly Json $json,
        private readonly Structure $configStructure,
        private readonly TypePool $typePool
    ) {
    }

    public function recordAction(string $fullActionName): void
    {
        $model = $this->actionLogFactory->create();
        $model->setData($this->baseData() + [
            'full_action_name' => $fullActionName,
            'event_type' => 'action',
        ]);
        $this->actionLogResource->save($model);
    }

    public function recordChange(object $entity, ?array $before, ?array $after, string $eventType): void
    {
        // Stored without the generated \Interceptor suffix so the grid shows "Product" and Restore creates the real model.
        $class = preg_replace('/\\\\Interceptor$/', '', get_class($entity));
        $model = $this->actionLogFactory->create();
        $model->setData($this->baseData() + [
            'full_action_name' => $this->request->getFullActionName(),
            'event_type' => $eventType,
            'entity_type' => $this->classBasename($class),
            'entity_id' => method_exists($entity, 'getId') ? $entity->getId() : null,
            'model_class' => $class,
            'data_before' => $before ? $this->json->serialize($this->snapshot($before)) : null,
            'data_after' => $after ? $this->json->serialize($this->snapshot($after)) : null,
        ]);
        $this->actionLogResource->save($model);
    }

    public function recordConfigChange(string $section, array $groups): void
    {
        $model = $this->actionLogFactory->create();
        $model->setData($this->baseData() + [
            'full_action_name' => $this->request->getFullActionName(),
            'event_type' => 'update',
            'entity_type' => 'Config',
            'entity_id' => $section,
            'data_after' => $this->json->serialize($this->redact($this->redactSensitiveFields($section, $groups))),
        ]);
        $this->actionLogResource->save($model);
    }

    public function recordLogin(string $username, bool $success, ?int $adminUserId = null): void
    {
        $this->loginLogResource->getConnection()->insert($this->loginLogResource->getMainTable(), [
            'admin_user_id' => $adminUserId,
            'username' => $username,
            'status' => $success ? 'success' : 'failure',
            'ip_address' => $this->remoteAddress->getRemoteAddress(),
            'user_agent' => $this->userAgent(),
        ]);
    }

    public function openSession(int $adminUserId, string $username, string $sessionId): void
    {
        $this->sessionResource->getConnection()->insert($this->sessionResource->getMainTable(), [
            'admin_user_id' => $adminUserId,
            'username' => $username,
            'session_id' => $sessionId,
            'ip_address' => $this->remoteAddress->getRemoteAddress(),
            'user_agent' => $this->userAgent(),
            'status' => 'active',
        ]);
    }

    private function baseData(): array
    {
        $user = $this->authSession->getUser();
        return [
            'admin_user_id' => $user ? (int) $user->getId() : null,
            'username' => $user ? (string) $user->getUsername() : '',
            'ip_address' => $this->remoteAddress->getRemoteAddress(),
            'user_agent' => $this->userAgent(),
        ];
    }

    private function userAgent(): string
    {
        return substr((string) $this->request->getServer('HTTP_USER_AGENT'), 0, 255);
    }

    private function classBasename(string $class): string
    {
        return basename(str_replace('\\', '/', $class));
    }

    /**
     * Column and attribute values only: objects (stock items, extension attributes) and nested arrays can't be
     * restored by Restorer anyway. Password hashes and reset tokens are redacted like config secrets.
     */
    private function snapshot(array $data): array
    {
        return $this->redact(array_filter($data, static fn ($value) => $value === null || is_scalar($value)));
    }

    /**
     * Redacts posted config fields that the config structure marks as secret: obscure inputs, encrypted backends and
     * paths registered as sensitive. Field names alone miss e.g. Braintree private_key or PayPal api_signature.
     */
    private function redactSensitiveFields(string $path, array $groups): array
    {
        foreach ($groups as $groupId => $group) {
            if (!is_array($group)) {
                continue;
            }
            $groupPath = $path . '/' . $groupId;
            foreach ((array) ($group['fields'] ?? []) as $fieldId => $field) {
                if (is_array($field) && array_key_exists('value', $field) && $this->isSensitiveField($groupPath . '/' . $fieldId)) {
                    $groups[$groupId]['fields'][$fieldId]['value'] = self::REDACTED;
                }
            }
            if (isset($group['groups']) && is_array($group['groups'])) {
                $groups[$groupId]['groups'] = $this->redactSensitiveFields($groupPath, $group['groups']);
            }
        }
        return $groups;
    }

    private function isSensitiveField(string $structurePath): bool
    {
        $field = $this->configStructure->getElement($structurePath);
        if (!$field instanceof Field) {
            return false;
        }
        $backend = (string) $field->getAttribute('backend_model');
        return $field->getType() === 'obscure'
            || ($backend !== '' && is_a($backend, Encrypted::class, true))
            || $this->typePool->isPresent($field->getConfigPath() ?: $structurePath, TypePool::TYPE_SENSITIVE);
    }

    private function redact(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && preg_match('/password|secret|token|api_key|access_key|private_key|signature/i', $key)) {
                $result[$key] = self::REDACTED;
            } elseif (is_array($value)) {
                $result[$key] = $this->redact($value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }
}
