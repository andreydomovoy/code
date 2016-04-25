<?php

namespace common\components\rbac;

use yii\rbac\ManagerInterface;
use yii\rbac\Item;

class RbacCreator
{
    public $config = 'config.php';

    /**
     * @param $auth ManagerInterface
     */
    public function run($auth) {
		$auth->removeAll();
        $this->create($auth, require ("/{$this->config}"));
    }

    /**
     * @param $auth ManagerInterface
     * @param $config array
     */
    protected function create($auth, $config, $parent = null) {

        foreach ($config as $name => $params) {

            if (is_array($params) && isset($params['enabled']) && $params['enabled']) {

                // создаем роль или разрешение
                $isRole = $params['type'] == Item::TYPE_ROLE;
                $item =  $isRole ? $auth->createRole($name) : $auth->createPermission($name);
                $item->description = $params['description'];

                // создаем правило, если указано и добавляем его
                if (!empty($params['ruleClass'])) {
                    $rule = new $params['ruleClass']();
                    $auth->add($rule);
                    $item->ruleName = $rule->name;
                }

                // добавляем роль/разрешение
                $auth->add($item);
                // если установлен родитель - подключаем к нему наследника
                if ($parent) $auth->addChild($parent, $item);

                // проходим по дочерним ролям и разрешениям
                if (isset($params['roles']))        $this->create($auth, $params['roles'], $item);
                if (isset($params['permissions']))  $this->create($auth, $params['permissions'], $item);
            }
        }
    }
}