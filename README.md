Yii 2 extension for RBAC migrations
===============================

Installation
------------
Add in `composer.json`:
```
{
    "require": {
        "rmrevin/yii2-rbac-migration": "1.1.*"
    }
}
```

Usage
-----
Create new migration extends \rmrevin\yii\rbac\RbacMigration
and execute as normal migration
```php
<?
// ...

class m140217_201400_rbac extends \rmrevin\yii\rbac\RbacMigration
{

    protected function getNewRoles()
    {
        return [
            RbacFactory::Role('admin', 'Administrator'),
            RbacFactory::Role('manager', 'Manager'),
            RbacFactory::Role('customer', 'Customer'),
            RbacFactory::Role('user', 'User'),
        ];
    }

    protected function getNewPermissions()
    {
        return [
            RbacFactory::Permission('catalog.view', 'Can view catalog'),
            RbacFactory::Permission('catalog.order', 'Can order items from catalog'),
            RbacFactory::Permission('catalog.favorite', 'Can mark favorite items'),
        ];
    }

    protected function getNewInheritance()
    {
        return [
            'admin' => [
                'manager', // inherit role manager and all permissions from role manager & user
            ],
            'manager' => [
                'user', // inherit role user and all permissions from role user
            ],
            'customer' => [
                'user', // inherit role user and all permissions from role user

                'catalog.order', // inherit permission catalog.order
                'catalog.favorite', // inherit permission catalog.favorite
            ],
            'user' => [
                'catalog.view', // inherit permission catalog.view
            ],
        ];
    }

    protected function getOldInheritance()
    {
        return [
            'admin' => [
                'manager', // inherit role manager and all permissions from role manager & user
            ],
            'manager' => [
                'user', // inherit role user and all permissions from role user
            ],
            'user' => [
            ],
        ];
    }
}

```

Reference
---------
Inheritance:
* `protected getNewInheritance()`
* `protected getOldInheritance()`

Rules:
* `protected getNewRules()`
* `protected getRenamedRules()`
* `protected getRemoveRules()`

Roles:
* `protected getNewRoles()`
* `protected getRenamedRoles()`
* `protected getRemoveRoles()`

Permissions:
* `protected getNewPermissions()`
* `protected getRenamedPermissions()`
* `protected getRemovePermissions()`