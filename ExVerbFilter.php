<?php

namespace subtego\filters;

use frontend\modules\subtego\helpers\StatusManager as SM;
use yii;
use yii\base\ActionEvent;
use yii\base\Behavior;
use yii\web\Controller;
use yii\web\MethodNotAllowedHttpException;

/**
 * Дополнительный класс фильтра методов доступа
 * Class ExVerbFilter
 * @package subtego\filters
 */
class ExVerbFilter extends Behavior
{
    /**
     * Действия контроллера для проверки
     * 'actionName' => ['ajax', ['pjax'] ... @see Request::is[Method]
     * @var array
     */
    public $actions = [];

    public function events()
    {
        return [
            Controller::EVENT_BEFORE_ACTION => 'beforeAction',
        ];
    }

    /**
	 * Проверка перед событием. @see Controller::EVENT_BEFORE_ACTION	
     * @param $event ActionEvent
     * @return bool
     * @throws yii\web\BadRequestHttpException
     */
    public function beforeAction($event)
    {

        $this->actions = array_change_key_case($this->actions, CASE_LOWER);

        $action     = $event->action->id;
        $allowed    = $this->actions[strtolower($action)];
        $others     = $this->actions['*'];

        if (isset($allowed) || isset($others)) {

            $allowed = isset($allowed) ? $allowed : $others;

            foreach ($allowed as $allow) {

                $allow  = ucfirst(strtolower($allow));
                $method = "getIs{$allow}";
                if (method_exists(Yii::$app->request, $method)) {
                    $event->isValid &= Yii::$app->request->{$method}();
                }
            }

        }

        if (!$event->isValid) throw new yii\web\BadRequestHttpException(SM::HTTP_BAD_REQUEST_400);

        return $event->isValid;
    }
}