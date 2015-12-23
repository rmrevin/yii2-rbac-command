<?php
/**
 * RbacFactory.php
 * @author Revin Roman
 * @link https://rmrevin.ru
 */

namespace rmrevin\yii\rbac;

/**
 * Class RbacFactory
 * @package rmrevin\yii\rbac
 */
class RbacFactory
{

    /**
     * @param string $name name of the rule
     * @param string $class class of the rule
     * @return \yii\rbac\Rule
     */
    public static function Rule($name, $class)
    {
        return \Yii::createObject([
            'class' => $class,
            'name' => $name,
        ]);
    }

    /**
     * @param string $name the name of the role. This must be globally unique.
     * @param string|null $description the role description
     * @param string|null $ruleName name of the rule associated with this role
     * @param mixed $data the additional data associated with this role
     * @return \yii\rbac\Role
     */
    public static function Role($name, $description = null, $ruleName = null, $data = null)
    {
        return self::Item('\yii\rbac\Role', $name, $description, $ruleName, $data);
    }

    /**
     * @param string $name the name of the permission. This must be globally unique.
     * @param string|null $description the permission description
     * @param string|null $ruleName name of the rule associated with this permission
     * @param mixed $data the additional data associated with this permission
     * @return \yii\rbac\Permission
     */
    public static function Permission($name, $description = null, $ruleName = null, $data = null)
    {
        return self::Item('\yii\rbac\Permission', $name, $description, $ruleName, $data);
    }

    /**
     * @param string $class
     * @param string $name the name of the item. This must be globally unique.
     * @param string|null $description the item description
     * @param string|null $ruleName name of the rule associated with this item
     * @param mixed $data the additional data associated with this item
     * @return object
     */
    public static function Item($class, $name, $description = null, $ruleName = null, $data = null)
    {
        $config = [
            'class' => $class,
            'name' => $name,
        ];
        if (null !== $description) {
            $config['description'] = $description;
        }
        if (null !== $ruleName) {
            $config['ruleName'] = $ruleName;
        }
        if (null !== $data) {
            $config['data'] = $data;
        }

        return \Yii::createObject($config);
    }
}