<?php

namespace markhuot\CraftQL\Listeners;

use craft\base\Field;
use craft\fields\Matrix;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\UnionType;
use markhuot\CraftQL\Builders\Argument;
use markhuot\CraftQL\Builders\InputSchema;
use markhuot\CraftQL\Builders\Schema;
use markhuot\CraftQL\Builders\Union;

class GetMatrixFieldSchema
{
    /**
     * @var Matrix
     */
    protected $field;

    /**
     * @var Schema
     */
    protected $schema;

    /**
     * @var \markhuot\CraftQL\Builders\Field
     */
    protected $mutation;

    /**
     * Handle the request for the schema
     *
     * @param \markhuot\CraftQL\Events\GetFieldSchema $event
     * @return void
     */
    function handle($event) {
        $event->handled = true;

        $this->field = $event->sender;
        $this->schema = $event->schema;
        $this->mutation = $event->mutation;

        $union = $this->getUnionField();
        foreach ($this->getBlockTypeObjects() as $type) {
            $union->addType($type);
        }


        $inputType = $this->getInputArgument()->getType();
        foreach ($this->getInputObjectTypes() as $type) {
            $inputType->addArgument($type->getContext()->handle)
                    ->type($type);
        }

    }

    /**
     * Get's a configured union field for the Craft field we're handling
     *
     * @return Union
     */
    protected function getUnionField(): Union {
        return $this->schema->addUnionField($this->field)
            ->lists()
            ->resolveType(function ($root, $args) {
                $block = $root->getType();
                return ucfirst($this->field->handle).ucfirst($block->handle);
            })
            ->resolve(function ($root, $args, $context, $info) {
                return $root->{$this->field->handle}->all();
            });
    }

    /**
     * Gets the GraphQL block types for the Craft field we're handling
     *
     * @return array
     */
    protected function getBlockTypeObjects(): array {
        $types = [];
        $blockTypes = $this->field->getBlockTypes();

        if (empty($blockTypes)) {
            return [$this->getEmptyBlockTypeFallback()];
        }

        foreach ($blockTypes as $blockType) {
            $type = $this->schema->createObjectType(ucfirst($this->field->handle).ucfirst($blockType->handle), $blockType);
            $type->addFieldsByLayoutId($blockType->fieldLayoutId);

            if (empty($type->getFields())) {
                $this->addEmptyBlockFieldsFallback($type);
            }

            $types[] = $type;
        }

        return $types;
    }

    /**
     * Gets a "empty" object type that will be inserted
     * into the schema if the Craft Field has no block types
     *
     * @return Schema
     */
    protected function getEmptyBlockTypeFallback(): Schema {
        $warning = 'The matrix field, `'.$this->field->name.'`, has no block types. This would violate the GraphQL spec so we filled it in with this placeholder.';

        $type = $this->schema->createObjectType(ucfirst($this->field->handle).'Empty', $this->field);
        $type->addStringField('empty')
            ->description($warning)
            ->resolve($warning);

        return $type;
    }

    /**
     * Adds an "empty" string field to the passed type
     * informing the user that the block is empty.
     *
     * @param Schema $type
     * @return self|static
     */
    protected function addEmptyBlockFieldsFallback(Schema $type) {
        $warning = 'The block type, `'.$type->getContext()->handle.'` on `'.$this->field->handle.'`, has no fields. This would violate the GraphQL spec so we filled it in with this placeholder.';

        $type->addStringField('empty')
            ->description($warning)
            ->resolve($warning);
    }

    /**
     * Gets the argument that will represent the mutation
     *
     * @return Argument
     */
    protected function getInputArgument(): Argument {
        $inputType = $this->mutation->createInputObjectType(ucfirst($this->field->handle) . 'Input');

        return $this->mutation->addArgument($this->field)
            ->lists()
            ->type($inputType)
            ->onSave(function ($values) {
                $newValues = [];

                foreach ($values as $key => $value) {
                    $type = array_keys($value)[0];

                    $newValues["new{$key}"] = [
                        'type' => $type,
                        'enabled' => 1,
                        'fields' => $value[$type],
                    ];
                }

                return $newValues;
            });
    }

    /**
     * Gets the input object types representing each Craft CMS block type
     *
     * @return array
     */
    protected function getInputObjectTypes(): array {
        $types = [];
        $blockTypes = $this->field->getBlockTypes();

        if (empty($blockTypes)) {
            return [$this->getEmptyInputBlockTypeFallback()];
        }

        foreach ($blockTypes as $blockType) {
            $blockInputType = $this->mutation->createInputObjectType(ucfirst($this->field->handle) . ucfirst($blockType->handle) . 'Input', $blockType);
            $blockInputType->addArgumentsByLayoutId($blockType->fieldLayoutId);

            if (empty($blockInputType->getArguments())) {
                $this->addEmptyInputBlockFieldsFallback($blockInputType);
            }

            $types[] = $blockInputType;
        }

        return $types;
    }

    /**
     * Gets a "empty" object type that will be inserted
     * into the schema if the Craft Field has no block types
     *
     * @return Schema
     */
    protected function getEmptyInputBlockTypeFallback(): InputSchema {
        $warning = 'The matrix field, `'.$this->field->name.'`, has no block types. This would violate the GraphQL spec so we filled it in with this placeholder.';

        $type = $this->schema->createInputObjectType(ucfirst($this->field->handle).'InputEmpty', $this->field);
        $type->addStringArgument('empty')
            ->description($warning);

        return $type;
    }

    /**
     * Adds an "empty" string argument to the passed type
     * informing the user that the block is empty.
     *
     * @param InputSchema $type
     */
    protected function addEmptyInputBlockFieldsFallback(InputSchema $type) {
        $warning = 'The block type, `'.$type->getContext()->handle.'` on `'.$this->field->handle.'`, has no fields. This would violate the GraphQL spec so we filled it in with this placeholder.';

        $type->addStringArgument('empty')
            ->description($warning);
    }

}
