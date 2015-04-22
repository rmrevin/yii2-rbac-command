<?php
/**
 * RbacFactory.php
 * @author Revin Roman http://phptime.ru
 */

namespace rmrevin\yii\rbac;

/**
 * Class RbacFactory
 * @package rmrevin\yii\rbac
 */
class RbacFactory
{

    /**
     * @param string $name
     * @param string $class
     * @param null $createdAt
     * @param null $updatedAt
     * @return \yii\rbac\Rule
     */
    public static function Rule($name, $class, $createdAt = null, $updatedAt = null)
    {
        return \Yii::createObject([
            'class' => $class,
            'name' => $name,
            'createdAt' => empty($createdAt) ? time() : $createdAt,
            'updatedAt' => empty($updatedAt) ? time() : $updatedAt,
        ]);
    }

    /**
     * @param string $name
     * @param null $description
     * @param null $ruleName
     * @param null $data
     * @return \yii\rbac\Role
     */
    public static function Role($name, $description = null, $ruleName = null, $data = null)
    {
        return self::Item('\yii\rbac\Role', $name, $description, $ruleName, $data);
    }

    /**
     * @param string $name
     * @param null $description
     * @param null $ruleName
     * @param null $data
     * @return \yii\rbac\Permission
     */
    public static function Permission($name, $description = null, $ruleName = null, $data = null)
    {
        return self::Item('\yii\rbac\Permission', $name, $description, $ruleName, $data);
    }

    /**
     * @param string $class
     * @param string $name
     * @param null $description
     * @param null $ruleName
     * @param null $data
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