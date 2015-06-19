<?php
/**
 * ItsMyContract.php
 * @author Revin Roman
 * @link https://rmrevin.ru
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