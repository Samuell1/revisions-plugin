<?php

namespace Samuell\Revisions\FormWidgets;

use Backend\Classes\FormField;
use Backend\Classes\FormWidgetBase;
use System\Models\Revision;

use Flash;
use Lang;

/**
 * RevisionHistory Form Widget
 */
class RevisionHistory extends FormWidgetBase
{
    protected $defaultAlias = 'samuell_revisions_revision_history';

    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('revisionhistory');
    }

    public function prepareVars()
    {
        $revisionHistory = $this->model->revision_history->reverse();
        $histories = [];
        foreach ($revisionHistory as $history) {
            $histories[$history->created_at . $history->user_id][] = $history;
        }
        $this->vars['histories'] = $histories;
        $this->vars['getFieldName'] = function ($fieldName) {
            $fields = $this->parentForm->getFields();
            if (array_key_exists($fieldName, $fields)) {
                $field = $fields[$fieldName];
                return $field->label ?? $field->tab ?? $fieldName;
            }
            return $fieldName;
        };
    }

    public function loadAssets()
    {
        $this->addCss('css/revisionhistory.css', 'samuell.revisionhistory');
        $this->addJs('js/revisionhistory.js', 'samuell.revisionhistory');
    }

    public function getSaveValue($value)
    {
        return FormField::NO_SAVE_DATA;
    }

    public function onRevertHistory()
    {
        $modelClass = $this->getClass();
        $section = $modelClass::find($this->model->id);

        $revision = Revision::find(input('revision_id'));

        if (input('restore_all')) {
            $revisions = Revision::where('user_id', $revision->user_id)->where('created_at', $revision->created_at)->get();
        } else {
            $revisionIds = input('checkbox_' . $revision->id);
            $revisions = Revision::find($revisionIds);
        }

        foreach ($revisions as $revision) {
            if ($section->isJsonable($revision->field)) {
                $section[$revision->field] = json_decode($revision->old_value);
            } else {
                $section[$revision->field] = $revision->old_value;
            }
        }

        $section->save();

        Flash::success(Lang::get('samuell.revisions::lang.revision.restored'));

        // TODO REFRESH PAGE
    }

    private function getClass()
    {
        return get_class($this->model);
    }
}
