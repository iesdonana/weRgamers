<?php

namespace app\models;

use Yii;
use yii\helpers\FileHelper;
use yii\imagine\Image;
use yii\web\UploadedFile;

/**
 * This is the model class for table "games".
 *
 * @property integer $id
 * @property string $name
 * @property string $genre
 * @property string $released
 * @property string $developers
 *
 * @property GamesPlatforms[] $gamesPlatforms
 */
class Game extends \yii\db\ActiveRecord
{
    /**
     * Escenario de creación de juego
     * @var string
     */
    const ESCENARIO_RECORD = 'create';

    /**
     * Escenario de subida de caratula
     * @var string
     */
    const ESCENARIO_UPLOAD = 'upload';

    /**
     * @var UploadedFile Carátula del juego
     */
    public $imageFile;

    /**
     * @var string[] Plataformas que tiene el modelo
     */
    public $platforms;

    /**
     * Nombre de la tabla asociada al modelo.
     * @return string
     */
    public static function tableName()
    {
        return 'games';
    }

    /**
     * Reglas del modelo.
     * @return array
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['released', 'platforms'], 'safe'],
            [['released'], 'date', 'format' => 'php:Y-m-d'],
            [['released'], 'match', 'pattern' => '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', 'message' => Yii::t('app', 'The format must be "yyyy-mm-dd" and must be a valid date.')],
            [['name', 'genre', 'developers'], 'string', 'max' => 255],
            [['imageFile'], 'image', 'skipOnEmpty' => false, 'extensions' => ['png', 'jpg', 'gif'], 'maxSize' => 1024*1024*1024*8],
        ];
    }

    /**
     * Labels de las propiedades del modelo.
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
            'genre' => Yii::t('app', 'Genre'),
            'released' => Yii::t('app', 'Released'),
            'developers' => Yii::t('app', 'Developers'),
            'imageFile' => Yii::t('app', 'Cover'),
            'namePlatforms' => Yii::t('app', 'Platforms'),
        ];
    }

    /**
     * Escenarios del modelo.
     * @return array
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::ESCENARIO_RECORD] = ['name', 'genre', 'released', 'developers', 'platforms'];
        $scenarios[self::ESCENARIO_UPLOAD] = ['imageFile'];
        return $scenarios;
    }

    /**
     * Guarda una imagen en covers.
     * @return bool
     */
    public function upload()
    {
        if ($this->validate()) {
            $covers = Yii::getAlias('@covers');
            $files = FileHelper::findFiles($covers);

            if (isset($files[0])) {
                foreach ($files as $file) {
                    $archivo = substr($file, strrpos($file, DIRECTORY_SEPARATOR) + 1);
                    $nombre = substr($archivo, 0, strlen($archivo) - 4);
                    if (strlen($nombre) === 1 && intval($nombre) === Yii::$app->user->id) {
                        unlink(Yii::getAlias('@app/web/' . $file));
                    }
                }
            }

            $nombre = Yii::getAlias('@covers/')
                . $this->id . '.' . $this->imageFile->extension;
            $this->imageFile->saveAs($nombre);
            Image::thumbnail($nombre, null, 500)
                ->save($nombre);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Cambia el escenario después de save().
     * @param  bool $insert            Indica si se ha insertado o modificado el modelo
     * @param  array $changedAttributes Atributos cambiados
     * @return bool
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        $this->scenario = self::ESCENARIO_UPLOAD;
        return $this->savePlatforms();
    }

    /**
    * Guarda las plataformas del juego.
    * @return bool true si ha guardado todas las plataformas, false en caso contrario
    */
    public function savePlatforms()
    {
        $this->refresh();
        $saved = true;
        if ($this->platforms !== '') {
            foreach ($this->platforms as $value) {
                $gamePlatform = new GamePlatform;
                $gamePlatform->id_game = $this->id;
                $gamePlatform->id_platform = $value;
                if (!GamePlatform::find()->where(['id_game' => $gamePlatform->id_game, 'id_platform' => $gamePlatform->id_platform])->exists()) {
                    $saved = $gamePlatform->save() && $saved;
                }
            }
        }
        return $saved;
    }

    /**
     * Devuelve la ruta a la caratula del juego.
     * @return string Ruta de la caratula del juego
     */
    public function getCover()
    {
        $covers = Yii::getAlias('@covers');
        $files = FileHelper::findFiles($covers);
        if (isset($files[0])) {
            foreach ($files as $file) {
                $archivo = substr($file, strrpos($file, DIRECTORY_SEPARATOR) + 1);
                $nombre = substr($archivo, 0, strrpos($archivo, '.'));
                if (intval($nombre) === $this->id) {
                    return "/$covers/$archivo";
                }
            }
        }
        return "/$covers/default.png";
    }


    /**
     * Devuelve los nombres de las plataformas del juego concatenadas.
     * @return string Nombres de las plataformas del juego
     */
    public function getNamePlatforms()
    {
        return implode(', ', Platform::find()
                        ->select('platforms.name')->joinWith('games')
                        ->where(['games.id' => $this->id])
                        ->column());
    }

    /**
     * Devuelve el número total de reseñas que tienen un juego.
     * @return int Número de reseñas
     */
    public function getTotalReviews()
    {
        return Review::find()->where(['id_game' => $this->id])->count();
    }
    /**
     * Devuelve la puntuación media del juego.
     * @return float Puntuación del juego
     */
    public function getScore()
    {
        $total = Review::find()->select('score')->where(['id_game' => $this->id])->sum('score');
        if ($total == 0) {
            return 0;
        } else {
            return round($total / $this->totalReviews, 1, PHP_ROUND_HALF_EVEN);
        }
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGamePlatforms()
    {
        return $this->hasMany(GamePlatform::className(), ['id_game' => 'id'])->inverseOf('game');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlatforms()
    {
        return $this->hasMany(Platform::className(), ['id' => 'id_platform'])->via('gamePlatforms');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReviews()
    {
        return $this->hasMany(Review::className(), ['id_game' => 'id'])->inverseOf('game');
    }
}
