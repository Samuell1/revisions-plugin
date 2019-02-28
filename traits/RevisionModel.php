<?php namespace Samuell\Revisions\Traits;

use BackendAuth;
use Exception;

/**
 * Extend model with revision methods
 */
trait RevisionModel
{
    public static function bootRevisionable()
    {
        if (!property_exists(get_called_class(), 'revisionable')) {
            throw new Exception(sprintf(
                'You must define a $revisionable property in %s to use the Revisionable trait.', get_called_class()
            ));
        }

        static::extend(function($model) {
            $model->morphMany['revision_history'] = ['System\Models\Revision', 'name' => 'revisionable'];
        });
    }

    public function getRevisionableUser()
    {
        return BackendAuth::getUser()
            ? BackendAuth::getUser()->id
            : null;
    }
}
