<?php namespace Samuell\Revisions\Traits;

use BackendAuth;

/**
 * Extend model with revision methods
 */
trait RevisionModel
{
    public function getRevisionableUser()
    {
        return BackendAuth::getUser()
            ? BackendAuth::getUser()->id
            : null;
    }
    // public $morphMany = [
    //     'revision_history' => ['System\Models\Revision', 'name' => 'revisionable']
    // ];
}
