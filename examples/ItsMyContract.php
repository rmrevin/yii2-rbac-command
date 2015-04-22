<?php
/**
 * ItsMyContract.php
 * @author Revin Roman http://phptime.ru
 */

namespace rmrevin\yii\rbac\examples;

/**
 * Class ItsMyContract
 * @package rmrevin\yii\rbac\examples
 */
class ItsMyContract extends \yii\rbac\Rule
{

    public $name = 'frontend.contract.its-my';

    /**
     * @inheritdoc
     */
    public function execute($user, $item, $params)
    {
        return $user === $params['Contract']->seller_id;
    }
}