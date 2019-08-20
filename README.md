# Revisions

Revisions allows to extend any model with Revisionable trait. They offer more features and are easy to use.

Extends OctoberCMS core trait Revisionable: https://octobercms.com/docs/database/traits#revisionable


## Usage

Extending model with Revisions trait.
```
class MyModel {
    use \Samuell\Revisions\Traits\Revisions;

    /**
     * @var array Monitor these attributes for changes.
     */
    protected $revisionable = ['name', 'email'];

}
```

Adding new widget to our form config

```
history:
    label: History of changes
    span: full
    disabled: true
    type: revisionhistory
```
