<?php

namespace frontend\modules\subtego\helpers;

use yii\base\ErrorException;
use Yii;
use yii\helpers\Json;

define('DS', '/');

class FileHelper extends \yii\helpers\FileHelper {

    private $_settings = [
        'root'  => 'u',
        'types' => ['a', 'w', 'p'],

        'cursor.name'			=> 	'cursor.json',
        'cursor.folder'		    =>  'folder',
        'cursor.sub'			=>  'subfolder',
        'cursor.sub.count'		=> 	'subfolders',
        'cursor.files.count'	=>  'count-files',

        'max.sub'			    => 100,
        'max.files'			    => 1000,
        'max.folder.length'	    => 15,
        'max.file.length'	    => 20
    ];

    // типы файлов для сохранения
    const TYPE_AVATAR 		= 0;
    const TYPE_WALLPAPER 	= 1;
    const TYPE_PHOTOS 		= 2;

    // источники файлов
    const SOURCE_FILESYSTEM = 1;

    private $_fp;
    private $_type;
    private $_cursorPath;
    private $_cursorSize;

    function __construct($type) {
        $this->initialize($type);
    }

    /**
     * Инициализация объекта
     *
     * @param $type integer Тип
     * @throws ErrorException
     * @throws \yii\base\Exception
     */
    public function initialize ($type) {
        // проверяем и устанавливаем тип
        if (!in_array($type, [self::TYPE_AVATAR, self::TYPE_WALLPAPER, self::TYPE_PHOTOS])) throw new ErrorException("Неверно указан тип");
        else $this->_type = $this->_settings['types'][$type];

        // если папки upload не существует - создаем ее
        if (!file_exists($this->_settings['root'])) $this->createDirectory($this->_settings['root']);

        // если папки $type не существует - создаем ее
        $typePath = "{$this->_settings['root']}/{$this->_type}";
        if (!file_exists($typePath)) $this->createDirectory($typePath);

        $this->_cursorPath = "{$this->_settings['root']}/{$this->_type}/{$this->_settings['cursor.name']}";
        $this->_cursorSize = file_exists ($this->_cursorPath) ? filesize($this->_cursorPath) : 0;
    }

    /**
     * Генерация пути для сохранения файла
     * @param $ext string Расширение
     * @return string Путь для сохранения
     */
    public function generatePath($ext) {

        // Составные пути файла $upload/$type/$folderName/$subfolderName/$fileName.ext
        $folderName 	= '';
        $subfolderName 	= '';
        $cursor 		= [];

        if ($this->openCursor()) {

            // файл курсора уже был ранее создан
            if ($this->_cursorSize) {

                $cursor = Json::decode($this->readCursor(), true);

                $folderName     = $cursor[$this->_settings['cursor.folder']];
                $subfolderName  = $cursor[$this->_settings['cursor.sub']];

                // если уже файлов в папке под завязку
                if ($cursor[$this->_settings['cursor.files.count']] >= $this->_settings['max.files']) {

                    // генерируем имя новой подпапки
                    $subfolderName = self::generateName($this->_settings['max.folder.length']);

                    // устанавливаем в курсор новую инфу
                    $cursor[$this->_settings['cursor.sub']] = $subfolderName;
                    $cursor[$this->_settings['cursor.sub.count']] ++;
                    $cursor[$this->_settings['cursor.files.count']] = 0;
                }

                // если превышено допустимое количество подпапок
                if ($cursor[$this->_settings['cursor.sub.count']] > $this->_settings['max.sub']) {

                    $folderName     = self::generateName($this->_settings['max.folder.length']);
                    $subfolderName  = self::generateName($this->_settings['max.folder.length']);

                    $cursor[$this->_settings['cursor.folder']]      = $folderName;
                    $cursor[$this->_settings['cursor.sub']]         = $subfolderName;
                    $cursor[$this->_settings['cursor.sub.count']]   = 1;
                    $cursor[$this->_settings['cursor.files.count']] = 0;
                }

            } else {

                $folderName = self::generateName($this->_settings['max.folder.length']);
                $subfolderName = self::generateName($this->_settings['max.folder.length']);

                $cursor = [
                    $this->_settings['cursor.folder']       => $folderName,
                    $this->_settings['cursor.sub']          => $subfolderName,
                    $this->_settings['cursor.sub.count']    => 1,
                    $this->_settings['cursor.files.count']  => 0
                ];
            }
        }

        // иначе просто увеличиваем количество файлов в курсоре
        $fileName = self::generateName($this->_settings['max.folder.length']);
        $cursor[$this->_settings['cursor.files.count']] ++;

        $this->updateCursor(Json::encode($cursor));

        $realPath = "{$this->_settings['root']}/{$this->_type}/{$folderName}/{$subfolderName}";
        if (!file_exists($realPath)) $this->createDirectory($realPath);

        return "{$realPath}/{$fileName}.{$ext}";
    }

    /**
     * Удаляет файл с изменением количества файлов в курсоре
     * @param $filename string
     * @return bool
     */
    public function removeFile($filename) {
        $filepath = Yii::getAlias("@webroot/{$filename}");

        if (file_exists($filepath)) {
            unlink($filepath);
            if ($this->openCursor()) {
                $cursor = Json::decode($this->readCursor(), true);
                $countIndex = $this->_settings['cursor.files.count'];

                $cursor[$countIndex]--;
                if ($cursor[$countIndex] < 0) $cursor[$countIndex] = 0;

                $this->updateCursor(Json::encode($cursor));
            }
        } else return true;
    }

    /**
     * Генерирует имя директории/файла
     * @param $length int Длина имени
     * @return string Имя
     */
    private static function generateName($length) {
        return \Yii::$app->security->generateRandomString($length);
    }

    /**
     * Возвращает расширение файла
     * @param $path string Путь к файлу
     * @return string Расширение файла
     */
    public static function getExtension($path) {
        return pathinfo($path)['extension'];
    }

    /**
     * Открывает файл курсора для записи с эксклюзивной блокировкой
     * @return bool Результат открытия
     */
    protected function openCursor() {

        $this->_fp = fopen ($this->_cursorPath, "a+");   // открытие файла
        return flock ($this->_fp, LOCK_EX);              // блокировка файла
    }

    /**
     * Считывает данные из Курсора
     * @return string Данные
     */
    protected function readCursor() {
        return fread($this->_fp, $this->_cursorSize);
    }

    /**
     * Записывает новые данные в Курсор и закрывает поток
     * @param $data mixed Данные для записи
     */
    protected function updateCursor($data) {
        ftruncate ($this->_fp, 0);      // УДАЛЯЕМ СОДЕРЖИМОЕ ФАЙЛА
        fputs ($this->_fp , $data);
        fflush ($this->_fp);            // очищение файлового буфера и запись в файл
        flock ($this->_fp, LOCK_UN);    //снятие блокировки
        fclose ($this->_fp);            // закрытие
    }

    /**
     * Возрващает полный путь к заданому адресу
     * @param $upload string Адрес
     * @return bool|string
     */
    public function getUploadPath($upload) {
        return Yii::getAlias("@webroot/$upload");
    }
}