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
            'description' => 'No description provided yet...',
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
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return []; // Remove this line to activate

        return [
            'Samuell\Revisions\Components\MyComponent' => 'myComponent',
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'samuell.revisions.some_permission' => [
                'tab' => 'Revisions',
                'label' => 'Some permission'
            ],
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return []; // Remove this line to activate

        return [
            'revisions' => [
                'label'       => 'Revisions',
                'url'         => Backend::url('samuell/revisions/mycontroller'),
                'icon'        => 'icon-leaf',
                'permissions' => ['samuell.revisions.*'],
                'order'       => 500,
            ],
        ];
    }

    /**
     * Registers any form widgets implemented in this plugin.
     */
    public function registerFormWidgets()
    {
        return [
            'Samuell\Revisions\RevisionHistory' => 'revisionhistory',
        ];
    }
}
