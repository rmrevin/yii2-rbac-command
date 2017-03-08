<?php
/**
 * ProjectRbacCommand.php
 * @author Revin Roman
 * @link https://rmrevin.ru
 */

namespace rmrevin\yii\rbac\examples;

use rmrevin\yii\rbac\RbacFactory;

/**
 * Class ProjectRbacCommand
 * @package rmrevin\yii\rbac\examples
 */
class ProjectRbacCommand extends \rmrevin\yii\rbac\Command
{

    protected function rules()
    {
        return [
            RbacFactory::Rule('frontend.contract.its-my', '\rmrevin\yii\rbac\examples\ItsMyContract'),
        ];
    }

    protected function roles()
    {
        return [
            RbacFactory::Role('admin', 'Admin'),
            RbacFactory::Role('manager', 'Manager'),
            RbacFactory::Role('seller', 'Seller'),
            RbacFactory::Role('buyer', 'Buyer'),
            RbacFactory::Role('user', 'User'),
        ];
    }

    protected function permissions()
    {
        return [
            RbacFactory::Permission('frontend.access', 'Can access'),
            /** Account module */
            RbacFactory::Permission('backend.account.access', 'Can access backend account module'),
            RbacFactory::Permission('backend.account.approve', 'Can approve accounts'),
            RbacFactory::Permission('backend.account.create', 'Can create accounts'),
            RbacFactory::Permission('backend.account.update', 'Can edit accounts'),
            RbacFactory::Permission('backend.account.delete', 'Can remove accounts'),
            /** Contract module */
            RbacFactory::Permission('frontend.contract.access', 'Can access contract module'),
            RbacFactory::Permission('frontend.contract.import', 'Can import contracts'),
            RbacFactory::Permission('frontend.contract.create', 'Can create contracts'),
            RbacFactory::Permission('frontend.contract.update', 'Can edit contracts'),
            RbacFactory::Permission('frontend.contract.update.own', 'Can edit own contracts', 'frontend.contract.its-my'),
            RbacFactory::Permission('frontend.contract.delete', 'Can remove contracts'),
            RbacFactory::Permission('frontend.contract.delete.own', 'Can remove own contracts', 'frontend.contract.its-my'),
            /** Deal module */
            RbacFactory::Permission('backend.deal.access', 'Can access backend deal module'),
            RbacFactory::Permission('frontend.deal.create', 'Can create deals'),
            RbacFactory::Permission('frontend.deal.buy', 'Can access own deals as buyer'),
            RbacFactory::Permission('frontend.deal.sell', 'Can access own deals as seller'),
            /** Pages module */
            RbacFactory::Permission('backend.pages.access', 'Can access backend pages module'),
            RbacFactory::Permission('backend.pages.create', 'Can create pages'),
            RbacFactory::Permission('backend.pages.update', 'Can edit pages'),
            RbacFactory::Permission('backend.pages.delete', 'Can remove pages'),
            RbacFactory::Permission('frontend.pages.view', 'Can view pages'),
            /** Settings module */
            RbacFactory::Permission('backend.settings.access', 'Can access backend settings module'),
        ];
    }

    protected function inheritanceRoles()
    {
        return [
            'admin' => ['manager'],
            'manager' => ['seller', 'buyer'],
            'seller' => ['user'],
            'buyer' => ['user'],
            'user' => [],
        ];
    }

    protected function inheritancePermissions()
    {
        return [
            'admin' => [
                'backend.account.delete',
                'backend.pages.delete',
            ],
            'manager' => [
                'backend.settings.access',
                'backend.account.access',
                'backend.account.approve',
                'backend.account.create',
                'backend.account.update',
                'backend.pages.access',
                'backend.pages.create',
                'backend.pages.update',
                'frontend.contract.update',
                'frontend.contract.delete',
                'backend.deal.access',
            ],
            'seller' => [
                'frontend.deal.buy',
                'frontend.deal.sell',
                'frontend.contract.import',
                'frontend.contract.create',
                'frontend.contract.update.own',
                'frontend.contract.delete.own',
            ],
            'buyer' => [
                'frontend.deal.buy',
                'frontend.deal.create',
            ],
            'user' => [
                'frontend.access',
                'frontend.contract.access',
                'frontend.pages.view',
            ],
        ];
    }
}