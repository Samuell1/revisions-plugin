<?php namespace Samuell\Revisions;

use Backend;
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
        // Extend revision model with relations for user
        Revision::extend(function($model){
            $model->belongsTo['user'] = ['Backend\Models\User'];
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
