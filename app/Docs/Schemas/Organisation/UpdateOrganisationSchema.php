<?php

namespace App\Docs\Schemas\Organisation;

use App\Docs\Schemas\Service\SocialMediaSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class UpdateOrganisationSchema extends Schema
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->type(static::TYPE_OBJECT)
            ->required(
                'name',
                'slug',
                'description',
            )
            ->properties(
                Schema::string('name'),
                Schema::string('slug'),
                Schema::string('description'),
                Schema::string('url')
                    ->nullable(),
                Schema::string('email')
                    ->nullable(),
                Schema::string('phone')
                    ->nullable(),
                Schema::string('logo_file_id')
                    ->format(Schema::FORMAT_UUID)
                    ->description('The ID of the file uploaded')
                    ->nullable(),
                Schema::array('social_medias')
                    ->items(
                        SocialMediaSchema::create()
                            ->required('type', 'url')
                    ),
                Schema::array('category_taxonomies')
                    ->items(
                        Schema::string()
                            ->format(Schema::FORMAT_UUID)
                    )
            );
    }
}
