<?php

namespace markhuot\CraftQL\Listeners;

use Craft;
use craft\helpers\ElementHelper;
use markhuot\CraftQL\Events\GetFieldSchema;

class GetCategoriesFieldSchema
{
    /**
     * Handle the request for the schema
     *
     * @param \markhuot\CraftQL\Events\GetFieldSchema $event
     * @return void
     */
    function handle(GetFieldSchema $event) {
        $event->handled = true;

        $field = $event->sender;

        if (!$event->schema->getRequest()->token()->can('query:categories')) {
            return;
        }

        if (preg_match('/^group:(\d+)$/', $field->source, $matches)) {
            $groupId = $matches[1];

            $event->schema->addField($field)
                ->lists()
                ->type($event->schema->getRequest()->categoryGroups()->get($groupId))
                ->resolve(function ($root, $args) use ($field) {
                    return $root->{$field->handle}->all();
                });

            $event->query->addStringArgument($field);

            $inputObject = $event->mutation->createInputObjectType(ucfirst($field->handle).'CategoryInput');
            $inputObject->addIntArgument('id');
            $inputObject->addStringArgument('title');
            $inputObject->addStringArgument('slug');

            $event->mutation->addArgument($field)
                ->type($inputObject)
                ->lists()
                ->nonNull($field->required)
                ->onSave(function ($values) use ($groupId) {
                    $group = Craft::$app->getCategories()->getGroupById($groupId);

                    foreach ($values as &$value) {
                        if (is_numeric($value)) {
                            continue;
                        }

                        if (empty($value['slug'])) {
                            $value['slug'] = ElementHelper::createSlug($value['title']);
                        }

                        $category = new \craft\elements\Category();
                        $category->groupId = $group->id;
                        $category->fieldLayoutId = $group->fieldLayoutId;
                        $category->title = @$value['title'];
                        $category->slug = @$value['slug'];
                        Craft::$app->getElements()->saveElement($category);

                        $value = $category->id;
                    }

                    return $values;
                });
        }

    }
}
