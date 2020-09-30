# Revisions

Revisions allows to extend any model with Revisionable trait. They offer more features and are easy to use.

Extends OctoberCMS core trait Revisionable: https://octobercms.com/docs/database/traits#revisionable


https://octobercms.com/plugin/samuell-revisions

## Usage

Extending model with Revisions trait.
```php
class MyModel {
    use \Samuell\Revisions\Traits\Revisions;

    /**
     * @var array Monitor these attributes for changes.
     */
    protected $revisionable = ['name', 'email'];

}
```

Adding new widget to our form config

```yaml
history:
    label: History of changes
    span: full
    disabled: true
    type: revisionhistory
    recordsPerPage: 10
    readOnly: false
```

### Displaying a changed relation

By default, when you make a relation revisionable, only the changed ID will be displayed.
To display the title or name of the relation instead, you can add the field below to the parent form.
```yaml
category_id:
    hidden: true
    revisions:
        relation: Acme\Plugin\Models\Category
        nameFrom: name # 'name' is the default
```
