<?php

namespace chiliec\vote\controllers;

use Yii;
use chiliec\vote\models\Rating;
use yii\web\MethodNotAllowedHttpException;
use yii\web\Response;

class DefaultController extends \yii\web\Controller
{
    public function actionIndex()
    {
        if(Yii::$app->request->getIsAjax()) { //todo: дополнительно проверить что запрос с нащего домена (или проверить токен)
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model_name = Yii::$app->request->getQueryParam('model_name');
            $target_id = Yii::$app->request->getQueryParam('target_id');
            $act = Yii::$app->request->getQueryParam('act');

            if(!Yii::$app->getModule('vote')->allow_guests) {
                if(!Yii::$app->user->getIsGuest()) {
                    $user_id = Yii::$app->user->getId();
                } else {
                    $user_id = null;
                }
            } else {
                $user_id = Yii::$app->request->getUserIP();
            }

            if($user_id==null) {
                return ['content' => Yii::t('vote','The user is not recognized'), 'successfully' => false];
            }

            $model_id = Rating::getModelIdByName($model_name);
            if(!is_int($model_id)) {
                return ['content' => Yii::t('vote','The model is not registered'), 'successfully' => false];
            }

            if($target_id==null) {
                return ['content' => Yii::t('vote', 'The purpose is not defined'), 'successfully' => false];
            }

            if($act=='like'){
                $act = 1;
            } elseif($act=='dislike') {
                $act = 0;
            } else {
                return ['content' => Yii::t('vote', 'Wrong action'), 'successfully' => false];
            }

            $isVoted = Rating::findOne(["model_id"=>$model_id, "target_id"=>$target_id, "user_id"=>$user_id]);
            if(is_null($isVoted)) {
                $newVote = new Rating;
                $newVote->model_id = $model_id;
                $newVote->target_id = $target_id;
                $newVote->user_id = (string)$user_id;
                $newVote->value = $act;
                if($newVote->save()) {
                    if($act===1) {
                        Yii::$app->cache->delete('likes'.$model_name.$target_id);
                        return ['content' => Yii::t('vote', 'Your vote is accepted. Thanks!'), 'successfully' => true];
                    } else {
                        Yii::$app->cache->delete('dislikes'.$model_name.$target_id);
                        return ['content' => Yii::t('vote', 'Thanks for your opinion'), 'successfully' => true];
                    }
                } else {
                    return ['content' => Yii::t('vote', 'Validation error'), 'successfully' => false];
                }
            } else {
                return ['content' => Yii::t('vote', 'You have already voted!'), 'successfully' => false];
            }
        } else {
            throw new MethodNotAllowedHttpException(Yii::t('vote', 'Forbidden method'), 405);
        }
    }
}
