<?php

namespace markhuot\CraftQL\Services;

use Yii;
use Craft;
use craft\elements\Asset;
use craft\fields\Tags as TagsField;
use craft\fields\Table as TableField;
use craft\helpers\Assets;
use GraphQL\Type\Definition\Type;
use markhuot\CraftQL\Events\GetFieldSchema as GetFieldSchemaEvent;
use GraphQL\Error\Error;


class FieldService {

    private $fieldSchemas = [];

    function getSchemaForField(\craft\base\Field $field, \markhuot\CraftQL\Request $request, $parent) {
        $event = new GetFieldSchemaEvent;
        $event->schema = new \markhuot\CraftQL\Builders\Schema($request);
        $event->query = new \markhuot\CraftQL\Builders\Field($request, 'QUERY');
        $event->mutation = new \markhuot\CraftQL\Builders\Field($request, 'MUTATION');
        $field->trigger('craftQlGetFieldSchema', $event);
        return [
            'schema' => $event->schema,
            'query' => $event->query,
            'mutation' => $event->mutation,
        ];
    }

    function getQueryArguments($request) {
        $graphQlArgs = [];

        $fields = Craft::$app->fields->getAllFields();
        foreach ($fields as $field) {
            $query = $this->getSchemaForField($field, $request, null)['query'];
            $graphQlArgs = array_merge($graphQlArgs, $query->getArguments());
        }

        return $graphQlArgs;
    }

    function getMutationArguments($fieldLayoutId, $request) {
        $graphQlArgs = [];

        if ($fieldLayoutId) {
            $fieldLayout = Craft::$app->fields->getLayoutById($fieldLayoutId);
            foreach ($fieldLayout->getFields() as $field) {
                $schema = $this->getSchemaForField($field, $request, null)['mutation'];
                $graphQlArgs = array_merge($graphQlArgs, $schema->getArguments());
            }
        }

        return $graphQlArgs;
    }

    function getFields($fieldLayoutId, $request, $parent=null) {
        $graphQlFields = [];

        if ($fieldLayoutId) {
            $fieldLayout = Craft::$app->fields->getLayoutById($fieldLayoutId);
            foreach ($fieldLayout->getFields() as $field) {
                $schema = $this->getSchemaForField($field, $request, $parent)['schema'];
                $graphQlFields = array_merge($graphQlFields, $schema->getFields());
            }
        }

        return $graphQlFields;
    }

}
