<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds "wysiwyg_style" and "wysiwyg_style" fields to the LocalizedFallbackValue entity.
 */
class AddLocalizedFallbackValueFields implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_fallback_localization_val');
        if (!$table->hasColumn('wysiwyg')) {
            $table->addColumn(
                'wysiwyg',
                'wysiwyg',
                [
                    'notnull' => false,
                    'comment' => '(DC2Type:wysiwyg)',
                    OroOptions::KEY => [
                        ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                        'extend' => ['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM],
                        'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_HIDDEN],
                        'dataaudit' => ['auditable' => false],
                        'importexport' => ['excluded' => false],
                    ],
                ]
            );
        }

        if (!$table->hasColumn('wysiwyg_style')) {
            $table->addColumn(
                'wysiwyg_style',
                'wysiwyg_style',
                [
                    'notnull' => false,
                    OroOptions::KEY => [
                        ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                        'extend' => ['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM],
                        'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_HIDDEN],
                        'dataaudit' => ['auditable' => false],
                        'importexport' => ['excluded' => false],
                    ],
                ]
            );
        }
    }
}
