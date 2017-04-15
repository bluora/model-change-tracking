<?php

namespace Bluora\LaravelModelChangeTracking;

use App;
use Auth;
use Config;

trait LogStateChangeTrait
{
    /**
     * Log the state.
     *
     * @param string $model
     * @param mixed  $state
     *
     * @return void
     */
    private static function addModelStateChange($model, $state)
    {
        $models_path = Config::get('model_change_tracking.ModelsPath');
        $log_model_name = Config::get('model_change_tracking.LogModelStateChange');

        $log = new $log_model_name();
        $log->model = str_replace($models_path, '', static::class);
        $log->model_id = $model->id;
        $log->state = $state;
        $log->log_by = null;
        if (!App::runningInConsole() && Auth::check()) {
            $log->log_by = Auth::user()->id;
        }
        $log->ip_address = request()->ip();
        $log->save();
    }

    /**
     * Boot the events that apply which user is making the last event change.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.NpathComplexity)
     */
    public static function bootLogStateChangeTrait()
    {
        static::created(function ($model) {
            static::addModelStateChange($model, 'created');
        });

        static::updated(function ($model) {
            static::addModelStateChange($model, 'updated');
        });

        if (method_exists(get_class(), 'deleted')) {
            static::deleted(function ($model) {
                static::addModelStateChange($model, 'deleted');
            });
        }

        if (method_exists(get_class(), 'restored')) {
            static::restored(function ($model) {
                static::addModelStateChange($model, 'restored');
            });
        }
    }
}
