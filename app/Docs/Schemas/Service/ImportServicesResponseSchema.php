<?php

namespace App\Docs\Schemas\Service;

use App\Models\Service;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class ImportServicesResponseSchema extends Schema
{
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->type(static::TYPE_OBJECT)
            ->properties(
                Schema::integer('imported_row_count'),
                Schema::object('errors')->properties(
                    Schema::array('spreadsheet')->items(
                        Schema::object()->properties(
                            Schema::object('row')->properties(
                                Schema::integer('index'),
                                Schema::string('id')
                                    ->format(static::FORMAT_UUID),
                                Schema::string('organisation_id')
                                    ->format(static::FORMAT_UUID),
                                Schema::string('name'),
                                Schema::string('type')
                                    ->enum(
                                        Service::TYPE_SERVICE,
                                        Service::TYPE_ACTIVITY,
                                        Service::TYPE_CLUB,
                                        Service::TYPE_GROUP
                                    ),
                                Schema::string('status')
                                    ->enum(Service::STATUS_ACTIVE, Service::STATUS_INACTIVE),
                                Schema::string('intro'),
                                Schema::string('description'),
                                Schema::string('wait_time')
                                    ->enum(
                                        Service::WAIT_TIME_ONE_WEEK,
                                        Service::WAIT_TIME_TWO_WEEKS,
                                        Service::WAIT_TIME_THREE_WEEKS,
                                        Service::WAIT_TIME_MONTH,
                                        Service::WAIT_TIME_LONGER
                                    )
                                    ->nullable(),
                                Schema::boolean('is_free'),
                                Schema::string('fees_text')
                                    ->nullable(),
                                Schema::string('fees_url')
                                    ->nullable(),
                                Schema::string('testimonial')
                                    ->nullable(),
                                Schema::string('video_embed')
                                    ->nullable(),
                                Schema::string('url')
                                    ->nullable(),
                                Schema::string('contact_name'),
                                Schema::string('contact_phone'),
                                Schema::string('contact_email'),
                                Schema::boolean('show_referral_disclaimer'),
                                Schema::string('referral_method')
                                    ->enum(
                                        Service::REFERRAL_METHOD_INTERNAL,
                                        Service::REFERRAL_METHOD_EXTERNAL,
                                        Service::REFERRAL_METHOD_NONE
                                    )
                            ),
                            Schema::object('errors')->properties(
                                Schema::string('id')
                                    ->format(static::FORMAT_UUID),
                                Schema::string('organisation_id')
                                    ->format(static::FORMAT_UUID),
                                Schema::string('name'),
                                Schema::string('type')
                                    ->enum(
                                        Service::TYPE_SERVICE,
                                        Service::TYPE_ACTIVITY,
                                        Service::TYPE_CLUB,
                                        Service::TYPE_GROUP
                                    ),
                                Schema::string('status')
                                    ->enum(Service::STATUS_ACTIVE, Service::STATUS_INACTIVE),
                                Schema::string('intro'),
                                Schema::string('description'),
                                Schema::string('wait_time')
                                    ->enum(
                                        Service::WAIT_TIME_ONE_WEEK,
                                        Service::WAIT_TIME_TWO_WEEKS,
                                        Service::WAIT_TIME_THREE_WEEKS,
                                        Service::WAIT_TIME_MONTH,
                                        Service::WAIT_TIME_LONGER
                                    )
                                    ->nullable(),
                                Schema::boolean('is_free'),
                                Schema::string('fees_text')
                                    ->nullable(),
                                Schema::string('fees_url')
                                    ->nullable(),
                                Schema::string('testimonial')
                                    ->nullable(),
                                Schema::string('video_embed')
                                    ->nullable(),
                                Schema::string('url')
                                    ->nullable(),
                                Schema::string('contact_name'),
                                Schema::string('contact_phone'),
                                Schema::string('contact_email'),
                                Schema::boolean('show_referral_disclaimer'),
                                Schema::string('referral_method')
                                    ->enum(
                                        Service::REFERRAL_METHOD_INTERNAL,
                                        Service::REFERRAL_METHOD_EXTERNAL,
                                        Service::REFERRAL_METHOD_NONE
                                    ),
                                Schema::string('referral_button_text')
                                    ->nullable(),
                                Schema::string('referral_email')
                                    ->nullable(),
                                Schema::string('referral_url')
                                    ->nullable()
                            )
                        )
                    )
                )
            );
    }
}
