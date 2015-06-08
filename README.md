Yii 2 extension for RBAC command
===============================

Installation
------------
Add in `composer.json`:
```
{
    "require": {
        "rmrevin/yii2-rbac-command": "1.0.*"
    }
}
```

Configuration
-------------
Create new command extends \rmrevin\yii\rbac\Command
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
return array(
  // ...
	'controllerMap' => array(
		// ...
		'rbac' => array(
			'class' => 'app\commands\RbacCommand',
			'batchSize' => 1000,
			'assignmentsMap' => [
			    'frontend.old' => 'frontend.new', // after next update all `frontend.old` will be replaced by `frontend.new`
			],
		),
	),
	// ...
);
```

Usage
-----
Execute command in command line
```
./yii rbac/update
```