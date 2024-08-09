<?php

namespace App\Docs\Schemas\Service;

use App\Docs\Schemas\Tag\TagSchema;
use App\Docs\Schemas\Taxonomy\Category\TaxonomyCategorySchema;
use App\Models\Service;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class ServiceSchema extends Schema
{
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->type(static::TYPE_OBJECT)
            ->properties(
                Schema::string('id')
                    ->format(static::FORMAT_UUID),
                Schema::string('organisation_id')
                    ->format(static::FORMAT_UUID),
                Schema::boolean('has_logo'),
                Schema::string('name'),
                Schema::string('slug'),
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
                Schema::object('image')
                    ->properties(
                        Schema::string('id'),
                        Schema::string('mime_type'),
                        Schema::string('alt_text'),
                        Schema::string('url')
                    )
                    ->nullable(),
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
                Schema::string('cqc_location_id')
                    ->nullable(),
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
                    ->nullable(),
                Schema::array('useful_infos')
                    ->items(UsefulInfoSchema::create()),
                Schema::array('offerings')
                    ->items(OfferingSchema::create()),
                Schema::array('social_medias')
                    ->items(SocialMediaSchema::create()),
                Schema::array('gallery_items')
                    ->items(GalleryItemSchema::create()),
                Schema::array('tags')
                    ->items(TagSchema::create()),
                Schema::array('category_taxonomies')
                    ->items(TaxonomyCategorySchema::create()),
                Schema::string('ends_at')
                    ->format(Schema::FORMAT_DATE_TIME)
                    ->nullable(),
                Schema::string('last_modified_at')
                    ->format(Schema::FORMAT_DATE_TIME),
                Schema::string('created_at')
                    ->format(Schema::FORMAT_DATE_TIME)
                    ->nullable(),
                Schema::string('updated_at')
                    ->format(Schema::FORMAT_DATE_TIME)
                    ->nullable(),
                Schema::object('eligibility_types')
                    ->properties(
                        Schema::object('custom')
                            ->properties(
                                Schema::string('age_group')->nullable(),
                                Schema::string('disability')->nullable(),
                                Schema::string('ethnicity')->nullable(),
                                Schema::string('gender')->nullable(),
                                Schema::string('income')->nullable(),
                                Schema::string('language')->nullable(),
                                Schema::string('housing')->nullable(),
                                Schema::string('other')->nullable()
                            ),
                        Schema::array('taxonomies')
                            ->items(
                                Schema::string()
                            )
                    )
            );
    }
}
