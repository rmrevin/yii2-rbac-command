Yii 2 RBAC update command
=========================
This extension provides a console command to update the RBAC rules, roles and permissions
for [Yii framework 2.0](http://www.yiiframework.com/) applications.


Installation
------------
```bash
composer require "rmrevin/yii2-rbac-command:~1.6"
```

Configuration
-------------
Create new console command extends `\rmrevin\yii\rbac\Command` ([example](https://github.com/rmrevin/yii2-rbac-command/blob/master/examples/ProjectRbacCommand.php))
```php
<?php

namespace app\commands;

class RbacCommand extends \rmrevin\yii\rbac\Command
{

    protected function rules()
    {
        // ...
    }

    protected function roles()
    {
        // ...
    }

    protected function permissions()
    {
        // ...
    }

    protected function inheritanceRoles()
    {
        // ...
    }

    protected function inheritancePermissions()
    {
        // ...
    }
}
```

In console application config
(example: `/protected/config/console.php`)
```php
<?
return [
  // ...
	'controllerMap' => [
		// ...
		'rbac' => [
			'class' => 'app\commands\RbacCommand',
			'batchSize' => 1000,
			'forceAssign' => ['user'], // force assign user role for all users
			'assignmentsMap' => [
			    'frontend.old' => 'frontend.new', // after next update all `frontend.old` will be replaced by `frontend.new`
			],
			'useTransaction' => true,
			'useCache' => true,
		],
	],
	// ...
];
```

Usage
-----
Execute command in command line
```
yii rbac/update
```