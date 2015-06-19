<?php
/**
 * Command.php
 * @author Revin Roman
 * @link https://rmrevin.ru
 */

namespace rmrevin\yii\rbac;

/**
 * Class Command
 * @package rmrevin\yii\rbac
 */
abstract class Command extends \yii\console\Controller
{

    /** @var integer */
    public $batchSize = 1000;

    /** @var array */
    public $forceAssign = [];

    /** @var array */
    public $assignmentsMap = [
        // 'frontend.acess' => 'frontend.access', // mistake example
    ];

    /** @var boolean */
    public $useTransaction = true;

    /** @var boolean */
    public $useCache = true;

    /** @var \yii\db\Connection */
    private $db;

    /**
     * @return \yii\rbac\Role[]
     */
    abstract protected function roles();

    /**
     * @return \yii\rbac\Rule[]
     */
    protected function rules()
    {
        return [];
    }

    /**
     * @return \yii\rbac\Permission[]
     */
    protected function permissions()
    {
        return [];
    }

    /**
     * @return array
     */
    abstract protected function inheritanceRoles();

    /**
     * @return array
     */
    abstract protected function inheritancePermissions();

    /**
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    public function actionUpdate()
    {
        $assignments = $this->getAllAssignments();

        $AuthManager = $this->getAuthManagerComponent();

        $useTransaction = $AuthManager instanceof \yii\rbac\DbManager && $this->useTransaction === true;

        $transaction = null;

        if ($useTransaction) {
            $this->db = \yii\di\Instance::ensure($AuthManager->db, \yii\db\Connection::className());

            $transaction = $this->db->beginTransaction();
        }

        try {
            $AuthManager->removeAll();

            $this->updateRoles();
            $this->updateRules();
            $this->updatePermission();
            $this->updateInheritanceRoles();
            $this->updateInheritancePermissions();

            if (!empty($assignments)) {
                $this->restoreAssignments($assignments);
            }

            if ($transaction !== null) {
                $transaction->commit();
            }
        } catch (\Exception $e) {
            $this->stderr($e->getMessage() . "\n");

            if ($transaction !== null) {
                $transaction->rollBack();
            }
        }
    }

    /**
     * @throws \yii\base\InvalidConfigException
     * @return \yii\rbac\DbManager
     */
    protected function getAuthManagerComponent()
    {
        $authManager = \Yii::$app->get('authManager');
        if (!$authManager instanceof \yii\rbac\BaseManager) {
            throw new \yii\base\InvalidConfigException(
                sprintf('You should configure "%s" component before executing this command.', 'authManager')
            );
        }

        return $authManager;
    }

    /**
     * @throws \yii\base\InvalidConfigException
     * @return \yii\web\User
     */
    protected function getUserComponent()
    {
        $user = \Yii::$app->get('user');
        if (!$user instanceof \yii\web\User) {
            throw new \yii\base\InvalidConfigException(
                sprintf('You should configure "%s" component before executing this command.', 'user')
            );
        }

        return $user;
    }

    /**
     * @return \yii\caching\FileCache
     */
    private function getCacheComponent()
    {
        return new \yii\caching\FileCache([
            'keyPrefix' => 'rbac-runtime-',
            'serializer' => [
                ['yii\helpers\Json', 'encode'],
                ['yii\helpers\Json', 'decode'],
            ],
        ]);
    }

    private function updateRoles()
    {
        foreach ($this->roles() as $Role) {
            $this->getAuthManagerComponent()->add($Role);

            echo sprintf('    > role `%s` added.', $Role->name) . PHP_EOL;
        }
    }

    private function updateRules()
    {
        foreach ($this->rules() as $Rule) {
            $this->getAuthManagerComponent()->add($Rule);

            echo sprintf('    > rule `%s` added.', $Rule->name) . PHP_EOL;
        }
    }

    private function updatePermission()
    {
        foreach ($this->permissions() as $Permission) {
            $this->getAuthManagerComponent()->add($Permission);

            echo sprintf('    > permission `%s` added.', $Permission->name) . PHP_EOL;
        }
    }

