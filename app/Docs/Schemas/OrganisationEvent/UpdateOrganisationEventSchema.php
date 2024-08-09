<?php

namespace App\Docs\Schemas\OrganisationEvent;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class UpdateOrganisationEventSchema extends Schema
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->type(static::TYPE_OBJECT)
            ->required('title', 'intro', 'description', 'start_date', 'end_date', 'start_time', 'end_time', 'is_free', 'is_virtual')
            ->properties(
                Schema::string('title'),
                Schema::string('slug'),
                Schema::string('intro'),
                Schema::string('description'),
                Schema::string('start_date')
                    ->format(Schema::FORMAT_DATE),
                Schema::string('end_date')
                    ->format(Schema::FORMAT_DATE),
                Schema::string('start_time')
                    ->format('H:i:s'),
                Schema::string('end_time')
                    ->format('H:i:s'),
                Schema::boolean('is_free'),
                Schema::string('fees_text')
                    ->nullable(),
                Schema::string('fees_url')
                    ->nullable(),
                Schema::string('organiser_name')
                    ->nullable(),
                Schema::string('organiser_phone')
                    ->nullable(),
                Schema::string('organiser_email')
                    ->nullable(),
                Schema::string('organiser_url')
                    ->nullable(),
                Schema::string('booking_title')
                    ->nullable(),
                Schema::string('booking_summary')
                    ->nullable(),
                Schema::string('booking_url')
                    ->nullable(),
                Schema::string('booking_cta')
                    ->nullable(),
                Schema::boolean('homepage'),
                Schema::boolean('is_virtual'),
                Schema::string('image_file_id')
                    ->format(Schema::FORMAT_UUID)
                    ->description('The ID of the file uploaded')
                    ->nullable(),
                Schema::array('category_taxonomies')
                    ->items(
                        Schema::string()
                            ->format(Schema::FORMAT_UUID)
                    )
            );
    }
}
