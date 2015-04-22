<?php
/**
 * Command.php
 * @author Revin Roman http://phptime.ru
 */

namespace rmrevin\yii\rbac;

/**
 * Class Command
 * @package rmrevin\yii\rbac
 */
abstract class Command extends \yii\console\Controller
{

    /** @var int */
    public $batch = 100;

    /** @var array */
    public $assignments_map = [
        // 'frontend.acess' => 'frontend.access', // mistake example
    ];

    public function actionUpdate()
    {
        $assignments = $this->getAllAssignments();

        $this->getAuthManagerComponent()
            ->removeAll();

        $this->updateRoles();
        $this->updateRules();
        $this->updatePermission();
        $this->updateInheritanceRoles();
        $this->updateInheritancePermissions();

        $this->restoreAssignments($assignments);
    }

    /**
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    protected function getAllAssignments()
    {
        $result = [];

        $AM = $this->getAuthManagerComponent();

        $User = $this->getUserComponent();
        $UsersQuery = call_user_func([$User->identityClass, 'find']);

        foreach ($UsersQuery->batch($this->batch) as $Users) {
            /** @var \yii\db\ActiveRecord|\yii\web\IdentityInterface $User */
            foreach ($Users as $User) {
                $assignments = $AM->getAssignments($User->primaryKey);
                $result[$User->primaryKey] = array_keys($assignments);
            }
        }

        return $result;
    }

    /**
     * @return \yii\rbac\Role[]
     */
    protected function roles()
    {
        return [];
    }

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
    protected function inheritanceRoles()
    {
        return [];
    }

    /**
     * @return array
     */
    protected function inheritancePermissions()
    {
        return [];
    }

    /**
     * @throws \yii\base\InvalidConfigException
     * @return \yii\rbac\DbManager
     */
    protected function getAuthManagerComponent()
    {
        $authManager = \Yii::$app->getAuthManager();
        if (!$authManager instanceof \yii\rbac\BaseManager) {
            throw new \yii\base\InvalidConfigException('You should configure "authManager" component before executing this command.');
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
            throw new \yii\base\InvalidConfigException('You should configure "user" component before executing this command.');
        }

        return $user;
    }

    private function updateRoles()
    {
        foreach ($this->roles() as $Role) {
            $this->getAuthManagerComponent()->add($Role);

            echo "    > role `{$Role->name}` added." . PHP_EOL;
        }
    }

    private function updateRules()
    {
        foreach ($this->rules() as $Rule) {
            $this->getAuthManagerComponent()->add($Rule);

            echo "    > rule `{$Rule->name}` added." . PHP_EOL;
        }
    }

    private function updatePermission()
    {
        foreach ($this->permissions() as $Permission) {
            $this->getAuthManagerComponent()->add($Permission);

            echo "    > permission `{$Permission->name}` added." . PHP_EOL;
        }
    }

    private function updateInheritanceRoles()
    {
        foreach ($this->inheritanceRoles() as $role => $items) {
            foreach ($items as $item) {
                $this->getAuthManagerComponent()
                    ->addChild(RbacFactory::Role($role), RbacFactory::Role($item));

                echo "    > role `{$role}` inherited role `{$item}`." . PHP_EOL;
            }
        }
    }

    private function updateInheritancePermissions()
    {
        foreach ($this->inheritancePermissions() as $role => $items) {
            foreach ($items as $item) {
                $this->getAuthManagerComponent()
                    ->addChild(RbacFactory::Role($role), RbacFactory::Permission($item));

                echo "    > role `{$role}` inherited permission `{$item}`." . PHP_EOL;
            }
        }
    }

    private function restoreAssignments($assignments)
    {
        foreach ($assignments as $user_id => $item) {
            $item = isset($this->assignments_map[$item])
                ? $this->assignments_map[$item]
                : $item;

            $this->getAuthManagerComponent()
                ->assign(RbacFactory::Role($item), $user_id);

            echo "    > role `{$item}` assigned to user id: {$user_id}." . PHP_EOL;
        }
    }
}