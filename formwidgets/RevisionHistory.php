<?php namespace Samuell\Revisions\FormWidgets;

use Backend\Classes\FormField;
use Backend\Classes\FormWidgetBase;
use Illuminate\Support\Facades\DB;
use October\Rain\Exception\ValidationException;
use Samuell\Revisions\Classes\Diff;
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
    public $readOnly = null;

    protected $defaultAlias = 'samuell_revisions_revision_history';
    protected $distinctRevisions;
    protected $currentPageNumber = 1;
    protected $showPagination = false;

    public function init()
    {
        $this->fillFromConfig([
            'recordsPerPage',
            'readOnly',
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
        $this->getDataFieldName();
        $this->getDataFieldDiff();
    }

    private function getDataFieldDiff()
    {
        $this->vars['getFieldDiff'] = function ($fieldName, $oldValue, $newValue) {
            return $this->getDiff($fieldName, $oldValue, $newValue);
        };
    }

    private function getDataFieldName()
    {
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
        if ($this->readOnly) {
            Flash::error(Lang::get('samuell.revisions::lang.revision.read_only_error'));
            return;
        }
        $this->validateInput();
        $modelClass = $this->getClass();
        $section = $modelClass::find($this->model->id);
        $revision = Revision::find(input('revision_id'));

        if (input('restore_all')) {
            $revisions = Revision::where('user_id', $revision->user_id)
                ->where('created_at', $revision->created_at)->get();
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
        $this->currentPageNumber = (int) post('page');
        return $this->onRefresh();
    }

    protected function getHistory($historyId = null)
    {
        $query = $this->model->revision_history()
            ->groupBy('created_at', 'user_id')
            ->orderByDesc('created_at');

        if ($historyId) {
            $query->where('id', $historyId);
        }

        if ($this->showPagination) {
            $distinctRevisions = $this->distinctRevisions =
                $query->paginate($this->recordsPerPage, $this->currentPageNumber);
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

    private function getDiff($fieldName, $oldValue, $newValue)
    {
        $fields = $this->parentForm->getFields();

        if (array_key_exists($fieldName, $fields)) {
            $field = $fields[$fieldName];

            if (isset($field->config['revisions'])) {
                return $this->getRelationDiff($field, $oldValue, $newValue);
            }

            if (isset($field->config['type']) && $field->config['type'] === 'switch') {
                return $this->getBooleanDiff($field, $oldValue, $newValue);
            }

            if (isset($field->config['options']) ) {
                return $this->getOptionsDiff($field, $oldValue, $newValue);
            }
        }

        return Diff::htmlDiff(e($oldValue), e($newValue));
    }

    private function getRelationDiff($field, $oldValue, $newValue)
    {

        $relationConfig = $field->config['revisions'];
        $relationClass = $relationConfig['relation'];

        if (method_exists($relationClass, 'withTrashed')) {
            $oldModel = $relationClass::withTrashed()->find($oldValue);
            $newModel = $relationClass::withTrashed()->find($newValue);
        } else {
            $oldModel = $relationClass::find($oldValue);
            $newModel = $relationClass::find($newValue);
        }

        $nameFrom = $relationConfig['nameFrom'] ?? 'name';

        return Diff::htmlDiff(
            e($oldModel->$nameFrom ?? 'Deleted relation'),
            e($newModel->$nameFrom ?? 'Deleted relation')
        );
    }

    private function getBooleanDiff($field, $oldValue, $newValue)
    {
        $onLabel = isset($field->config['on']) ? Lang::get($field->config['on'])
            : Lang::get('backend::lang.form.field_on');
        $offLabel = isset($field->config['on']) ? Lang::get($field->config['off'])
            : Lang::get('backend::lang.form.field_off');
        $oldValue = $oldValue ? $onLabel : $offLabel;
        $newValue = $newValue ? $onLabel : $offLabel;
        return Diff::htmlDiff(e($oldValue), e($newValue));
    }

    private function getOptionsDiff($field, $oldValue, $newValue)
    {
        $optionConfig = $field->config['options'];

        $oldValue = explode(',', preg_replace("/[^,.0-9]/", '', $oldValue));
        $newValue = explode(',', preg_replace("/[^,.0-9]/", '', $newValue));
        $old = '';
        $new = '';

        foreach ($oldValue as $item) {
            $old .= isset($optionConfig[$item]) ? $optionConfig[$item].' • ' : '' ;
        }
        foreach ($newValue as $item) {
            $new .= isset($optionConfig[$item]) ? $optionConfig[$item].' • ' : '' ;
        }

        $oldValue = $old;
        $newValue = $new;

        return Diff::htmlDiff(e($oldValue), e($newValue));
    }

    /**
     * Find revision, check if exist and delete this
     */
    public function onDeleteRevisionById()
    {
        if ($this->readOnly) {
            Flash::error(Lang::get('samuell.revisions::lang.revision.read_only_error'));
            return;
        }

        if ($revision = Revision::where('id', input('revision_id'))->first()) {
            Db::table('system_revisions')->where([
                ['revisionable_id', $revision->revisionable_id],
                ['created_at', $revision->created_at],
            ])->delete();
            Flash::success(Lang::get('samuell.revisions::lang.messages.successfully_deleted'));
        } else {
            Flash::warning(Lang::get('samuell.revisions::lang.messages.revision_not_found'));
        }

        $this->prepareVars();

        return [
            '#RevisionHistory-formHistory-history' => $this->makePartial('revisionhistory-container')
        ];
    }

    /**
     * Delete all revision by model id
     *
     * return mixed
     */
    public function onDeleteAllRevisionsByModel()
    {
        if ($this->readOnly) {
            Flash::error(Lang::get('samuell.revisions::lang.revision.read_only_error'));
            return;
        }

        if ($id = $this->model->id) {
            Revision::where('revisionable_id', $id)->delete();

            Flash::success(Lang::get('samuell.revisions::lang.messages.all_successfully_deleted'));
        } else {
            Flash::warning(Lang::get('samuell.revisions::lang.messages.model_not_found'));
        }

        $this->prepareVars();

        return [
            '#RevisionHistory-formHistory-history' => $this->makePartial('revisionhistory-container')
        ];
    }

    public function onLoadRevision()
    {
        $this->vars['history'] = $this->getHistory(post('revision_id'));

        $this->getDataFieldName();
        $this->getDataFieldDiff();

        return $this->makePartial('revision_model');
    }
}
