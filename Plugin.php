<?php namespace Samuell\Revisions;

use Backend;
use Lang;
use System\Classes\PluginBase;
use System\Models\Revision;

/**
 * Revisions Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Revisions',
            'description' => 'Revisions allows to extend any model with Revisionable trait with more features and easy to use.',
            'author'      => 'Samuell',
            'icon'        => 'icon-leaf'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {
        Revision::extend(function ($model) {
            // Extend revision model with relations for user
            $model->belongsTo['user'] = ['Backend\Models\User'];

            // Add method to get formatted date that is compatible with Carbon 1 & 2
            $model->addDynamicMethod('getFormattedDate', function ($date) use ($model) {
                if (method_exists($model->$date, 'isoFormat')) {
                    return $model->$date->locale(Lang::getLocale())->isoFormat('LLL');
                } else {
                    return $model->$date->format('d. m. Y H:i');
                }
            });
        });
    }

    /**
     * Registers any form widgets implemented in this plugin.
     */
    public function registerFormWidgets()
    {
        return [
            'Samuell\Revisions\FormWidgets\RevisionHistory' => 'revisionhistory',
        ];
    }
}
