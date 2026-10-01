<?php
namespace Tonyapps\ActionLog\Model;

use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Serialize\Serializer\Json;
use Tonyapps\ActionLog\Model\ResourceModel\ActionLog as ActionLogResource;
use Tonyapps\ActionLog\Model\ResourceModel\AdminLoginLog as LoginLogResource;
use Tonyapps\ActionLog\Model\ResourceModel\AdminSession as SessionResource;

class Logger
{
    private ActionLogFactory $actionLogFactory;
    private ActionLogResource $actionLogResource;
    private AdminLoginLogFactory $loginLogFactory;
    private LoginLogResource $loginLogResource;
    private AdminSessionFactory $sessionFactory;
    private SessionResource $sessionResource;
    private RemoteAddress $remoteAddress;
    private RequestInterface $request;
    private AuthSession $authSession;
    private Json $json;

    public function __construct(
        ActionLogFactory $actionLogFactory,
        ActionLogResource $actionLogResource,
        AdminLoginLogFactory $loginLogFactory,
        LoginLogResource $loginLogResource,
        AdminSessionFactory $sessionFactory,
        SessionResource $sessionResource,
        RemoteAddress $remoteAddress,
        RequestInterface $request,
        AuthSession $authSession,
        Json $json
    ) {
        $this->actionLogFactory = $actionLogFactory;
        $this->actionLogResource = $actionLogResource;
        $this->loginLogFactory = $loginLogFactory;
        $this->loginLogResource = $loginLogResource;
        $this->sessionFactory = $sessionFactory;
        $this->sessionResource = $sessionResource;
        $this->remoteAddress = $remoteAddress;
        $this->request = $request;
        $this->authSession = $authSession;
        $this->json = $json;
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
        $model = $this->actionLogFactory->create();
        $model->setData($this->baseData() + [
            'full_action_name' => $this->request->getFullActionName(),
            'event_type' => $eventType,
            'entity_type' => $this->classBasename(get_class($entity)),
            'entity_id' => method_exists($entity, 'getId') ? $entity->getId() : null,
            'model_class' => get_class($entity),
            'data_before' => $before ? $this->json->serialize($before) : null,
            'data_after' => $after ? $this->json->serialize($after) : null,
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
            'data_after' => $this->json->serialize($this->redact($groups)),
        ]);
        $this->actionLogResource->save($model);
    }

    public function recordLogin(string $username, bool $success, ?int $adminUserId = null): void
    {
        $model = $this->loginLogFactory->create();
        $model->setData([
            'admin_user_id' => $adminUserId,
            'username' => $username,
            'status' => $success ? 'success' : 'failure',
            'ip_address' => $this->remoteAddress->getRemoteAddress(),
            'user_agent' => $this->userAgent(),
        ]);
        $this->loginLogResource->save($model);
    }

    public function openSession(int $adminUserId, string $username, string $sessionId): void
    {
        $model = $this->sessionFactory->create();
        $model->setData([
            'admin_user_id' => $adminUserId,
            'username' => $username,
            'session_id' => $sessionId,
            'ip_address' => $this->remoteAddress->getRemoteAddress(),
            'user_agent' => $this->userAgent(),
            'status' => 'active',
        ]);
        $this->sessionResource->save($model);
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
        $parts = explode('\\', $class);
        return (string) end($parts);
    }

    // ponytail: redacts by key name only; a secret nested under a differently-named key slips through. Add a field-path allowlist if that shows up.
    private function redact(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && preg_match('/password|secret|token|api_key|access_key/i', $key)) {
                $result[$key] = '***redacted***';
            } elseif (is_array($value)) {
                $result[$key] = $this->redact($value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }
}
