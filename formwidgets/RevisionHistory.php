<?php namespace Samuell\Revisions\FormWidgets;

use Backend\Classes\FormField;
use Backend\Classes\FormWidgetBase;
use System\Models\Revision;

use Flash;
use Input;

/**
 * RevisionHistory Form Widget
 */
class RevisionHistory extends FormWidgetBase
{
    /**
     * {@inheritDoc}
     */
    protected $defaultAlias = 'abcreate_eshop_revision_history';

    /**
     * {@inheritDoc}
     */
    public function init()
    {

    }

    /**
     * {@inheritDoc}
     */
    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('revisionhistory');
    }

    /**
     * Prepares the form widget view data
     */
    public function prepareVars()
    {
        $this->vars['history'] = $this->model->revision_history;
    }

    /**
     * {@inheritDoc}
     */
    public function loadAssets()
    {
        $this->addCss('css/revisionhistory.css', 'samuell.revisionhistory');
        $this->addJs('js/revisionhistory.js', 'samuell.revisionhistory');
    }

    /**
     * {@inheritDoc}
     */
     public function getSaveValue($value)
     {
         return FormField::NO_SAVE_DATA;
     }


     // Revert ajax handler
     public function onRevertHistory()
     {
        // dynamic load model class
        $section = Order::find($this->model->id);

        $revision = Revision::find(Input::get('revision_id'));

        $section->{$revision->field} = $revision->old_value;
        $section->save();

        Flash::success('Changes have been restored, changes are not visible without restoring the page.');

        // TODO REFRESH PAGE
     }
}
