<?php

namespace frontend\modules\subtego;

use yii;
use yii\web\GroupUrlRule;
use yii\base\BootstrapInterface;

class BaseModule extends \yii\base\Module implements BootstrapInterface
{
    public $urlPrefix;
    public $urlRules = [];

    public $core = [
        'basePath'      => 'core',
        'instance'      => 'core',

        'components'    => [
            'templates'     => 'templates',
            'models'        => 'models',
            'collections'   => 'collections',
            'views'         => 'views',
            'routers'       => 'routers',
        ],
    ];

    /**
     * Возваращает путь к папке с шаблонами (templates)
     * @return string
     */
    public function getTemplatesPath() {
        return "@{$this->id}/{$this->core['basePath']}/{$this->core['components']['templates']}";
    }

    public function init()
    {
        parent::init();

        Yii::configure($this, [
            'layout'            => 'main.php',
            'layoutPath'	    => '@app/modules/subtego/views/layouts',
        ]);

        // custom initialization code goes here
        $this->registerTranslations();
    }

    public function behaviors()
    {
        return [
            'as filter' 	=> [
                'class' => 'app\modules\subtego\filters\ProfileFilter',
            ]
        ];
    }

    public function registerTranslations()
    {
        Yii::$app->i18n->translations['subtego*'] = [
            'class'          => 'yii\i18n\PhpMessageSource',
            'sourceLanguage' => 'ru-RU',
            'basePath'       => "@app/modules/subtego/messages",
            'fileMap'        => [

            ],
        ];

        Yii::$app->i18n->translations['*'] = [
            'class'          => 'yii\i18n\PhpMessageSource',
            'sourceLanguage' => 'ru-RU',
            'basePath'       => "@app/modules/{$this->id}/messages",
        ];
    }

    public static function t($category, $message, $params = [], $language = null)
    {
        return Yii::t($category, $message, $params, $language);
    }

    public function bootstrap($app) {

        $configUrlRule = [
            'rules'         => $this->urlRules,
            'routePrefix'   => $this->urlPrefix
        ];

        $app->urlManager->addRules([new GroupUrlRule($configUrlRule)], false);
    }
}