<?php

namespace Samuell\Revisions\Traits;

use BackendAuth;
use Exception;
use System\Models\Revision;
use October\Rain\Database\Traits\Revisionable;

/**
 * Extend model with revision methods
 */
trait Revisions
{
    use Revisionable;

    public static function initializeRevisionable()
    {
        $this->morphMany['revision_history'] = [Revision::class, 'name' => 'revisionable'];
    }

    public function getRevisionableUser()
    {
        return BackendAuth::getUser() ? BackendAuth::getUser()->id : null;
    }
}
