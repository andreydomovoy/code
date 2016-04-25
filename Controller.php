<?php

namespace subtego\extended;

use yii;
use yii\base\ActionEvent;
use yii\base\InlineAction;

/**
 * Расширенный контроллер с дополнительными возможностями
 * Class Controller
 * @package subtego\controllers
 */
class Controller extends \yii\web\Controller
{
    protected $finder;

    /**
     * Проверяет существование файла представления
     * @param $action string Имя представления для проверки
     * @return mixed
     */
    public function existView($action) {
        $viewPath = $this->module->getViewPath() . DIRECTORY_SEPARATOR . $this->id;
        return file_exists($viewPath . DIRECTORY_SEPARATOR . $action . "." . $this->getView()->defaultExtension);
    }

    /**
     * Перегружает страницу
     * @param bool $clean Очистить параметры запроса
     * @param string $anchor
     * @return $this|yii\web\Response
     */
    public function refresh($clean = false, $anchor = '')
    {
        return $clean ?
            Yii::$app->getResponse()->redirect(preg_replace('/(.+)\?.+/', "$1", Yii::$app->request->url)) :
            parent::refresh($anchor);
    }

    /**
     * Возвращает идентификатор текущего пользователя. Создан для упрощения записи.
     * @return int|string
     */
    public function getCurrentUser() {
        return Yii::$app->getUser()->getIdentity()->getId();
    }

    /**
     * Перенаправление на новый роут с возможностью передачи параметров
     * this::rerouting('main') -> call this::actionMain()
     *
     * @param $act string Экшн
     * @param $prefix string Префикс экшена
     * @return mixed
     */
    public function rerouting($act, $prefix = 'action') {

        $action = $prefix . ucfirst($act);

        $this->trigger(self::EVENT_BEFORE_ACTION, new ActionEvent(new InlineAction(strtolower($act), Yii::$app->controller, $action)));

        if (func_num_args() > 2) {
            $argsList = func_get_args();
            unset($argsList[0], $argsList[1]);
            return call_user_func_array([$this, $action], $argsList);
        }

        return $this->$action();
    }

    /**
     * Получение параметров модуля, к котрому принадлежит вызваный контроллер
     * @return array
     */
    public function getModuleParams() {
        return $this->module->params;
    }

    /**
     * @return Formatter
     */
    public function getFormatter() {
        return Yii::$app->formatter;
    }

    /**
     * Проверка запроса: он должен быть POST и Ajax.
     * Используется при запросах на обновление модели через backbone
     * @return bool
     */
    public function isAjaxPost() {
        return ($_SERVER['REQUEST_METHOD'] === 'POST' && Yii::$app->request->isAjax);
    }

    /**
     * Проверяет является ли параметр запроса числом.
     * @param $query string
     * @return bool
     */
    public function isDigits($query) {
        return preg_match("/\\d+/", $query) === 1;
    }
}