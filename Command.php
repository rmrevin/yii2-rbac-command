<?php
/**
 * Command.php
 * @author Revin Roman
 * @link https://rmrevin.ru
 */

namespace rmrevin\yii\rbac;

use yii\caching\Cache;
use yii\db\Connection;
use yii\di\Instance;
use yii\helpers\ArrayHelper;
use yii\rbac\BaseManager;
use yii\rbac\DbManager;
use yii\web\User;

/**
 * Class Command
 * @package rmrevin\yii\rbac
 */
abstract class Command extends \yii\console\Controller
{

    /**
     * Number of users processed in one step of the script
     * @var integer
     */
    public $batchSize = 1000;

    /**
     * Roles to be added to all users
     * @var array
     */
    public $forceAssign = [];

    /**
     * Map of roles reversal
     * @var array
     */
    public $assignmentsMap = [
        // 'frontend.acess' => 'frontend.access', // mistake example
    ];

    /**
     * Use transaction if used db auth manager
     * @var boolean
     */
    public $useTransaction = true;

    /**
     * Use the cache to recover roles if the failure occurred during initialization of new roles
     * @var boolean
     */
    public $useCache = true;

    /**
     * Database component
     * @var string|array|Connection
     */
    public $db = 'db';

    /**
     * Auth manager component
     * @var string|array|BaseManager
     */
    public $authManager = 'authManager';

    /**
     * Web user component
     * @var string|array|User
     */
    public $user = 'user';

    /**
     * Cache component (if null, will create a new copy of the file cache)
     * @var null|string|array|Cache
     */
    public $cache;

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (parent::beforeAction($action)) {
            $this->db = Instance::ensure($this->db, Connection::className());
            $this->authManager = Instance::ensure($this->authManager, BaseManager::className());
            $this->user = Instance::ensure($this->user, User::className());

            if ($this->authManager instanceof DbManager) {
                $this->authManager->db = $this->db;
            }

            if (empty($this->cache)) {
                $this->cache = $this->createCacheComponent();
            } else {
                $this->cache = Instance::ensure($this->cache, Cache::className());
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * @return \yii\rbac\Role[]
     */
    abstract protected function roles();

    /**
     * @return \yii\rbac\Rule[]
     */
    abstract protected function rules();

    /**
     * @return \yii\rbac\Permission[]
     */
    abstract protected function permissions();

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

        $useTransaction =
            $this->authManager instanceof DbManager
            && $this->useTransaction === true;

        $transaction = null;

        if ($useTransaction) {
            $transaction = $this->db->beginTransaction();
        }

        try {
            $this->authManager->removeAll();

            $this->updateRules();
            $this->updateRoles();
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
            $this->stderr($e->getMessage() . PHP_EOL);

            if ($transaction !== null) {
                $transaction->rollBack();
            }
        }

        if ($this->authManager instanceof DbManager) {
            $this->authManager->invalidateCache();
        }
    }

    /**
     * Update roles method
     */
    protected function updateRoles()
    {
        foreach ($this->roles() as $Role) {
            $this->authManager->add($Role);

            echo sprintf('    > role `%s` added.', $Role->name) . PHP_EOL;
        }
    }

    /**
     * Update rules method
     */
    protected function updateRules()
    {
        foreach ($this->rules() as $Rule) {
            $this->authManager->add($Rule);

            echo sprintf('    > rule `%s` added.', $Rule->name) . PHP_EOL;
        }
    }

    /**
     * Update permissions method
     */
    protected function updatePermission()
    {
        foreach ($this->permissions() as $Permission) {
            $this->authManager->add($Permission);

            echo sprintf('    > permission `%s` added.', $Permission->name) . PHP_EOL;
        }
    }

    /**
     * Update inheritance roles method
     */
    protected function updateInheritanceRoles()
    {
        foreach ($this->inheritanceRoles() as $role => $items) {
            foreach ($items as $item) {
                $this->authManager
                    ->addChild(RbacFactory::Role($role), RbacFactory::Role($item));

                echo sprintf('    > role `%s` inherited role `%s`.', $role, $item) . PHP_EOL;
            }
        }
    }

    /**
     * Update inheritance permissions method
     */
    protected function updateInheritancePermissions()
    {
        foreach ($this->inheritancePermissions() as $role => $items) {
            foreach ($items as $item) {
                $this->authManager
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

        $useCache = $this->useCache === true;

        if ($useCache && $this->cache->exists('assignments-0')) {
            echo '    > Assignments cache exists.' . PHP_EOL;

            $answer = $this->prompt('      > Use cache? [yes/no]');

            if (strpos($answer, 'y') === 0) {
                $this->cacheIterator(function ($key) use (&$result) {
                    $result = ArrayHelper::merge($result, $this->cache->get($key));
                });

                return $result;
            }
        }

        /** @var \yii\db\ActiveQuery $UsersQuery */
        $UsersQuery = call_user_func([$this->user->identityClass, 'find']);

        /** @var \yii\web\IdentityInterface[] $Users */
        foreach ($UsersQuery->batch($this->batchSize, $this->db) as $k => $Users) {
            $chunk = [];

            foreach ($Users as $User) {
                $pk = $User->getId();

                $assignments = array_keys($this->authManager->getAssignments($pk));

                $chunk[$pk] = $assignments;
                $result[$pk] = $assignments;
            }

            if ($useCache) {
                $this->cache->set(sprintf('assignments-%d', $k), $chunk);
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
    protected function restoreAssignments($assignments)
    {
        $useCache = $this->useCache === true;

        foreach ($assignments as $user_id => $items) {
            if (!empty($this->forceAssign)) {
                if (!is_array($this->forceAssign)) {
                    $this->forceAssign = (array)$this->forceAssign;
                }

                foreach ($this->forceAssign as $role) {
                    $this->authManager
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

                    $this->authManager
                        ->assign(RbacFactory::Role($item), $user_id);

                    echo sprintf('    > role `%s` assigned to user id: %s.', $item, $user_id) . PHP_EOL;
                }
            }
        }

        if ($useCache) {
            if ($this->cache->exists('assignments-0')) {
                $this->cacheIterator(function ($key) {
                    $this->cache->delete($key);
                });
            }
        }
    }

    /**
     * @param callable $callback
     */
    protected function cacheIterator($callback)
    {
        $i = 0;

        while (true) {
            $key = sprintf('assignments-%d', $i);

            if ($this->cache->exists($key)) {
                call_user_func($callback, $key);
            } else {
                break;
            }

            $i++;
        }
    }

    /**
     * @return \yii\caching\FileCache
     */
    protected function createCacheComponent()
    {
        return new \yii\caching\FileCache([
            'keyPrefix' => 'rbac-runtime-',
            'cacheFileSuffix' => '.json',
            'serializer' => [
                ['yii\helpers\Json', 'encode'],
                ['yii\helpers\Json', 'decode'],
            ],
        ]);
    }
}