    private function updateInheritanceRoles()
    {
        foreach ($this->inheritanceRoles() as $role => $items) {
            foreach ($items as $item) {
                $this->getAuthManagerComponent()
                    ->addChild(RbacFactory::Role($role), RbacFactory::Role($item));

                echo sprintf('    > role `%s` inherited role `%s`.', $role, $item) . PHP_EOL;
            }
        }
    }

    private function updateInheritancePermissions()
    {
        foreach ($this->inheritancePermissions() as $role => $items) {
            foreach ($items as $item) {
                $this->getAuthManagerComponent()
                    ->addChild(RbacFactory::Role($role), RbacFactory::Permission($item));

                echo sprintf('    > role `%s` inherited permission `%s`.', $role, $item) . PHP_EOL;
            }
        }
    }

    /**
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    protected function getAllAssignments()
    {
        $result = [];

        $Cache = $this->getCacheComponent();
        $AM = $this->getAuthManagerComponent();

        $useCache = $this->useCache === true;

        if ($useCache && $Cache->exists('assignments-0')) {
            echo '    > Assignments cache exists.' . PHP_EOL;

            $answer = $this->prompt('      > Use cache? [yes/no]');

            if (strpos($answer, 'y') === 0) {
                $this->cacheIterator(function ($key) use ($Cache, &$result) {
                    $result = \yii\helpers\ArrayHelper::merge($result, $Cache->get($key));
                });

                return $result;
            }
        }

        $User = $this->getUserComponent();
        $UsersQuery = call_user_func([$User->identityClass, 'find']);

        foreach ($UsersQuery->batch($this->batchSize) as $k => $Users) {
            $chunk = [];

            /** @var \yii\db\ActiveRecord|\yii\web\IdentityInterface $User */
            foreach ($Users as $User) {
                $pk = $User->primaryKey;

                $assignments = $AM->getAssignments($pk);
                $chunk[$pk] = array_keys($assignments);
                $result[$pk] = $chunk[$pk];
            }

            if ($useCache) {
                $Cache->set(sprintf('assignments-%d', $k), $chunk);
            }
        }

        return $result;
    }

    /**
     * @param array $assignments
     * $assignments = [
     *  'user_id' => ['role_1', 'role_2', 'role_3'],
     *  '1' => ['admin', 'user'],
     *  '2' => ['client', 'user'],
     *  '3' => ['manager', 'seller', 'support', 'user'],
     * ];
     * @throws \yii\base\InvalidConfigException
     */
    private function restoreAssignments($assignments)
    {
        $Cache = $this->getCacheComponent();

        $useCache = $this->useCache === true;

        foreach ($assignments as $user_id => $items) {
            if (!empty($this->forceAssign)) {
                if (!is_array($this->forceAssign)) {
                    $this->forceAssign = (array)$this->forceAssign;
                }

                foreach ($this->forceAssign as $role) {
                    $this->getAuthManagerComponent()
                        ->assign(RbacFactory::Role($role), $user_id);

                    echo sprintf('    > role `%s` force assigned to user id: %s.', $role, $user_id) . PHP_EOL;
                }
            }

            if (!empty($items)) {
                foreach ($items as $item) {
                    $item = isset($this->assignmentsMap[$item])
                        ? $this->assignmentsMap[$item]
                        : $item;

                    if (empty($item) || in_array($item, (array)$this->forceAssign, true)) {
                        continue;
                    }

                    $this->getAuthManagerComponent()
                        ->assign(RbacFactory::Role($item), $user_id);

                    echo sprintf('    > role `%s` assigned to user id: %s.', $item, $user_id) . PHP_EOL;
                }
            }
        }

        if ($useCache) {
            if ($Cache->exists('assignments-0')) {
                $this->cacheIterator(function ($key) use ($Cache) {
                    $Cache->delete($key);
                });
            }
        }
    }

    /**
     * @param callable $callback
     */
    private function cacheIterator($callback)
    {
        $Cache = $this->getCacheComponent();

        $limit = 1000;
        for ($i = 0; $i < $limit; $i++) {
            $key = sprintf('assignments-%d', $i);
            if ($Cache->exists($key)) {
                call_user_func($callback, $key);
            } else {
                break;
            }
        }
    }
}