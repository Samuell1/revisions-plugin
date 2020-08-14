<?php

namespace Samuell\Revisions\FormWidgets;

use Backend\Classes\FormField;
use Backend\Classes\FormWidgetBase;
use October\Rain\Exception\ValidationException;
use System\Models\Revision;
use Validator;
use Input;

use Flash;
use Lang;

/**
 * RevisionHistory Form Widget
 */
class RevisionHistory extends FormWidgetBase
{
    public $recordsPerPage = null;

    protected $defaultAlias = 'samuell_revisions_revision_history';
    protected $distinctRevisions;
    protected $currentPageNumber = 1;
    protected $showPagination = false;

    public function init()
    {
        $this->fillFromConfig([
            'recordsPerPage',
        ]);

        $this->showPagination = $this->recordsPerPage && $this->recordsPerPage > 0;
    }

    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('revisionhistory-container');
    }

    public function prepareVars()
    {
        $this->vars['history'] = $this->getHistory();
        $this->vars['showPagination'] = $this->showPagination;

        if ($this->showPagination) {
            $this->vars['pageCurrent'] = $this->distinctRevisions->currentPage();
            $this->vars['recordTotal'] = $this->distinctRevisions->total();
            $this->vars['pageLast'] = $this->distinctRevisions->lastPage();
            $this->vars['pageFrom'] = $this->distinctRevisions->firstItem();
            $this->vars['pageTo'] = $this->distinctRevisions->lastItem();
        }

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
        $this->validateInput();

        $modelClass = $this->getClass();
        $section = $modelClass::find($this->model->id);

        $revision = Revision::find(input('revision_id'));

        if (input('restore_all')) {
            $revisions = Revision::where('user_id', $revision->user_id)->where('created_at', $revision->created_at)->get();
        } else {
            $revisionIds = input('revisions');
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

        Flash::success(Lang::get('samuell.revisions::lang.revision.changes_restored'));

        // TODO REFRESH PAGE
    }

    public function onRefresh()
    {
        $this->prepareVars();
        return ['#' . $this->getId() => $this->makePartial('revisionhistory')];
    }

    public function onPaginate()
    {
        $this->currentPageNumber = (int)post('page');
        return $this->onRefresh();
    }

    protected function getHistory()
    {
        $query = $this->model->revision_history()
            ->groupBy('created_at', 'user_id')
            ->orderByDesc('created_at');

        if ($this->showPagination) {
            $distinctRevisions = $this->distinctRevisions = $query->paginate($this->recordsPerPage, $this->currentPageNumber);
        } else {
            $distinctRevisions = $query->get();
        }

        $history = [];

        foreach ($distinctRevisions as $distinctRevision) {
            $history[] = $this->model->revision_history()
                ->where('created_at', $distinctRevision->created_at)
                ->where('user_id', $distinctRevision->user_id)
                ->get();
        }

        return $history;
    }

    private function getClass()
    {
        return get_class($this->model);
    }

    private function validateInput()
    {
        $validator = Validator::make(
            Input::all(),
            ['revisions' => 'required_without:restore_all'],
            ['revisions.required_without' => 'Select at least one revision to restore.']
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
